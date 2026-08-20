<?php

namespace App\Services;

use App\Models\Setting;

class SettingsService
{
    public function get(string $key, mixed $default = null, string $group = 'general'): mixed
    {
        $setting = Setting::query()->where('group', $group)->where('key', $key)->first();

        if (! $setting) {
            return $default;
        }

        return match ($setting->type) {
            'boolean' => filter_var($setting->value, FILTER_VALIDATE_BOOL),
            'integer' => (int) $setting->value,
            'json' => json_decode($setting->value ?? 'null', true),
            default => $setting->value,
        };
    }

    public function set(string $key, mixed $value, string $type = 'string', string $group = 'general', bool $isPublic = false): Setting
    {
        $storedValue = $type === 'json' ? json_encode($value, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) : (string) $value;

        return Setting::query()->updateOrCreate(
            ['group' => $group, 'key' => $key],
            ['value' => $storedValue, 'type' => $type, 'is_public' => $isPublic],
        );
    }
}
