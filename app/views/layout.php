<?php $user = current_user(); $unreadCount = $user ? unread_messages_count($user) : 0; ?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? config('app_name')) ?> · <?= e(config('app_name')) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="icon" href="/favicon.png" type="image/png">
    <link rel="stylesheet" href="/assets/css/app.css?v=20260511-auth-links">
</head>
<body>
    <header class="site-header">
        <a class="brand" href="/" aria-label="<?= e(config('app_name')) ?> home">
            <img class="brand-logo" src="/assets/img/luma-esl-icon-small.png?v=1" alt="" aria-hidden="true">
            <span class="brand-text notranslate">
                <span class="brand-name"><?= e(config('app_name')) ?></span>
                <span class="brand-slogan">Speak first. Join with confidence.</span>
            </span>
        </a>
        <button class="nav-toggle" type="button" data-nav-toggle aria-label="Open navigation">☰</button>
        <nav class="site-nav" data-nav>
            <a href="/teachers" class="<?= route_is('/teachers') ? 'active' : '' ?>">Teachers</a>
            <a href="/lessons" class="<?= route_is('/lessons') ? 'active' : '' ?>">Lessons</a>
            <a href="/calendar" class="<?= route_is('/calendar') || route_is('/pricing') ? 'active' : '' ?>">Calendar</a>
            <?php if (!$user): ?>
                <label class="language-picker nav-language">Language
                    <select data-language-select>
                        <option value="en">English</option>
                        <option value="vi">Vietnamese</option>
                        <option value="es">Spanish</option>
                    </select>
                </label>
            <?php endif; ?>
            <?php if ($user): ?>
                <a href="/dashboard" class="<?= route_is('/dashboard') ? 'active' : '' ?>">Dashboard</a>
                <a href="/messages" class="<?= route_is('/messages') ? 'active' : '' ?>">Messages<?php if ($unreadCount > 0): ?><span class="nav-badge"><?= $unreadCount ?></span><?php endif; ?></a>
                <?php if ($user['role'] === 'educator'): ?>
                    <a href="/teacher/classes">Classes</a>
                <?php endif; ?>
                <?php if ($user['role'] === 'admin'): ?>
                    <a href="/admin">Admin</a>
                <?php endif; ?>
                <details class="nav-account" title="Signed in as <?= e($user['email']) ?>">
                    <summary>
                        <span class="nav-user-role"><?= e(ucfirst($user['role'])) ?></span>
                    </summary>
                    <div class="nav-account-menu">
                        <strong class="nav-user-name"><?= e($user['name']) ?></strong>
                        <form action="/logout" method="post" class="nav-form">
                            <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                            <button class="link-button" type="submit">Sign out</button>
                        </form>
                        <label class="language-picker">Language
                            <select data-language-select>
                                <option value="en">English</option>
                                <option value="vi">Vietnamese</option>
                                <option value="es">Spanish</option>
                            </select>
                        </label>
                    </div>
                </details>
            <?php else: ?>
                <a href="/login">Sign in</a>
                <a class="button button-dark" href="/register">Get started</a>
            <?php endif; ?>
        </nav>
    </header>

    <?php if ($message = flash('success')): ?>
        <div class="flash success"><?= e($message) ?></div>
    <?php endif; ?>
    <?php if ($message = flash('error')): ?>
        <div class="flash error"><?= e($message) ?></div>
    <?php endif; ?>

    <main>
        <?php require dirname(__DIR__) . '/views/' . $template . '.php'; ?>
    </main>

    <footer class="site-footer">
        <div>
            <strong><?= e(config('app_name')) ?></strong>
            <span>Speak first. Join with confidence. Verified ESL educators, calm booking, and Zoom classes that feel easy to enter.</span>
        </div>
        <div class="footer-contact">
            <span>Contact</span>
            <a class="notranslate" href="mailto:admin.lumaesl@gmail.com">admin.lumaesl@gmail.com</a>
        </div>
    </footer>
    <div id="google_translate_element" aria-hidden="true"></div>
    <script src="/assets/js/app.js?v=20260511-auth-links"></script>
    <script src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>
</body>
</html>
