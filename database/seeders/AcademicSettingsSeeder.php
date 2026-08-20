<?php

namespace Database\Seeders;

use App\Models\Setting;
use App\Services\SettingsService;
use Illuminate\Database\Seeder;

class AcademicSettingsSeeder extends Seeder
{
    public function run(SettingsService $settings): void
    {
        $defaults = [
            ['score_weights', config('system.academic.score_weights'), 'json'],
            ['monthly_memorization_sessions_target', config('system.academic.monthly_memorization_sessions_target'), 'integer'],
            ['monthly_revision_sessions_target', config('system.academic.monthly_revision_sessions_target'), 'integer'],
            ['absence_alert_count', config('system.academic.absence_alert_count'), 'integer'],
            ['revision_delay_days', config('system.academic.revision_delay_days'), 'integer'],
            ['missing_record_days', config('system.academic.missing_record_days'), 'integer'],
        ];

        foreach ($defaults as [$key, $value, $type]) {
            if (! Setting::query()->where('group', 'academic')->where('key', $key)->exists()) {
                $settings->set($key, $value, $type, 'academic');
            }
        }
    }
}
