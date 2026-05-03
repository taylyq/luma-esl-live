<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if (isset($GLOBALS['pdo_error'])) {
    view('error', ['title' => 'Database setup needed', 'message' => $GLOBALS['pdo_error']]);
    exit;
}

if ($method === 'POST') {
    verify_csrf();
}

$routes = require dirname(__DIR__) . '/app/routes.php';
$handler = $routes[$method][$path] ?? null;

if (!$handler && preg_match('#^/teachers/(\d+)$#', $path, $matches)) {
    $handler = ['TeacherController', 'show'];
    $_GET['id'] = $matches[1];
}

if (!$handler && preg_match('#^/messages/(\d+)$#', $path, $matches)) {
    $handler = ['ChatController', 'show'];
    $_GET['id'] = $matches[1];
}

if (!$handler) {
    http_response_code(404);
    view('error', ['title' => 'Page not found', 'message' => 'The page you requested does not exist.']);
    exit;
}

[$controller, $action] = $handler;
$class = 'App\\Controllers\\' . $controller;
(new $class())->$action();
