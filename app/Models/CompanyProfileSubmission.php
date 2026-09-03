<?php

namespace App\Models;

use App\Mail\CompanyProfileApprovedMail;
use App\Mail\CompanyProfileRejectedMail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class CompanyProfileSubmission extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'company_id',
        'status',
        'contact_name',
        'contact_email',
        'proposed_name',
        'proposed_logo_path',
        'proposed_website_url',
        'proposed_description',
        'proposed_education_ids',
        'proposed_sector_ids',
        'submitted_at',
        'reviewed_at',
        'reviewed_by',
        'review_note',
    ];

    protected $casts = [
        'proposed_education_ids' => 'array',
        'proposed_sector_ids' => 'array',
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

    public function proposedEducationNames(): string
    {
        if (! $this->proposed_education_ids) {
            return 'Geen';
        }

        return Education::query()
            ->whereIn('id', $this->proposed_education_ids)
            ->orderBy('name')
            ->pluck('name')
            ->implode(', ') ?: 'Geen';
    }

    public function proposedSectorNames(): string
    {
        if (! $this->proposed_sector_ids) {
            return 'Geen';
        }

        return Sector::query()
            ->whereIn('id', $this->proposed_sector_ids)
            ->orderBy('name')
            ->pluck('name')
            ->implode(', ') ?: 'Geen';
    }

    public function approve(?string $note = null): void
    {
        if ($this->status !== self::STATUS_PENDING) {
            return;
        }

        $this->company->update([
            'name' => $this->proposed_name,
            'logo_path' => $this->proposed_logo_path ?: $this->company->logo_path,
            'website_url' => $this->proposed_website_url,
            'description' => [
                'html' => $this->descriptionHtml(),
            ],
        ]);

        $this->company->educations()->sync($this->proposed_education_ids ?? []);
        $this->company->sectors()->sync($this->proposed_sector_ids ?? []);

        $this->forceFill([
            'status' => self::STATUS_APPROVED,
            'reviewed_at' => now(),
            'reviewed_by' => Auth::id(),
            'review_note' => $note,
        ])->save();

        if ($email = $this->notificationEmail()) {
            Mail::to($email)->send(new CompanyProfileApprovedMail($this));
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

        if ($email = $this->notificationEmail()) {
            Mail::to($email)->send(new CompanyProfileRejectedMail($this));
        }
    }

    public function notificationEmail(): ?string
    {
        return $this->contact_email ?: $this->company?->profile_contact_email;
    }

    private function descriptionHtml(): ?string
    {
        $description = trim((string) $this->proposed_description);

        if ($description === '') {
            return null;
        }

        if (str_contains($description, '<')) {
            return $description;
        }

        return collect(preg_split("/\R{2,}/", $description) ?: [])
            ->map(fn (string $paragraph): string => trim($paragraph))
            ->filter()
            ->map(fn (string $paragraph): string => '<p>'.nl2br(e($paragraph), false).'</p>')
            ->implode('') ?: null;
    }
}
