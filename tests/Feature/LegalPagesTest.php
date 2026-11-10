<?php

use App\Settings\PrivacyPolicySettings;
use App\Support\PolicyDraft;
use Illuminate\Support\Facades\Validator;

it('serves the three legal pages', function (string $route) {
    $this->get(route($route))->assertOk();
})->with(['privacy-policy', 'terms-of-service', 'cookie-policy']);

it('keeps an administrator-customised policy untouched when drafts are loaded', function () {
    $settings = app(PrivacyPolicySettings::class);
    $settings->content = '<p>Eigen tekst</p>';
    $settings->save();

    PolicyDraft::html('privacy-policy');

    expect(app(PrivacyPolicySettings::class)->content)->toBe('<p>Eigen tekst</p>');
});

it('blocks saving a policy that still has open items and allows a finished one', function () {
    $rule = fn (string $v) => Validator::make(['c' => $v], ['c' => [PolicyDraft::rule()]])->passes();

    expect($rule(PolicyDraft::html('privacy-policy')))->toBeFalse()
        ->and($rule(PolicyDraft::html('terms-of-service')))->toBeFalse()
        ->and($rule(PolicyDraft::html('cookie-policy')))->toBeFalse()
        ->and($rule('<p>Schrijf hier je privacy policy…</p>'))->toBeFalse()
        ->and($rule('<h1>Klaar</h1><p>Definitieve tekst.</p>'))->toBeTrue();
});

it('cross-links the three policies from each draft and keeps retention figures consistent', function () {
    $privacy = file_get_contents(base_path('policies/privacy-policy.md'));
    $terms = file_get_contents(base_path('policies/terms-of-service.md'));
    $cookie = file_get_contents(base_path('policies/cookie-policy.md'));

    expect($privacy)->toContain('(/cookie-policy)')->toContain('(/terms-of-service)')
        ->and($terms)->toContain('(/privacy-policy)')->toContain('(/cookie-policy)')
        ->and($cookie)->toContain('(/privacy-policy)')->toContain('(/terms-of-service)')
        ->and($privacy)->toContain('zeven dagen')->toContain('drie kalendermaanden')->toContain('28 dagen')->toContain('90 dagen');
});

it('generates and type checks the frontend routes under one shared lock', function () {
    $package = json_decode(file_get_contents(base_path('package.json')), true);
    $vite = file_get_contents(base_path('vite.config.ts'));

    expect($package['scripts']['types'])->toBe('node scripts/typecheck.mjs')
        ->and($package['scripts']['wayfinder'])->toContain('wayfinder-generate.mjs')
        ->and($vite)->toContain("command: 'node scripts/wayfinder-generate.mjs'")
        ->and(file_get_contents(base_path('scripts/typecheck.mjs')))->toContain('acquire()')->not->toContain('--skipLibCheck');
});

/** The real admin editors: loading a draft is not saving, and saving it is refused while open items remain. */
dataset('policy editors', [
    'privacy' => [App\Filament\Pages\ManagePrivacyPolicy::class, App\Settings\PrivacyPolicySettings::class, 'privacy-policy'],
    'terms' => [App\Filament\Pages\ManageTermsOfService::class, App\Settings\TermsOfServiceSettings::class, 'terms-of-service'],
    'cookie' => [App\Filament\Pages\ManageCookiePolicy::class, App\Settings\CookiePolicySettings::class, 'cookie-policy'],
]);

it('loads a draft into the editor without saving, refuses to save it, and still saves finished text', function (string $page, string $settingsClass, string $file) {
    $settings = app($settingsClass);
    $settings->content = '<p>Bestaande gepubliceerde tekst</p>';
    $settings->save();

    $lw = Livewire\Livewire::test($page);
    $lw->actingAs(reviewerWithout(['settings.legal']));
    $lw = Livewire\Livewire::actingAs(reviewerWithout(['settings.legal']))->test($page);

    $lw->callAction('loadDraft');
    expect(app($settingsClass)->content)->toBe('<p>Bestaande gepubliceerde tekst</p>'); // loaded, not saved

    $lw->call('save')->assertHasFormErrors(['content']);
    expect(app($settingsClass)->content)->toBe('<p>Bestaande gepubliceerde tekst</p>');

    $lw->fillForm(['content' => '<h1>Definitieve tekst</h1><p>Klaar.</p>'])->call('save')->assertHasNoFormErrors();
    expect(app($settingsClass)->content)->toContain('Definitieve tekst');
})->with('policy editors');
