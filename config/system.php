<?php

return [
    'initial_admin' => [
        'name' => env('INITIAL_ADMIN_NAME'),
        'email' => env('INITIAL_ADMIN_EMAIL'),
        'password' => env('INITIAL_ADMIN_PASSWORD'),
    ],

    'academic' => [
        'score_weights' => [
            'recitation_quality' => 35,
            'revision_adherence' => 20,
            'attendance' => 20,
            'target_achievement' => 15,
            'improvement_trend' => 10,
        ],
        'monthly_memorization_sessions_target' => 12,
        'monthly_revision_sessions_target' => 8,
        'absence_alert_count' => 3,
        'monthly_excused_absence_allowance' => 3,
        'revision_delay_days' => 14,
        'missing_record_days' => 7,
    ],

    'monitoring' => [
        'query_budget_ms' => (int) env('DB_QUERY_BUDGET_MS', 750),
    ],

    'identity' => [
        // Temporarily disabled. Set TWO_FACTOR_AUTH_ENABLED=true to restore all 2FA flows.
        'two_factor_enabled' => (bool) env('TWO_FACTOR_AUTH_ENABLED', false),
        'two_factor_required_roles' => [
            'super-admin',
            'center-manager',
            'academic-supervisor',
            'registrar',
            'website-editor',
            'report-viewer',
        ],
        'challenge_ttl_seconds' => 300,
        'challenge_max_attempts' => 5,
    ],

    'api' => [
        'version' => '1',
        'status' => 'stable',
        'minimum_supported_client' => '1.0.0',
    ],

    'mobile_app' => [
        'release_enabled' => (bool) env('MOBILE_APP_RELEASE_ENABLED', false),
        'version' => env('MOBILE_APP_VERSION', '1.3.1'),
        'version_code' => (int) env('MOBILE_APP_VERSION_CODE', 5),
        'minimum_version_code' => (int) env('MOBILE_APP_MINIMUM_VERSION_CODE', 1),
        'android_apk_path' => env('MOBILE_ANDROID_APK_PATH', 'releases/gofran-mobile-1.3.1-5.apk'),
        // Normally derived from APP_URL. A local dashboard can point to the
        // already-approved HTTPS production API to make the same signed APK
        // available for installation without weakening release validation.
        'api_base_url' => env('MOBILE_API_BASE_URL'),
        'release_notes' => env('MOBILE_APP_RELEASE_NOTES'),
        'published_at' => env('MOBILE_APP_PUBLISHED_AT'),
    ],

    'public_contact' => [
        // Keep public communication in one auditable channel. The defaults are safe
        // for new installations; production can override them without code changes.
        'whatsapp_number' => env('PUBLIC_WHATSAPP_NUMBER', '972567973076'),
        'whatsapp_display_number' => env('PUBLIC_WHATSAPP_DISPLAY_NUMBER', '+972 56 797 3076'),
        'whatsapp_message' => env('PUBLIC_WHATSAPP_MESSAGE', 'السلام عليكم، أود الاستفسار عن مركز الغفران لتحفيظ القرآن الكريم.'),
        'latitude' => env('PUBLIC_CENTER_LATITUDE', '31.382208090207207'),
        'longitude' => env('PUBLIC_CENTER_LONGITUDE', '34.331760937834474'),
    ],

    'contact_phone' => [
        // Local mobile numbers such as 059xxxxxxx are expanded only when a
        // WhatsApp link is generated; the original number remains unchanged.
        'default_country_calling_code' => env('CONTACT_PHONE_DEFAULT_COUNTRY_CALLING_CODE', '972'),
    ],
];
