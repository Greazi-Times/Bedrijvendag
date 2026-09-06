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
        ->assertSee('<html lang="en" class="dark">', false);
});

test('ignores an invalid appearance cookie', function () {
    $this->withUnencryptedCookie('appearance', 'invalid')
        ->get(route('home'))
        ->assertOk()
        ->assertDontSee('<html lang="en" class="dark">', false);
});

test('shares the saved appearance with the dashboard', function () {
    $this->withUnencryptedCookie('appearance', 'dark')
        ->get(route('filament.dashboard.auth.login'))
        ->assertOk()
        ->assertSee("const cookieTheme = 'dark';", false);
});
