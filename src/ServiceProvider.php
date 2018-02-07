<?php

namespace Sungmee\Payment;

use Illuminate\Support\ServiceProvider as LSP;

class ServiceProvider extends LSP
{
    /**
     * @var bool
     */
    protected $defer = false;

    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/payment.php', 'pay');
        $this->loadRoutesFrom(__DIR__.'/../routes/payment.php');
        $this->loadMigrationsFrom(__DIR__.'/../migrations');
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('Pay', function () {
            return new Pay;
        });
    }

    /**
     * @return array
     */
    public function provides()
    {
        return array('Pay');
    }
}