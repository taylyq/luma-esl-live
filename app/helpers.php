<?php

declare(strict_types=1);

function db(): PDO
{
    if (!isset($GLOBALS['pdo'])) {
        throw new RuntimeException($GLOBALS['pdo_error'] ?? 'Database connection failed.');
    }

    return $GLOBALS['pdo'];
}

function config(string $key, mixed $default = null): mixed
{
    $value = $GLOBALS['config'] ?? [];
    foreach (explode('.', $key) as $segment) {
        if (!is_array($value) || !array_key_exists($segment, $value)) {
            return $default;
        }
        $value = $value[$segment];
    }

    return $value;
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): never
{
    header('Location: ' . $path);
    exit;
}

function current_user(): ?array
{
    if (empty($_SESSION['user_id'])) {
        return null;
    }

    static $user = null;
    if ($user && (int) $user['id'] === (int) $_SESSION['user_id']) {
        return $user;
    }

    $statement = db()->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
    $statement->execute([$_SESSION['user_id']]);
    $user = $statement->fetch() ?: null;

    return $user;
}

function require_auth(?string $role = null): array
{
    $user = current_user();
    if (!$user) {
        redirect('/login');
    }

    if ($role && $user['role'] !== $role) {
        redirect('/dashboard');
    }

    return $user;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function verify_csrf(): void
{
    $token = $_POST['_token'] ?? '';
    if (!is_string($token) || !hash_equals(csrf_token(), $token)) {
        http_response_code(419);
        exit('Invalid session token.');
    }
}

function flash(string $key, ?string $message = null): ?string
{
    if ($message !== null) {
        $_SESSION['flash'][$key] = $message;
        return null;
    }

    $message = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);

    return $message;
}

function view(string $template, array $data = []): void
{
    extract($data, EXTR_SKIP);
    require dirname(__DIR__) . '/app/views/layout.php';
}

function post_value(string $key, string $default = ''): string
{
    return e($_POST[$key] ?? $default);
}

function route_is(string $path): bool
{
    return parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) === $path;
}

function db_driver(): string
{
    return (string) config('db.driver', 'mysql');
}

function insert_ignore_sql(string $table, array $columns): string
{
    $columnList = implode(', ', $columns);
    $placeholders = implode(', ', array_fill(0, count($columns), '?'));

    if (db_driver() === 'sqlite') {
        return "INSERT OR IGNORE INTO {$table} ({$columnList}) VALUES ({$placeholders})";
    }

    return "INSERT IGNORE INTO {$table} ({$columnList}) VALUES ({$placeholders})";
}
