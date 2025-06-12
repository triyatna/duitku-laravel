<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Duitku Merchant Key
    |--------------------------------------------------------------------------
    |
    | Kunci unik merchant Anda yang dapat ditemukan pada panel Duitku.
    |
    */
    'merchant_key' => env('DUITKU_MERCHANT_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | Duitku Merchant Code
    |--------------------------------------------------------------------------
    |
    | Kode unik merchant Anda yang dapat ditemukan pada panel Duitku.
    |
    */
    'merchant_code' => env('DUITKU_MERCHANT_CODE', ''),

    /*
    |--------------------------------------------------------------------------
    | Duitku API Endpoint
    |--------------------------------------------------------------------------
    |
    | Atur ke true untuk menggunakan mode sandbox/pengembangan.
    | Atur ke false untuk menggunakan mode production/live.
    |
    */
    'sandbox_mode' => env('DUITKU_SANDBOX_MODE', true),

    /*
    |--------------------------------------------------------------------------
    | Duitku Callback URL
    |--------------------------------------------------------------------------
    |
    | URL yang akan dipanggil oleh Duitku untuk notifikasi status transaksi.
    |
    */
    'callback_url' => env('DUITKU_CALLBACK_URL', 'http://example.com/callback'),

    /*
    |--------------------------------------------------------------------------
    | Duitku Return URL
    |--------------------------------------------------------------------------
    |
    | URL tujuan setelah pelanggan menyelesaikan (atau membatalkan) pembayaran.
    |
    */
    'return_url' => env('DUITKU_RETURN_URL', 'http://example.com/return'),
];
