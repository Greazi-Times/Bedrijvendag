<?php

namespace App\Models;

use App\Exceptions\AlreadyReviewedException;
use App\Mail\CompanyAccessApprovedMail;
use App\Mail\CompanyAccessRejectedMail;
use App\Models\Concerns\Audited;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Throwable;

class CompanyAccessRequest extends Model
{
    use Audited;

    /** Contact details stay in the request itself; the log tracks the review. */
    protected array $auditAttributes = ['status', 'type', 'company_id', 'reviewed_by', 'review_note'];

    public const TYPE_EXISTING = 'existing';

    public const TYPE_NEW = 'new';

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    /** null: no email attempted; true: queued after commit; false: dispatch failed (the review itself is saved). */
    public ?bool $notificationQueued = null;

    protected $fillable = [
        'type',
        'status',
        'company_id',
        'company_name',
        'website_url',
        'logo_path',
        'description',
        'education_ids',
        'sector_ids',
        'new_sector_names',
        'contact_name',
        'contact_email',
        'message',
        'submitted_at',
        'reviewed_at',
        'reviewed_by',
        'review_note',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'education_ids' => 'array',
        'sector_ids' => 'array',
        'new_sector_names' => 'array',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * @param  ?int  $linkCompanyId  Approve a new-company request by linking an existing company instead of creating one.
     *
     * @throws ValidationException when approval would create a duplicate company.
     */
    public function approve(?string $note = null, ?string $message = null, ?int $linkCompanyId = null): bool
    {
        if ($this->status !== self::STATUS_PENDING) {
            return false;
        }

        try {
            // Serialises company creation so two admins approving the same name cannot both pass the duplicate check.
            Cache::lock('company-access-approval', 15)->block(10, function () use ($note, $message, $linkCompanyId): void {
                DB::transaction(function () use ($note, $message, $linkCompanyId): void {
                    // Re-read under a row lock: a concurrent or repeated approval sees the new status and stops.
                    $fresh = self::query()->lockForUpdate()->findOrFail($this->id);

                    if ($fresh->status !== self::STATUS_PENDING) {
                        throw new AlreadyReviewedException;
                    }

                    if ($linkCompanyId) {
                        $this->company()->associate(Company::query()->findOrFail($linkCompanyId));
                        $this->type = self::TYPE_EXISTING;
                    }

                    if ($this->type === self::TYPE_NEW && ! $this->company_id) {
                        if (Company::matchesForName($this->company_name)['exact']->isNotEmpty()) {
                            throw ValidationException::withMessages(['company_name' => __('messages.company_already_exists')]);
                        }

                        $company = Company::query()->create([
                            'name' => $this->company_name,
                            'logo_path' => $this->logo_path,
                            'website_url' => $this->website_url,
                            'profile_contact_email' => $this->contact_email,
                            'description_nl' => $this->descriptionHtml(),
                        ]);

                        $company->educations()->sync($this->education_ids ?? []);
                        $company->sectors()->sync($this->approvedSectorIds());

                        $this->company()->associate($company);
                    }

                    if ($this->type === self::TYPE_EXISTING && $this->company && ! $this->company->profile_contact_email) {
                        $this->company->forceFill([
                            'profile_contact_email' => $this->contact_email,
                        ])->save();
                    }

                    $this->forceFill([
                        'status' => self::STATUS_APPROVED,
                        'reviewed_at' => now(),
                        'reviewed_by' => Auth::id(),
                        'review_note' => $note,
                    ])->save();

                    $this->queueMailAfterCommit(new CompanyAccessApprovedMail($this, $message));
                });
            });

            return true;
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (AlreadyReviewedException) {
            return false;
        } catch (Throwable $exception) {
            $this->refresh();
            Log::error('Failed to approve company access request.', [
                'company_access_request_id' => $this->id,
                'contact_email' => $this->contact_email,
                'exception' => $exception,
            ]);

            return false;
        }
    }

    /**
     * Rejects a pending request. A non-empty $message is emailed to the requester; $note stays internal.
     *
     * @return bool false when the request was already reviewed
     */
    public function reject(?string $note = null, ?string $message = null): bool
    {
        if ($this->status !== self::STATUS_PENDING) {
            return false;
        }

        try {
            DB::transaction(function () use ($note, $message): void {
                if (self::query()->lockForUpdate()->findOrFail($this->id)->status !== self::STATUS_PENDING) {
                    throw new AlreadyReviewedException;
                }

                $this->forceFill([
                    'status' => self::STATUS_REJECTED,
                    'reviewed_at' => now(),
                    'reviewed_by' => Auth::id(),
                    'review_note' => $note,
                ])->save();

                if (filled($message)) {
                    $this->queueMailAfterCommit(new CompanyAccessRejectedMail($this, $message));
                }
            });

            return true;
        } catch (AlreadyReviewedException) {
            $this->refresh();

            return false;
        }
    }

    /** Queued only once the surrounding transaction commits; a rollback discards it. SMTP errors then land in failed_jobs. */
    private function queueMailAfterCommit(Mailable $mail): void
    {
        DB::afterCommit(function () use ($mail): void {
            try {
                Mail::to($this->contact_email)->queue($mail);
                $this->notificationQueued = true;
            } catch (Throwable $exception) {
                $this->notificationQueued = false;
                Log::error('Failed to queue company access email.', [
                    'company_access_request_id' => $this->id,
                    'mail' => $mail::class,
                    'exception' => $exception,
                ]);
            }
        });
    }

    public function verificationUrl(): ?string
    {
        return $this->company?->profileVerificationUrl();
    }

    public function proposedEducationNames(): string
    {
        return Education::query()
            ->whereIn('id', $this->education_ids ?? [])
            ->orderBy('name')
            ->pluck('name')
            ->implode(', ') ?: 'Geen';
    }

    public function proposedSectorNames(): string
    {
        $existing = Sector::query()
            ->whereIn('id', $this->sector_ids ?? [])
            ->orderBy('name')
            ->pluck('name');

        return $existing
            ->merge(collect($this->new_sector_names ?? [])->map(fn (string $name): string => "{$name} (new)"))
            ->filter()
            ->implode(', ') ?: 'Geen';
    }

    private function descriptionHtml(): ?string
    {
        $description = trim((string) $this->description);

        if ($description === '') {
            return null;
        }

        return collect(preg_split("/\R{2,}/", $description) ?: [])
            ->map(fn (string $paragraph): string => trim($paragraph))
            ->filter()
            ->map(fn (string $paragraph): string => '<p>'.nl2br(e($paragraph), false).'</p>')
            ->implode('') ?: null;
    }

    /**
     * @return array<int, int>
     */
    private function approvedSectorIds(): array
    {
        $sectorIds = collect($this->sector_ids ?? [])
            ->map(fn (int|string $id): int => (int) $id)
            ->filter()
            ->values();

        collect($this->new_sector_names ?? [])
            ->map(fn (string $name): string => trim(preg_replace('/\s+/', ' ', $name) ?? $name))
            ->filter()
            ->each(function (string $name) use ($sectorIds): void {
                $sector = Sector::query()
                    ->whereRaw('lower(name) = ?', [mb_strtolower($name)])
                    ->first() ?: Sector::query()->create(['name' => $name]);

                $sectorIds->push($sector->id);
            });

        return $sectorIds->unique()->values()->all();
    }
}
