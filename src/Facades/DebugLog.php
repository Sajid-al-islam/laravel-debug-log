<?php

namespace SajidAlIslam\DebugLog\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static void autoDebug()
 * @method static void debug(mixed $variable, string $name = null)
 * @method static void startTimer(string $name = 'default')
 * @method static float stopTimer(string $name = 'default')
 * @method static void logMemory(string $label = 'Memory Usage')
 * @method static void startSession(string $name = null)
 * @method static void endSession(string $name = null)
 * 
 * @see \SajidAlIslam\DebugLog\DebugLog
 */
class DebugLog extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'debug-log';
    }
}