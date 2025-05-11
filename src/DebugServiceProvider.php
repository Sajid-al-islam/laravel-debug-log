<?php

namespace YourVendor\DebugLog;

use Illuminate\Support\ServiceProvider;

class DebugServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('debug-logger', function ($app) {
            return new DebugLogger();
        });

        $this->mergeConfigFrom(__DIR__.'/../config/debuglog.php', 'debuglog');
    }

    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/debuglog.php' => config_path('debuglog.php'),
        ], 'debuglog-config');
    }
}