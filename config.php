<?php

return [
    'app_name' => 'Luma ESL',
    'app_url' => 'http://localhost:8080',
    'db' => [
        'driver' => 'sqlite',
        'path' => __DIR__ . '/database/demo.sqlite',
        /*
        Switch back to MySQL by changing driver to mysql and using these values.
        The app's MySQL schema remains in database/schema.sql.
        */
        'host' => '127.0.0.1',
        'port' => '3307',
        'database' => 'luma_esl',
        'username' => 'root',
        'password' => '',
        'charset' => 'utf8mb4',
    ],
];
