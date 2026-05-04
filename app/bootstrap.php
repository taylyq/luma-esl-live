<?php

declare(strict_types=1);

session_start();

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relativeClass = substr($class, strlen($prefix));
    $path = __DIR__ . '/' . str_replace('\\', '/', $relativeClass) . '.php';

    if (is_file($path)) {
        require $path;
    }
});

require __DIR__ . '/helpers.php';

load_env(dirname(__DIR__) . '/.env');

$config = require dirname(__DIR__) . '/config.php';

try {
    $GLOBALS['pdo'] = App\Database::connect($config['db']);
} catch (Throwable $exception) {
    $GLOBALS['pdo_error'] = $exception->getMessage();
}

$GLOBALS['config'] = $config;
