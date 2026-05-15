<?php

$config = [
    'app_name' => env_value('APP_NAME', 'Luma ESL'),
    'app_url' => env_value('APP_URL', 'https://lumaesl.alpacatravels.com'),
    'app_env' => env_value('APP_ENV', 'production'),
    'mail' => [
        'from' => env_value('APP_MAIL_FROM', 'admin.lumaesl@gmail.com'),
        'use_php_mail' => filter_var(env_value('APP_USE_PHP_MAIL', true), FILTER_VALIDATE_BOOL),
    ],
    'db' => [
        'driver' => env_value('DB_DRIVER', 'mysql'),
        'path' => __DIR__ . '/' . env_value('DB_PATH', 'database/demo.sqlite'),
        'host' => env_value('DB_HOST', 'localhost'),
        'port' => env_value('DB_PORT', '3306'),
        'database' => env_value('DB_DATABASE', 'u223591156_lumaesl'),
        'username' => env_value('DB_USERNAME', 'u223591156_lumaesladmin'),
        'password' => env_value('DB_PASSWORD', ''),
        'charset' => env_value('DB_CHARSET', 'utf8mb4'),
    ],
];

$localConfigPath = __DIR__ . '/config.local.php';
if (is_file($localConfigPath)) {
    $localConfig = require $localConfigPath;
    if (is_array($localConfig)) {
        $config = array_replace_recursive($config, $localConfig);
    }
}

return $config;
