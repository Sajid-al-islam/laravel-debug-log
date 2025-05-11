<?php

namespace SajidAlIslam\DebugLog;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use InvalidArgumentException;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\CliDumper;

class DebugLog
{
    /**
     * @var array Timer store
     */
    protected $timers = [];

    /**
     * @var array Session store
     */
    protected $sessions = [];

    /**
     * @var VarCloner
     */
    protected $cloner;

    /**
     * @var CliDumper
     */
    protected $dumper;

    /**
     * @var int Debug backtrace skip steps
     */
    protected $backtraceSkip = 3;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->cloner = new VarCloner();
        $this->cloner->setMaxItems(config('debug-log.max_dump_depth', 2));
        
        $this->dumper = new CliDumper();
    }

    /**
     * Debug a variable with its name, memory usage, execution time, and file path
     *
     * @param mixed $variable
     * @param string|null $name
     * @return void
     */
    public function debug($variable, ?string $name = null)
    {
        $data = [];
        
        // Detect variable name if not provided
        if (is_null($name)) {
            $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
            $file = $trace[0]['file'] ?? '';
            $line = $trace[0]['line'] ?? '';
            
            if (file_exists($file)) {
                $fileContents = file($file);
                $relevant = $fileContents[$line - 1] ?? '';
                
                // Try to extract variable name from code
                if (preg_match('/debug\s*\(\s*\$([a-zA-Z0-9_]+)/', $relevant, $matches)) {
                    $name = $matches[1];
                } elseif (preg_match('/debug_log\s*\(\s*\$([a-zA-Z0-9_]+)/', $relevant, $matches)) {
                    $name = $matches[1];
                } elseif (preg_match('/DebugLog::debug\s*\(\s*\$([a-zA-Z0-9_]+)/', $relevant, $matches)) {
                    $name = $matches[1];
                } else {
                    $name = 'unknown';
                }
            }
        }
        
        // Add variable value
        if (config('debug-log.log_variables', true)) {
            $data['Value'] = $this->captureVarDump($variable);
        }
        
        // Add memory usage
        if (config('debug-log.log_memory', true)) {
            $memory = memory_get_usage(true) / 1024 / 1024; // MB
            $data['Memory Usage'] = round($memory, 2) . ' MB';
            
            if ($memory > config('debug-log.memory_limit_warning', 128)) {
                $data['Memory Warning'] = 'HIGH MEMORY USAGE DETECTED';
            }
        }
        
        // Add file paths
        if (config('debug-log.log_file_paths', true)) {
            $backtraceItems = $this->getBacktrace();
            $data['Called From'] = $backtraceItems;
        }
        
        $this->log($name, $data);
    }

    /**
     * Automatically debug all variables in the current scope
     *
     * @return void
     */
    public function autoDebug()
    {
        // Get calling function's file, line, and scope
        $trace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 2);
        $caller = $trace[0] ?? null;
        
        if (!$caller) {
            $this->log('Auto Debug', ['Error' => 'Could not get caller information']);
            return;
        }
        
        $file = $caller['file'] ?? '';
        $line = $caller['line'] ?? '';
        
        // Generate session name for this auto debug
        $sessionName = 'AutoDebug-' . basename($file) . '-' . $line;
        $this->startSession($sessionName);
        
        // Get all variables in the calling scope
        $this->logSourceCodeInfo($file, $line);
        
        $this->endSession($sessionName);
    }
    
    /**
     * Logs information about the source code
     * 
     * @param string $file
     * @param int $line
     * @return void
     */
    protected function logSourceCodeInfo(string $file, int $line)
    {
        // Get source code lines around the auto_debug call
        $this->log('Source File', ['Path' => $file, 'Line' => $line]);
        
        if (file_exists($file)) {
            // Parse the file to find variables
            $content = file_get_contents($file);
            $tokens = token_get_all($content);
            
            // Extract function/method body where autoDebug() is called
            $functionBody = $this->extractFunctionBody($content, $line);
            
            if ($functionBody) {
                $this->log('Function Body', ['Code' => $functionBody]);
                
                // Find variables in the function body
                preg_match_all('/\$([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)/', $functionBody, $matches);
                $variables = array_unique($matches[1] ?? []);
                
                // Start timer for entire function execution
                $this->startTimer('function_total');
                
                // Try to analyze the function body
                $this->analyzeCode($functionBody);
                
                // Log the execution time
                $this->stopTimer('function_total');
            }
        }
    }
    
    /**
     * Try to analyze the source code to extract key information
     * 
     * @param string $code
     * @return void
     */
    protected function analyzeCode(string $code)
    {
        // Look for variable assignments
        preg_match_all('/\$([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)\s*=\s*([^;]+);/', $code, $assignments);
        
        if (!empty($assignments[0])) {
            for ($i = 0; $i < count($assignments[0]); $i++) {
                $varName = $assignments[1][$i] ?? null;
                $expression = $assignments[2][$i] ?? null;
                
                if ($varName && $expression) {
                    $this->startTimer("var_{$varName}");
                    
                    // Try to evaluate variable value - this actually can't work as expected
                    // as we don't have access to the actual variable, but we can show the expression
                    $this->log("Variable \${$varName}", [
                        'Expression' => trim($expression),
                        'Note' => 'Use dedicated debug() calls to see actual values'
                    ]);
                    
                    $this->stopTimer("var_{$varName}");
                }
            }
        }
        
        // Look for method/function calls
        preg_match_all('/([a-zA-Z_\x7f-\xff\\\\][a-zA-Z0-9_\x7f-\xff\\\\]*)\s*\([^)]*\)/', $code, $calls);
        
        if (!empty($calls[0])) {
            $this->log('Function/Method Calls', [
                'Calls' => implode(', ', array_unique($calls[0]))
            ]);
        }
    }
    
    /**
     * Extract the function body from a file
     * 
     * @param string $content
     * @param int $targetLine
     * @return string|null
     */
    protected function extractFunctionBody(string $content, int $targetLine)
    {
        $lines = explode("\n", $content);
        $targetLine--; // Convert to 0-based index
        
        $braceLevel = 0;
        $functionStartLine = null;
        $functionEndLine = null;
        
        // Find the function start (going backwards from target line)
        for ($i = $targetLine; $i >= 0; $i--) {
            $line = $lines[$i];
            
            // Count closing braces
            $closingBraces = substr_count($line, '}');
            $braceLevel += $closingBraces;
            
            // Count opening braces
            $openingBraces = substr_count($line, '{');
            $braceLevel -= $openingBraces;
            
            // If we found the function start
            if ($braceLevel <= 0 && $openingBraces > 0) {
                $functionStartLine = $i;
                break;
            }
        }
        
        // Reset brace level for forward search
        $braceLevel = 1; // Start at 1 because we already found the opening brace
        
        // Find the function end (going forward from function start)
        if ($functionStartLine !== null) {
            for ($i = $functionStartLine + 1; $i < count($lines); $i++) {
                $line = $lines[$i];
                
                // Count opening braces
                $openingBraces = substr_count($line, '{');
                $braceLevel += $openingBraces;
                
                // Count closing braces
                $closingBraces = substr_count($line, '}');
                $braceLevel -= $closingBraces;
                
                // If we found the function end
                if ($braceLevel <= 0) {
                    $functionEndLine = $i;
                    break;
                }
            }
        }
        
        // Extract the function body
        if ($functionStartLine !== null && $functionEndLine !== null) {
            $body = array_slice($lines, $functionStartLine, $functionEndLine - $functionStartLine + 1);
            return implode("\n", $body);
        }
        
        return null;
    }

    /**
     * Start a timer
     *
     * @param string $name
     * @return void
     */
    public function startTimer(string $name = 'default')
    {
        $this->timers[$name] = microtime(true);
    }

    /**
     * Stop a timer and return elapsed time
     *
     * @param string $name
     * @return float Elapsed time in seconds
     * @throws InvalidArgumentException
     */
    public function stopTimer(string $name = 'default')
    {
        if (!isset($this->timers[$name])) {
            throw new InvalidArgumentException("Timer '$name' does not exist");
        }

        $elapsed = microtime(true) - $this->timers[$name];
        unset($this->timers[$name]);

        if (config('debug-log.log_execution_time', true)) {
            $data = ['Execution Time' => round($elapsed, 4) . ' seconds'];
            
            if ($elapsed > config('debug-log.execution_time_warning', 5)) {
                $data['Time Warning'] = 'SLOW EXECUTION DETECTED';
            }
            
            $this->log("Timer: $name", $data);
        }

        return $elapsed;
    }

    /**
     * Log current memory usage
     *
     * @param string $label
     * @return void
     */
    public function logMemory(string $label = 'Memory Usage')
    {
        $memory = memory_get_usage(true) / 1024 / 1024; // MB
        $data = ['Current' => round($memory, 2) . ' MB'];
        
        $peak = memory_get_peak_usage(true) / 1024 / 1024; // MB
        $data['Peak'] = round($peak, 2) . ' MB';
        
        if ($memory > config('debug-log.memory_limit_warning', 128)) {
            $data['Warning'] = 'HIGH MEMORY USAGE DETECTED';
        }
        
        $this->log($label, $data);
    }

    /**
     * Start a debug session
     *
     * @param string|null $name
     * @return void
     */
    public function startSession(?string $name = null)
    {
        if (is_null($name)) {
            $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
            $caller = $trace[1] ?? $trace[0] ?? null;
            $function = $caller['function'] ?? 'unknown';
            $name = 'Session-' . $function;
        }

        $this->sessions[$name] = [
            'start_time' => microtime(true),
            'start_memory' => memory_get_usage(true)
        ];

        $this->log("Started Debug Session: $name", [
            'Time' => Carbon::now()->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * End a debug session
     *
     * @param string|null $name
     * @return void
     * @throws InvalidArgumentException
     */
    public function endSession(?string $name = null)
    {
        if (is_null($name)) {
            $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
            $caller = $trace[1] ?? $trace[0] ?? null;
            $function = $caller['function'] ?? 'unknown';
            $name = 'Session-' . $function;
        }

        if (!isset($this->sessions[$name])) {
            throw new InvalidArgumentException("Session '$name' does not exist or has already ended");
        }

        $session = $this->sessions[$name];
        $elapsed = microtime(true) - $session['start_time'];
        $memoryDiff = (memory_get_usage(true) - $session['start_memory']) / 1024 / 1024; // MB

        $this->log("Ended Debug Session: $name", [
            'Duration' => round($elapsed, 4) . ' seconds',
            'Memory Delta' => round($memoryDiff, 2) . ' MB',
            'Time' => Carbon::now()->format('Y-m-d H:i:s')
        ]);

        unset($this->sessions[$name]);
    }

    /**
     * Write log entry to the debug log file
     *
     * @param string $subject
     * @param array $data
     * @return void
     */
    protected function log(string $subject, array $data)
    {
        $logPath = config('debug-log.log_path', storage_path('logs/debug.log'));
        
        // Ensure log directory exists
        $logDir = dirname($logPath);
        if (!file_exists($logDir)) {
            File::makeDirectory($logDir, 0755, true);
        }
        
        $timestamp = Carbon::now()->format('Y-m-d H:i:s.u');
        $separator = str_repeat('-', 50);
        
        $logEntry = "[$timestamp] $subject\n";
        
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $logEntry .= "  $key:\n";
                foreach ($value as $subKey => $subValue) {
                    $logEntry .= "    $subKey: $subValue\n";
                }
            } else {
                $logEntry .= "  $key: $value\n";
            }
        }
        
        $logEntry .= "$separator\n";
        
        // Append to log file
        file_put_contents($logPath, $logEntry, FILE_APPEND);
    }

    /**
     * Capture var_dump output as a string
     *
     * @param mixed $variable
     * @return string
     */
    protected function captureVarDump($variable)
    {
        $clonedVar = $this->cloner->cloneVar($variable);
        
        // Capture the dump output
        ob_start();
        $this->dumper->dump($clonedVar);
        return ob_get_clean();
    }

    /**
     * Get formatted backtrace
     *
     * @return array
     */
    protected function getBacktrace()
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        $result = [];
        
        // Skip internal framework and package calls
        for ($i = $this->backtraceSkip; $i < count($trace); $i++) {
            $item = $trace[$i];
            $file = $item['file'] ?? 'unknown';
            $line = $item['line'] ?? 0;
            $function = $item['function'] ?? 'unknown';
            $class = $item['class'] ?? null;
            
            if ($class) {
                $function = "$class::{$function}";
            }
            
            $result[] = "$function() in $file:$line";
        }
        
        return $result;
    }
}