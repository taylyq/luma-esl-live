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
                <div class="chat-header-actions">
                    <span class="badge"><?= e($chat['counterpart_role']) ?></span>
                    <?php if ($canUseSafetyActions && !$blockedByMe): ?>
                        <form method="post" action="/messages/block" onsubmit="return confirm('Block this user from messaging you?');">
                            <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                            <input type="hidden" name="chat_id" value="<?= (int) $chat['id'] ?>">
                            <button class="button button-light button-small" type="submit">Block</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
            <?php if ($blockedByMe): ?>
                <div class="conversation-notice">You blocked this user. Messaging is paused for this conversation.</div>
            <?php elseif ($blockedByCounterpart): ?>
                <div class="conversation-notice">Messaging is paused for this conversation.</div>
            <?php else: ?>
                <div class="quick-prompts" data-prompts>
                    <button type="button">Can I join your next Zoom class?</button>
                    <button type="button">What level is this class for?</button>
                    <button type="button">Do you teach Vietnamese speakers?</button>
                </div>
            <?php endif; ?>
            <div class="message-stream">
                <?php foreach ($messages as $message): ?>
                    <?php $isMine = (int) $message['sender_id'] === (int) $user['id']; ?>
                    <div class="bubble <?= $isMine ? 'mine' : '' ?>">
                        <p><?= nl2br(e($message['message_body'])) ?></p>
                        <span><?= date('M j, g:i A', strtotime($message['created_at'])) ?></span>
                        <?php if (!$isMine && $canUseSafetyActions): ?>
                            <form method="post" action="/messages/report" class="message-action-form">
                                <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                                <input type="hidden" name="chat_id" value="<?= (int) $chat['id'] ?>">
                                <input type="hidden" name="message_id" value="<?= (int) $message['id'] ?>">
                                <input type="hidden" name="reason" value="inappropriate">
                                <button type="submit">Report message</button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php if (!$blockedByMe && !$blockedByCounterpart): ?>
                <form method="post" action="/messages/send" class="composer">
                    <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="chat_id" value="<?= (int) $chat['id'] ?>">
                    <input name="message_body" data-message-input placeholder="Write a thoughtful message..." autocomplete="off" required>
                    <button class="button button-dark" type="submit">Send</button>
                </form>
            <?php else: ?>
                <div class="composer composer-disabled">Message sending is disabled.</div>
            <?php endif; ?>
        </article>
    </div>
</section>
