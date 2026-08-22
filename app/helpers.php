<?php

declare(strict_types=1);

function load_env(string $path): void
{
    if (!is_file($path)) {
        return;
    }

    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }

        if (str_starts_with($line, 'export ')) {
            $line = trim(substr($line, 7));
        }

        [$key, $value] = explode('=', $line, 2);
        $key = ltrim(trim($key), "\xEF\xBB\xBF");
        $value = trim($value);
        $value = trim($value, "\"'");

        if (getenv($key) === false) {
            putenv($key . '=' . $value);
            $_ENV[$key] = $value;
        } elseif (!array_key_exists($key, $_ENV)) {
            $_ENV[$key] = (string) getenv($key);
        }
    }
}

function env_value(string $key, mixed $default = null): mixed
{
    $value = getenv($key);
    if ($value !== false) {
        return $value;
    }

    if (array_key_exists($key, $_ENV)) {
        return $_ENV[$key];
    }

    return $default;
}

function env_location_label(string $publicRoot, string $envPath): string
{
    $publicRootPath = realpath($publicRoot) ?: $publicRoot;
    $realEnvPath = realpath($envPath) ?: $envPath;
    $publicRootPath = rtrim(str_replace('\\', '/', $publicRootPath), '/') . '/';
    $realEnvPath = str_replace('\\', '/', $realEnvPath);

    if (str_starts_with($realEnvPath, $publicRootPath)) {
        return 'Found in public_html';
    }

    return 'Found outside public_html';
}

function upload_storage_root(): string
{
    $configuredPath = trim((string) env_value('LUMA_UPLOAD_PATH', ''));
    if ($configuredPath !== '') {
        return rtrim($configuredPath, '/\\');
    }

    return dirname(__DIR__, 2) . '/luma-esl-uploads';
}

function upload_storage_path(string $folder = ''): string
{
    $root = upload_storage_root();
    $folder = trim($folder, '/\\');

    return $folder === '' ? $root : $root . '/' . $folder;
}

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

    if (!isset($GLOBALS['pdo'])) {
        return null;
    }

    static $user = null;
    if ($user && (int) $user['id'] === (int) $_SESSION['user_id']) {
        return $user;
    }

    $statement = db()->prepare(
        'SELECT id, role, name, email, email_verified_at, avatar, status, age_confirmed_at, terms_accepted_at, created_at
         FROM users WHERE id = ? LIMIT 1'
    );
    $statement->execute([$_SESSION['user_id']]);
    $user = $statement->fetch() ?: null;

    if ($user && ($user['status'] ?? '') === 'suspended') {
        unset($_SESSION['user_id']);
        session_regenerate_id(true);
        $user = null;
    }

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

    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $verificationPaths = ['/email/verify', '/email/resend', '/logout'];
    if (empty($user['email_verified_at']) && !in_array($path, $verificationPaths, true)) {
        redirect('/email/verify');
    }

    return $user;
}

function ensure_user_compliance_columns(): void
{
    static $checked = false;
    if ($checked) {
        return;
    }

    if (db_driver() === 'sqlite') {
        $columns = db()->query('PRAGMA table_info(users)')->fetchAll();
        $existing = array_column($columns, 'name');

        if (!in_array('age_confirmed_at', $existing, true)) {
            db()->exec('ALTER TABLE users ADD COLUMN age_confirmed_at TEXT NULL');
        }
        if (!in_array('terms_accepted_at', $existing, true)) {
            db()->exec('ALTER TABLE users ADD COLUMN terms_accepted_at TEXT NULL');
        }

        $checked = true;
        return;
    }

    $ageColumn = db()->query("SHOW COLUMNS FROM users LIKE 'age_confirmed_at'");
    if (!$ageColumn->fetch()) {
        db()->exec('ALTER TABLE users ADD COLUMN age_confirmed_at DATETIME NULL AFTER status');
    }

    $termsColumn = db()->query("SHOW COLUMNS FROM users LIKE 'terms_accepted_at'");
    if (!$termsColumn->fetch()) {
        db()->exec('ALTER TABLE users ADD COLUMN terms_accepted_at DATETIME NULL AFTER age_confirmed_at');
    }

    $checked = true;
}

function age_confirmed_user(?array $user = null): ?array
{
    $user = $user ?: current_user();
    if (!$user) {
        return null;
    }

    ensure_user_compliance_columns();
    $statement = db()->prepare(
        'SELECT id, role, name, email, email_verified_at, avatar, status, age_confirmed_at, terms_accepted_at, created_at
         FROM users WHERE id = ? LIMIT 1'
    );
    $statement->execute([$user['id']]);

    return $statement->fetch() ?: null;
}

function require_adult_confirmed(?array $user = null): array
{
    $user = age_confirmed_user($user);
    if (!$user) {
        redirect('/login');
    }

    if (empty($user['age_confirmed_at']) || empty($user['terms_accepted_at'])) {
        redirect('/age-confirmation');
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

function token_hash(string $token): string
{
    return hash('sha256', $token);
}

function rate_limit_identity(string $identity = ''): string
{
    $ip = trim((string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
    return substr($ip . '|' . strtolower(trim($identity)), 0, 500);
}

function rate_limit_exceeded(string $bucket, string $identity, int $maximum, int $windowSeconds): bool
{
    return count(rate_limit_attempts($bucket, $identity, $windowSeconds, false)) >= $maximum;
}

function rate_limit_hit(string $bucket, string $identity, int $windowSeconds): void
{
    rate_limit_attempts($bucket, $identity, $windowSeconds, true);
}

function rate_limit_clear(string $bucket, string $identity): void
{
    $path = rate_limit_file($bucket, $identity);
    if (is_file($path)) {
        @unlink($path);
    }
}

function rate_limit_attempts(string $bucket, string $identity, int $windowSeconds, bool $addAttempt): array
{
    $path = rate_limit_file($bucket, $identity);
    $directory = dirname($path);
    if (!is_dir($directory) && !@mkdir($directory, 0770, true) && !is_dir($directory)) {
        return [];
    }

    $handle = @fopen($path, 'c+');
    if ($handle === false || !flock($handle, LOCK_EX)) {
        if (is_resource($handle)) {
            fclose($handle);
        }
        return [];
    }

    rewind($handle);
    $decoded = json_decode((string) stream_get_contents($handle), true);
    $cutoff = time() - max(1, $windowSeconds);
    $attempts = array_values(array_filter(
        is_array($decoded) ? $decoded : [],
        static fn (mixed $timestamp): bool => is_int($timestamp) && $timestamp >= $cutoff
    ));

    if ($addAttempt) {
        $attempts[] = time();
    }

    ftruncate($handle, 0);
    rewind($handle);
    fwrite($handle, json_encode($attempts, JSON_THROW_ON_ERROR));
    fflush($handle);
    flock($handle, LOCK_UN);
    fclose($handle);

    return $attempts;
}

function rate_limit_file(string $bucket, string $identity): string
{
    $safeBucket = preg_replace('/[^a-z0-9_-]+/i', '-', $bucket) ?: 'request';
    $key = hash('sha256', rate_limit_identity($identity));
    return dirname(__DIR__) . '/storage/rate-limits/' . $safeBucket . '-' . $key . '.json';
}

function app_url(string $path = ''): string
{
    return rtrim((string) config('app_url'), '/') . '/' . ltrim($path, '/');
}

function send_app_mail(string $to, string $subject, string $body): void
{
    $headers = 'From: ' . config('mail.from') . "\r\n";
    $sent = false;

    if (config('mail.use_php_mail', false)) {
        $sent = @mail($to, $subject, $body, $headers);
    }

    if (!$sent) {
        $logPath = dirname(__DIR__) . '/storage/mail.log';
        if (!is_dir(dirname($logPath))) {
            mkdir(dirname($logPath), 0775, true);
        }

        file_put_contents(
            $logPath,
            "To: {$to}\nSubject: {$subject}\n{$body}\n---\n",
            FILE_APPEND
        );
    }
}

function unread_messages_count(?array $user = null): int
{
    $user = $user ?: current_user();
    if (!$user) {
        return 0;
    }

    $statement = db()->prepare(
        "SELECT COUNT(*)
         FROM messages m
         JOIN chats c ON c.id = m.chat_id
         WHERE m.sender_id != ?
           AND m.read_at IS NULL
           AND (c.user_one_id = ? + 0 OR c.user_two_id = ? + 0)"
    );
    $statement->execute([$user['id'], $user['id'], $user['id']]);

    return (int) $statement->fetchColumn();
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

function format_class_price(array $class): string
{
    $currency = strtoupper((string) ($class['price_currency'] ?? 'USD'));
    $price = (float) ($class['price'] ?? 0);

    if ($currency === 'FREE' || $price <= 0) {
        return 'Free';
    }

    if ($currency === 'VND') {
        return number_format($price, 0) . ' VND';
    }

    return '$' . number_format($price, 2);
}

function ensure_site_settings_table(): void
{
    static $checked = false;
    if ($checked) {
        return;
    }

    if (db_driver() === 'sqlite') {
        db()->exec(
            "CREATE TABLE IF NOT EXISTS site_settings (
                setting_key TEXT PRIMARY KEY,
                setting_value TEXT NOT NULL,
                updated_at TEXT DEFAULT CURRENT_TIMESTAMP
            )"
        );
    } else {
        db()->exec(
            "CREATE TABLE IF NOT EXISTS site_settings (
                setting_key VARCHAR(120) PRIMARY KEY,
                setting_value VARCHAR(255) NOT NULL,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )"
        );
    }

    $insert = db()->prepare(insert_ignore_sql('site_settings', ['setting_key', 'setting_value']));
    $insert->execute(['show_teacher_hourly_rates', '1']);
    $checked = true;
}

function site_setting(string $key, string $default = ''): string
{
    ensure_site_settings_table();

    static $settings = [];
    if (array_key_exists($key, $settings)) {
        return $settings[$key];
    }

    $statement = db()->prepare('SELECT setting_value FROM site_settings WHERE setting_key = ? LIMIT 1');
    $statement->execute([$key]);
    $value = $statement->fetchColumn();
    $settings[$key] = $value === false ? $default : (string) $value;

    return $settings[$key];
}

function show_teacher_hourly_rates(): bool
{
    return site_setting('show_teacher_hourly_rates', '1') === '1';
}

function ensure_class_price_currency_column(): void
{
    static $checked = false;
    if ($checked) {
        return;
    }

    if (db_driver() === 'sqlite') {
        $columns = db()->query('PRAGMA table_info(class_listings)')->fetchAll();
        foreach ($columns as $column) {
            if (($column['name'] ?? '') === 'price_currency') {
                $checked = true;
                return;
            }
        }

        db()->exec("ALTER TABLE class_listings ADD COLUMN price_currency TEXT NOT NULL DEFAULT 'USD'");
        $checked = true;
        return;
    }

    $statement = db()->query("SHOW COLUMNS FROM class_listings LIKE 'price_currency'");
    if (!$statement->fetch()) {
        db()->exec("ALTER TABLE class_listings ADD COLUMN price_currency VARCHAR(10) NOT NULL DEFAULT 'USD' AFTER price");
    }

    $checked = true;
}

function default_lesson_topics(): array
{
    return [
        ['Me and my world', 'Greetings', 'children greeting classroom'],
        ['Me and my world', 'Introducing yourself', 'child introducing self english class'],
        ['Me and my world', 'Numbers', 'children counting numbers'],
        ['Me and my world', 'Colors', 'children learning colors'],
        ['Me and my world', 'Shapes', 'children learning shapes'],
        ['Me and my world', 'Family members', 'happy family children'],
        ['Me and my world', 'Friends', 'children friends classroom'],
        ['Me and my world', 'Body parts', 'kids body parts lesson'],
        ['Me and my world', 'Feelings and emotions', 'children emotions flashcards'],
        ['Me and my world', 'Clothes', 'children clothes lesson'],
        ['Me and my world', 'Good manners', 'children saying thank you'],
        ['Me and my world', 'Healthy habits', 'children healthy habits'],
        ['School and home', 'Classroom objects', 'classroom objects school'],
        ['School and home', 'School subjects', 'school subjects books'],
        ['School and home', 'Days of the week', 'calendar days of week'],
        ['School and home', 'Months of the year', 'calendar months learning'],
        ['School and home', 'My house', 'family house children'],
        ['School and home', 'Rooms in the house', 'children bedroom home'],
        ['School and home', 'Furniture', 'home furniture kids'],
        ['School and home', 'Technology for kids', 'children tablet learning'],
        ['Food and daily life', 'Weather', 'children weather lesson'],
        ['Food and daily life', 'Seasons', 'children seasons nature'],
        ['Food and daily life', 'Food', 'kids food table'],
        ['Food and daily life', 'Drinks', 'children drinking water juice'],
        ['Food and daily life', 'Fruit and vegetables', 'fruit vegetables children'],
        ['Food and daily life', 'At the supermarket', 'children supermarket shopping'],
        ['Food and daily life', 'In the kitchen', 'children cooking kitchen'],
        ['Food and daily life', 'Daily routines', 'child morning routine'],
        ['Food and daily life', 'Telling the time', 'child clock learning time'],
        ['Play, sports, and hobbies', 'Hobbies', 'children hobbies art music'],
        ['Play, sports, and hobbies', 'Sports', 'children sports field'],
        ['Play, sports, and hobbies', 'Playing basketball outside', '/assets/img/lessons/playing-basketball-outside.jpeg'],
        ['Play, sports, and hobbies', 'At the park', 'children park'],
        ['Play, sports, and hobbies', 'At the playground', 'children playground'],
        ['Play, sports, and hobbies', 'Animals', 'children animals learning'],
        ['Play, sports, and hobbies', 'Pets', 'child pet dog'],
        ['Community and nature', 'Farm animals', 'children farm animals'],
        ['Community and nature', 'Wild animals', 'children zoo wild animals'],
        ['Community and nature', 'Jobs and occupations', 'children jobs occupations'],
        ['Community and nature', 'Community places', 'community places town children'],
        ['Community and nature', 'At the restaurant', 'family restaurant children'],
        ['Community and nature', 'At the doctor', 'child doctor visit'],
        ['Community and nature', 'Transportation', 'children transportation bus'],
        ['Community and nature', 'Road safety', 'children road safety crosswalk'],
        ['Community and nature', 'Holidays and celebrations', 'children holiday celebration'],
        ['Community and nature', 'Birthday party', 'children birthday party'],
        ['Community and nature', 'Nature', 'children nature forest'],
        ['Community and nature', 'The beach', 'children beach'],
        ['Community and nature', 'Camping', 'family camping children'],
        ['Community and nature', 'Shopping', 'children shopping bags'],
    ];
}

function lesson_image_url(string $image): string
{
    return str_starts_with($image, '/') || str_starts_with($image, 'http')
        ? $image
        : 'https://source.unsplash.com/1200x850/?' . rawurlencode($image);
}

function safe_profile_image_url(string $image): string
{
    $image = trim($image);
    if ($image === '') {
        return '';
    }

    if (str_starts_with($image, '/uploads/teachers/')) {
        $filename = basename(rawurldecode($image));
        return $filename !== '' && !in_array($filename, ['.', '..'], true)
            ? '/uploads/teachers/' . rawurlencode($filename)
            : '';
    }

    if (filter_var($image, FILTER_VALIDATE_URL) && strtolower((string) parse_url($image, PHP_URL_SCHEME)) === 'https') {
        return $image;
    }

    return '';
}

function ensure_lesson_topics_table(): void
{
    static $checked = false;
    if ($checked) {
        return;
    }

    if (db_driver() === 'sqlite') {
        db()->exec(
            "CREATE TABLE IF NOT EXISTS lesson_topics (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                unit TEXT NOT NULL,
                topic TEXT NOT NULL UNIQUE,
                image_url TEXT NOT NULL,
                sort_order INTEGER NOT NULL DEFAULT 0,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP,
                updated_at TEXT DEFAULT CURRENT_TIMESTAMP
            )"
        );
    } else {
        db()->exec(
            "CREATE TABLE IF NOT EXISTS lesson_topics (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                unit VARCHAR(120) NOT NULL,
                topic VARCHAR(190) NOT NULL UNIQUE,
                image_url VARCHAR(255) NOT NULL,
                sort_order INT NOT NULL DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )"
        );
    }

    $count = (int) db()->query('SELECT COUNT(*) FROM lesson_topics')->fetchColumn();
    if ($count === 0) {
        $insert = db()->prepare('INSERT INTO lesson_topics (unit, topic, image_url, sort_order) VALUES (?, ?, ?, ?)');
        foreach (default_lesson_topics() as $index => [$unit, $topic, $image]) {
            $insert->execute([$unit, $topic, $image, $index + 1]);
        }
    }

    $checked = true;
}
