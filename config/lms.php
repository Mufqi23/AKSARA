<?php

return [
    // Presentation only: existing routes, permissions and academic data remain available.
    'show_advanced_features' => false,

    // AI Academic Early Warning System (Deteksi Dini Siswa Berisiko)
    'ai_ews' => [
        'enabled' => (bool) env('AI_EWS_ENABLED', true),
        'provider' => env('AI_EWS_PROVIDER', 'local'),
        'gemini_api_key' => env('GEMINI_API_KEY', ''),
        'gemini_model' => env('GEMINI_MODEL', 'gemini-2.0-flash'),
    ],
];
