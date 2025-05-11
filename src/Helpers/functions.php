<?php

if (!function_exists('debug_log')) {
    /**
     * Log a debug message with variable information
     *
     * @param mixed $variable
     * @param string|null $name
     * @return void
     */
    function debug_log($variable, ?string $name = null)
    {
        app('debug-log')->debug($variable, $name);
    }
}

if (!function_exists('debug_memory')) {
    /**
     * Log current memory usage
     *
     * @param string $label
     * @return void
     */
    function debug_memory(string $label = 'Memory Usage')
    {
        app('debug-log')->logMemory($label);
    }
}

if (!function_exists('debug_timer_start')) {
    /**
     * Start a timer for execution time tracking
     *
     * @param string $name
     * @return void
     */
    function debug_timer_start(string $name = 'default')
    {
        app('debug-log')->startTimer($name);
    }
}

if (!function_exists('debug_timer_stop')) {
    /**
     * Stop a timer and log execution time
     *
     * @param string $name
     * @return float Elapsed time in seconds
     */
    function debug_timer_stop(string $name = 'default')
    {
        return app('debug-log')->stopTimer($name);
    }
}

if (!function_exists('auto_debug')) {
    /**
     * Automatically debug variables in the current scope
     *
     * @return void
     */
    function auto_debug()
    {
        app('debug-log')->autoDebug();
    }
}

if (!function_exists('debug_session_start')) {
    /**
     * Start a debug session
     *
     * @param string|null $name
     * @return void
     */
    function debug_session_start(?string $name = null)
    {
        app('debug-log')->startSession($name);
    }
}

if (!function_exists('debug_session_end')) {
    /**
     * End a debug session
     *
     * @param string|null $name
     * @return void
     */
    function debug_session_end(?string $name = null)
    {
        app('debug-log')->endSession($name);
    }
}