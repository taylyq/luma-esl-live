<?php $user = current_user(); ?>
<section class="app-shell">
    <div class="messages-shell">
        <aside class="chat-list">
            <?php foreach ($chats as $item): ?>
                <a href="/messages/<?= (int) $item['id'] ?>" class="chat-list-item <?= (int) $item['id'] === (int) $chat['id'] ? 'active' : '' ?>">
                    <strong><?= e($item['counterpart']) ?><?php if ((int) $item['unread_count'] > 0): ?><span class="unread-pill"><?= (int) $item['unread_count'] ?></span><?php endif; ?></strong>
                    <span><?= e($item['preview'] ?: 'No messages yet') ?></span>
                </a>
            <?php endforeach; ?>
        </aside>
        <article class="chat-panel">
            <div class="chat-header">
                <div>
                    <span class="eyebrow">Conversation</span>
                    <h1><?= e($chat['counterpart']) ?></h1>
                </div>
                <span class="badge"><?= e($chat['counterpart_role']) ?></span>
            </div>
            <div class="quick-prompts" data-prompts>
                <button type="button">Can I join your next Zoom class?</button>
                <button type="button">What level is this class for?</button>
                <button type="button">Do you teach Vietnamese speakers?</button>
            </div>
            <div class="message-stream">
                <?php foreach ($messages as $message): ?>
                    <div class="bubble <?= (int) $message['sender_id'] === (int) $user['id'] ? 'mine' : '' ?>">
                        <p><?= nl2br(e($message['message_body'])) ?></p>
                        <span><?= date('M j, g:i A', strtotime($message['created_at'])) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
            <form method="post" action="/messages/send" class="composer">
                <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="chat_id" value="<?= (int) $chat['id'] ?>">
                <input name="message_body" data-message-input placeholder="Write a thoughtful message..." autocomplete="off" required>
                <button class="button button-dark" type="submit">Send</button>
            </form>
        </article>
    </div>
</section>
