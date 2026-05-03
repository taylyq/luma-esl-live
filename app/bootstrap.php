<?php

declare(strict_types=1);

session_start();

$config = require dirname(__DIR__) . '/config.php';

spl_autoload_register(function (string $class): void {
    $path = dirname(__DIR__) . '/' . str_replace('\\', '/', $class) . '.php';
    if (is_file($path)) {
        require $path;
    }
});

require __DIR__ . '/helpers.php';

try {
    $GLOBALS['pdo'] = App\Database::connect($config['db']);
} catch (Throwable $exception) {
    $GLOBALS['pdo_error'] = $exception->getMessage();
}

$GLOBALS['config'] = $config;
