<?php

return [
    'app_name' => env_value('APP_NAME', 'Luma ESL'),
    'app_url' => env_value('APP_URL', 'http://localhost:8080'),
    'app_env' => env_value('APP_ENV', 'local'),
    'mail' => [
        'from' => env_value('APP_MAIL_FROM', 'hello@luma.test'),
        'use_php_mail' => filter_var(env_value('APP_USE_PHP_MAIL', false), FILTER_VALIDATE_BOOL),
    ],
    'db' => [
        'driver' => env_value('DB_DRIVER', 'sqlite'),
        'path' => __DIR__ . '/' . env_value('DB_PATH', 'database/demo.sqlite'),
        /*
        Switch back to MySQL by changing driver to mysql and using these values.
        The app's MySQL schema remains in database/schema.sql.
        */
        'host' => env_value('DB_HOST', '127.0.0.1'),
        'port' => env_value('DB_PORT', '3307'),
        'database' => env_value('DB_DATABASE', 'luma_esl'),
        'username' => env_value('DB_USERNAME', 'root'),
        'password' => env_value('DB_PASSWORD', ''),
        'charset' => env_value('DB_CHARSET', 'utf8mb4'),
    ],
];
