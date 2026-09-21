<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MobileDistributionDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_super_administrator_can_open_the_mobile_distribution_dashboard(): void
    {
        $administrator = User::factory()->create();
        $administrator->assignRole('super-admin');

        $this->actingAs($administrator)
            ->get(route('mobile.distribution'))
            ->assertOk()
            ->assertSee('توزيع تطبيق المحفّظ')
            ->assertSee('يجري رفع APK')
            ->assertSee(route('teacher.mobile.app'), false)
            ->assertSee('لا يوجد إصدار منشور بعد');
    }

    public function test_other_users_cannot_open_the_mobile_distribution_dashboard(): void
    {
        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');

        $this->actingAs($teacher)
            ->get(route('mobile.distribution'))
            ->assertForbidden();
    }
}
