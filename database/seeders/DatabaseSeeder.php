<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        if ($this->container->environment(['local', 'testing'])) {
            $this->call(GofranManualTestingSeeder::class);

            return;
        }

        $this->call(RolesAndPermissionsSeeder::class);
        $this->call(QuranReferenceSeeder::class);
        $this->call(AcademicSettingsSeeder::class);

        $admin = config('system.initial_admin');

        if ($admin['email'] && $admin['password']) {
            $user = User::query()->firstOrCreate(
                ['email' => $admin['email']],
                [
                    'name' => $admin['name'] ?: 'مدير النظام',
                    'password' => Hash::make($admin['password']),
                    'active' => true,
                    'email_verified_at' => now(),
                ],
            );
            $user->syncRoles(['super-admin']);
        }
    }
}
