<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProductionBootstrapTest extends TestCase
{
    use RefreshDatabase;

    public function test_production_bootstrap_creates_only_reference_data_and_the_configured_administrator(): void
    {
        // RefreshDatabase has already initialized SQLite :memory: before setting this environment.
        $this->app->instance('env', 'production');
        config(['system.initial_admin' => [
            'name' => 'مدير المركز',
            'email' => 'launch-admin@example.test',
            'password' => 'ProductionFixture123!',
        ]]);

        $this->artisan('db:seed', ['--class' => DatabaseSeeder::class, '--force' => true])->assertSuccessful();
        $admin = User::query()->sole();
        $this->assertTrue($admin->hasRole('super-admin'));
        $this->assertTrue(Hash::check('ProductionFixture123!', $admin->password));
        $this->assertDatabaseCount('quran_surahs', 114);
        $this->assertDatabaseCount('quran_ayahs', 6236);
        foreach (['centers', 'halaqas', 'teacher_profiles', 'students', 'daily_records', 'cms_contents'] as $table) {
            $this->assertDatabaseCount($table, 0);
        }

        config(['system.initial_admin.password' => 'DoNotReplaceExisting123!']);
        $this->artisan('db:seed', ['--class' => DatabaseSeeder::class, '--force' => true])->assertSuccessful();
        $this->assertDatabaseCount('users', 1);
        $this->assertTrue(Hash::check('ProductionFixture123!', $admin->fresh()->password));
        $this->assertDatabaseCount('quran_surahs', 114);
        $this->assertDatabaseCount('quran_ayahs', 6236);
    }
}
