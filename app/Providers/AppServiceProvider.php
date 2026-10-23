<?php

namespace App\Providers;

use App\Contracts\TranslatesText;
use App\Listeners\AuditAuthenticationEvents;
use App\Models\User;
use App\Policies\RolePolicy;
use App\Services\DeepLTranslator;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Vite;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View as ViewFacade;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(TranslatesText::class, DeepLTranslator::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureAuthorization();
        $this->configureAuditing();
        $this->configureErrorPages();
    }

    /**
     * The 500 page must still render when the app is broken: no web middleware, so it reads
     * the theme itself, and a missing asset build falls back to inline styles.
     */
    protected function configureErrorPages(): void
    {
        ViewFacade::composer(['errors.500', 'errors::500'], function (View $view): void {
            $appearance = request()->cookie('appearance');

            try {
                $styles = app(Vite::class)('resources/css/app.css');
            } catch (\Throwable) {
                $styles = null;
            }

            $view->with([
                'appearance' => in_array($appearance, ['light', 'dark'], true) ? $appearance : 'system',
                'styles' => $styles,
            ]);
        });
    }

    protected function configureAuditing(): void
    {
        Event::subscribe(AuditAuthenticationEvents::class);

        if ($proxies = config('app.trusted_proxies')) {
            TrustProxies::at($proxies === '*' ? '*' : array_map('trim', explode(',', $proxies)));
        }
    }

    protected function configureAuthorization(): void
    {
        // Super Admins pass every gate and policy (Spatie's recommended setup).
        // The invariants in App\Support\Access still apply to them.
        Gate::before(fn (User $user): ?bool => $user->isSuperAdmin() ? true : null);

        Gate::policy(Role::class, RolePolicy::class);
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null
        );
    }
}
