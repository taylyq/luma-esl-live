<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
header('X-Permitted-Cross-Domain-Policies: none');

if ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (int) ($_SERVER['SERVER_PORT'] ?? 0) === 443) {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if (isset($GLOBALS['pdo_error'])) {
    $isProduction = strtolower((string) config('app_env', 'production')) === 'production';
    http_response_code(503);
    header('Retry-After: 300');
    view('error', [
        'title' => $isProduction ? 'Temporarily unavailable' : 'Database setup needed',
        'message' => $GLOBALS['pdo_error'],
        'details' => $GLOBALS['pdo_setup'] ?? [],
    ]);
    exit;
}

if ($method === 'POST') {
    verify_csrf();
}

$routes = require dirname(__DIR__) . '/app/routes.php';
$routeMethod = $method === 'HEAD' ? 'GET' : $method;
$handler = $routes[$routeMethod][$path] ?? null;

if (!$handler && preg_match('#^/teachers/(\d+)$#', $path, $matches)) {
    $handler = ['TeacherController', 'show'];
    $_GET['id'] = $matches[1];
}

if (!$handler && preg_match('#^/messages/(\d+)$#', $path, $matches)) {
    $handler = ['ChatController', 'show'];
    $_GET['id'] = $matches[1];
}

if (!$handler && preg_match('#^/uploads/lessons/(.+)$#', $path, $matches)) {
    $handler = ['UploadController', 'lesson'];
    $_GET['filename'] = $matches[1];
}

if (!$handler && preg_match('#^/uploads/teachers/(.+)$#', $path, $matches)) {
    $handler = ['UploadController', 'teacher'];
    $_GET['filename'] = $matches[1];
}

if (!$handler) {
    http_response_code(404);
    view('error', ['title' => 'Page not found', 'message' => 'The page you requested does not exist.']);
    exit;
}

[$controller, $action] = $handler;
$class = 'App\\Controllers\\' . $controller;
(new $class())->$action();
