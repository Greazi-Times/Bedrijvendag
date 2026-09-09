<?php

use Inertia\Testing\AssertableInertia as Assert;

test('returns a successful response', function () {
    $response = $this->get(route('home'));

    $response->assertOk();
});

test('shares the generated deployment version', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('deploymentVersion', config('version.display'))
        );
});

test('renders the public site using the saved dark appearance', function () {
    $this->withUnencryptedCookie('appearance', 'dark')
        ->get(route('home'))
        ->assertOk()
        ->assertSee('<html lang="nl" class="dark">', false);
});

test('ignores an invalid appearance cookie', function () {
    $this->withUnencryptedCookie('appearance', 'invalid')
        ->get(route('home'))
        ->assertOk()
        ->assertDontSee('<html lang="nl" class="dark">', false);
});

test('uses Dutch as the default public locale', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('<html lang="nl"', false)
        ->assertInertia(fn (Assert $page) => $page
            ->where('locale', 'nl')
            ->where('supportedLocales', ['nl', 'en'])
        );
});

test('stores and applies the selected locale', function () {
    $this->post(route('locale.update'), ['locale' => 'en'])
        ->assertRedirect()
        ->assertSessionHas('locale', 'en');

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('<html lang="en"', false)
        ->assertInertia(fn (Assert $page) => $page->where('locale', 'en'));
});

test('rejects unsupported locales', function () {
    $this->from(route('home'))
        ->post(route('locale.update'), ['locale' => 'de'])
        ->assertRedirect(route('home'))
        ->assertSessionHasErrors('locale');
});

test('shares the saved appearance with the dashboard', function () {
    $this->withUnencryptedCookie('appearance', 'dark')
        ->get(route('filament.dashboard.auth.login'))
        ->assertOk()
        ->assertSee("const cookieTheme = 'dark';", false);
});
