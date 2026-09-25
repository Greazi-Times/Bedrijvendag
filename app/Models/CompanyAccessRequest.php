<?php

namespace App\Models;

use App\Mail\CompanyAccessApprovedMail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class CompanyAccessRequest extends Model
{
    public const TYPE_EXISTING = 'existing';

    public const TYPE_NEW = 'new';

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

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

    public function approve(?string $note = null): bool
    {
        if ($this->status !== self::STATUS_PENDING) {
            return false;
        }

        try {
            DB::transaction(function () use ($note): void {
                if ($this->type === self::TYPE_NEW && ! $this->company_id) {
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

                Mail::to($this->contact_email)->send(new CompanyAccessApprovedMail($this));

                $this->forceFill([
                    'status' => self::STATUS_APPROVED,
                    'reviewed_at' => now(),
                    'reviewed_by' => Auth::id(),
                    'review_note' => $note,
                ])->save();
            });

            return true;
        } catch (Throwable $exception) {
            Log::error('Failed to send company access approval email.', [
                'company_access_request_id' => $this->id,
                'contact_email' => $this->contact_email,
                'exception' => $exception,
            ]);

            return false;
        }
    }

    public function reject(?string $note = null): void
    {
        if ($this->status !== self::STATUS_PENDING) {
            return;
        }

        $this->forceFill([
            'status' => self::STATUS_REJECTED,
            'reviewed_at' => now(),
            'reviewed_by' => Auth::id(),
            'review_note' => $note,
        ])->save();
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
