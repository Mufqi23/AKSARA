<?php

return [
    // Presentation only: existing routes, permissions and academic data remain available.
    'show_advanced_features' => false,

    // Pemasangan awal tanpa terminal. Kosongkan SETUP_TOKEN setelah selesai agar rute /setup tertutup.
    'setup_token' => env('SETUP_TOKEN', ''),
    'setup_admin' => [
        'name' => env('SETUP_ADMIN_NAME', 'Administrator'),
        'email' => env('SETUP_ADMIN_EMAIL', ''),
        'password' => env('SETUP_ADMIN_PASSWORD', ''),
    ],

    // AI Academic Early Warning System (Deteksi Dini Siswa Berisiko)
    'ai_ews' => [
        'enabled' => (bool) env('AI_EWS_ENABLED', true),
        'provider' => env('AI_EWS_PROVIDER', 'local'),
        'gemini_api_key' => env('GEMINI_API_KEY', ''),
        'gemini_model' => env('GEMINI_MODEL', 'gemini-2.0-flash'),
    ],
];
