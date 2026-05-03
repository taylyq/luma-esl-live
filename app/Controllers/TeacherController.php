<?php

declare(strict_types=1);

namespace App\Controllers;

final class TeacherController
{
    public function index(): void
    {
        $query = trim((string) ($_GET['q'] ?? ''));
        $level = trim((string) ($_GET['level'] ?? ''));
        $type = trim((string) ($_GET['type'] ?? ''));

        $sql = "SELECT ep.*, u.name,
                    COALESCE(AVG(r.rating), 0) AS rating,
                    COUNT(DISTINCT r.id) AS review_count,
                    MIN(cl.start_time) AS next_class
                FROM educator_profiles ep
                JOIN users u ON u.id = ep.user_id
                LEFT JOIN reviews r ON r.educator_id = ep.id AND r.status = 'published'
                LEFT JOIN class_listings cl ON cl.educator_id = ep.id AND cl.status = 'published' AND cl.start_time >= CURRENT_TIMESTAMP
                WHERE ep.approval_status = 'approved'";
        $params = [];

        if ($query !== '') {
            $sql .= ' AND (u.name LIKE ? OR ep.headline LIKE ? OR ep.specialties LIKE ?)';
            $like = '%' . $query . '%';
            array_push($params, $like, $like, $like);
        }

        if ($level !== '') {
            $sql .= ' AND EXISTS (SELECT 1 FROM class_listings cl2 WHERE cl2.educator_id = ep.id AND cl2.english_level = ?)';
            $params[] = $level;
        }

        if ($type !== '') {
            $sql .= ' AND EXISTS (SELECT 1 FROM class_listings cl3 WHERE cl3.educator_id = ep.id AND cl3.class_type = ?)';
            $params[] = $type;
        }

        $sql .= ' GROUP BY ep.id, u.name ORDER BY ep.verified DESC, rating DESC, ep.hourly_rate ASC';
        $statement = db()->prepare($sql);
        $statement->execute($params);

        view('teachers/index', [
            'title' => 'Browse teachers',
            'teachers' => $statement->fetchAll(),
            'query' => $query,
            'level' => $level,
            'type' => $type,
        ]);
    }

    public function show(): void
    {
        $id = (int) ($_GET['id'] ?? 0);
        $teacher = $this->teacher($id);

        if (!$teacher) {
            http_response_code(404);
            view('error', ['title' => 'Teacher not found', 'message' => 'That teacher profile is not available.']);
            return;
        }

        $classes = db()->prepare("SELECT * FROM class_listings WHERE educator_id = ? AND status = 'published' AND start_time >= CURRENT_TIMESTAMP ORDER BY start_time");
        $classes->execute([$id]);

        $reviews = db()->prepare(
            "SELECT r.*, u.name AS student_name
             FROM reviews r
             JOIN users u ON u.id = r.student_id
             WHERE r.educator_id = ? AND r.status = 'published'
             ORDER BY r.created_at DESC"
        );
        $reviews->execute([$id]);

        view('teachers/show', [
            'title' => $teacher['name'],
            'teacher' => $teacher,
            'classes' => $classes->fetchAll(),
            'reviews' => $reviews->fetchAll(),
        ]);
    }

    public function edit(): void
    {
        $user = require_auth('educator');
        $statement = db()->prepare('SELECT * FROM educator_profiles WHERE user_id = ? LIMIT 1');
        $statement->execute([$user['id']]);

        view('teacher/profile', ['title' => 'Teacher profile', 'profile' => $statement->fetch()]);
    }

    public function update(): void
    {
        $user = require_auth('educator');
        $photo = trim((string) ($_POST['profile_photo'] ?? ''));

        if (!empty($_FILES['profile_photo_upload']['tmp_name'])) {
            $photo = $this->storeProfilePhoto($_FILES['profile_photo_upload']);
        }

        $statement = db()->prepare(
            'UPDATE educator_profiles
             SET headline = ?, bio = ?, years_experience = ?, native_language = ?, teaching_languages = ?, specialties = ?, hourly_rate = ?, timezone = ?, profile_photo = ?, approval_status = CASE WHEN approval_status = "rejected" THEN "pending" ELSE approval_status END
             WHERE user_id = ?'
        );
        $statement->execute([
            trim((string) $_POST['headline']),
            trim((string) $_POST['bio']),
            (int) $_POST['years_experience'],
            trim((string) $_POST['native_language']),
            trim((string) $_POST['teaching_languages']),
            trim((string) $_POST['specialties']),
            (float) $_POST['hourly_rate'],
            trim((string) $_POST['timezone']),
            $photo,
            $user['id'],
        ]);

        flash('success', 'Your profile was updated.');
        redirect('/teacher/profile');
    }

    private function teacher(int $id): ?array
    {
        $statement = db()->prepare(
            "SELECT ep.*, u.name,
                COALESCE(AVG(r.rating), 0) AS rating,
                COUNT(DISTINCT r.id) AS review_count
             FROM educator_profiles ep
             JOIN users u ON u.id = ep.user_id
             LEFT JOIN reviews r ON r.educator_id = ep.id AND r.status = 'published'
             WHERE ep.id = ? AND ep.approval_status = 'approved'
             GROUP BY ep.id, u.name
             LIMIT 1"
        );
        $statement->execute([$id]);

        return $statement->fetch() ?: null;
    }

    private function storeProfilePhoto(array $file): string
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            flash('error', 'The photo upload failed. Please try again.');
            redirect('/teacher/profile');
        }

        if (($file['size'] ?? 0) > 2 * 1024 * 1024) {
            flash('error', 'Profile photos must be smaller than 2MB.');
            redirect('/teacher/profile');
        }

        $mime = mime_content_type($file['tmp_name']);
        $extensions = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
        ];

        if (!isset($extensions[$mime])) {
            flash('error', 'Upload a JPG, PNG, or WebP profile photo.');
            redirect('/teacher/profile');
        }

        $directory = dirname(__DIR__, 2) . '/public/uploads/teachers';
        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $filename = bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
        $destination = $directory . '/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            flash('error', 'The photo could not be saved.');
            redirect('/teacher/profile');
        }

        return '/uploads/teachers/' . $filename;
    }
}
