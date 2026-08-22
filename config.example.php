<?php

$databasePath = (string) env_value('DB_PATH', 'database/demo.sqlite');
$databasePath = str_starts_with($databasePath, '/') ? $databasePath : __DIR__ . '/' . $databasePath;

return [
    'app_name' => env_value('APP_NAME', 'Luma ESL'),
    'app_url' => env_value('APP_URL', 'http://localhost:8080'),
    'app_env' => env_value('APP_ENV', 'production'),
    'mail' => [
        'from' => env_value('APP_MAIL_FROM', 'hello@luma.test'),
        'use_php_mail' => filter_var(env_value('APP_USE_PHP_MAIL', true), FILTER_VALIDATE_BOOL),
    ],
    'db' => [
        'driver' => env_value('DB_DRIVER', 'mysql'),
        'path' => $databasePath,
        'host' => env_value('DB_HOST', '127.0.0.1'),
        'port' => env_value('DB_PORT', '3306'),
        'database' => env_value('DB_DATABASE', 'luma_esl'),
        'username' => env_value('DB_USERNAME', 'root'),
        'password' => env_value('DB_PASSWORD', ''),
        'charset' => env_value('DB_CHARSET', 'utf8mb4'),
    ],
];
