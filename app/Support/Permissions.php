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

    /**
     * Grouped like the dashboard sidebar. The description says what the module
     * covers, so whoever edits a role knows what they hand out.
     *
     * @var array<string, array{label: string, section: string, description: string, actions: list<string>}>
     */
    public const MODULES = [
        'dashboard' => ['label' => 'Dashboard', 'section' => 'General', 'description' => 'Sign in to the dashboard. Each widget also needs the view permission of its module.', 'actions' => ['view']],
        'events' => ['label' => 'Events', 'section' => 'Edition', 'description' => 'Editions with their dates, programme and partners.', 'actions' => ['view', 'create', 'update', 'delete']],
        'event-stands' => ['label' => 'Stands & map', 'section' => 'Edition', 'description' => 'Stand assignment, map markers and map points per edition.', 'actions' => ['view', 'update']],
        'borrel-enrollments' => ['label' => 'Borrel enrollments', 'section' => 'Edition', 'description' => 'Names and email addresses of borrel guests.', 'actions' => ['view', 'create', 'update', 'delete']],
        'companies' => ['label' => 'Companies', 'section' => 'Company', 'description' => 'Company profiles. Verification links let anyone holding them edit a profile.', 'actions' => ['view', 'create', 'update', 'delete', 'links']],
        'company-access-requests' => ['label' => 'Access requests', 'section' => 'Company', 'description' => 'Requests from companies for a profile link.', 'actions' => ['view', 'review']],
        'company-profile-submissions' => ['label' => 'Profile submissions', 'section' => 'Company', 'description' => 'Profile changes proposed by companies.', 'actions' => ['view', 'review']],
        'company-interest-requests' => ['label' => 'Edition interest', 'section' => 'Company', 'description' => 'Contact details of companies interested in an edition.', 'actions' => ['view', 'delete', 'export']],
        'partners' => ['label' => 'Partners', 'section' => 'Catalog', 'description' => 'Organising partners shown on the website.', 'actions' => ['view', 'create', 'update', 'delete']],
        'education' => ['label' => 'Educations', 'section' => 'Catalog', 'description' => 'Study programmes companies can pick.', 'actions' => ['view', 'create', 'update', 'delete']],
        'sectors' => ['label' => 'Sectors', 'section' => 'Catalog', 'description' => 'Sectors companies can pick.', 'actions' => ['view', 'create', 'update', 'delete']],
        'newsletter-subscribers' => ['label' => 'Newsletter subscribers', 'section' => 'Catalog', 'description' => 'Email addresses of newsletter subscribers.', 'actions' => ['view', 'export']],
        'settings' => ['label' => 'Site settings', 'section' => 'Settings', 'description' => 'Page media, and the privacy, cookie and terms pages.', 'actions' => ['media', 'legal']],
        'users' => ['label' => 'Users', 'section' => 'Administration', 'description' => 'Dashboard accounts and their roles.', 'actions' => ['view', 'create', 'update', 'delete']],
        'roles' => ['label' => 'Roles', 'section' => 'Administration', 'description' => 'Roles and the permissions they grant.', 'actions' => ['view', 'create', 'update', 'delete']],
        'permissions' => ['label' => 'Permissions', 'section' => 'Administration', 'description' => 'The overview of which role grants what.', 'actions' => ['view']],
        'audit' => ['label' => 'Audit log', 'section' => 'Administration', 'description' => 'Who did what, from which IP address. Contains personal data.', 'actions' => ['view', 'export', 'manage-retention']],
        'security' => ['label' => 'Security overview', 'section' => 'Administration', 'description' => 'Failed logins, denied access and security alerts.', 'actions' => ['view', 'manage-alerts']],
    ];

    /** Standard columns in the permission matrix; other actions are listed under "Other". */
    public const STANDARD_ACTIONS = ['view', 'create', 'update', 'delete'];

    /**
     * Permissions split out of an older, broader one. When sync first creates
     * one of these, every role holding an origin gets it, so nobody loses access.
     *
     * @var array<string, list<string>>
     */
    private const ORIGINS = [
        'event-stands.view' => ['event-stands.manage', 'event-map-points.manage'],
        'event-stands.update' => ['event-stands.manage', 'event-map-points.manage'],
        'companies.links' => ['companies.update'],
        'company-interest-requests.export' => ['company-interest-requests.view'],
        'newsletter-subscribers.export' => ['newsletter-subscribers.view'],
        'settings.media' => ['settings.manage'],
        'settings.legal' => ['settings.manage'],
        'audit.view' => ['audit-logs.view'],
    ];

    /** Replaced by the permissions above; removed by sync once their roles are carried over. */
    private const RETIRED = ['event-stands.manage', 'event-map-points.manage', 'settings.manage', 'audit-logs.view'];

    private const ACTION_LABELS = [
        'view' => 'View',
        'create' => 'Create',
        'update' => 'Update',
        'delete' => 'Delete',
        'review' => 'Approve and reject',
        'export' => 'Export',
        'links' => 'Verification links',
        'media' => 'Page media',
        'legal' => 'Legal pages',
        'manage-retention' => 'Retention cleanup',
        'manage-alerts' => 'Acknowledge alerts',
    ];

    /**
     * Administrators get everything except changing roles and the sensitive
     * parts of the audit log, which stay with Super Admins until granted.
     */
    private const ADMINISTRATOR_EXCLUDED = [
        'roles.create', 'roles.update', 'roles.delete',
        'audit.export', 'audit.manage-retention', 'security.view', 'security.manage-alerts',
    ];

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

    /** @return array<string, array<string, array{label: string, section: string, description: string, actions: list<string>}>> */
    public static function sections(): array
    {
        return collect(self::MODULES)->groupBy('section', preserveKeys: true)->map->all()->all();
    }

    public static function label(string $name): string
    {
        $action = str($name)->after('.')->toString();

        return self::ACTION_LABELS[$action] ?? str($action)->headline()->toString();
    }

    /**
     * Create missing permissions and system roles. Safe to run repeatedly.
     * Default permissions are only given to a role when that role is first
     * created, so dashboard changes are kept. Only the RETIRED permissions are
     * ever removed, after their roles received the permissions that replace them.
     */
    public static function sync(): void
    {
        $guard = config('auth.defaults.guard');

        foreach (self::all() as $name) {
            $permission = Permission::findOrCreate($name, $guard);

            if ($permission->wasRecentlyCreated && isset(self::ORIGINS[$name])) {
                Role::query()
                    ->whereHas('permissions', fn ($query) => $query->whereIn('name', self::ORIGINS[$name]))
                    ->each(fn (Role $role) => $role->givePermissionTo($permission));
            }
        }

        Permission::query()->whereIn('name', self::RETIRED)->delete();

        foreach (self::SYSTEM_ROLES as $name) {
            $role = Role::findOrCreate($name, $guard);

            if ($name === self::ADMINISTRATOR && $role->wasRecentlyCreated) {
                $role->givePermissionTo(array_values(array_diff(self::all(), self::ADMINISTRATOR_EXCLUDED)));
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
