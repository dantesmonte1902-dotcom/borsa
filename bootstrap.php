<?php

declare(strict_types=1);

const BASE_PATH = __DIR__;

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $path = BASE_PATH . '/classes/' . str_replace('\\', '/', $relative) . '.php';

    if (is_file($path)) {
        require_once $path;
    }
});

App\Services\Env::load(BASE_PATH . '/.env');
App\Services\Security::bootSession();

$config = App\Services\Config::all('app');
date_default_timezone_set($config['timezone'] ?? 'Europe/Istanbul');

error_reporting(E_ALL);
ini_set('display_errors', ($config['debug'] ?? false) ? '1' : '0');
ini_set('log_errors', '1');
ini_set('error_log', BASE_PATH . '/storage/logs/php-error.log');

set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
    if (!(error_reporting() & $severity)) {
        return false;
    }

    throw new ErrorException($message, 0, $severity, $file, $line);
});
