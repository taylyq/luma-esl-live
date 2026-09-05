<?php

declare(strict_types=1);

$publicRoot = __DIR__ . '/public';
$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$requestedFile = realpath($publicRoot . $requestPath);
$publicRootReal = realpath($publicRoot);

if (
    $publicRootReal !== false
    && $requestedFile !== false
    && str_starts_with($requestedFile, $publicRootReal . DIRECTORY_SEPARATOR)
    && is_file($requestedFile)
) {
    return false;
}

require $publicRoot . '/index.php';
