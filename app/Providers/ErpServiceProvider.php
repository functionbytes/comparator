<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\ErpService;

class ErpServiceProvider extends ServiceProvider
{

    public function register()
    {
        $this->app->singleton(ErpService::class, function ($app) {
            return new ErpService();
        });
    }


    public function boot()
    {
        $this->publishes([
            __DIR__.'/../../config/erp.php' => config_path('erp.php'),
        ], 'config');
    }
}
