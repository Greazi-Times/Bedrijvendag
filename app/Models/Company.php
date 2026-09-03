<?php

namespace App\Models;

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
        'description',
        'profile_token',
        'profile_token_expires_at',
    ];

    protected $casts = [
        'description' => 'array',
        'profile_token_expires_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Company $company): void {
            if (! $company->profile_token) {
                $company->profile_token = static::generateUniqueProfileToken();
            }
        });
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
