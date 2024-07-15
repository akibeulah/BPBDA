<?php

namespace AkiBe\PaymentRouter;

use Illuminate\Support\ServiceProvider;
use AkiBe\PaymentRouter\Console\InitCommand;
use AkiBe\PaymentRouter\Console\GenerateEnvCommand;

class PaymentRouterServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->commands([
            InitCommand::class,
            GenerateEnvCommand::class,
        ]);
    }


    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/PaymentRoutesConfig.php' => config_path('PaymentRoutesConfig.php'),
            ], 'config');

            $this->commands([
                InitCommand::class,
                GenerateEnvCommand::class,
            ]);
        }
    }
}