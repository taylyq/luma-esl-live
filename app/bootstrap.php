<?php

declare(strict_types=1);

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
    'httponly' => true,
    'samesite' => 'Lax',
]);

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

$rootPath = dirname(__DIR__);
$envPath = $rootPath . '/.env';
load_env($envPath);

$config = require $rootPath . '/config.php';

try {
    $GLOBALS['pdo'] = App\Database::connect($config['db']);
} catch (Throwable $exception) {
    $dbConfig = $config['db'] ?? [];
    $rawMessage = $exception->getMessage();
    $safeReason = 'Connection failed';

    if (str_contains($rawMessage, 'Access denied')) {
        $safeReason = 'Access denied. The database username or password is incorrect, or the user is not assigned to this database.';
    } elseif (str_contains($rawMessage, 'Unknown database')) {
        $safeReason = 'Unknown database. The database name does not exist on this Hostinger account.';
    } elseif (str_contains($rawMessage, 'Connection refused') || str_contains($rawMessage, 'No such file or directory')) {
        $safeReason = 'Cannot reach MySQL. The host or port is not correct for this hosting account.';
    } elseif (str_contains($rawMessage, 'could not find driver')) {
        $safeReason = 'PHP MySQL driver is missing. Enable the PDO MySQL extension in Hostinger PHP settings.';
    }

    $GLOBALS['pdo_error'] = 'Database connection failed. Check the details below and storage/database-error.log on Hostinger.';
    $GLOBALS['pdo_setup'] = [
        'reason' => $safeReason,
        'env_file' => is_file($envPath) ? 'Found' : 'Missing',
        'driver' => (string) ($dbConfig['driver'] ?? ''),
        'host' => (string) ($dbConfig['host'] ?? ''),
        'port' => (string) ($dbConfig['port'] ?? ''),
        'database' => (string) ($dbConfig['database'] ?? ''),
        'username' => (string) ($dbConfig['username'] ?? ''),
        'password' => !empty($dbConfig['password']) ? 'Set' : 'Missing',
    ];

    $logPath = $rootPath . '/storage/database-error.log';
    if (!is_dir(dirname($logPath))) {
        mkdir(dirname($logPath), 0775, true);
    }
    error_log(
        sprintf(
            "[%s] %s using driver=%s host=%s port=%s database=%s username=%s password=%s\n",
            date('c'),
            $exception->getMessage(),
            $GLOBALS['pdo_setup']['driver'],
            $GLOBALS['pdo_setup']['host'],
            $GLOBALS['pdo_setup']['port'],
            $GLOBALS['pdo_setup']['database'],
            $GLOBALS['pdo_setup']['username'],
            $GLOBALS['pdo_setup']['password']
        ),
        3,
        $logPath
    );
}

$GLOBALS['config'] = $config;
