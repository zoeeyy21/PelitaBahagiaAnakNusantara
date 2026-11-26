<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Koneksi CMS
    |--------------------------------------------------------------------------
    |
    | Nilai dibaca lewat config() (bukan env() langsung) agar tetap terbaca
    | setelah `php artisan config:cache` dijalankan di production.
    |
    */

    'api_url' => env('CMS_API_URL', 'http://127.0.0.1:8000/api'),

    'asset_url' => env('CMS_ASSET_URL', 'http://127.0.0.1:8000/storage'),

    'cache_ttl' => (int) env('CMS_CACHE_TTL', 3),

];
