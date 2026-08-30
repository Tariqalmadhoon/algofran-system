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
];
