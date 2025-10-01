<?php

namespace Triyatna\DuitkuLaravel;

use Illuminate\Support\ServiceProvider;

class DuitkuServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        // Gabungkan konfigurasi package dengan konfigurasi aplikasi
        $this->mergeConfigFrom(
            __DIR__ . '/../config/duitku.php',
            'duitku'
        );

        // Daftarkan 'duitku' sebagai singleton di service container
        $this->app->singleton('duitku', function ($app) {
            $config = $app['config']['duitku'];
            return new Duitku(
                $config['merchant_key'],
                $config['merchant_code'],
                $config['sandbox_mode']
            );
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        if (! file_exists(config_path('duitku.php'))) {
        $this->publishes([
            __DIR__ . '/../config/duitku.php' => config_path('duitku.php'),
        ], 'config');
        }
    }
}

