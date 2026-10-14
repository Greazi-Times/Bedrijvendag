<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Concerns\Audited;
use App\Support\Audit;
use App\Support\Permissions;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Jeffgreco13\FilamentBreezy\Traits\TwoFactorAuthenticatable;
use Spatie\Activitylog\Traits\CausesActivity;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use Audited, CausesActivity, HasFactory, HasRoles, Notifiable, TwoFactorAuthenticatable;

    protected string $auditCategory = 'users';

    /** Never the password, tokens or 2FA secrets. */
    protected array $auditAttributes = ['name', 'email', 'email_verified_at'];

    /** Deletion is logged by Access::deleteUser, together with the roles the account held. */
    protected static $recordEvents = ['created', 'updated'];

    protected static function booted(): void
    {
        // The hash is never logged, only that it changed. Resets are logged by the PasswordReset listener.
        static::updated(function (User $user): void {
            if ($user->wasChanged('password') && ! Audit::isPasswordResetRequest()) {
                Audit::log('auth.password.changed', "Password changed for {$user->name}", $user);
            }
        });
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->can('dashboard.view');
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole(Permissions::SUPER_ADMIN);
    }
}
