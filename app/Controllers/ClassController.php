<?php

declare(strict_types=1);

namespace App\Controllers;

final class ClassController
{
    public function index(): void
    {
        $user = require_auth('educator');
        $profile = $this->profile($user);

        $classes = db()->prepare('SELECT * FROM class_listings WHERE educator_id = ? ORDER BY start_time DESC');
        $classes->execute([$profile['id']]);

        $requests = db()->prepare(
            "SELECT cr.*, cl.title, u.name AS student_name
             FROM class_requests cr
             JOIN class_listings cl ON cl.id = cr.class_id
             JOIN users u ON u.id = cr.student_id
             WHERE cl.educator_id = ?
             ORDER BY cr.created_at DESC"
        );
        $requests->execute([$profile['id']]);

        view('teacher/classes', [
            'title' => 'Classes',
            'classes' => $classes->fetchAll(),
            'requests' => $requests->fetchAll(),
        ]);
    }

    public function store(): void
    {
        $user = require_auth('educator');
        $profile = $this->profile($user);

        $start = trim((string) $_POST['start_time']);
        $duration = max(15, (int) ($_POST['duration'] ?? 60));
        $end = date('Y-m-d H:i:s', strtotime($start) + ($duration * 60));

        $statement = db()->prepare(
            'INSERT INTO class_listings (educator_id, title, description, class_type, english_level, capacity, price, zoom_link, start_time, end_time, recurrence_rule, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $statement->execute([
            $profile['id'],
            trim((string) $_POST['title']),
            trim((string) $_POST['description']),
            (string) $_POST['class_type'],
            trim((string) $_POST['english_level']),
            max(1, (int) $_POST['capacity']),
            max(0, (float) $_POST['price']),
            trim((string) $_POST['zoom_link']),
            date('Y-m-d H:i:s', strtotime($start)),
            $end,
            trim((string) ($_POST['recurrence_rule'] ?? '')),
            'published',
        ]);

        flash('success', 'Class published.');
        redirect('/teacher/classes');
    }

    public function requestJoin(): void
    {
        $user = require_auth('student');
        $classId = (int) ($_POST['class_id'] ?? 0);
        $message = trim((string) ($_POST['message'] ?? 'I would like to join this class.'));

        $statement = db()->prepare(insert_ignore_sql('class_requests', ['class_id', 'student_id', 'message']));
        $statement->execute([$classId, $user['id'], $message]);

        flash('success', 'Your request was sent. The teacher will review it soon.');
        redirect('/dashboard');
    }

    public function updateRequest(): void
    {
        $user = require_auth('educator');
        $profile = $this->profile($user);
        $requestId = (int) ($_POST['request_id'] ?? 0);
        $status = (string) ($_POST['status'] ?? 'pending');

        if (!in_array($status, ['approved', 'rejected'], true)) {
            redirect('/teacher/classes');
        }

        $request = db()->prepare(
            "SELECT cr.*
             FROM class_requests cr
             JOIN class_listings cl ON cl.id = cr.class_id
             WHERE cr.id = ? AND cl.educator_id = ?
             LIMIT 1"
        );
        $request->execute([$requestId, $profile['id']]);
        $row = $request->fetch();

        if (!$row) {
            redirect('/teacher/classes');
        }

        $update = db()->prepare('UPDATE class_requests SET status = ? WHERE id = ?');
        $update->execute([$status, $requestId]);

        if ($status === 'approved') {
            $enroll = db()->prepare(insert_ignore_sql('enrollments', ['class_id', 'student_id']));
            $enroll->execute([$row['class_id'], $row['student_id']]);
        }

        flash('success', 'Request updated.');
        redirect('/teacher/classes');
    }

    private function profile(array $user): array
    {
        $statement = db()->prepare('SELECT * FROM educator_profiles WHERE user_id = ? LIMIT 1');
        $statement->execute([$user['id']]);

        return $statement->fetch();
    }
}
