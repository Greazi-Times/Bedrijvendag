<?php

namespace App\Models;

use App\Jobs\TranslateCompanyDescription;
use App\Services\DeepLTranslator;
use App\Support\TranslationContent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Company extends Model
{
    protected $fillable = [
        'name',
        'logo_path',
        'website_url',
        'profile_contact_email',
        'description_nl',
        'description_en',
        'profile_token',
        'profile_token_expires_at',
    ];

    protected $casts = [
        'profile_token_expires_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Company $company): void {
            if (! $company->profile_token) {
                $company->profile_token = static::generateUniqueProfileToken();
            }
        });

        static::created(function (Company $company): void {
            if (blank($company->description_en)) {
                $company->queueEnglishDescriptionTranslation(overwrite: true);
            }
        });

        static::updated(function (Company $company): void {
            if ($company->wasChanged('description_nl') && ! $company->wasChanged('description_en')) {
                $company->queueEnglishDescriptionTranslation(overwrite: true);
            }
        });
    }

    public function localizedDescription(?string $locale = null): ?string
    {
        $locale ??= app()->getLocale();
        $primary = $locale === 'en' ? $this->description_en : $this->description_nl;
        $fallback = $locale === 'en' ? $this->description_nl : $this->description_en;

        return filled($primary) ? $primary : (filled($fallback) ? $fallback : null);
    }

    public function queueEnglishDescriptionTranslation(bool $overwrite = false): bool
    {
        $description = trim((string) $this->description_nl);

        if ($description === '' || (! $overwrite && filled($this->description_en)) || ! app(DeepLTranslator::class)->configured()) {
            return false;
        }

        TranslateCompanyDescription::dispatch($this->getKey(), TranslationContent::hash($description))->afterResponse();

        return true;
    }

    public static function generateUniqueProfileToken(): string
    {
        do {
            $token = Str::random(64);
        } while (static::query()->where('profile_token', $token)->exists());

        return $token;
    }

    public function regenerateProfileToken(): string
    {
        $this->forceFill([
            'profile_token' => static::generateUniqueProfileToken(),
            'profile_token_expires_at' => null,
        ])->save();

        return $this->profile_token;
    }

    public function profileVerificationUrl(): string
    {
        if (! $this->profile_token) {
            $this->regenerateProfileToken();
        }

        return route('company-profile.edit', ['token' => $this->profile_token]);
    }

    public function getProfileVerificationUrlAttribute(): string
    {
        return $this->profileVerificationUrl();
    }

    public function events(): BelongsToMany
    {
        return $this->belongsToMany(Event::class, 'event_stands', 'company_id', 'event_id')
            ->wherePivot('type', 'company')
            ->withPivot('type', 'stand_number', 'x_percent', 'y_percent')
            ->withTimestamps();
    }

    public function educations(): BelongsToMany
    {
        return $this->belongsToMany(Education::class);
    }

    public function sectors(): BelongsToMany
    {
        return $this->belongsToMany(Sector::class);
    }

    public function stands(): HasMany
    {
        return $this->hasMany(EventStand::class, 'company_id');
    }

    public function profileSubmissions(): HasMany
    {
        return $this->hasMany(CompanyProfileSubmission::class);
    }

    public function accessRequests(): HasMany
    {
        return $this->hasMany(CompanyAccessRequest::class);
    }
}
