<?php
/**
 * Simple Framework Kernel/Autoloader
 * Enhanced with PSR standards and professional packages
 */

// Load Composer autoloader
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
} else {
    // Fallback autoloader for development
    spl_autoload_register(function ($className) {
        $className = str_replace('MyLib\\', '', $className);
        $className = str_replace('\\', DIRECTORY_SEPARATOR, $className);
        
        $file = __DIR__ . '/src/' . $className . '.php';
        
        if (file_exists($file)) {
            require_once $file;
        }
    });
}

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\ErrorLogHandler;

// Initialize global logger
if (!isset($GLOBALS['framework_logger'])) {
    $logger = new Logger('HTTPSERVER');
    $logger->pushHandler(new StreamHandler(__DIR__ . '/../logs/app.log', Logger::DEBUG));
    $logger->pushHandler(new ErrorLogHandler(ErrorLogHandler::OPERATING_SYSTEM, Logger::ERROR));
    $GLOBALS['framework_logger'] = $logger;
}

// Enhanced error handling with logging
set_error_handler(function($severity, $message, $file, $line) {
    if (isset($GLOBALS['framework_logger'])) {
        $GLOBALS['framework_logger']->error("PHP Error: {$message}", [
            'file' => $file,
            'line' => $line,
            'severity' => $severity
        ]);
    }
    
    if (!(error_reporting() & $severity)) {
        return false;
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
});

set_exception_handler(function($exception) {
    if (isset($GLOBALS['framework_logger'])) {
        $GLOBALS['framework_logger']->critical("Uncaught Exception: " . $exception->getMessage(), [
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString()
        ]);
    }
    
    http_response_code(500);
    if (!headers_sent()) {
        header('Content-Type: application/json');
    }
    echo json_encode([
        'error' => 'Internal Server Error',
        'message' => 'An unexpected error occurred'
    ]);
});

// Create logs directory if it doesn't exist
$logsDir = __DIR__ . '/../logs';
if (!is_dir($logsDir)) {
    mkdir($logsDir, 0755, true);
}

// Set default timezone and error reporting
date_default_timezone_set('UTC');
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors in production

// Set default content type
if (!headers_sent()) {
    header('Content-Type: text/html; charset=UTF-8');
}