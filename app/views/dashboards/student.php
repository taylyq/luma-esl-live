<section class="app-shell">
    <div class="app-heading">
        <span class="eyebrow">Student dashboard</span>
        <h1>Your next English steps.</h1>
        <a class="button button-dark" href="/teachers">Browse teachers</a>
    </div>
    <div class="dashboard-grid">
        <article class="panel large">
            <h2>Upcoming approved classes</h2>
            <?php foreach ($classes as $class): ?>
                <div class="list-row">
                    <div><strong><?= e($class['title']) ?></strong><span><?= e($class['teacher_name']) ?> · <?= date('M j, g:i A', strtotime($class['start_time'])) ?></span></div>
                    <a class="button button-light" href="<?= e($class['zoom_link']) ?>" target="_blank" rel="noreferrer">Join Zoom</a>
                </div>
            <?php endforeach; ?>
            <?php if (!$classes): ?><div class="empty-state small">No approved classes yet.</div><?php endif; ?>
        </article>
        <article class="panel">
            <h2>Requests</h2>
            <?php foreach ($requests as $request): ?>
                <div class="list-row simple"><strong><?= e($request['title']) ?></strong><span class="status <?= e($request['status']) ?>"><?= e($request['status']) ?></span></div>
            <?php endforeach; ?>
            <?php if (!$requests): ?><div class="empty-state small">Request a class from a teacher profile.</div><?php endif; ?>
        </article>
        <article class="panel">
            <h2>Recent messages</h2>
            <?php foreach ($messages as $chat): ?>
                <a class="list-row simple" href="/messages/<?= (int) $chat['id'] ?>"><strong><?= e($chat['teacher_name']) ?><?php if ((int) $chat['unread_count'] > 0): ?> <span class="unread-pill"><?= (int) $chat['unread_count'] ?></span><?php endif; ?></strong><span><?= $chat['last_message_at'] ? date('M j', strtotime($chat['last_message_at'])) : 'New chat' ?></span></a>
            <?php endforeach; ?>
            <?php if (!$messages): ?><div class="empty-state small">No messages yet.</div><?php endif; ?>
        </article>
    </div>
</section>
