<?php

return [
    'enabled' => env('DEBUG_LOG_ENABLED', true),
    'channel' => env('DEBUG_LOG_CHANNEL', 'daily'),
    'max_file_size' => env('DEBUG_LOG_MAX_SIZE', 50), // MB
];