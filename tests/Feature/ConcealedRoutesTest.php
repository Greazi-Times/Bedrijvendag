<?php

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Jeffgreco13\FilamentBreezy\Middleware\MustTwoFactor;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->withoutMiddleware(MustTwoFactor::class);
});

function concealedUser(array $permissions): User
{
    return tap(User::factory()->create())->assignRole(Role::findOrCreate('Concealed '.implode(',', $permissions))->syncPermissions($permissions));
}

/** What a visitor gets for a URL that does not exist at all: the Error page and its props. */
function missingPage($test): array
{
    return errorPage($test->get('/this-page-does-not-exist')->assertNotFound());
}

function errorPage($response): array
{
    $page = $response->viewData('page');

    return [$page['component'], $page['props']];
}

test('missing public urls show the Error page with fixed props', function () {
    $this->actingAs(concealedUser(['dashboard.view']))
        ->get('/this-page-does-not-exist')
        ->assertNotFound()
        ->assertInertia(fn ($page) => $page
            ->component('Error')
            ->where('status', 404)
            ->where('auth', ['user' => null, 'canAccessDashboard' => false])
            ->where('media.siteLogo', '/favicon.svg')
            ->missing('borrelEnrollmentOpen'));
});

test('the 404 page follows the visitor\'s language', function () {
    $this->withSession(['locale' => 'en'])
        ->get('/this-page-does-not-exist')
        ->assertNotFound()
        ->assertInertia(fn ($page) => $page->component('Error')->where('locale', 'en'));
});

test('the 403 page exists for refusals the app chooses to disclose', function () {
    Route::get('/_test/denied', fn () => abort(403))->middleware('web');

    $this->get('/_test/denied')->assertForbidden()->assertInertia(fn ($page) => $page->component('Error')->where('status', 403));
});

test('the 500 page still renders when the asset build is unavailable', function () {
    config(['app.debug' => false]);
    Route::get('/_test/boom', fn () => throw new RuntimeException('boom'));
    $this->app->instance(Illuminate\Foundation\Vite::class, new class extends Illuminate\Foundation\Vite
    {
        public function __invoke($entrypoints, $buildDirectory = null)
        {
            throw new RuntimeException('no manifest');
        }
    });

    $this->get('/_test/boom')->assertStatus(500)->assertSee('Er ging iets mis');
});

test('guests get the same 404 as a missing page on protected routes', function (string $url) {
    $response = $this->get($url)->assertNotFound();

    expect(errorPage($response))->toBe(missingPage($this));
})->with(['/dashboard', '/dashboard/users', '/dashboard/users/create', '/dashboard/events/1/edit', '/dashboard/security', '/dashboard/does-not-exist']);

test('users without the permission get the same 404 as a missing page', function (array $permissions, string $url) {
    $this->actingAs(concealedUser($permissions));

    expect(errorPage($this->get($url)->assertNotFound()))->toBe(missingPage($this));
})->with([
    'no dashboard access' => [[], '/dashboard/users'],
    'no module access' => [['dashboard.view'], '/dashboard/users'],
    'view but not create' => [['dashboard.view', 'events.view'], '/dashboard/events/create'],
]);

test('authorized users keep access to their pages', function () {
    $this->actingAs(concealedUser(['dashboard.view', 'users.view']));

    $this->get('/dashboard')->assertOk();
    $this->get('/dashboard/users')->assertOk();
    $this->get('/dashboard/roles')->assertNotFound();
});

test('every http method is concealed for guests and unauthorized users', function (string $method) {
    $this->withoutMiddleware(VerifyCsrfToken::class);

    foreach (['/dashboard/users', '/dashboard/logout', '/dashboard/does-not-exist'] as $url) {
        $this->call($method, $url)->assertNotFound();
        $this->actingAs(concealedUser([]))->call($method, $url)->assertNotFound();
        auth()->logout();
    }
})->with(['POST', 'PUT', 'PATCH', 'DELETE']);

test('protected endpoints return a generic json 404', function () {
    $missing = $this->getJson('/api/does-not-exist')->assertNotFound()->json();

    expect($missing)->toBe(['message' => 'Not Found']);

    $this->getJson('/dashboard/users')->assertNotFound()->assertExactJson($missing);
    // Signed export downloads: an unsigned request would otherwise be a 403.
    $this->getJson('/filament-excel/export.xlsx')->assertNotFound()->assertExactJson($missing);
    $this->actingAs(concealedUser(['dashboard.view']))->getJson('/dashboard/roles')->assertNotFound()->assertExactJson($missing);
    $this->deleteJson('/dashboard/users/1')->assertNotFound()->assertExactJson($missing);
});

test('public pages and the login pages stay reachable', function (string $url) {
    $this->get($url)->assertOk();
})->with(['/', '/bedrijven', '/contact', '/dashboard/login', '/dashboard/password-reset/request']);

test('logging in to the dashboard still works', function () {
    $user = concealedUser(['dashboard.view']);

    Livewire\Livewire::test(App\Filament\Pages\Auth\Login::class)
        ->fillForm(['email' => $user->email, 'password' => 'password'])
        ->call('authenticate')
        ->assertHasNoFormErrors();

    expect(auth()->id())->toBe($user->id);
});

test('the dashboard link in the footer is only shared with users who can open it', function () {
    $this->get('/')->assertInertia(fn ($page) => $page->where('auth.canAccessDashboard', false));
    $this->actingAs(concealedUser([]))->get('/')->assertInertia(fn ($page) => $page->where('auth.canAccessDashboard', false));
    $this->actingAs(concealedUser(['dashboard.view']))->get('/')->assertInertia(fn ($page) => $page->where('auth.canAccessDashboard', true));
});

test('the 419 and 500 pages hide internals', function () {
    config(['app.debug' => false]);
    Route::get('/_test/boom', fn () => throw new RuntimeException('secret internal detail'))->middleware('web');
    Route::post('/_test/form', fn () => throw new Illuminate\Session\TokenMismatchException)->middleware('web');

    $this->get('/_test/boom')->assertStatus(500)->assertSee(['Er ging iets mis', 'Opnieuw proberen'])->assertDontSee('secret internal detail')->assertDontSee('RuntimeException');
    $this->post('/_test/form')->assertStatus(419)->assertInertia(fn ($page) => $page->component('Error')->where('status', 419));
});
