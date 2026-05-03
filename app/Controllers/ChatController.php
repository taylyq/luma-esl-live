<?php

declare(strict_types=1);

namespace App\Controllers;

final class ChatController
{
    public function index(): void
    {
        $user = require_auth();
        $chats = $this->chatList($user);
        view('messages/index', ['title' => 'Messages', 'chats' => $chats]);
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
        ]);
    }

    public function start(): void
    {
        $user = require_auth('student');
        $educatorId = (int) ($_POST['educator_id'] ?? 0);
        $body = trim((string) ($_POST['message_body'] ?? 'I am interested in your next Zoom class.'));

        $teacher = db()->prepare('SELECT id FROM educator_profiles WHERE id = ? AND approval_status = "approved" LIMIT 1');
        $teacher->execute([$educatorId]);
        if (!$teacher->fetch()) {
            redirect('/teachers');
        }

        $insert = db()->prepare(insert_ignore_sql('chats', ['student_id', 'educator_id']));
        $insert->execute([$user['id'], $educatorId]);

        $chat = db()->prepare('SELECT id FROM chats WHERE student_id = ? AND educator_id = ? LIMIT 1');
        $chat->execute([$user['id'], $educatorId]);
        $chatId = (int) $chat->fetch()['id'];

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
        $this->authorizeChat($chatId, $user);

        if ($body !== '') {
            $message = db()->prepare('INSERT INTO messages (chat_id, sender_id, message_body) VALUES (?, ?, ?)');
            $message->execute([$chatId, $user['id'], $body]);
        }

        redirect('/messages/' . $chatId);
    }

    private function authorizeChat(int $chatId, array $user): array
    {
        $statement = db()->prepare(
            "SELECT c.*, su.name AS student_name, eu.name AS teacher_name, ep.user_id AS teacher_user_id
             FROM chats c
             JOIN users su ON su.id = c.student_id
             JOIN educator_profiles ep ON ep.id = c.educator_id
             JOIN users eu ON eu.id = ep.user_id
             WHERE c.id = ?
             LIMIT 1"
        );
        $statement->execute([$chatId]);
        $chat = $statement->fetch();

        if (!$chat || ((int) $chat['student_id'] !== (int) $user['id'] && (int) $chat['teacher_user_id'] !== (int) $user['id'])) {
            http_response_code(403);
            exit('Not allowed.');
        }

        return $chat;
    }

    private function chatList(array $user): array
    {
        if ($user['role'] === 'educator') {
            $profile = db()->prepare('SELECT id FROM educator_profiles WHERE user_id = ? LIMIT 1');
            $profile->execute([$user['id']]);
            $educator = $profile->fetch();

            if (!$educator) {
                return [];
            }

            $statement = db()->prepare(
                "SELECT c.id, su.name AS counterpart, MAX(m.created_at) AS last_message_at,
                    SUM(CASE WHEN m.sender_id != ? AND m.read_at IS NULL THEN 1 ELSE 0 END) AS unread_count,
                    (SELECT message_body FROM messages WHERE chat_id = c.id ORDER BY created_at DESC, id DESC LIMIT 1) AS preview
                 FROM chats c
                 JOIN users su ON su.id = c.student_id
                 LEFT JOIN messages m ON m.chat_id = c.id
                 WHERE c.educator_id = ?
                 GROUP BY c.id, su.name
                 ORDER BY last_message_at DESC"
            );
            $statement->execute([$user['id'], $educator['id']]);
            return $statement->fetchAll();
        }

        $statement = db()->prepare(
            "SELECT c.id, eu.name AS counterpart, MAX(m.created_at) AS last_message_at,
                SUM(CASE WHEN m.sender_id != ? AND m.read_at IS NULL THEN 1 ELSE 0 END) AS unread_count,
                    (SELECT message_body FROM messages WHERE chat_id = c.id ORDER BY created_at DESC, id DESC LIMIT 1) AS preview
             FROM chats c
             JOIN educator_profiles ep ON ep.id = c.educator_id
             JOIN users eu ON eu.id = ep.user_id
             LEFT JOIN messages m ON m.chat_id = c.id
             WHERE c.student_id = ?
             GROUP BY c.id, eu.name
             ORDER BY last_message_at DESC"
        );
        $statement->execute([$user['id'], $user['id']]);

        return $statement->fetchAll();
    }

    private function markRead(int $chatId, int $userId): void
    {
        $statement = db()->prepare('UPDATE messages SET read_at = CURRENT_TIMESTAMP WHERE chat_id = ? AND sender_id != ? AND read_at IS NULL');
        $statement->execute([$chatId, $userId]);
    }
}
