<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class RoleUsersSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            QuranReferenceSeeder::class,
            AcademicSettingsSeeder::class,
            PrimaryAccountsSeeder::class,
        ]);
    }
}
