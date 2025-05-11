<?php

namespace SajidAlIslam\DebugLog;

use Illuminate\Support\ServiceProvider;

class DebugLogServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/debug-log.php', 'debug-log'
        );

        $this->app->singleton('debug-log', function ($app) {
            return new DebugLog();
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../config/debug-log.php' => config_path('debug-log.php'),
        ], 'config');
    }
}