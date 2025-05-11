<?php

namespace YourVendor\DebugLog;

use Illuminate\Support\Facades\Facade;

/**
 * @method static void log($variable, string $label = null)
 * @method static void dump($variable)
 * @method static void enable()
 * @method static void disable()
 * @method static void setChannel(string $channel)
 */
class DebugLog extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'debug-logger';
    }
}