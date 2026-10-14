<?php

use App\Filament\Pages\ManagePrivacyPolicy;
use App\Filament\Resources\Roles\Pages\CreateRole;
use App\Filament\Resources\Roles\Pages\EditRole;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Models\AuditLog;
use App\Models\CompanyAccessRequest;
use App\Models\Event;
use App\Models\User;
use App\Support\Access;
use App\Support\Permissions;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\AuthorizationException;
use Jeffgreco13\FilamentBreezy\Middleware\MustTwoFactor;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    Filament::setCurrentPanel(Filament::getPanel('dashboard'));
    // The panel forces 2FA setup first; these tests are about what happens after it.
    $this->withoutMiddleware(MustTwoFactor::class);
});

/** A user with one role that holds exactly these permissions. */
function userWith(array $permissions, string $role = 'Tester'): User
{
    $user = User::factory()->create();
    $user->assignRole(Role::findOrCreate($role)->syncPermissions($permissions));

    return $user;
}

function superAdmin(): User
{
    return tap(User::factory()->create())->assignRole(Permissions::SUPER_ADMIN);
}

function administrator(): User
{
    return tap(User::factory()->create())->assignRole(Permissions::ADMINISTRATOR);
}

// Authentication and page access

test('guests are redirected to the login page', function () {
    $this->get('/dashboard/users')->assertRedirect('/dashboard/login');
});

test('users without dashboard access get a 403', function () {
    $this->actingAs(User::factory()->create())
        ->get('/dashboard')
        ->assertForbidden();
});

test('administration pages require their permission', function (string $url, string $permission) {
    $this->actingAs(userWith(['dashboard.view']))->get($url)->assertForbidden();
    $this->actingAs(userWith(['dashboard.view', $permission], 'Allowed'))->get($url)->assertOk();
})->with([
    ['/dashboard/users', 'users.view'],
    ['/dashboard/roles', 'roles.view'],
    ['/dashboard/permissions', 'permissions.view'],
    ['/dashboard/audit-logs', 'audit-logs.view'],
]);

test('super admins can open every administration page', function () {
    $this->actingAs(superAdmin());

    foreach (['/dashboard', '/dashboard/users', '/dashboard/roles', '/dashboard/permissions', '/dashboard/audit-logs', '/dashboard/events'] as $url) {
        $this->get($url)->assertOk();
    }
});

test('existing modules are checked per action', function () {
    $user = userWith(['dashboard.view', 'events.view']);

    expect($user->can('viewAny', Event::class))->toBeTrue()
        ->and($user->can('create', Event::class))->toBeFalse()
        ->and($user->can('delete', Event::class))->toBeFalse();

    $this->actingAs($user)->get('/dashboard/events/create')->assertForbidden();
});

test('approving requests and editing settings need their own permission', function () {
    $viewer = userWith(['dashboard.view', 'company-access-requests.view']);
    $this->actingAs($viewer);

    expect($viewer->can('viewAny', CompanyAccessRequest::class))->toBeTrue()
        ->and($viewer->can('review', CompanyAccessRequest::class))->toBeFalse()
        ->and(ManagePrivacyPolicy::canAccess())->toBeFalse();

    $this->actingAs(userWith(['dashboard.view', 'settings.manage'], 'Settings'));

    expect(ManagePrivacyPolicy::canAccess())->toBeTrue();
});

// User management

test('authorized users can create a user with roles', function () {
    $this->actingAs(administrator());
    $editor = Role::create(['name' => 'Editor'])->givePermissionTo('events.view');

    Livewire::test(CreateUser::class)
        ->fillForm([
            'name' => 'New Person',
            'email' => 'new@example.com',
            'password' => 'a-Strong-password-1',
            'password_confirmation' => 'a-Strong-password-1',
            'roles' => [$editor->id],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $user = User::query()->where('email', 'new@example.com')->firstOrFail();

    expect($user->hasRole('Editor'))->toBeTrue()
        ->and(password_verify('a-Strong-password-1', $user->password))->toBeTrue();
});

test('user creation rejects invalid data', function () {
    $this->actingAs(administrator());

    Livewire::test(CreateUser::class)
        ->fillForm(['name' => '', 'email' => 'not-an-email', 'password' => 'x', 'password_confirmation' => 'y'])
        ->call('create')
        ->assertHasFormErrors(['name', 'email', 'password']);

    expect(User::query()->where('email', 'not-an-email')->exists())->toBeFalse();
});

test('authorized users can edit a user and change roles', function () {
    $this->actingAs(administrator());
    $target = User::factory()->create(['name' => 'Old Name']);
    $editor = Role::create(['name' => 'Editor'])->givePermissionTo('events.view');

    Livewire::test(EditUser::class, ['record' => $target->getRouteKey()])
        ->fillForm(['name' => 'New Name', 'roles' => [$editor->id]])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($target->fresh()->name)->toBe('New Name')
        ->and($target->fresh()->hasRole('Editor'))->toBeTrue();
});

test('administrators cannot assign the super admin role', function () {
    $admin = administrator();
    $target = User::factory()->create();
    $this->actingAs($admin);

    Livewire::test(EditUser::class, ['record' => $target->getRouteKey()])
        ->fillForm(['roles' => [Role::findByName(Permissions::SUPER_ADMIN)->id]])
        ->call('save');

    expect($target->fresh()->isSuperAdmin())->toBeFalse();
    expect(fn () => Access::syncRoles($admin, $target, [Role::findByName(Permissions::SUPER_ADMIN)->id]))
        ->toThrow(AuthorizationException::class);
});

test('roles with permissions the actor lacks cannot be assigned', function () {
    $actor = userWith(['dashboard.view', 'users.view', 'users.update']);
    $powerful = Role::create(['name' => 'Powerful'])->givePermissionTo('roles.update');
    $target = User::factory()->create();

    expect(fn () => Access::syncRoles($actor, $target, [$powerful->id]))->toThrow(AuthorizationException::class)
        ->and($target->fresh()->roles)->toBeEmpty();
});

test('users cannot change their own roles', function () {
    $admin = administrator();

    expect(fn () => Access::syncRoles($admin, $admin, []))->toThrow(AuthorizationException::class)
        ->and($admin->fresh()->hasRole(Permissions::ADMINISTRATOR))->toBeTrue();
});

test('administrators cannot edit or delete super admin accounts', function () {
    $admin = administrator();
    $super = superAdmin();
    $this->actingAs($admin);

    expect($admin->can('update', $super))->toBeFalse()
        ->and($admin->can('delete', $super))->toBeFalse();

    Livewire::test(EditUser::class, ['record' => $super->getRouteKey()])->assertForbidden();

    expect(fn () => Access::deleteUser($admin, $super))->toThrow(AuthorizationException::class)
        ->and($super->fresh())->not->toBeNull();
});

test('a user cannot manage an account with more permissions than their own', function () {
    $actor = userWith(['dashboard.view', 'users.view', 'users.update', 'users.delete']);
    $stronger = userWith(['dashboard.view', 'roles.update'], 'Stronger');

    expect($actor->can('update', $stronger))->toBeFalse()
        ->and($actor->can('delete', $stronger))->toBeFalse();
});

test('the last super admin cannot delete themselves or be removed', function () {
    $super = superAdmin();

    expect(fn () => Access::deleteUser($super, $super))->toThrow(AuthorizationException::class)
        ->and(fn () => Access::syncRoles($super, $super, []))->toThrow(AuthorizationException::class)
        ->and($super->fresh()->isSuperAdmin())->toBeTrue();
});

test('a super admin can remove another super admin', function () {
    $first = superAdmin();
    $second = superAdmin();

    Access::syncRoles($first, $second, []);

    expect($second->fresh()->isSuperAdmin())->toBeFalse();
});

test('deleting a user is logged with a readable label', function () {
    $admin = administrator();
    $target = User::factory()->create(['email' => 'gone@example.com']);

    Access::deleteUser($admin, $target);

    expect(User::query()->whereKey($target->id)->exists())->toBeFalse()
        ->and(AuditLog::query()->where('action', 'user.deleted')->value('subject_label'))->toBe($target->name);
});

test('unauthorized requests do not change data', function () {
    $viewer = userWith(['dashboard.view', 'users.view']);
    $target = User::factory()->create(['name' => 'Unchanged']);
    $this->actingAs($viewer);

    Livewire::test(EditUser::class, ['record' => $target->getRouteKey()])->assertForbidden();
    Livewire::test(CreateUser::class)->assertForbidden();

    expect($target->fresh()->name)->toBe('Unchanged')
        ->and(AuditLog::query()->count())->toBe(0);
});

// Role management

test('roles can be created with permissions', function () {
    $this->actingAs(superAdmin());

    Livewire::test(CreateRole::class)
        ->fillForm(['name' => 'Event Manager', 'permissions' => ['events' => ['events.view', 'events.update']]])
        ->call('create')
        ->assertHasNoFormErrors();

    $role = Role::findByName('Event Manager');

    expect($role->permissions->pluck('name')->sort()->values()->all())->toBe(['events.update', 'events.view'])
        ->and(AuditLog::query()->where('action', 'role.created')->exists())->toBeTrue();
});

test('roles can be renamed and permissions added and removed', function () {
    $this->actingAs(superAdmin());
    $role = Role::create(['name' => 'Editor'])->givePermissionTo(['events.view', 'sectors.view']);

    Livewire::test(EditRole::class, ['record' => $role->getRouteKey()])
        ->fillForm(['name' => 'Content Editor', 'permissions' => ['events' => ['events.view', 'events.create'], 'sectors' => []]])
        ->call('save')
        ->assertHasNoFormErrors();

    $role->refresh();

    expect($role->name)->toBe('Content Editor')
        ->and($role->permissions->pluck('name')->sort()->values()->all())->toBe(['events.create', 'events.view'])
        ->and(AuditLog::query()->where('action', 'role.updated')->first()->changes)->toMatchArray([
            'permissions_added' => ['events.create'],
            'permissions_removed' => ['sectors.view'],
        ]);
});

test('custom roles can be deleted but system roles cannot', function () {
    $super = superAdmin();
    $custom = Role::create(['name' => 'Temporary']);

    Access::deleteRole($super, $custom);
    expect(Role::query()->where('name', 'Temporary')->exists())->toBeFalse();

    foreach (Permissions::SYSTEM_ROLES as $name) {
        expect(fn () => Access::deleteRole($super, Role::findByName($name)))->toThrow(AuthorizationException::class);
    }
});

test('the super admin role cannot be edited, even by a super admin', function () {
    $super = superAdmin();
    $this->actingAs($super);
    $role = Role::findByName(Permissions::SUPER_ADMIN);

    Livewire::test(EditRole::class, ['record' => $role->getRouteKey()])->assertForbidden();
    expect(fn () => Access::saveRole($super, $role, 'Renamed', []))->toThrow(AuthorizationException::class);
});

test('administrators cannot grant permissions they do not hold', function () {
    $actor = userWith(['dashboard.view', 'roles.view', 'roles.create', 'roles.update']);

    expect(fn () => Access::saveRole($actor, null, 'Sneaky', ['users.delete']))->toThrow(AuthorizationException::class)
        ->and(Role::query()->where('name', 'Sneaky')->exists())->toBeFalse();

    $this->actingAs($actor);

    Livewire::test(CreateRole::class)
        ->fillForm(['name' => 'Sneaky', 'permissions' => ['users' => ['users.delete']]])
        ->call('create');

    expect(Role::query()->where('name', 'Sneaky')->exists())->toBeFalse();
});

test('users cannot escalate through a role they hold', function () {
    $actor = userWith(['dashboard.view', 'roles.view', 'roles.update', 'events.view'], 'Own Role');
    $ownRole = Role::findByName('Own Role');

    expect($actor->can('update', $ownRole))->toBeFalse()
        ->and(fn () => Access::saveRole($actor, $ownRole, 'Own Role', [...$ownRole->permissions->pluck('name'), 'users.delete']))
        ->toThrow(AuthorizationException::class);
});

// Effective permissions

test('effective permissions show which role grants each permission', function () {
    $user = User::factory()->create();
    $user->assignRole(Role::create(['name' => 'Event Manager'])->givePermissionTo('events.view'));
    $user->givePermissionTo('sectors.view');

    $events = collect(Access::effectivePermissions($user)['Events'])->keyBy('label');
    $sectors = collect(Access::effectivePermissions($user)['Sectors'])->keyBy('label');

    expect($events['View'])->toMatchArray(['allowed' => true, 'via' => ['Event Manager']])
        ->and($events['Delete'])->toMatchArray(['allowed' => false, 'via' => []])
        ->and($sectors['View'])->toMatchArray(['allowed' => true, 'via' => ['Direct']]);
});

// Database and seeding

test('seeding is idempotent and keeps dashboard changes', function () {
    $administrator = Role::findByName(Permissions::ADMINISTRATOR);
    $administrator->revokePermissionTo('events.delete');

    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(RolesAndPermissionsSeeder::class);

    expect(Permission::query()->count())->toBe(count(Permissions::all()))
        ->and(Role::query()->count())->toBe(count(Permissions::SYSTEM_ROLES))
        ->and($administrator->fresh()->hasPermissionTo('events.delete'))->toBeFalse()
        ->and($administrator->fresh()->hasPermissionTo('events.view'))->toBeTrue();
});

test('existing users are kept and get no privileges from seeding', function () {
    $existing = User::factory()->create();

    $this->seed(RolesAndPermissionsSeeder::class);

    expect($existing->fresh())->not->toBeNull()
        ->and($existing->fresh()->roles)->toBeEmpty()
        ->and($existing->fresh()->can('dashboard.view'))->toBeFalse();
});

test('the super admin command only grants the named user', function () {
    $chosen = User::factory()->create(['email' => 'chosen@example.com']);
    $other = User::factory()->create();

    $this->artisan('users:grant-super-admin', ['email' => 'chosen@example.com'])
        ->expectsConfirmation("Give {$chosen->name} <chosen@example.com> unrestricted access to the dashboard?", 'yes')
        ->assertSuccessful();

    expect($chosen->fresh()->isSuperAdmin())->toBeTrue()
        ->and($other->fresh()->isSuperAdmin())->toBeFalse();
});

// Audit logging

test('audit entries never contain passwords or hashes', function () {
    $this->actingAs(administrator());

    Livewire::test(CreateUser::class)
        ->fillForm([
            'name' => 'Logged Person',
            'email' => 'logged@example.com',
            'password' => 'a-Secret-value-9',
            'password_confirmation' => 'a-Secret-value-9',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $hash = User::query()->where('email', 'logged@example.com')->value('password');
    $logged = AuditLog::query()->get()->toJson();

    expect(AuditLog::query()->where('action', 'user.created')->exists())->toBeTrue()
        ->and($logged)->not->toContain('a-Secret-value-9')
        ->and($logged)->not->toContain($hash)
        ->and($logged)->not->toContain('password');
});
