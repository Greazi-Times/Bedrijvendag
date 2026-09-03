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
                        'website_url' => $this->website_url,
                        'profile_contact_email' => $this->contact_email,
                    ]);

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
}
