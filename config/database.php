<?php
if (!function_exists('env')) {
    function env($key, $default = null) {
        static $loaded = false;
        if (!$loaded) {
            $loaded = true;
            $envFile = __DIR__ . '/../.env';
            if (file_exists($envFile)) {
                foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                    if (strpos(trim($line), '#') === 0) {
                        continue;
                    }
                    if (strpos($line, '=') !== false) {
                        list($name, $value) = explode('=', $line, 2);
                        $name = trim($name);
                        $value = trim($value);
                        if (!isset($_ENV[$name]) && !getenv($name)) {
                            putenv("$name=$value");
                        }
                    }
                }
            }
        }
        $value = getenv($key);
        if ($value === false) {
            $value = $_ENV[$key] ?? null;
        }
        return $value !== false ? $value : $default;
    }
}

define('DB_HOST', env('DB_HOST', 'localhost'));
define('DB_NAME', env('DB_NAME', 'qms_db'));
define('DB_USER', env('DB_USER', 'root'));
define('DB_PASS', env('DB_PASS', ''));
$httpHost = $_SERVER['HTTP_HOST'] ?? env('BASE_HOST', 'localhost');
$envBaseUrl = env('BASE_URL');
if ($envBaseUrl) {
    define('BASE_URL', rtrim($envBaseUrl, '/') . '/');
} else {
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https');
    $scheme = $isHttps ? 'https' : 'http';
    $appDir = str_replace('\\', '/', dirname(__FILE__));
    $docRoot = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? '');
    $basePath = '/';
    if ($docRoot && strpos($appDir, $docRoot) === 0) {
        $rel = substr($appDir, strlen($docRoot));
        $basePath = rtrim(str_replace('/config', '', $rel), '/') . '/';
    }
    define('BASE_URL', $scheme . '://' . $httpHost . $basePath);
}
define('SITE_NAME', env('SITE_NAME', 'Quotation Management System'));
define('TIMEZONE', env('TIMEZONE', 'Asia/Dhaka'));

spl_autoload_register(function($class){
    $paths = [
        __DIR__ . '/../classes/' . $class . '.php',
        __DIR__ . '/../includes/' . $class . '.php',
    ];
    foreach($paths as $file){
        if(file_exists($file)){
            require_once $file;
            break;
        }
    }
});
if (session_status() === PHP_SESSION_NONE) {
    $sessionHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,          // not readable from JavaScript
        'samesite' => 'Lax',         // blocks cross-site POSTs carrying the cookie
        'secure'   => $sessionHttps, // HTTPS only when the request is HTTPS
    ]);
    session_start();
}
date_default_timezone_set(TIMEZONE);
?>
