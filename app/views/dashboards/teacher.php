<section class="app-shell">
    <div class="app-heading">
        <span class="eyebrow">Teacher dashboard</span>
        <h1>Today’s teaching cockpit.</h1>
        <a class="button button-dark" href="/teacher/classes">Manage classes</a>
    </div>
    <div class="dashboard-grid">
        <article class="panel">
            <h2>Profile health</h2>
            <div class="progress"><span style="width: <?= $profile['approval_status'] === 'approved' ? '92' : '56' ?>%"></span></div>
            <p>Status: <strong><?= e($profile['approval_status']) ?></strong></p>
            <a href="/teacher/profile" class="button button-light full">Edit profile</a>
        </article>
        <article class="panel large">
            <h2>Upcoming classes</h2>
            <?php foreach ($classes as $class): ?>
                <div class="list-row">
                    <div><strong><?= e($class['title']) ?></strong><span><?= date('M j, g:i A', strtotime($class['start_time'])) ?> · <?= e($class['class_type']) ?></span></div>
                    <span class="status"><?= e($class['status']) ?></span>
                </div>
            <?php endforeach; ?>
            <?php if (!$classes): ?><div class="empty-state small">No upcoming classes.</div><?php endif; ?>
        </article>
        <article class="panel large">
            <h2>Classes taught</h2>
            <?php foreach ($passedClasses as $class): ?>
                <div class="list-row">
                    <div><strong><?= e($class['title']) ?></strong><span><?= date('M j, g:i A', strtotime($class['start_time'])) ?> · <?= e($class['class_type']) ?></span></div>
                    <span class="status passed">Passed</span>
                </div>
            <?php endforeach; ?>
            <?php if (!$passedClasses): ?><div class="empty-state small">No passed classes yet.</div><?php endif; ?>
        </article>
        <article class="panel">
            <h2>New requests</h2>
            <?php foreach ($requests as $request): ?>
                <div class="request-mini">
                    <strong><?= e($request['student_name']) ?></strong>
                    <span><?= e($request['title']) ?></span>
                </div>
            <?php endforeach; ?>
            <?php if (!$requests): ?><div class="empty-state small">No pending requests.</div><?php endif; ?>
        </article>
        <article class="panel">
            <h2>Inbox</h2>
            <?php foreach ($messages as $chat): ?>
                <a class="list-row simple" href="/messages/<?= (int) $chat['id'] ?>"><strong><?= e($chat['student_name']) ?><?php if ((int) $chat['unread_count'] > 0): ?> <span class="unread-pill"><?= (int) $chat['unread_count'] ?></span><?php endif; ?></strong><span><?= $chat['last_message_at'] ? date('M j', strtotime($chat['last_message_at'])) : 'New chat' ?></span></a>
            <?php endforeach; ?>
        </article>
    </div>
</section>
