<?php

namespace YourVendor\DebugLog;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DebugLogger
{
    protected $startTime;
    protected $lastTime;
    protected $enabled;
    protected $logChannel;

    public function __construct()
    {
        $this->enabled = config('debuglog.enabled');
        $this->logChannel = config('debuglog.channel');
        $this->startTime = microtime(true);
        $this->lastTime = $this->startTime;
    }

    public function log($variable, string $label = null): void
    {
        if (!$this->enabled) return;

        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $caller = $backtrace[1] ?? $backtrace[0];

        $logData = [
            'label' => $label ?? 'Debug Log',
            'data' => $this->convertVariable($variable),
            'time' => [
                'total_elapsed' => $this->getElapsedTime($this->startTime),
                'since_last' => $this->getElapsedTime($this->lastTime),
            ],
            'origin' => [
                'file' => $caller['file'] ?? null,
                'line' => $caller['line'] ?? null,
                'function' => $caller['function'] ?? null,
                'class' => $caller['class'] ?? null,
            ]
        ];

        Log::channel($this->logChannel)->info('Debug Log Entry', $logData);
        $this->lastTime = microtime(true);
    }

    public function dump($variable): void
    {
        $this->log($variable, 'DUMP');
    }

    protected function getElapsedTime(float $startTime): string
    {
        return number_format((microtime(true) - $startTime) * 1000, 2) . 'ms';
    }

    protected function convertVariable($variable)
    {
        if (is_object($variable) && method_exists($variable, 'toArray')) {
            return $variable->toArray();
        }

        if (is_resource($variable)) {
            return 'Resource of type: ' . get_resource_type($variable);
        }

        return $variable;
    }

    // Configuration update methods
    public function enable(): void { $this->enabled = true; }
    public function disable(): void { $this->enabled = false; }
    public function setChannel(string $channel): void { $this->logChannel = $channel; }
}