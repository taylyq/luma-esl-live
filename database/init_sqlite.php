<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$dbPath = $root . '/database/demo.sqlite';
$pdo = new PDO('sqlite:' . $dbPath);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec('PRAGMA foreign_keys = ON');
$pdo->exec(file_get_contents($root . '/database/sqlite_schema.sql'));
$pdo->exec(file_get_contents($root . '/database/sqlite_seed.sql'));

echo "Created demo database: {$dbPath}\n";
