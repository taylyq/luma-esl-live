<?php

declare(strict_types=1);

namespace App\Controllers;

final class DashboardController
{
    public function index(): void
    {
        $user = require_auth();

        if ($user['role'] === 'admin') {
            redirect('/admin');
        }

        if ($user['role'] === 'educator') {
            $profile = $this->educatorProfile((int) $user['id']);
            $classes = db()->prepare("SELECT * FROM class_listings WHERE educator_id = ? ORDER BY start_time LIMIT 5");
            $classes->execute([$profile['id']]);

            $requests = db()->prepare(
                "SELECT cr.*, cl.title, u.name AS student_name
                 FROM class_requests cr
                 JOIN class_listings cl ON cl.id = cr.class_id
                 JOIN users u ON u.id = cr.student_id
                 WHERE cl.educator_id = ? AND cr.status = 'pending'
                 ORDER BY cr.created_at DESC"
            );
            $requests->execute([$profile['id']]);

            $messages = db()->prepare(
                "SELECT c.id, u.name AS student_name, MAX(m.created_at) AS last_message_at
                 FROM chats c
                 JOIN users u ON u.id = c.student_id
                 LEFT JOIN messages m ON m.chat_id = c.id
                 WHERE c.educator_id = ?
                 GROUP BY c.id, u.name
                 ORDER BY last_message_at DESC
                 LIMIT 5"
            );
            $messages->execute([$profile['id']]);

            view('dashboards/teacher', [
                'title' => 'Teacher dashboard',
                'profile' => $profile,
                'classes' => $classes->fetchAll(),
                'requests' => $requests->fetchAll(),
                'messages' => $messages->fetchAll(),
            ]);
            return;
        }

        $classes = db()->prepare(
            "SELECT e.*, cl.title, cl.start_time, cl.end_time, cl.zoom_link, u.name AS teacher_name
             FROM enrollments e
             JOIN class_listings cl ON cl.id = e.class_id
             JOIN educator_profiles ep ON ep.id = cl.educator_id
             JOIN users u ON u.id = ep.user_id
             WHERE e.student_id = ?
             ORDER BY cl.start_time
             LIMIT 5"
        );
        $classes->execute([$user['id']]);

        $requests = db()->prepare(
            "SELECT cr.*, cl.title, u.name AS teacher_name
             FROM class_requests cr
             JOIN class_listings cl ON cl.id = cr.class_id
             JOIN educator_profiles ep ON ep.id = cl.educator_id
             JOIN users u ON u.id = ep.user_id
             WHERE cr.student_id = ?
             ORDER BY cr.created_at DESC
             LIMIT 5"
        );
        $requests->execute([$user['id']]);

        $messages = db()->prepare(
            "SELECT c.id, u.name AS teacher_name, MAX(m.created_at) AS last_message_at
             FROM chats c
             JOIN educator_profiles ep ON ep.id = c.educator_id
             JOIN users u ON u.id = ep.user_id
             LEFT JOIN messages m ON m.chat_id = c.id
             WHERE c.student_id = ?
             GROUP BY c.id, u.name
             ORDER BY last_message_at DESC
             LIMIT 5"
        );
        $messages->execute([$user['id']]);

        view('dashboards/student', [
            'title' => 'Student dashboard',
            'classes' => $classes->fetchAll(),
            'requests' => $requests->fetchAll(),
            'messages' => $messages->fetchAll(),
        ]);
    }

    private function educatorProfile(int $userId): array
    {
        $statement = db()->prepare('SELECT * FROM educator_profiles WHERE user_id = ? LIMIT 1');
        $statement->execute([$userId]);

        return $statement->fetch();
    }
}
