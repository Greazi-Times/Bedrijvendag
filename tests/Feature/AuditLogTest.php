<?php

use App\Exceptions\PrivilegeDenied;
use App\Filament\Pages\Auth\Login;
use App\Filament\Resources\AuditLogs\Pages\ListAuditLogs;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\RelationManagers\AccountHistoryRelationManager;
use App\Filament\Resources\Users\RelationManagers\ActivityRelationManager;
use App\Filament\Security\Widgets\OpenSecurityAlerts;
use App\Filament\Security\Widgets\SecurityStats;
use App\Filament\Security\Widgets\TopSourceIps;
use App\Models\AuditLog;
use App\Models\Company;
use App\Models\Event;
use App\Models\SecurityAlert;
use App\Models\User;
use App\Support\Access;
use App\Support\Audit;
use App\Support\AuditRetention;
use App\Support\Permissions;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Facades\Filament;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Database\Eloquent\MassAssignmentException;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Route;
use Jeffgreco13\FilamentBreezy\Middleware\MustTwoFactor;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    Filament::setCurrentPanel(Filament::getPanel('dashboard'));
    $this->withoutMiddleware(MustTwoFactor::class);
});

afterEach(fn () => TrustProxies::flushState());

function auditor(array $permissions = ['dashboard.view', 'audit.view'], string $role = 'Auditor'): User
{
    $user = User::factory()->create();
    $user->assignRole(Role::findOrCreate($role)->syncPermissions($permissions));

    return $user;
}

function auditSuperAdmin(): User
{
    return tap(User::factory()->create())->assignRole(Permissions::SUPER_ADMIN);
}

/** Moves an entry back in time without going through the (append-only) model. */
function backdate(AuditLog $log, int $days): void
{
    DB::table('activity_log')->where('id', $log->id)->update(['created_at' => now()->subDays($days)]);
}

// Authentication

test('a successful login is logged with the user', function () {
    $user = auditor(['dashboard.view']);
    $user->forceFill(['email' => 'jane@example.com', 'password' => 'secret-pass'])->save();

    Livewire::test(Login::class)
        ->fillForm(['email' => 'jane@example.com', 'password' => 'secret-pass'])
        ->call('authenticate');

    $log = AuditLog::query()->where('event', 'auth.login.success')->sole();

    expect($log->causer_id)->toBe($user->id)
        ->and($log->actor_type)->toBe('user')
        ->and($log->actor_label)->toBe($user->name)
        ->and($log->log_name)->toBe('auth')
        ->and($log->severity)->toBe('info')
        ->and($log->ip_address)->not->toBeNull()
        ->and($log->properties['guard'])->toBe('web');
});

test('a failed login is logged with a masked identifier and never the password', function () {
    $user = User::factory()->create(['email' => 'jane.doe@example.com']);

    Livewire::test(Login::class)
        ->fillForm(['email' => 'jane.doe@example.com', 'password' => 'wrong-guess-123'])
        ->call('authenticate')
        ->assertHasFormErrors();

    Livewire::test(Login::class)
        ->fillForm(['email' => 'nobody@example.com', 'password' => 'wrong-guess-123'])
        ->call('authenticate');

    [$known, $unknown] = AuditLog::query()->where('event', 'auth.login.failed')->orderBy('id')->get();

    expect($known->subject_id)->toBe($user->id)
        ->and($known->causer_id)->toBeNull()
        ->and($known->actor_type)->toBe('guest')
        ->and($known->result)->toBe('failure')
        ->and($known->severity)->toBe('warning')
        ->and($known->properties['identifier'])->toBe('ja***@example.com')
        ->and($known->properties['reason'])->toBe('credentials_rejected')
        ->and($unknown->subject_id)->toBeNull()
        ->and($unknown->properties['reason'])->toBe('unknown_account')
        ->and(AuditLog::query()->get()->toJson())->not->toContain('wrong-guess-123')
        ->and(AuditLog::query()->where('log_name', 'auth')->get()->toJson())->not->toContain('jane.doe@');
});

test('a login lockout is logged once the rate limit is hit', function () {
    User::factory()->create(['email' => 'jane@example.com']);

    foreach (range(1, 6) as $attempt) {
        Livewire::test(Login::class)
            ->fillForm(['email' => 'jane@example.com', 'password' => 'wrong'])
            ->call('authenticate');
    }

    $lockout = AuditLog::query()->where('event', 'security.login.lockout')->first();

    expect($lockout)->not->toBeNull()
        ->and($lockout->severity)->toBe('warning')
        ->and($lockout->result)->toBe('denied')
        ->and($lockout->properties['identifier_hash'])->toBe(Audit::hashIdentifier('jane@example.com'));
});

test('logging out is logged', function () {
    $user = auditor();

    $this->actingAs($user)->post('/dashboard/logout');

    expect(AuditLog::query()->where('event', 'auth.logout')->where('causer_id', $user->id)->exists())->toBeTrue();
});

test('password reset requests and completed resets are logged once, without tokens', function () {
    $user = User::factory()->create(['email' => 'jane@example.com']);

    Password::sendResetLink(['email' => 'jane@example.com']);
    $token = Password::createToken($user);

    $this->post('/reset-password', [
        'token' => $token,
        'email' => 'jane@example.com',
        'password' => 'brand-new-Pass-1',
        'password_confirmation' => 'brand-new-Pass-1',
    ]);

    expect(AuditLog::query()->where('event', 'auth.password.reset_requested')->where('subject_id', $user->id)->exists())->toBeTrue()
        ->and(AuditLog::query()->where('event', 'auth.password.reset')->where('subject_id', $user->id)->exists())->toBeTrue()
        ->and(AuditLog::query()->where('event', 'auth.password.changed')->exists())->toBeFalse()
        ->and(AuditLog::query()->get()->toJson())->not->toContain($token)->not->toContain('brand-new-Pass-1');
});

test('a password change is logged without the hash', function () {
    $user = auditor();
    $this->actingAs($user);

    $user->update(['password' => 'changed-Pass-1']);

    $log = AuditLog::query()->where('event', 'auth.password.changed')->sole();

    expect($log->subject_id)->toBe($user->id)
        ->and($log->causer_id)->toBe($user->id)
        ->and(AuditLog::query()->where('event', 'users.updated')->exists())->toBeFalse()
        ->and(AuditLog::query()->get()->toJson())->not->toContain($user->fresh()->password);
});

// Model changes

test('model changes are logged with the actor and only the changed values', function () {
    $admin = auditSuperAdmin();
    $this->actingAs($admin);

    $event = Event::query()->create(['name' => 'Summer Party', 'date' => '2026-06-01', 'max_stands' => 100]);
    $event->update(['name' => 'Winter Party', 'max_stands' => 250]);
    $event->update(['name' => 'Winter Party']);
    $event->save();
    $event->delete();

    $logs = AuditLog::query()->where('log_name', 'events')->orderBy('id')->get();

    expect($logs->pluck('event')->all())->toBe(['events.created', 'events.updated', 'events.deleted'])
        ->and($logs[0]->properties['attributes']['name'])->toBe('Summer Party')
        ->and($logs[1]->properties['old'])->toMatchArray(['name' => 'Summer Party', 'max_stands' => 100])
        ->and($logs[1]->properties['attributes'])->toMatchArray(['name' => 'Winter Party', 'max_stands' => 250])
        ->and($logs[1]->properties['attributes'])->not->toHaveKeys(['description', 'date'])
        ->and($logs[1]->causer_id)->toBe($admin->id)
        ->and($logs[1]->actor_label)->toBe($admin->name)
        ->and($logs[1]->subject_id)->toBe($event->id)
        ->and($logs[1]->subject_label)->toBe('Winter Party')
        ->and($logs[2]->properties['old']['name'])->toBe('Winter Party');

    $this->get("/dashboard/audit-logs/{$logs[1]->id}")
        ->assertOk()
        ->assertSeeInOrder(['Before', 'After', 'Name', 'Summer Party', 'Winter Party'])
        ->assertSee('This record has since been deleted.');
});

test('sensitive attributes never reach the log', function () {
    $this->actingAs(auditSuperAdmin());

    $company = Company::query()->create(['name' => 'Acme']);
    $company->update(['profile_token' => 'secret-link-token', 'name' => 'Acme BV']);
    $user = User::factory()->create(['password' => 'plain-Secret-1']);

    $logged = AuditLog::query()->get()->toJson();

    expect($logged)->not->toContain('secret-link-token')
        ->not->toContain($company->fresh()->profile_token)
        ->not->toContain('plain-Secret-1')
        ->not->toContain($user->password)
        ->not->toContain('remember_token')
        ->and(AuditLog::query()->where('event', 'companies.updated')->sole()->properties['attributes'])->toBe(['name' => 'Acme BV']);
});

// Roles and permissions

test('role assignment and removal are logged with the acting administrator', function () {
    $admin = auditSuperAdmin();
    $target = User::factory()->create();
    $editor = Role::create(['name' => 'Editor']);
    $viewer = Role::create(['name' => 'Viewer']);
    $this->actingAs($admin);

    Access::syncRoles($admin, $target, [$editor->id]);
    Access::syncRoles($admin, $target, [$viewer->id]);

    $assigned = AuditLog::query()->where('event', 'roles.assigned')->orderBy('id')->get();
    $revoked = AuditLog::query()->where('event', 'roles.revoked')->sole();

    expect($assigned)->toHaveCount(2)
        ->and($assigned[0]->properties['roles'])->toBe(['Editor'])
        ->and($assigned[0]->causer_id)->toBe($admin->id)
        ->and($assigned[0]->subject_id)->toBe($target->id)
        ->and($assigned[1]->properties->only(['old', 'new'])->all())->toBe(['old' => ['Editor'], 'new' => ['Viewer']])
        ->and($revoked->properties['roles'])->toBe(['Editor'])
        ->and($revoked->severity)->toBe('warning');
});

test('granting the super admin role is high severity', function () {
    $admin = auditSuperAdmin();
    $this->actingAs($admin);

    Access::syncRoles($admin, User::factory()->create(), [Role::findByName(Permissions::SUPER_ADMIN)->id]);

    expect(AuditLog::query()->where('event', 'roles.assigned')->sole()->severity)->toBe('high');
});

test('permission changes record what was added and removed', function () {
    $admin = auditSuperAdmin();
    $this->actingAs($admin);
    $role = Role::create(['name' => 'Manager'])->givePermissionTo(['events.view', 'users.view']);

    Access::saveRole($admin, $role, 'Manager', ['events.view', 'events.update']);

    $log = AuditLog::query()->where('event', 'permissions.updated')->sole();

    expect($log->properties['added'])->toBe(['events.update'])
        ->and($log->properties['removed'])->toBe(['users.view'])
        ->and($log->properties['old'])->toBe(['events.view', 'users.view'])
        ->and($log->causer_id)->toBe($admin->id)
        ->and($log->severity)->toBe('high');
});

// Security events

test('forbidden requests are logged as denied access', function () {
    $user = auditor(['dashboard.view'], 'Plain');

    $this->actingAs($user)->get('/dashboard/audit-logs')->assertNotFound();

    $log = AuditLog::query()->where('event', 'security.access.denied')->sole();

    expect($log->causer_id)->toBe($user->id)
        ->and($log->result)->toBe('denied')
        ->and($log->severity)->toBe('warning')
        ->and($log->path)->toBe('/dashboard/audit-logs')
        ->and($log->route_name)->toBe('filament.dashboard.resources.audit-logs.index');
});

test('refused privilege changes are logged with their severity', function () {
    $this->actingAs(auditSuperAdmin());

    app(ExceptionHandler::class)->render(request(), new PrivilegeDenied('The last Super Admin cannot be removed.', 'critical'));

    $log = AuditLog::query()->where('event', 'security.privilege.denied')->sole();

    expect($log->severity)->toBe('critical')
        ->and($log->description)->toBe('The last Super Admin cannot be removed.')
        ->and(AuditLog::query()->where('event', 'security.access.denied')->exists())->toBeFalse();
});

test('ordinary 404s are not logged as security events', function () {
    $this->get('/does-not-exist')->assertNotFound();

    expect(AuditLog::query()->where('log_name', 'security')->exists())->toBeFalse();
});

test('critical events open one alert and repeats only raise its count', function () {
    $this->actingAs(auditSuperAdmin());

    Audit::log('security.privilege.denied', 'Attempt one', severity: 'critical', result: 'denied');
    Audit::log('security.privilege.denied', 'Attempt two', severity: 'critical', result: 'denied');

    $alert = SecurityAlert::query()->sole();

    expect($alert->rule)->toBe('privilege_events')
        ->and($alert->count)->toBe(2);
});

test('failed logins raise an alert only at the threshold, per IP address', function () {
    request()->server->set('REMOTE_ADDR', '198.51.100.7');

    foreach (range(1, 9) as $attempt) {
        Audit::log('auth.login.failed', 'Failed sign-in attempt', properties: ['identifier_hash' => "hash-{$attempt}"], result: 'failure');
    }

    expect(SecurityAlert::query()->where('rule', 'failed_logins_per_ip')->exists())->toBeFalse();

    Audit::log('auth.login.failed', 'Failed sign-in attempt', properties: ['identifier_hash' => 'hash-10'], result: 'failure');
    Audit::log('auth.login.failed', 'Failed sign-in attempt', properties: ['identifier_hash' => 'hash-11'], result: 'failure');

    $alert = SecurityAlert::query()->where('rule', 'failed_logins_per_ip')->sole();

    expect($alert->group_key)->toBe('198.51.100.7')
        ->and($alert->count)->toBe(11);
});

test('acknowledged alerts let a new burst open a new alert', function () {
    $this->actingAs(auditSuperAdmin());

    Audit::log('security.privilege.denied', 'One', severity: 'critical');
    SecurityAlert::query()->sole()->forceFill(['acknowledged_at' => now()])->save();
    Audit::log('security.privilege.denied', 'Two', severity: 'critical');

    expect(SecurityAlert::query()->count())->toBe(2)
        ->and(SecurityAlert::query()->whereNull('acknowledged_at')->count())->toBe(1);
});

// Access to the audit log

test('audit entries are read-only', function () {
    $log = Audit::log('settings.updated', 'Settings updated');

    expect(fn () => $log->forceFill(['description' => 'Rewritten'])->save())->toThrow(LogicException::class)
        ->and(fn () => $log->delete())->toThrow(LogicException::class)
        ->and(fn () => AuditLog::query()->create(['event' => 'forged']))->toThrow(MassAssignmentException::class);
});

test('users without audit.view cannot open entries', function () {
    $log = Audit::log('settings.updated', 'Settings updated');

    $this->actingAs(auditor(['dashboard.view'], 'Plain'))->get("/dashboard/audit-logs/{$log->id}")->assertNotFound();
    $this->actingAs(auditor())->get("/dashboard/audit-logs/{$log->id}")->assertOk();
});

test('exporting needs audit.export and is itself logged', function () {
    Audit::log('settings.updated', 'Settings updated');

    $this->actingAs(auditor());
    Livewire::test(ListAuditLogs::class)->assertActionHidden('exportAuditLog');

    $this->actingAs(auditor(['dashboard.view', 'audit.view', 'audit.export'], 'Exporter'));
    Livewire::test(ListAuditLogs::class)
        ->filterTable('severity', ['info'])
        ->callAction('exportAuditLog');

    $log = AuditLog::query()->where('event', 'audit.exported')->sole();

    expect($log->severity)->toBe('warning')
        ->and($log->properties['filters'])->toHaveKey('severity');
});

test('administrators do not get the sensitive audit permissions by default', function () {
    $administrator = Role::findByName(Permissions::ADMINISTRATOR);

    expect($administrator->hasPermissionTo('audit.view'))->toBeTrue()
        ->and($administrator->hasPermissionTo('audit.export'))->toBeFalse()
        ->and($administrator->hasPermissionTo('audit.manage-retention'))->toBeFalse()
        ->and($administrator->hasPermissionTo('security.view'))->toBeFalse();
});

test('logged content is escaped when displayed', function () {
    request()->headers->set('User-Agent', '<script>alert("ua")</script>');
    $log = Audit::log('settings.updated', '<img src=x onerror=alert(1)>', properties: ['note' => '<b>bold</b>']);

    $this->actingAs(auditSuperAdmin())
        ->get("/dashboard/audit-logs/{$log->id}")
        ->assertOk()
        ->assertDontSee('<script>alert("ua")</script>', false)
        ->assertDontSee('<img src=x onerror=alert(1)>', false)
        ->assertDontSee('<b>bold</b>', false)
        ->assertSee('&lt;b&gt;bold&lt;/b&gt;', false);
});

// Request metadata

test('the connecting IP is used unless the proxy is explicitly trusted', function () {
    $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.2'])
        ->withHeader('X-Forwarded-For', '203.0.113.99')
        ->get('/does-not-matter');
    Audit::log('settings.updated', 'Untrusted proxy');

    TrustProxies::at('10.0.0.2');
    $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.2'])
        ->withHeader('X-Forwarded-For', '2001:db8::1')
        ->get('/does-not-matter');
    Audit::log('settings.updated', 'Trusted proxy');

    expect(AuditLog::query()->where('description', 'Untrusted proxy')->value('ip_address'))->toBe('10.0.0.2')
        ->and(AuditLog::query()->where('description', 'Trusted proxy')->value('ip_address'))->toBe('2001:db8::1');
});

test('entries of one request share its request id, which the response returns', function () {
    Route::get('/_audit-probe', function () {
        Audit::log('settings.updated', 'First');
        Audit::log('settings.updated', 'Second');

        return 'ok';
    });

    $response = $this->get('/_audit-probe');
    $ids = AuditLog::query()->pluck('request_id')->unique();

    expect($ids)->toHaveCount(1)
        ->and($ids->first())->toBe($response->headers->get('X-Request-Id'));
});

test('tokens in route parameters and query strings are not logged', function () {
    Route::get('/_audit-probe/{token}', function () {
        Audit::log('settings.updated', 'Probe');

        return 'ok';
    })->name('audit.probe');

    $this->get('/_audit-probe/secret-link-value?email=jane@example.com&signature=abc');

    $log = AuditLog::query()->sole();

    expect($log->path)->toBe('/_audit-probe/{token}')
        ->and($log->route_name)->toBe('audit.probe')
        ->and($log->toJson())->not->toContain('secret-link-value')->not->toContain('jane@example.com');
});

// Dashboard

test('the audit log paginates, filters and does not query per row', function () {
    $this->actingAs(auditSuperAdmin());

    foreach (range(1, 30) as $i) {
        Audit::log('settings.updated', "Info {$i}");
    }

    $high = Audit::log('security.privilege.denied', 'Severe', severity: 'high');
    $oldest = AuditLog::query()->orderBy('id')->first();

    Livewire::test(ListAuditLogs::class)
        ->assertCanSeeTableRecords([$high])
        ->assertCanNotSeeTableRecords([$oldest])
        ->filterTable('severity', ['high'])
        ->assertCanSeeTableRecords([$high])
        ->assertCountTableRecords(1);

    DB::enableQueryLog();
    Livewire::test(ListAuditLogs::class)->set('tableRecordsPerPage', 10);
    $few = count(DB::getQueryLog());
    DB::flushQueryLog();
    Livewire::test(ListAuditLogs::class)->set('tableRecordsPerPage', 25);
    $many = count(DB::getQueryLog());

    expect($many)->toBe($few);
});

test('a user page separates what the user did from changes to the account', function () {
    $admin = auditSuperAdmin();
    $user = User::factory()->create();

    $this->actingAs($user);
    $own = Audit::log('settings.updated', 'Done by the user');

    $this->actingAs($admin);
    $user->update(['name' => 'Renamed']);
    $change = AuditLog::query()->where('event', 'users.updated')->sole();

    Livewire::test(ActivityRelationManager::class, ['ownerRecord' => $user, 'pageClass' => EditUser::class])
        ->assertCanSeeTableRecords([$own])
        ->assertCanNotSeeTableRecords([$change]);

    Livewire::test(AccountHistoryRelationManager::class, ['ownerRecord' => $user, 'pageClass' => EditUser::class])
        ->assertCanSeeTableRecords([$change])
        ->assertCanNotSeeTableRecords([$own]);

    $this->get("/dashboard/users/{$user->id}/edit")->assertOk()->assertSee('Activity by this user');
});

test('alerts can only be acknowledged with security.manage-alerts, which is logged', function () {
    Audit::log('security.privilege.denied', 'Attempt', severity: 'critical');
    $alert = SecurityAlert::query()->sole();

    $this->actingAs(auditor(['dashboard.view', 'security.view'], 'Viewer'));
    Livewire::test(OpenSecurityAlerts::class)->assertTableActionHidden('acknowledge', $alert);

    $this->actingAs(auditor(['dashboard.view', 'security.view', 'security.manage-alerts'], 'Responder'));
    Livewire::test(OpenSecurityAlerts::class)->callTableAction('acknowledge', $alert);

    expect($alert->fresh()->acknowledged_at)->not->toBeNull()
        ->and(AuditLog::query()->where('event', 'security.alert.acknowledged')->exists())->toBeTrue();
});

test('the security overview counts events and lists source IPs', function () {
    $this->actingAs(auditSuperAdmin());
    request()->server->set('REMOTE_ADDR', '198.51.100.20');
    Audit::log('auth.login.failed', 'Failed', result: 'failure');
    Audit::log('auth.login.failed', 'Failed', result: 'failure');

    Livewire::test(SecurityStats::class)->assertSee('Failed sign-ins')->assertSee('2');
    Livewire::test(TopSourceIps::class)->assertSee('198.51.100.20');
    $this->get('/dashboard/security')->assertOk();
});

// Retention

test('retention removes expired entries per category and keeps protected ones', function () {
    $oldLogin = Audit::log('auth.login.failed', 'Old failure');
    $oldCritical = Audit::log('security.privilege.denied', 'Old critical', severity: 'critical');
    $oldRoleChange = Audit::log('roles.created', 'Old role');
    $recent = Audit::log('auth.login.failed', 'Recent failure');
    backdate($oldLogin, 100);
    backdate($oldCritical, 100);
    backdate($oldRoleChange, 100);

    expect(AuditRetention::prune(dryRun: true))->toBe(['auth' => 1])
        ->and(AuditLog::query()->count())->toBe(4);

    $this->artisan('audit:prune')->assertSuccessful();

    expect(AuditLog::query()->whereKey($oldLogin->id)->exists())->toBeFalse()
        ->and(AuditLog::query()->whereKey($oldCritical->id)->exists())->toBeTrue()
        ->and(AuditLog::query()->whereKey($oldRoleChange->id)->exists())->toBeTrue()
        ->and(AuditLog::query()->whereKey($recent->id)->exists())->toBeTrue()
        ->and(AuditLog::query()->where('event', 'audit.retention.pruned')->sole()->properties['removed'])->toBe(['auth' => 1]);
});

test('protected entries are removed after the longest retention period', function () {
    $critical = Audit::log('security.privilege.denied', 'Very old', severity: 'critical');
    backdate($critical, 400);

    AuditRetention::prune(dryRun: false);

    expect(AuditLog::query()->whereKey($critical->id)->exists())->toBeFalse();
});

test('CSRF failures are logged for signed-in users only', function () {
    // Laravel skips the CSRF check in tests, so raise what it would throw.
    Route::post('/_audit-csrf', fn () => throw new TokenMismatchException('CSRF token mismatch.'));

    $this->post('/_audit-csrf')->assertStatus(419);
    expect(AuditLog::query()->where('event', 'security.csrf.failed')->exists())->toBeFalse();

    $this->actingAs(auditor())->post('/_audit-csrf')->assertStatus(419);
    expect(AuditLog::query()->where('event', 'security.csrf.failed')->sole()->result)->toBe('denied');
});
