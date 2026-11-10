<?php

namespace App\Models;

use App\Casts\EncryptedEmail;
use App\Casts\EncryptedPayload;
use App\Models\Concerns\Audited;
use App\Models\Concerns\HasEmailHash;
use App\Services\EnrollmentWorkflow;
use App\Support\Forms\FormSchema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Storage;

class Enrollment extends Model
{
    use Audited, HasEmailHash;

    /** @var array<string, string> encrypted email column => keyed lookup hash column */
    protected array $emailHashes = ['contact_email' => 'contact_email_hash'];

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const INFO_NOT_INVITED = 'not_invited';

    public const INFO_AWAITING = 'awaiting';

    public const INFO_SUBMITTED = 'submitted';

    public const STATUS_COLORS = ['pending' => 'warning', 'approved' => 'success', 'rejected' => 'danger'];

    public const INFO_COLORS = ['not_invited' => 'gray', 'awaiting' => 'warning', 'submitted' => 'success'];

    /** Contact and invoice details stay in the enrollment; the log only tracks the review. */
    protected array $auditAttributes = ['status', 'info_status', 'company_id', 'reviewed_by', 'review_note'];

    /** Only the public submit path fills these; review fields are set with forceFill by the workflow. */
    protected $fillable = [
        'event_id', 'form_version_id', 'selected_company_id', 'company_name', 'name_key', 'contact_name', 'contact_email',
        'answers', 'files', 'fee_cents_accepted', 'currency_accepted', 'terms_version_accepted', 'terms_html_accepted',
        'accepted_at', 'submission_key', 'submitted_at',
    ];

    protected $attributes = ['status' => self::STATUS_PENDING, 'info_status' => self::INFO_NOT_INVITED];

    protected $casts = [
        'answers' => EncryptedPayload::class, 'contact_email' => EncryptedEmail::class, 'contact_name' => \App\Casts\EncryptedText::class, 'files' => 'array', 'accepted_at' => 'datetime', 'submitted_at' => 'datetime', 'reviewed_at' => 'datetime',
    ];

    protected $hidden = ['answers', 'files', 'terms_html_accepted'];

    protected static function booted(): void
    {
        // Personal data and uploaded files go together: no orphaned private files after a deletion.
        static::deleted(fn (self $enrollment) => \App\Support\Privacy\ErasureLedger::recordEnrollment($enrollment->id));

        static::deleting(function (self $enrollment): void {
            $disk = Storage::disk(EnrollmentWorkflow::PRIVATE_DISK);

            foreach (array_merge([$enrollment->files ?? []], $enrollment->infoSubmissions()->pluck('files')->map(fn ($f) => is_string($f) ? json_decode($f, true) : $f)->all()) as $files) {
                foreach ((array) $files as $file) {
                    if (is_array($file) && ! empty($file['path'])) {
                        $disk->delete($file['path']);
                    }
                }
            }
        });
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function formVersion(): BelongsTo
    {
        return $this->belongsTo(FormVersion::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function selectedCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'selected_company_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(EnrollmentInvitation::class);
    }

    public function infoSubmissions(): HasMany
    {
        return $this->hasMany(EnrollmentInfoSubmission::class)->orderByDesc('revision');
    }

    public function latestInfoSubmission(): HasOne
    {
        return $this->hasOne(EnrollmentInfoSubmission::class)->latestOfMany('revision');
    }

    public function contactChanges(): HasMany
    {
        return $this->hasMany(EnrollmentContactChange::class);
    }

    /**
     * Three kinds of contact:
     *  - the enrollment contact (contact_name/contact_email): the trusted applicant, never rewritten;
     *  - the current operational contact: the latest VERIFIED change, otherwise the enrollment contact;
     *  - a pending proposal: an address typed on the second form that has not been verified yet. It is never used for mail.
     *
     * @return array{name: ?string, email: string, source: string, differs: bool, pending: ?array{name: ?string, email: string, expires_at: \Illuminate\Support\Carbon, queued: bool}}
     */
    public function operationalContact(): array
    {
        $verified = $this->contactChanges()->whereNotNull('verified_at')->whereNull('superseded_at')->latest('id')->first();
        $pending = $this->contactChanges()->whereNull('verified_at')->whereNull('superseded_at')->where('expires_at', '>', now())->latest('id')->first();

        // A verified change brings its own details; the previous contact's name must never be attached to a new address.
        $name = $verified ? $verified->name : $this->contact_name;
        $email = $verified?->email ?? $this->contact_email;

        return [
            'name' => $name,
            'email' => $email,
            'source' => $verified ? 'verified' : 'original',
            'differs' => mb_strtolower($email) !== mb_strtolower($this->contact_email) || $name !== $this->contact_name,
            'pending' => $pending ? ['name' => $pending->name, 'email' => $pending->email, 'expires_at' => $pending->expires_at, 'queued' => $pending->queued_at !== null] : null,
        ];
    }

    /** The one invitation that currently grants access, if any. */
    public function activeInvitation(): ?EnrollmentInvitation
    {
        return $this->invitations()->whereNull('revoked_at')->where('expires_at', '>', now())->latest('id')->first();
    }

    public function profileSubmission(): BelongsTo
    {
        return $this->belongsTo(CompanyProfileSubmission::class, 'profile_submission_id');
    }

    /** Value of the question mapped to a system property, e.g. "company.website", whatever its label or key is now. */
    public function mapped(string $map): mixed
    {
        $key = FormSchema::mappedKey($this->formVersion->schema ?? [], $map);

        return $key ? ($this->answers[$key] ?? null) : null;
    }

    /** Stored upload for a mapped question, or null. @return array{path: string, name: string, mime: string, size: int}|null */
    public function mappedFile(string $map): ?array
    {
        $key = FormSchema::mappedKey($this->formVersion->schema ?? [], $map);

        return $key ? ($this->files[$key] ?? null) : null;
    }
}
