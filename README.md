# Duitku Payment Gateway for Laravel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/triyatna/duitku-laravel.svg?style=flat-square)](https://packagist.org/packages/triyatna/duitku-laravel)
[![Total Downloads](https://img.shields.io/packagist/dt/triyatna/duitku-laravel.svg?style=flat-square)](https://packagist.org/packages/triyatna/duitku-laravel)

Package ini menyediakan implementasi untuk mengintegrasikan layanan Duitku Payment Gateway ke dalam aplikasi yang dibangun dengan framework Laravel. Tujuannya adalah untuk menyederhanakan proses interaksi dengan API Duitku melalui penyediaan komponen-komponen yang lazim digunakan dalam ekosistem Laravel, seperti Service Provider dan Facade.

Dengan menggunakan package ini, pengembang dapat melakukan tugas-tugas esensial seperti pembuatan permintaan pembayaran, pengecekan status transaksi, dan penanganan notifikasi callback secara terstruktur dan sesuai dengan konvensi Laravel. Seluruh konfigurasi, termasuk kredensial merchant dan pengaturan lingkungan (sandbox atau produksi), dapat dikelola secara terpusat melalui file konfigurasi dan variabel lingkungan (.env), sehingga mempermudah proses pengembangan dan deployment.

## Fitur

-   Antarmuka facade yang sederhana (`Duitku::...`).
-   Konfigurasi yang terpusat dalam *file* `config/duitku.php`.
-   Pengaturan kredensial yang aman melalui *file* `.env`.
-   *Auto-discovery* untuk Laravel, tidak perlu registrasi manual.
-   Dukungan untuk mode *Sandbox* dan *Produksi*.
-   Metode bantuan untuk validasi *callback* dari Duitku.

## Instalasi
Anda dapat menginstal package ini melalui Composer.

```bash
composer require triyatna/duitku-laravel
```

## Konfigurasi
### 1. Publikasikan File Konfigurasi
Jalankan perintah `vendor:publish` untuk menyalin file konfigurasi package ke direktori `config` aplikasi Anda.

```bash
php artisan vendor:publish --provider="Triyatna\DuitkuLaravel\DuitkuServiceProvider" --tag="config"
```
Perintah ini akan membuat file `config/duitku.php`.

### 2. Atur Variabel Lingkungan (.env)
Selanjutnya, buka file `.env` Anda dan tambahkan kredensial Duitku. Anda bisa mendapatkan kredensial ini dari dasbor Duitku Anda.

```.env
DUITKU_MERCHANT_KEY=xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
DUITKU_MERCHANT_CODE=DXXXX
DUITKU_SANDBOX_MODE=true
DUITKU_CALLBACK_URL=https://situsanda.com/callback
DUITKU_RETURN_URL=https://situsanda.com/payment/finish
```
Keterangan:
- `DUITKU_MERCHANT_KEY`: Merchant Key Anda dari Duitku.
- `DUITKU_MERCHANT_CODE`: Kode Merchant Anda.
- `DUITKU_SANDBOX_MODE`: Atur ke true untuk mode pengembangan/sandbox, atau false untuk mode produksi.
- `DUITKU_CALLBACK_URL`: URL di aplikasi Anda yang akan menerima notifikasi status transaksi dari Duitku.
- `DUITKU_RETURN_URL`: URL tujuan pengguna setelah menyelesaikan pembayaran.

## Penggunaan
Package ini menyediakan Facade Duitku untuk kemudahan akses. Berikut adalah contoh cara menggunakannya.
### 1. Membuat Invoice Pembayaran
Anda dapat membuat invoice baru dan mengarahkan pengguna ke halaman pembayaran Duitku.
Contoh di dalam `Controllers`:

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Triyatna\DuitkuLaravel\Facades\Duitku;

class PaymentController extends Controller
{
    public function createTransaction()
    {
        $merchantOrderId = 'INV-' . time();
        $paymentAmount   = 15000;
        $paymentMethod   = 'VC'; // Contoh: Virtual Account BCA (lihat daftar metode pembayaran)
        $productDetails  = 'Pembelian Produk A';
        $customerVaName  = 'Budi Santoso';
        $email           = 'budi@example.com';
        $phoneNumber     = '08123456789';

        // Ambil URL dari file konfigurasi
        $callbackUrl = config('duitku.callback_url');
        $returnUrl   = config('duitku.return_url');

        try {
            $response = Duitku::createInvoice(
                $paymentAmount,
                $paymentMethod,
                $merchantOrderId,
                $productDetails,
                $customerVaName,
                $email,
                $phoneNumber,
                $callbackUrl,
                $returnUrl
            );

            // Jika sukses, response akan berisi paymentUrl
            if (isset($response['paymentUrl'])) {
                return redirect()->away($response['paymentUrl']);
            }

            // Tangani jika gagal
            return back()->with('error', $response['message'] ?? 'Gagal membuat invoice Duitku.');

        } catch (\Exception $e) {
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }
}
```

### 2. Menangani Callback dari Duitku
Duitku akan mengirimkan notifikasi ke `DUITKU_CALLBACK_URL` yang telah Anda tentukan. Buat sebuah route dan controller untuk menangani permintaan ini.
Definisikan Route (misal: `routes/web.php`)

```php
use App\Http\Controllers\PaymentController;

Route::post('/callback', [PaymentController::class, 'handleCallback'])->name('duitku.callback');
```

Buat Metode di `Controllers`:
Gunakan metode `handleCallback()` untuk memvalidasi signature secara otomatis.

```php
use Triyatna\DuitkuLaravel\Duitku;
// ... di dalam PaymentController

public function handleCallback(Request $request)
{
    $callback = Duitku::handleCallback();

    if ($callback['status'] === 'success') {
        // Callback valid, signature cocok.
        // Lakukan logika bisnis Anda di sini.
        // $callback['data'] berisi semua data dari Duitku
        // Contoh:
        // $order = Order::where('order_id', $callback['data']['merchantOrderId'])->first();
        // $order->update(['status' => 'paid']);

        // Kirim respons OK ke Duitku
        return response()->json(['status' => 'OK']);
    }

    // Callback tidak valid
    return response()->json(['status' => 'ERROR', 'message' => $callback['message']], 400);
}
```
> Wajib: Pastikan untuk mengecualikan URL callback Anda dari verifikasi token CSRF. Tambahkan URL sesuai dengan yang dibuat tadi (`/callback`) ke dalam properti `except`.

> Contoh dalam laravel 12, ada pada file bootstrap/app.php, pilih bagian withMiddleware dan tambahkan `$middleware->validateCsrfTokens(except:['callback', // URI yang ingin dikecualikan 'duitku/*', // Anda juga bisa menggunakan wildcard (*)]);`

### 3. Memeriksa Status Transaksi
Anda dapat secara manual memeriksa status sebuah transaksi menggunakan `merchantOrderId`

```php
$merchantOrderId = 'INV-123456';
$status = Duitku::checkTransactionStatus($merchantOrderId);

if ($status['statusCode'] == '00') {
    echo "Transaksi sukses!";
} else {
    echo "Status Transaksi: " . $status['statusMessage'];
}
```

### 4. Mendapatkan Daftar Metode Pembayaran
Untuk mendapatkan daftar metode pembayaran yang aktif untuk sejumlah transaksi tertentu:

```php
$amount = 50000;
$paymentMethods = Duitku::getPaymentMethods($amount);

// Loop melalui metode pembayaran
foreach ($paymentMethods['paymentFee'] as $method) {
    echo "Metode: " . $method['paymentMethod'] . ", Biaya: " . $method['totalFee'] . "<br>";
}
```

## Daftar Metode yang Tersedia
Berikut adalah daftar metode yang bisa Anda panggil melalui Facade `Duitku`
- `Duitku::getPaymentMethods(int $amount)`
- `Duitku::createInvoice(...)`
- `Duitku::checkTransactionStatus(string $merchantOrderId)`
- `Duitku::handleCallback()`

## Lisensi
Paket ini dirilis di bawah [Lisensi MIT](https://opensource.org/license/mit).
