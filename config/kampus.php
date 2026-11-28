<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Konfigurasi Base URL Bridge API Kampus
    |--------------------------------------------------------------------------
    |
    | Base URL dari layanan Bridge API kampus (contoh: https://bridge.uinssc.ac.id).
    |
    */
    'base_url' => env('KAMPUS_API_BASE_URL', 'https://bridge.uinssc.ac.id'),

    /*
    |--------------------------------------------------------------------------
    | Static System Token (Service-to-Service)
    |--------------------------------------------------------------------------
    |
    | Token statis yang digunakan untuk mengambil data master akademik publik
    | seperti Fakultas dan Program Studi (/api/fakultas, /api/program_studi).
    |
    */
    'static_token' => env('KAMPUS_API_STATIC_TOKEN', 'ptipd-access-token'),

    /*
    |--------------------------------------------------------------------------
    | Request Timeout & Retry
    |--------------------------------------------------------------------------
    |
    | Durasi maksimum (dalam detik) sebelum request HTTP timeout,
    | serta jumlah percobaan ulang jika terjadi kegagalan jaringan sementara.
    |
    */
    'timeout' => (int) env('KAMPUS_API_TIMEOUT', 15),
    'retry_times' => (int) env('KAMPUS_API_RETRY_TIMES', 2),
    'retry_sleep' => 100, // milidetik

    /*
    |--------------------------------------------------------------------------
    | Endpoint API Kampus
    |--------------------------------------------------------------------------
    */
    'endpoints' => [
        'login' => '/api/v2/portal/login',
        'logout' => '/api/v2/logout',
        'profil' => '/api/v2/profil',
        'fakultas' => '/api/fakultas',
        'program_studi' => '/api/program_studi',
    ],
];
