<?php

return [
    /*
    |--------------------------------------------------------------------------
    | CORS Configuration
    |--------------------------------------------------------------------------
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie', 'login', 'logout'],

    // Mengizinkan permintaan dari alamat front-end Live Server Anda
    'allowed_origins' => [
        'http://127.0.0.1:5500', // Alamat front-end Live Server Anda
        'http://localhost:57032'  // Opsi fallback
    ],

    // Memungkinkan semua header dan method (cara paling sederhana)
    'allowed_methods' => ['*'],
    'allowed_headers' => ['*'],
    
    // Konfigurasi lainnya (tidak perlu diubah)
    'allowed_origins_patterns' => [],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => false,
];