<section class="app-shell">
    <div class="app-heading">
        <span class="eyebrow">Messages</span>
        <h1>Your conversations.</h1>
        <a class="button button-dark" href="/teachers">Find teachers</a>
    </div>
    <div class="messages-shell solo">
        <aside class="chat-list">
            <?php foreach ($chats as $chat): ?>
                <a href="/messages/<?= (int) $chat['id'] ?>" class="chat-list-item">
                    <strong><?= e($chat['counterpart']) ?><?php if ((int) $chat['unread_count'] > 0): ?><span class="unread-pill"><?= (int) $chat['unread_count'] ?></span><?php endif; ?></strong>
                    <span><?= e(ucfirst($chat['counterpart_role'])) ?> · <?= e($chat['preview'] ?: 'No messages yet') ?></span>
                </a>
            <?php endforeach; ?>
            <?php if (!$chats): ?><div class="empty-state small">Start a chat from a teacher profile.</div><?php endif; ?>
        </aside>
    </div>
</section>
