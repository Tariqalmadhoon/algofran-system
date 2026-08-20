<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'organization.view', 'organization.manage',
            'students.view', 'students.create', 'students.update', 'students.archive', 'students.export',
            'halaqas.view', 'halaqas.manage',
            'recitations.view', 'recitations.create', 'recitations.update',
            'attendance.manage', 'courses.manage', 'certificates.manage', 'achievements.manage',
            'reports.view', 'reports.export', 'alerts.view', 'alerts.manage',
            'reports.upload', 'calendar.view', 'calendar.manage', 'notifications.view',
            'website.manage', 'users.manage', 'roles.manage', 'settings.manage', 'audit.view',
            'guardian.private-data.view', 'private-files.view',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $roles = [
            'super-admin' => $permissions,
            'center-manager' => ['organization.view', 'organization.manage', 'students.view', 'students.create', 'students.update', 'students.archive', 'students.export', 'halaqas.view', 'halaqas.manage', 'recitations.view', 'attendance.manage', 'courses.manage', 'certificates.manage', 'achievements.manage', 'reports.view', 'reports.export', 'reports.upload', 'calendar.view', 'calendar.manage', 'notifications.view', 'alerts.view', 'alerts.manage', 'users.manage', 'settings.manage', 'audit.view', 'guardian.private-data.view', 'private-files.view'],
            'academic-supervisor' => ['organization.view', 'students.view', 'halaqas.view', 'halaqas.manage', 'recitations.view', 'courses.manage', 'certificates.manage', 'achievements.manage', 'reports.view', 'reports.export', 'reports.upload', 'calendar.view', 'calendar.manage', 'notifications.view', 'alerts.view', 'alerts.manage'],
            'registrar' => ['organization.view', 'students.view', 'students.create', 'students.update', 'students.archive', 'students.export', 'halaqas.view', 'reports.view', 'reports.upload', 'calendar.view', 'notifications.view', 'guardian.private-data.view', 'private-files.view'],
            'teacher' => ['organization.view', 'students.view', 'halaqas.view', 'recitations.view', 'recitations.create', 'recitations.update', 'attendance.manage', 'reports.view', 'calendar.view', 'notifications.view', 'alerts.view'],
            'guardian' => ['students.view', 'recitations.view', 'calendar.view', 'notifications.view'],
            'student' => ['students.view', 'recitations.view', 'calendar.view', 'notifications.view'],
            'website-editor' => ['website.manage'],
            'report-viewer' => ['reports.view', 'reports.export', 'calendar.view', 'notifications.view'],
        ];

        foreach ($roles as $name => $rolePermissions) {
            Role::findOrCreate($name, 'web')->syncPermissions($rolePermissions);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
