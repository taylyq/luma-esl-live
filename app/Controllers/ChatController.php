<?php

declare(strict_types=1);

namespace App\Controllers;

final class ChatController
{
    public function index(): void
    {
        $user = require_auth();
        view('messages/index', ['title' => 'Messages', 'chats' => $this->chatList($user)]);
    }

    public function show(): void
    {
        $user = require_auth();
        $chatId = (int) ($_GET['id'] ?? 0);
        $chat = $this->authorizeChat($chatId, $user);
        $this->markRead($chatId, (int) $user['id']);

        $messages = db()->prepare(
            "SELECT m.*, u.name AS sender_name
             FROM messages m
             JOIN users u ON u.id = m.sender_id
             WHERE m.chat_id = ?
             ORDER BY m.created_at"
        );
        $messages->execute([$chatId]);

        view('messages/show', [
            'title' => 'Conversation',
            'chat' => $chat,
            'messages' => $messages->fetchAll(),
            'chats' => $this->chatList($user),
            'blockedByMe' => $this->hasBlock((int) $user['id'], (int) $chat['counterpart_id']),
            'blockedByCounterpart' => $this->hasBlock((int) $chat['counterpart_id'], (int) $user['id']),
            'canUseSafetyActions' => $this->canUseSafetyActions($user, $chat),
        ]);
    }

    public function start(): void
    {
        $user = require_auth();
        $recipientId = (int) ($_POST['recipient_id'] ?? 0);
        $educatorId = (int) ($_POST['educator_id'] ?? 0);
        $body = trim((string) ($_POST['message_body'] ?? 'I am interested in your next Zoom class.'));

        if ($recipientId === 0 && $educatorId > 0) {
            $teacher = db()->prepare('SELECT id, user_id FROM educator_profiles WHERE id = ? AND approval_status = "approved" LIMIT 1');
            $teacher->execute([$educatorId]);
            $profile = $teacher->fetch();
            if (!$profile) {
                redirect('/teachers');
            }
            $recipientId = (int) $profile['user_id'];
        }

        $recipient = $this->recipient($recipientId);
        if (!$recipient || !$this->canMessage($user, $recipient)) {
            flash('error', 'That conversation is not allowed.');
            redirect($user['role'] === 'admin' ? '/admin' : '/messages');
        }

        if ($this->isBlockedConversation($user, $recipient)) {
            flash('error', 'This conversation is blocked.');
            redirect('/messages');
        }

        $chatId = $this->findOrCreateChat((int) $user['id'], (int) $recipient['id']);

        if ($body !== '') {
            $message = db()->prepare('INSERT INTO messages (chat_id, sender_id, message_body) VALUES (?, ?, ?)');
            $message->execute([$chatId, $user['id'], $body]);
        }

        redirect('/messages/' . $chatId);
    }

    public function send(): void
    {
        $user = require_auth();
        $chatId = (int) ($_POST['chat_id'] ?? 0);
        $body = trim((string) ($_POST['message_body'] ?? ''));
        $chat = $this->authorizeChat($chatId, $user);

        if ($this->isBlockedConversation($user, [
            'id' => $chat['counterpart_id'],
            'role' => $chat['counterpart_role'],
        ])) {
            flash('error', 'This conversation is blocked.');
            redirect('/messages/' . $chatId);
        }

        if ($body !== '') {
            $message = db()->prepare('INSERT INTO messages (chat_id, sender_id, message_body) VALUES (?, ?, ?)');
            $message->execute([$chatId, $user['id'], $body]);
        }

        redirect('/messages/' . $chatId);
    }

    public function block(): void
    {
        $user = require_auth();
        $chatId = (int) ($_POST['chat_id'] ?? 0);
        $chat = $this->authorizeChat($chatId, $user);

        if (!$this->canUseSafetyActions($user, $chat)) {
            flash('error', 'Admins cannot be blocked.');
            redirect('/messages/' . $chatId);
        }

        $this->ensureMessageSafetyTables();

        $this->insertIgnore(
            'INSERT INTO message_blocks (blocker_id, blocked_user_id) VALUES (?, ?)',
            [(int) $user['id'], (int) $chat['counterpart_id']]
        );

        flash('success', 'User blocked. They can no longer message you.');
        redirect('/messages/' . $chatId);
    }

    public function report(): void
    {
        $user = require_auth();
        $chatId = (int) ($_POST['chat_id'] ?? 0);
        $messageId = (int) ($_POST['message_id'] ?? 0);
        $reason = (string) ($_POST['reason'] ?? 'other');
        $details = trim((string) ($_POST['details'] ?? ''));
        $chat = $this->authorizeChat($chatId, $user);

        if (!$this->canUseSafetyActions($user, $chat)) {
            flash('error', 'Admins cannot be reported.');
            redirect('/messages/' . $chatId);
        }

        if (!in_array($reason, ['spam', 'inappropriate', 'safety', 'other'], true)) {
            $reason = 'other';
        }

        if ($messageId > 0) {
            $message = db()->prepare('SELECT sender_id FROM messages WHERE id = ? AND chat_id = ? LIMIT 1');
            $message->execute([$messageId, $chatId]);
            $row = $message->fetch();
            if (!$row || (int) $row['sender_id'] !== (int) $chat['counterpart_id']) {
                flash('error', 'Only messages from the other person can be reported.');
                redirect('/messages/' . $chatId);
            }
        } else {
            $messageId = null;
        }

        $this->ensureMessageSafetyTables();

        $statement = db()->prepare(
            'INSERT INTO message_reports (reporter_id, reported_user_id, chat_id, message_id, reason, details) VALUES (?, ?, ?, ?, ?, ?)'
        );
        $statement->execute([
            (int) $user['id'],
            (int) $chat['counterpart_id'],
            $chatId,
            $messageId,
            $reason,
            $details !== '' ? $details : null,
        ]);

        flash('success', 'Report sent to the admin team.');
        redirect('/messages/' . $chatId);
    }

    private function authorizeChat(int $chatId, array $user): array
    {
        $statement = db()->prepare(
            "SELECT c.*,
                other_user.id AS counterpart_id,
                other_user.name AS counterpart,
                other_user.role AS counterpart_role
             FROM chats c
             JOIN users other_user ON other_user.id = CASE WHEN c.user_one_id = ? + 0 THEN c.user_two_id ELSE c.user_one_id END
             WHERE c.id = ? AND (c.user_one_id = ? + 0 OR c.user_two_id = ? + 0)
             LIMIT 1"
        );
        $statement->execute([$user['id'], $chatId, $user['id'], $user['id']]);
        $chat = $statement->fetch();

        if (!$chat) {
            http_response_code(403);
            exit('Not allowed.');
        }

        return $chat;
    }

    private function chatList(array $user): array
    {
        $statement = db()->prepare(
            "SELECT c.id,
                other_user.name AS counterpart,
                other_user.role AS counterpart_role,
                MAX(c.created_at) AS chat_created_at,
                MAX(m.created_at) AS last_message_at,
                SUM(CASE WHEN m.sender_id != ? AND m.read_at IS NULL THEN 1 ELSE 0 END) AS unread_count,
                (SELECT message_body FROM messages WHERE chat_id = c.id ORDER BY created_at DESC, id DESC LIMIT 1) AS preview
             FROM chats c
             JOIN users other_user ON other_user.id = CASE WHEN c.user_one_id = ? + 0 THEN c.user_two_id ELSE c.user_one_id END
             LEFT JOIN messages m ON m.chat_id = c.id
             WHERE c.user_one_id = ? + 0 OR c.user_two_id = ? + 0
             GROUP BY c.id, other_user.name, other_user.role
             ORDER BY last_message_at DESC, chat_created_at DESC"
        );
        $statement->execute([$user['id'], $user['id'], $user['id'], $user['id']]);

        return $statement->fetchAll();
    }

    private function findOrCreateChat(int $senderId, int $recipientId): int
    {
        $one = min($senderId, $recipientId);
        $two = max($senderId, $recipientId);

        $existing = db()->prepare('SELECT id FROM chats WHERE user_one_id = ? AND user_two_id = ? LIMIT 1');
        $existing->execute([$one, $two]);
        $chat = $existing->fetch();
        if ($chat) {
            return (int) $chat['id'];
        }

        $studentId = null;
        $educatorId = null;
        foreach ([$senderId, $recipientId] as $userId) {
            $user = $this->recipient($userId);
            if ($user && $user['role'] === 'student') {
                $studentId = $userId;
            }
            if ($user && $user['role'] === 'educator') {
                $profile = db()->prepare('SELECT id FROM educator_profiles WHERE user_id = ? LIMIT 1');
                $profile->execute([$userId]);
                $row = $profile->fetch();
                $educatorId = $row ? (int) $row['id'] : null;
            }
        }

        $insert = db()->prepare('INSERT INTO chats (user_one_id, user_two_id, student_id, educator_id) VALUES (?, ?, ?, ?)');
        $insert->execute([$one, $two, $studentId, $educatorId]);

        return (int) db()->lastInsertId();
    }

    private function canMessage(array $sender, array $recipient): bool
    {
        if ((int) $sender['id'] === (int) $recipient['id']) {
            return false;
        }

        return !($sender['role'] === 'student' && $recipient['role'] === 'student');
    }

    private function canUseSafetyActions(array $user, array $chat): bool
    {
        return $user['role'] !== 'admin' && $chat['counterpart_role'] !== 'admin';
    }

    private function isBlockedConversation(array $sender, array $recipient): bool
    {
        if ($sender['role'] === 'admin' || $recipient['role'] === 'admin') {
            return false;
        }

        return $this->hasBlock((int) $sender['id'], (int) $recipient['id'])
            || $this->hasBlock((int) $recipient['id'], (int) $sender['id']);
    }

    private function hasBlock(int $blockerId, int $blockedUserId): bool
    {
        try {
            $statement = db()->prepare('SELECT id FROM message_blocks WHERE blocker_id = ? AND blocked_user_id = ? LIMIT 1');
            $statement->execute([$blockerId, $blockedUserId]);

            return (bool) $statement->fetch();
        } catch (\Throwable) {
            return false;
        }
    }

    private function ensureMessageSafetyTables(): void
    {
        $driver = db()->getAttribute(\PDO::ATTR_DRIVER_NAME);

        if ($driver === 'sqlite') {
            db()->exec("CREATE TABLE IF NOT EXISTS message_blocks (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                blocker_id INTEGER NOT NULL,
                blocked_user_id INTEGER NOT NULL,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP,
                UNIQUE (blocker_id, blocked_user_id),
                FOREIGN KEY (blocker_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (blocked_user_id) REFERENCES users(id) ON DELETE CASCADE
            )");

            db()->exec("CREATE TABLE IF NOT EXISTS message_reports (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                reporter_id INTEGER NOT NULL,
                reported_user_id INTEGER NOT NULL,
                chat_id INTEGER NOT NULL,
                message_id INTEGER NULL,
                reason TEXT NOT NULL DEFAULT 'other' CHECK (reason IN ('spam','inappropriate','safety','other')),
                details TEXT NULL,
                status TEXT NOT NULL DEFAULT 'open' CHECK (status IN ('open','reviewed','dismissed')),
                created_at TEXT DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (reporter_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (reported_user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (chat_id) REFERENCES chats(id) ON DELETE CASCADE,
                FOREIGN KEY (message_id) REFERENCES messages(id) ON DELETE SET NULL
            )");
            return;
        }

        db()->exec("CREATE TABLE IF NOT EXISTS message_blocks (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            blocker_id BIGINT UNSIGNED NOT NULL,
            blocked_user_id BIGINT UNSIGNED NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_message_block (blocker_id, blocked_user_id),
            CONSTRAINT fk_message_blocks_blocker FOREIGN KEY (blocker_id) REFERENCES users(id) ON DELETE CASCADE,
            CONSTRAINT fk_message_blocks_blocked FOREIGN KEY (blocked_user_id) REFERENCES users(id) ON DELETE CASCADE
        )");

        db()->exec("CREATE TABLE IF NOT EXISTS message_reports (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            reporter_id BIGINT UNSIGNED NOT NULL,
            reported_user_id BIGINT UNSIGNED NOT NULL,
            chat_id BIGINT UNSIGNED NOT NULL,
            message_id BIGINT UNSIGNED NULL,
            reason ENUM('spam','inappropriate','safety','other') NOT NULL DEFAULT 'other',
            details TEXT NULL,
            status ENUM('open','reviewed','dismissed') NOT NULL DEFAULT 'open',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_message_reports_reporter FOREIGN KEY (reporter_id) REFERENCES users(id) ON DELETE CASCADE,
            CONSTRAINT fk_message_reports_reported FOREIGN KEY (reported_user_id) REFERENCES users(id) ON DELETE CASCADE,
            CONSTRAINT fk_message_reports_chat FOREIGN KEY (chat_id) REFERENCES chats(id) ON DELETE CASCADE,
            CONSTRAINT fk_message_reports_message FOREIGN KEY (message_id) REFERENCES messages(id) ON DELETE SET NULL
        )");
    }

    private function insertIgnore(string $mysqlSql, array $params): void
    {
        $driver = db()->getAttribute(\PDO::ATTR_DRIVER_NAME);
        $sql = $driver === 'sqlite'
            ? str_replace('INSERT INTO', 'INSERT OR IGNORE INTO', $mysqlSql)
            : str_replace('INSERT INTO', 'INSERT IGNORE INTO', $mysqlSql);

        $statement = db()->prepare($sql);
        $statement->execute($params);
    }

    private function recipient(int $id): ?array
    {
        $statement = db()->prepare('SELECT id, name, email, role, status FROM users WHERE id = ? AND status != "suspended" LIMIT 1');
        $statement->execute([$id]);

        return $statement->fetch() ?: null;
    }

    private function markRead(int $chatId, int $userId): void
    {
        $statement = db()->prepare('UPDATE messages SET read_at = CURRENT_TIMESTAMP WHERE chat_id = ? AND sender_id != ? AND read_at IS NULL');
        $statement->execute([$chatId, $userId]);
    }
}
