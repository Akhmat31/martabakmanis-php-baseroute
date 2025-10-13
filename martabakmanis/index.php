<?php
declare(strict_types=1);
/**
 * Framework Kernel/Bootstrap - Procedural Style
 * PSR-4 compliant with enhanced security and best practices
 *
 * this file is very important, dont change this function block
 * it's contain file kernel loader, system configuration, and many,
 * =========================================================
 * | IF YOU STILL WANT CHANGE THIS FILE, FIX FOR ONLY JUST |
 * | CONFIGURATION THAT ARE NOT TOO IMPORTANT AND NEEDED   |
 * =========================================================
 */
use Monolog\Logger;

if (!defined('FRAMEWORK_ROOT')) {
    define('FRAMEWORK_ROOT', dirname(__DIR__));
}

function martabak_load_config(): array
{
    $configFile = FRAMEWORK_ROOT . '/config/app.php';
    $config = [];
    
    if (file_exists($configFile)) {
        $config = require $configFile;
    }

    return array_merge([
        'environment' => getenv('APP_ENV') ?: 'production',
        'timezone' => 'UTC',
        'charset' => 'UTF-8',
        'log_level' => (getenv('APP_ENV') === 'production') ? 'WARNING' : 'DEBUG',
        'display_errors' => getenv('APP_ENV') !== 'production',
        'log_path' => FRAMEWORK_ROOT . './martabakmanis/logs',
        'debug' => getenv('APP_ENV') !== 'production',
    ], $config);
}



function martabak_init_logger(array $config): ?object
{
    static $logger = null;
    
    if ($logger !== null) {
        return $logger;
    }

    try {
        $logger = new Logger('app');

        /**
         * @var 
         * File path loader
         */
        $logFile = $config['log_path'] . '/' . date('Y-m-d') . '.log';
        $logLevel = constant('\Monolog\Logger::' . $config['log_level']);
        $fileHandler = new \Monolog\Handler\StreamHandler($logFile, $logLevel);
        
        /**
         * @var mixed
         * 
         * Date any: Logger usage only
         */
        $formatter = new \Monolog\Formatter\LineFormatter(
            "[%datetime%] %channel%.%level_name%: %message% %context%\n",
            'Y-m-d H:i:s'
        );
        $fileHandler->setFormatter($formatter);
        $logger->pushHandler($fileHandler);

        /**
         * this for err log handler for critical errors
         * maybe, it can useful if you are on production mode
         */
        if ($config['environment'] === 'production') {
            $errorHandler = new \Monolog\Handler\ErrorLogHandler(
                \Monolog\Handler\ErrorLogHandler::OPERATING_SYSTEM,
                \Monolog\Logger::ERROR
            );
            $logger->pushHandler($errorHandler);
        }

        return $logger;
    } catch (\Exception $e) {
        error_log('Failed to initialize logger: ' . $e->getMessage());
        return null;
    }
}
function martabak_logger(): ?object
{
    if (!isset($GLOBALS['FRAMEWORK_LOGGER'])) {
        if (!isset($GLOBALS['FRAMEWORK_CONFIG']) || empty($GLOBALS['FRAMEWORK_CONFIG'])) {
            $GLOBALS['FRAMEWORK_CONFIG'] = martabak_load_config();
        }
        
        $GLOBALS['FRAMEWORK_LOGGER'] = martabak_init_logger($GLOBALS['FRAMEWORK_CONFIG']);
    }
    
    return $GLOBALS['FRAMEWORK_LOGGER'];
}

function martabak_get_severity_name(int $severity): string
{
    static $severities = null;
    
    if ($severities === null) {
        $severities = [
            E_ERROR => 'E_ERROR',
            E_WARNING => 'E_WARNING',
            E_PARSE => 'E_PARSE',
            E_NOTICE => 'E_NOTICE',
            E_CORE_ERROR => 'E_CORE_ERROR',
            E_CORE_WARNING => 'E_CORE_WARNING',
            E_COMPILE_ERROR => 'E_COMPILE_ERROR',
            E_COMPILE_WARNING => 'E_COMPILE_WARNING',
            E_USER_ERROR => 'E_USER_ERROR',
            E_USER_WARNING => 'E_USER_WARNING',
            E_USER_NOTICE => 'E_USER_NOTICE',
            E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',
            E_DEPRECATED => 'E_DEPRECATED',
            E_USER_DEPRECATED => 'E_USER_DEPRECATED',
        ];
        

    }

    return $severities[$severity] ?? 'UNKNOWN';
}

function martabak_render_error(\Throwable $exception): void
{
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: application/json; charset=UTF-8');
    }

    $response = [
        'error' => 'Internal Server Error',
        'message' => 'An unexpected error occurred'
    ];

    $isDebug = martabak_is_debug();
    if ($isDebug) {
        $response['debug'] = [
            'exception' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => explode("\n", $exception->getTraceAsString())
        ];
    }

    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit(1);
}

function martabak_configure_error_handling(): void
{
    set_error_handler(function (int $severity, string $message, string $file, int $line): bool {
        if (!(error_reporting() & $severity)) {
            return false;
        }

        try {
            $logger = martabak_logger();
            if ($logger) {
                $logger->error("PHP Error: {$message}", [
                    'file' => $file,
                    'line' => $line,
                    'severity' => $severity,
                    'severity_name' => martabak_get_severity_name($severity)
                ]);
            }
        } catch (\Throwable $e) {
            error_log(sprintf(
                "PHP Error [%s]: %s in %s:%d",
                martabak_get_severity_name($severity),
                $message,
                $file,
                $line
            ));
        }

        throw new \ErrorException($message, 0, $severity, $file, $line);
    });
    set_exception_handler(function (\Throwable $exception): void {
        try {
            $logger = martabak_logger();
            if ($logger) {
                $logger->critical('Uncaught Exception: ' . $exception->getMessage(), [
                    'exception' => get_class($exception),
                    'file' => $exception->getFile(),
                    'line' => $exception->getLine(),
                    'trace' => $exception->getTraceAsString()
                ]);
            }
        } catch (\Throwable $e) {
            error_log(sprintf(
                "Uncaught Exception [%s]: %s in %s:%d",
                get_class($exception),
                $exception->getMessage(),
                $exception->getFile(),
                $exception->getLine()
            ));
        }

        martabak_render_error($exception);
    });

    register_shutdown_function(function (): void {
        $error = error_get_last();
        if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            try {
                $logger = martabak_logger();
                if ($logger) {
                    $logger->critical('Fatal Error', $error);
                }
            } catch (\Throwable $e) {
                error_log('Fatal Error: ' . print_r($error, true));
            }
        }
    });
}

function martabak_configure_php(array $config): void
{
    date_default_timezone_set($config['timezone']);
    error_reporting(E_ALL);
    ini_set('display_errors', $config['display_errors'] ? '1' : '0');
    ini_set('log_errors', '1');
    
    ini_set('expose_php', '0');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_secure', '1');
    ini_set('session.use_strict_mode', '1');
}

function martabak_set_headers(array $config): void
{
    if (headers_sent()) {
        return;
    }

    header('Content-Type: text/html; charset=' . $config['charset']);
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('X-XSS-Protection: 1; mode=block');
    
    if ($config['environment'] === 'production') {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}

function martabak_init_composer(): void
{
    $composerAutoload = FRAMEWORK_ROOT . '/vendor/autoload.php';
    
    if (file_exists($composerAutoload)) {
        require_once $composerAutoload;
    }
}

function martabak_init_autoloader(): void
{
    spl_autoload_register(function (string $className): void {
        if (strpos($className, 'MyLib\\') !== 0) {
            return;
        }

        $relativeClass = substr($className, strlen('MyLib\\'));
        
        if (preg_match('/[^a-zA-Z0-9_\\\\]/', $relativeClass)) {
            return;
        }

        $file = FRAMEWORK_ROOT . '/src/' 
              . str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass) 
              . '.php';

        $realFile = realpath($file);
        $srcDir = realpath(FRAMEWORK_ROOT . '/src');

        if ($realFile && $srcDir && strpos($realFile, $srcDir) === 0 && file_exists($realFile)) {
            require_once $realFile;
        }
    }, true, true);
}

function martabak_config(string $key, $default = null)
{
    if (!isset($GLOBALS['FRAMEWORK_CONFIG']) || empty($GLOBALS['FRAMEWORK_CONFIG'])) {
        $GLOBALS['FRAMEWORK_CONFIG'] = martabak_load_config();
    }
    
    $keys = explode('.', $key);
    $value = $GLOBALS['FRAMEWORK_CONFIG'];
    
    foreach ($keys as $k) {
        if (!isset($value[$k])) {
            return $default;
        }
        $value = $value[$k];
    }
    
    return $value;
}

/**
 * Debug
 * @return bool
 */
function martabak_is_debug(): bool
{
    return martabak_config('debug', false);
}

/**
 * Get environment
 */
function martabak_env(): string
{
    return martabak_config('environment', 'production');
}

/**
 * Bootstrap app
 */
function martabak_boot(): void
{
    if (isset($GLOBALS['FRAMEWORK_BOOTED']) && $GLOBALS['FRAMEWORK_BOOTED']) {
        return;
    }
    
    $GLOBALS['FRAMEWORK_CONFIG'] = martabak_load_config();
    
    martabak_init_composer();
    martabak_init_autoloader();
    $GLOBALS['FRAMEWORK_LOGGER'] = martabak_init_logger($GLOBALS['FRAMEWORK_CONFIG']);
    martabak_configure_error_handling();
    martabak_configure_php($GLOBALS['FRAMEWORK_CONFIG']);
    martabak_set_headers($GLOBALS['FRAMEWORK_CONFIG']);
    
    $GLOBALS['FRAMEWORK_BOOTED'] = true;
}

/*============================================
 *   EXEC
/*============================================*/

$GLOBALS['FRAMEWORK_CONFIG'] = [];
$GLOBALS['FRAMEWORK_LOGGER'] = null;
$GLOBALS['FRAMEWORK_BOOTED'] = false;

$GLOBALS['framework_config'] = &$GLOBALS['FRAMEWORK_CONFIG'];
$GLOBALS['framework_logger'] = &$GLOBALS['FRAMEWORK_LOGGER'];
$GLOBALS['framework_booted'] = &$GLOBALS['FRAMEWORK_BOOTED'];

martabak_boot();