# Debug Log for Laravel

A powerful Laravel package for debug logging that helps developers track variables, memory usage, execution time, and file paths - all in a clean, organized log file.

Developed by Sajidul Islam.

## Features

- 🔍 Auto-debugging mode that traces your function execution
- 📝 Log variable values with proper formatting
- ⏱️ Track execution time of code blocks
- 💾 Monitor memory usage
- 📂 Log exact file paths and execution stack
- ⚙️ Configurable settings
- 🔄 Debug sessions to group related logs

## Installation

You can install the package via composer:

```bash
composer require sajid-al-islam/debug-log
```

### Configuration

Publish the configuration file (optional):

```bash
php artisan vendor:publish --provider="SajidAlIslam\DebugLog\DebugLogServiceProvider" --tag="config"
```

## Usage

### Quick Start: Auto-Debug

To automatically debug all variables and execution within a function, just add this line at the start:

```php
Route::get('/test-debug', function() {
    DebugLog::autoDebug(); // 👈 Add this line
    
    $user = App\Models\User::first();
    $array = ['a' => 1, 'b' => 2];
    $calculation = 123 * 456;
    
    return 'Check your logs';
});
```

### Manual Debugging

Debug specific variables:

```php
$user = User::find(1);
DebugLog::debug($user, 'User Data');

// Helper function equivalent
debug_log($user, 'User Data');
```

### Timing Execution

Track how long operations take:

```php
DebugLog::startTimer('database-query');
$users = User::all();
DebugLog::stopTimer('database-query');

// Helper function equivalent
debug_timer_start('database-query');
$users = User::all();
debug_timer_stop('database-query');
```

### Memory Usage

Monitor memory consumption:

```php
DebugLog::logMemory('Before Heavy Operation');
// ... some memory-intensive operation
DebugLog::logMemory('After Heavy Operation');

// Helper function equivalent
debug_memory('Before Heavy Operation');
```

### Debug Sessions

Group related debug logs together:

```php
DebugLog::startSession('user-import');

// Multiple debug operations...
DebugLog::debug($userData);
DebugLog::startTimer('validation');
// ... validation logic
DebugLog::stopTimer('validation');

DebugLog::endSession('user-import');

// Helper function equivalent
debug_session_start('user-import');
// ... operations
debug_session_end('user-import');
```

## Configuration Options

You can customize the package by modifying the `config/debug-log.php` file:

```php
return [
    // Path to the debug log file
    'log_path' => storage_path('logs/debug.log'),
    
    // Maximum memory usage to trigger warnings (MB)
    'memory_limit_warning' => 128,
    
    // Maximum execution time to trigger warnings (seconds)
    'execution_time_warning' => 5,
    
    // Which features to enable
    'log_variables' => true,
    'log_memory' => true,
    'log_execution_time' => true,
    'log_file_paths' => true,
    
    // Maximum depth for array/object dumping
    'max_dump_depth' => 2,
];
```

## Log File Example

The generated log file (`storage/logs/debug.log`) looks like:

```
[2025-05-11 10:15:23.456789] Source File
  Path: /app/Http/Controllers/HomeController.php
  Line: 24
--------------------------------------------------
[2025-05-11 10:15:23.456890] Function Body
  Code: public function index()
{
    DebugLog::autoDebug();
    $user = User::first();
    $posts = Post::latest()->take(5)->get();
    $count = DB::table('visitors')->count();
    return view('home', compact('user', 'posts', 'count'));
}
--------------------------------------------------
[2025-05-11 10:15:23.457012] Variable $user
  Expression: User::first()
  Note: Use dedicated debug() calls to see actual values
--------------------------------------------------
[2025-05-11 10:15:23.457123] Timer: var_user
  Execution Time: 0.0421 seconds
--------------------------------------------------
[2025-05-11 10:15:23.457234] Variable $posts
  Expression: Post::latest()->take(5)->get()
  Note: Use dedicated debug() calls to see actual values
--------------------------------------------------
[2025-05-11 10:15:23.457345] Timer: var_posts
  Execution Time: 0.0314 seconds
--------------------------------------------------
[2025-05-11 10:15:23.457456] Timer: function_total
  Execution Time: 0.1235 seconds
--------------------------------------------------
[2025-05-11 10:15:23.457567] Ended Debug Session: AutoDebug-HomeController-24
  Duration: 0.1350 seconds
  Memory Delta: 4.25 MB
  Time: 2025-05-11 10:15:23
--------------------------------------------------
```

## Advanced Features

### Custom Debugging

You can create more targeted debugging for specific scenarios:

```php
// Start a custom debug session
DebugLog::startSession('api-request');

// Log request data
DebugLog::debug($request->all(), 'API Request Data');

try {
    // Track time for validation
    DebugLog::startTimer('validation');
    $validated = $request->validate([/* rules */]);
    DebugLog::stopTimer('validation');
    
    // Process request and check memory usage
    DebugLog::logMemory('Before Processing');
    $result = $this->processRequest($validated);
    DebugLog::logMemory('After Processing');
    
    // Debug the result
    DebugLog::debug($result, 'API Result');
} catch (\Exception $e) {
    DebugLog::debug($e, 'API Exception');
}

// End the session
DebugLog::endSession('api-request');
```

## Helper Functions

For convenience, the package provides these global helper functions:

| Helper Function | Equivalent |
|-----------------|------------|
| `auto_debug()` | `DebugLog::autoDebug()` |
| `debug_log($var, $name)` | `DebugLog::debug($var, $name)` |
| `debug_timer_start($name)` | `DebugLog::startTimer($name)` |
| `debug_timer_stop($name)` | `DebugLog::stopTimer($name)` |
| `debug_memory($label)` | `DebugLog::logMemory($label)` |
| `debug_session_start($name)` | `DebugLog::startSession($name)` |
| `debug_session_end($name)` | `DebugLog::endSession($name)` |

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.456789] Started Debug Session: AutoDebug-HomeController-24
  Time: 2025-05-11 10:15:23
--------------------------------------------------
[2025-05-11 10:15:23.