<?php

declare(strict_types=1);

namespace App\Controllers;

final class AdminController
{
    public function index(): void
    {
        require_auth('admin');
        ensure_lesson_topics_table();
        ensure_site_settings_table();

        $stats = [
            'students' => db()->query("SELECT COUNT(*) AS total FROM users WHERE role = 'student'")->fetch()['total'],
            'educators' => db()->query("SELECT COUNT(*) AS total FROM users WHERE role = 'educator'")->fetch()['total'],
            'classes' => db()->query("SELECT COUNT(*) AS total FROM class_listings")->fetch()['total'],
            'messages' => db()->query("SELECT COUNT(*) AS total FROM messages")->fetch()['total'],
            'reports' => $this->openReportCount(),
        ];

        $educators = db()->query(
            "SELECT ep.*, u.name, u.email
             FROM educator_profiles ep
             JOIN users u ON u.id = ep.user_id
             ORDER BY CASE ep.approval_status WHEN 'pending' THEN 1 WHEN 'rejected' THEN 2 WHEN 'approved' THEN 3 ELSE 4 END, ep.created_at DESC"
        )->fetchAll();

        $admins = db()->query(
            "SELECT id, name, email, status, email_verified_at, created_at
             FROM users
             WHERE role = 'admin'
             ORDER BY created_at DESC"
        )->fetchAll();

        $students = db()->query(
            "SELECT id, name, email, status, email_verified_at, created_at
             FROM users
             WHERE role = 'student'
             ORDER BY created_at DESC"
        )->fetchAll();

        $reports = $this->recentReports();
        $lessonTopics = db()->query('SELECT * FROM lesson_topics ORDER BY sort_order ASC, id ASC')->fetchAll();

        view('admin/index', [
            'title' => 'Admin',
            'stats' => $stats,
            'educators' => $educators,
            'admins' => $admins,
            'students' => $students,
            'reports' => $reports,
            'lessonTopics' => $lessonTopics,
            'showTeacherHourlyRates' => show_teacher_hourly_rates(),
        ]);
    }

    public function createAdmin(): void
    {
        require_auth('admin');
        ensure_user_compliance_columns();

        $name = trim((string) ($_POST['name'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $confirm = (string) ($_POST['password_confirmation'] ?? '');

        if (strlen($name) < 2 || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 8 || $password !== $confirm) {
            flash('error', 'Enter a name, valid email, and matching password with at least 8 characters.');
            redirect('/admin');
        }

        $statement = db()->prepare(
            "INSERT INTO users (role, name, email, password, email_verified_at, status, age_confirmed_at, terms_accepted_at)
             VALUES ('admin', ?, ?, ?, CURRENT_TIMESTAMP, 'active', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)"
        );

        try {
            $statement->execute([
                $name,
                $email,
                password_hash($password, PASSWORD_DEFAULT),
            ]);
        } catch (\Throwable) {
            flash('error', 'An account already exists for that email.');
            redirect('/admin');
        }

        flash('success', 'Admin account created.');
        redirect('/admin');
    }

    public function updateEducator(): void
    {
        require_auth('admin');
        $profileId = (int) ($_POST['profile_id'] ?? 0);
        $status = (string) ($_POST['approval_status'] ?? 'pending');
        $verified = isset($_POST['verified']) ? 1 : 0;

        if (!in_array($status, ['pending', 'approved', 'rejected'], true)) {
            redirect('/admin');
        }

        $statement = db()->prepare('UPDATE educator_profiles SET approval_status = ?, verified = ? WHERE id = ?');
        $statement->execute([$status, $verified, $profileId]);

        flash('success', 'Educator updated.');
        redirect('/admin');
    }

    public function updateStudent(): void
    {
        require_auth('admin');
        $studentId = (int) ($_POST['student_id'] ?? 0);
        $status = (string) ($_POST['status'] ?? 'active');

        if (!in_array($status, ['active', 'suspended'], true)) {
            redirect('/admin');
        }

        $statement = db()->prepare("UPDATE users SET status = ? WHERE id = ? AND role = 'student'");
        $statement->execute([$status, $studentId]);

        flash('success', 'Student updated.');
        redirect('/admin');
    }

    public function updateSettings(): void
    {
        require_auth('admin');
        ensure_site_settings_table();

        $showHourlyRates = isset($_POST['show_teacher_hourly_rates']) ? '1' : '0';

        if (db_driver() === 'sqlite') {
            $statement = db()->prepare(
                'INSERT INTO site_settings (setting_key, setting_value, updated_at)
                 VALUES (?, ?, CURRENT_TIMESTAMP)
                 ON CONFLICT(setting_key) DO UPDATE SET setting_value = excluded.setting_value, updated_at = CURRENT_TIMESTAMP'
            );
        } else {
            $statement = db()->prepare(
                'INSERT INTO site_settings (setting_key, setting_value)
                 VALUES (?, ?)
                 ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
            );
        }

        $statement->execute(['show_teacher_hourly_rates', $showHourlyRates]);

        flash('success', $showHourlyRates === '1' ? 'Teacher hourly rates are visible.' : 'Teacher hourly rates are hidden.');
        redirect('/admin');
    }

    public function createLessonTopic(): void
    {
        require_auth('admin');
        ensure_lesson_topics_table();

        $data = $this->lessonTopicData();
        $statement = db()->prepare('INSERT INTO lesson_topics (unit, topic, image_url, sort_order) VALUES (?, ?, ?, ?)');
        try {
            $statement->execute([$data['unit'], $data['topic'], $data['image_url'], $data['sort_order']]);
        } catch (\Throwable) {
            flash('error', 'A lesson topic with that name already exists.');
            redirect('/admin');
        }

        flash('success', 'Lesson topic created.');
        redirect('/admin');
    }

    public function updateLessonTopic(): void
    {
        require_auth('admin');
        ensure_lesson_topics_table();

        $topicId = (int) ($_POST['topic_id'] ?? 0);
        $data = $this->lessonTopicData();
        $statement = db()->prepare('UPDATE lesson_topics SET unit = ?, topic = ?, image_url = ?, sort_order = ? WHERE id = ?');
        $statement->execute([$data['unit'], $data['topic'], $data['image_url'], $data['sort_order'], $topicId]);

        flash('success', 'Lesson topic updated.');
        redirect('/admin');
    }

    public function bulkUpdateLessonTopics(): void
    {
        require_auth('admin');
        ensure_lesson_topics_table();

        $topics = $_POST['topics'] ?? [];
        if (!is_array($topics)) {
            redirect('/admin');
        }

        $update = db()->prepare('UPDATE lesson_topics SET unit = ?, topic = ?, image_url = ?, sort_order = ? WHERE id = ?');
        $delete = db()->prepare('DELETE FROM lesson_topics WHERE id = ?');
        $saved = 0;
        $deleted = 0;

        foreach ($topics as $topicId => $topicData) {
            $topicId = (int) $topicId;
            if ($topicId <= 0 || !is_array($topicData)) {
                continue;
            }

            if (!empty($topicData['delete'])) {
                $delete->execute([$topicId]);
                $deleted++;
                continue;
            }

            $unit = trim((string) ($topicData['unit'] ?? ''));
            $topic = trim((string) ($topicData['topic'] ?? ''));
            $imageUrl = trim((string) ($topicData['image_url'] ?? ''));
            $sortOrder = max(0, (int) ($topicData['sort_order'] ?? 0));

            $uploadedFile = $this->nestedLessonUpload($topicId);
            if ($uploadedFile !== null) {
                $imageUrl = $this->storeLessonImage($uploadedFile);
            }

            if ($unit === '' || $topic === '' || $imageUrl === '') {
                continue;
            }

            try {
                $update->execute([$unit, $topic, $imageUrl, $sortOrder, $topicId]);
                $saved++;
            } catch (\Throwable) {
                continue;
            }
        }

        flash('success', "Lesson topics updated. Saved {$saved}, deleted {$deleted}.");
        redirect('/admin');
    }

    public function deleteLessonTopic(): void
    {
        require_auth('admin');
        ensure_lesson_topics_table();

        $topicId = (int) ($_POST['topic_id'] ?? 0);
        $statement = db()->prepare('DELETE FROM lesson_topics WHERE id = ?');
        $statement->execute([$topicId]);

        flash('success', 'Lesson topic deleted.');
        redirect('/admin');
    }

    private function lessonTopicData(): array
    {
        $unit = trim((string) ($_POST['unit'] ?? ''));
        $topic = trim((string) ($_POST['topic'] ?? ''));
        $imageUrl = trim((string) ($_POST['image_url'] ?? ''));
        $sortOrder = max(0, (int) ($_POST['sort_order'] ?? 0));

        if (!empty($_FILES['image_upload']['tmp_name'])) {
            $imageUrl = $this->storeLessonImage($_FILES['image_upload']);
        }

        if ($unit === '' || $topic === '' || $imageUrl === '') {
            flash('error', 'Lesson unit, topic, and image are required.');
            redirect('/admin');
        }

        return [
            'unit' => $unit,
            'topic' => $topic,
            'image_url' => $imageUrl,
            'sort_order' => $sortOrder,
        ];
    }

    private function storeLessonImage(array $file): string
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            flash('error', 'The lesson image upload failed.');
            redirect('/admin');
        }

        if (($file['size'] ?? 0) > 4 * 1024 * 1024) {
            flash('error', 'Lesson images must be smaller than 4MB.');
            redirect('/admin');
        }

        $mime = mime_content_type($file['tmp_name']);
        $extensions = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
        ];

        if (!isset($extensions[$mime])) {
            flash('error', 'Upload a JPG, PNG, or WebP lesson image.');
            redirect('/admin');
        }

        $directory = dirname(__DIR__, 2) . '/public/uploads/lessons';
        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $filename = $this->safeOriginalLessonFilename((string) ($file['name'] ?? ''), $mime, $extensions);
        $destination = $directory . '/' . $filename;
        if (is_file($destination) && !is_writable($destination)) {
            flash('error', 'The existing lesson image could not be replaced.');
            redirect('/admin');
        }

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            flash('error', 'The lesson image could not be saved.');
            redirect('/admin');
        }

        return '/uploads/lessons/' . $filename;
    }

    private function safeOriginalLessonFilename(string $originalName, string $mime, array $extensions): string
    {
        $fallbackExtension = $extensions[$mime] ?? 'jpg';
        $originalName = basename(str_replace('\\', '/', $originalName));
        $originalName = trim((string) preg_replace('/[^\w.\- ]+/', '-', $originalName));
        $originalName = trim((string) preg_replace('/\s+/', ' ', $originalName));

        if ($originalName === '' || $originalName === '.' || $originalName === '..') {
            return 'lesson-image.' . $fallbackExtension;
        }

        $name = pathinfo($originalName, PATHINFO_FILENAME) ?: 'lesson-image';
        $submittedExtension = strtolower((string) pathinfo($originalName, PATHINFO_EXTENSION));
        $matchingExtensions = [
            'image/jpeg' => ['jpg', 'jpeg'],
            'image/png' => ['png'],
            'image/webp' => ['webp'],
        ];

        $extension = in_array($submittedExtension, $matchingExtensions[$mime] ?? [], true)
            ? $submittedExtension
            : $fallbackExtension;

        return $name . '.' . $extension;
    }

    private function nestedLessonUpload(int $topicId): ?array
    {
        if (empty($_FILES['image_upload']['tmp_name'][$topicId])) {
            return null;
        }

        return [
            'name' => $_FILES['image_upload']['name'][$topicId] ?? '',
            'type' => $_FILES['image_upload']['type'][$topicId] ?? '',
            'tmp_name' => $_FILES['image_upload']['tmp_name'][$topicId],
            'error' => $_FILES['image_upload']['error'][$topicId] ?? UPLOAD_ERR_NO_FILE,
            'size' => $_FILES['image_upload']['size'][$topicId] ?? 0,
        ];
    }

    private function openReportCount(): int
    {
        try {
            return (int) db()->query("SELECT COUNT(*) AS total FROM message_reports WHERE status = 'open'")->fetch()['total'];
        } catch (\Throwable) {
            return 0;
        }
    }

    private function recentReports(): array
    {
        try {
            return db()->query(
                "SELECT mr.*, reporter.name AS reporter_name, reported.name AS reported_name, m.message_body
                 FROM message_reports mr
                 JOIN users reporter ON reporter.id = mr.reporter_id
                 JOIN users reported ON reported.id = mr.reported_user_id
                 LEFT JOIN messages m ON m.id = mr.message_id
                 ORDER BY mr.created_at DESC
                 LIMIT 12"
            )->fetchAll();
        } catch (\Throwable) {
            return [];
        }
    }

}
