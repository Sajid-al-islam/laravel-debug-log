<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Debug Log Settings
    |--------------------------------------------------------------------------
    |
    | Configure the behavior of the debug log package
    |
    */

    // Path to the debug log file
    'log_path' => storage_path('logs/debug.log'),

    // Maximum memory usage to log (in MB)
    'memory_limit_warning' => 128,

    // Maximum execution time to log (in seconds)
    'execution_time_warning' => 5,

    // Should variable values be logged
    'log_variables' => true,

    // Should memory usage be logged
    'log_memory' => true,

    // Should execution time be logged
    'log_execution_time' => true,

    // Should file paths be logged
    'log_file_paths' => true,

    // Maximum depth for array/object dumping
    'max_dump_depth' => 2,
];