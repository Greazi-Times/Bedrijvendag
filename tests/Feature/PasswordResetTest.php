<?php

use App\Filament\Pages\Auth\RequestPasswordReset;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Auth\Notifications\ResetPassword;
use Filament\Auth\Pages\PasswordReset\ResetPassword as ResetPasswordPage;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    Filament::setCurrentPanel('dashboard');
    $this->user = User::factory()->create();
    $this->user->assignRole('Super Admin');
});

test('a known address gets a reset notification with a stored token', function () {
    Notification::fake();

    Livewire::test(RequestPasswordReset::class)->fillForm(['email' => $this->user->email])->call('request');

    Notification::assertSentTo($this->user, ResetPassword::class);
    expect(DB::table('password_reset_tokens')->where('email', $this->user->email)->exists())->toBeTrue();
});

test('unknown and throttled addresses look the same as known ones', function () {
    Notification::fake();

    $page = Livewire::test(RequestPasswordReset::class);
    $page->fillForm(['email' => 'nobody@example.com'])->call('request')->assertNotified(__('passwords.sent'));
    Notification::assertNothingSent();

    // Second request for a known address within the broker throttle window.
    Password::broker('users')->createToken($this->user);
    Livewire::test(RequestPasswordReset::class)
        ->fillForm(['email' => $this->user->email])->call('request')->assertNotified(__('passwords.sent'));
    Notification::assertNothingSent();
});

test('a valid token sets a new password', function () {
    $token = Password::broker('users')->createToken($this->user);

    Livewire::test(ResetPasswordPage::class, ['email' => $this->user->email, 'token' => $token])
        ->fillForm(['email' => $this->user->email, 'password' => 'a-Strong-password-1', 'passwordConfirmation' => 'a-Strong-password-1'])
        ->call('resetPassword')
        ->assertHasNoFormErrors();

    expect(Hash::check('a-Strong-password-1', $this->user->fresh()->password))->toBeTrue()
        ->and(DB::table('password_reset_tokens')->where('email', $this->user->email)->exists())->toBeFalse();
});

test('an expired token is rejected', function () {
    $token = Password::broker('users')->createToken($this->user);
    $hash = $this->user->password;
    $this->travel(61)->minutes();

    Livewire::test(ResetPasswordPage::class, ['email' => $this->user->email, 'token' => $token])
        ->fillForm(['email' => $this->user->email, 'password' => 'a-Strong-password-1', 'passwordConfirmation' => 'a-Strong-password-1'])
        ->call('resetPassword');

    expect($this->user->fresh()->password)->toBe($hash);
});

test('manually setting a password sends no email', function () {
    Notification::fake();
    Mail::fake();

    $this->actingAs($this->user);
    $target = User::factory()->create();
    Livewire::test(\App\Filament\Resources\Users\Pages\EditUser::class, ['record' => $target->getRouteKey()])
        ->fillForm(['password' => 'a-Strong-password-1', 'password_confirmation' => 'a-Strong-password-1'])
        ->call('save');

    Notification::assertNothingSent();
    Mail::assertNothingSent();
});
