<?php

namespace Database\Seeders;

use App\Models\User;
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
            'recitations.view', 'recitations.create', 'recitations.update', 'recitations.export',
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
            'center-manager' => ['organization.view', 'organization.manage', 'students.view', 'students.create', 'students.update', 'students.archive', 'students.export', 'halaqas.view', 'halaqas.manage', 'recitations.view', 'recitations.create', 'recitations.update', 'recitations.export', 'attendance.manage', 'courses.manage', 'certificates.manage', 'achievements.manage', 'reports.view', 'reports.export', 'reports.upload', 'calendar.view', 'calendar.manage', 'notifications.view', 'alerts.view', 'alerts.manage', 'settings.manage', 'audit.view', 'guardian.private-data.view', 'private-files.view'],
            'academic-supervisor' => ['organization.view', 'students.view', 'halaqas.view', 'halaqas.manage', 'recitations.view', 'courses.manage', 'certificates.manage', 'achievements.manage', 'reports.view', 'reports.export', 'reports.upload', 'calendar.view', 'calendar.manage', 'notifications.view', 'alerts.view', 'alerts.manage'],
            'registrar' => ['organization.view', 'students.view', 'students.create', 'students.update', 'students.archive', 'students.export', 'halaqas.view', 'reports.view', 'reports.upload', 'calendar.view', 'notifications.view', 'guardian.private-data.view', 'private-files.view'],
            'teacher' => ['students.view', 'students.create', 'students.update', 'halaqas.view', 'recitations.view', 'recitations.create', 'recitations.update', 'recitations.export', 'attendance.manage', 'reports.view', 'calendar.view', 'notifications.view', 'alerts.view'],
            'guardian' => ['students.view', 'recitations.view', 'calendar.view', 'notifications.view'],
            'student' => ['students.view', 'recitations.view', 'calendar.view', 'notifications.view'],
            'website-editor' => ['website.manage'],
            'report-viewer' => ['reports.view', 'reports.export', 'calendar.view', 'notifications.view'],
        ];

        foreach ($roles as $name => $rolePermissions) {
            Role::findOrCreate($name, 'web')->syncPermissions($rolePermissions);
        }

        User::query()
            ->where('active', true)
            ->whereNull('archived_at')
            ->whereHas('teacherProfile', fn ($query) => $query->where('active', true))
            ->whereDoesntHave('roles', fn ($query) => $query->where('name', 'teacher'))
            ->eachById(fn (User $user) => $user->assignRole('teacher'));

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
