<?php

namespace App\Support;

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * The single list of permissions the application checks, named
 * "module.action". Adding an entry here only creates the permission;
 * the code that checks it (a policy or a ->can() call) must exist too.
 */
final class Permissions
{
    public const SUPER_ADMIN = 'Super Admin';

    public const ADMINISTRATOR = 'Administrator';

    public const USER = 'User';

    /** Roles that are seeded by the application and can't be renamed or deleted. */
    public const SYSTEM_ROLES = [self::SUPER_ADMIN, self::ADMINISTRATOR, self::USER];

    /** @var array<string, array{label: string, actions: list<string>}> */
    public const MODULES = [
        'dashboard' => ['label' => 'Dashboard', 'actions' => ['view']],
        'events' => ['label' => 'Events', 'actions' => ['view', 'create', 'update', 'delete']],
        'event-stands' => ['label' => 'Event stands', 'actions' => ['manage']],
        'event-map-points' => ['label' => 'Map points', 'actions' => ['manage']],
        'borrel-enrollments' => ['label' => 'Borrel enrollments', 'actions' => ['view', 'create', 'update', 'delete']],
        'companies' => ['label' => 'Companies', 'actions' => ['view', 'create', 'update', 'delete']],
        'company-access-requests' => ['label' => 'Company access requests', 'actions' => ['view', 'review']],
        'company-profile-submissions' => ['label' => 'Company profile submissions', 'actions' => ['view', 'review']],
        'company-interest-requests' => ['label' => 'Company interest requests', 'actions' => ['view', 'delete']],
        'partners' => ['label' => 'Partners', 'actions' => ['view', 'create', 'update', 'delete']],
        'education' => ['label' => 'Educations', 'actions' => ['view', 'create', 'update', 'delete']],
        'sectors' => ['label' => 'Sectors', 'actions' => ['view', 'create', 'update', 'delete']],
        'newsletter-subscribers' => ['label' => 'Newsletter subscribers', 'actions' => ['view']],
        'settings' => ['label' => 'Site settings', 'actions' => ['manage']],
        'users' => ['label' => 'Users', 'actions' => ['view', 'create', 'update', 'delete']],
        'roles' => ['label' => 'Roles', 'actions' => ['view', 'create', 'update', 'delete']],
        'permissions' => ['label' => 'Permissions', 'actions' => ['view']],
        'audit-logs' => ['label' => 'Audit log', 'actions' => ['view']],
    ];

    private const ACTION_LABELS = [
        'view' => 'View',
        'create' => 'Create',
        'update' => 'Update',
        'delete' => 'Delete',
        'manage' => 'Manage',
        'review' => 'Approve and reject',
    ];

    /** Administrators get everything except changing roles, which stays with Super Admins until granted. */
    private const ADMINISTRATOR_EXCLUDED = ['roles.create', 'roles.update', 'roles.delete'];

    /** @return list<string> */
    public static function all(): array
    {
        $names = [];

        foreach (self::MODULES as $module => $definition) {
            foreach ($definition['actions'] as $action) {
                $names[] = "{$module}.{$action}";
            }
        }

        return $names;
    }

    public static function defined(string $name): bool
    {
        return in_array($name, self::all(), true);
    }

    public static function module(string $name): string
    {
        return str($name)->before('.')->toString();
    }

    public static function moduleLabel(string $module): string
    {
        return self::MODULES[$module]['label'] ?? str($module)->headline()->toString();
    }

    public static function label(string $name): string
    {
        $action = str($name)->after('.')->toString();

        return self::ACTION_LABELS[$action] ?? str($action)->headline()->toString();
    }

    /**
     * Create missing permissions and system roles. Safe to run repeatedly: it
     * never removes permissions, and default permissions are only given to a
     * role when that role is first created, so dashboard changes are kept.
     */
    public static function sync(): void
    {
        $guard = config('auth.defaults.guard');

        foreach (self::all() as $name) {
            Permission::findOrCreate($name, $guard);
        }

        foreach (self::SYSTEM_ROLES as $name) {
            $role = Role::findOrCreate($name, $guard);

            if ($name === self::ADMINISTRATOR && $role->wasRecentlyCreated) {
                $role->givePermissionTo(array_values(array_diff(self::all(), self::ADMINISTRATOR_EXCLUDED)));
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
