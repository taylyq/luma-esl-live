<?php

declare(strict_types=1);

namespace App\Controllers;

final class ClassController
{
    public function index(): void
    {
        $user = require_auth('educator');
        $profile = $this->profile($user);
        ensure_class_price_currency_column();

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
        ensure_class_price_currency_column();
        $data = $this->classFormData();

        $statement = db()->prepare(
            'INSERT INTO class_listings (educator_id, title, description, class_type, english_level, capacity, price, price_currency, zoom_link, start_time, end_time, recurrence_rule, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $statement->execute([
            $profile['id'],
            $data['title'],
            $data['description'],
            $data['class_type'],
            $data['english_level'],
            $data['capacity'],
            $data['price'],
            $data['price_currency'],
            $data['zoom_link'],
            $data['start_time'],
            $data['end_time'],
            $data['recurrence_rule'],
            'published',
        ]);

        flash('success', 'Class published.');
        redirect('/teacher/classes');
    }

    public function edit(): void
    {
        $user = require_auth('educator');
        $profile = $this->profile($user);
        ensure_class_price_currency_column();

        $class = $this->upcomingClassForProfile((int) ($_GET['id'] ?? 0), (int) $profile['id']);
        if (!$class) {
            flash('error', 'Only upcoming classes you created can be edited.');
            redirect('/teacher/classes');
        }

        view('teacher/class-edit', [
            'title' => 'Edit class',
            'class' => $class,
            'duration' => max(15, (int) round((strtotime($class['end_time']) - strtotime($class['start_time'])) / 60)),
        ]);
    }

    public function update(): void
    {
        $user = require_auth('educator');
        $profile = $this->profile($user);
        ensure_class_price_currency_column();

        $classId = (int) ($_POST['class_id'] ?? 0);
        $class = $this->upcomingClassForProfile($classId, (int) $profile['id']);
        if (!$class) {
            flash('error', 'Only upcoming classes you created can be edited.');
            redirect('/teacher/classes');
        }

        $data = $this->classFormData();
        $statement = db()->prepare(
            'UPDATE class_listings
             SET title = ?, description = ?, class_type = ?, english_level = ?, capacity = ?, price = ?, price_currency = ?, zoom_link = ?, start_time = ?, end_time = ?, recurrence_rule = ?
             WHERE id = ? AND educator_id = ?'
        );
        $statement->execute([
            $data['title'],
            $data['description'],
            $data['class_type'],
            $data['english_level'],
            $data['capacity'],
            $data['price'],
            $data['price_currency'],
            $data['zoom_link'],
            $data['start_time'],
            $data['end_time'],
            $data['recurrence_rule'],
            $classId,
            $profile['id'],
        ]);

        flash('success', 'Class updated.');
        redirect('/teacher/classes');
    }

    public function requestJoin(): void
    {
        $user = require_auth('student');
        $classId = (int) ($_POST['class_id'] ?? 0);
        $message = substr(trim((string) ($_POST['message'] ?? 'I would like to join this class.')), 0, 1000);

        $class = db()->prepare(
            "SELECT cl.id, cl.capacity,
                    (SELECT COUNT(*) FROM enrollments e WHERE e.class_id = cl.id) AS enrolled_count,
                    EXISTS(SELECT 1 FROM enrollments own_e WHERE own_e.class_id = cl.id AND own_e.student_id = ?) AS already_enrolled
             FROM class_listings cl
             JOIN educator_profiles ep ON ep.id = cl.educator_id
             JOIN users teacher ON teacher.id = ep.user_id
             WHERE cl.id = ?
               AND cl.status = 'published'
               AND cl.start_time > CURRENT_TIMESTAMP
               AND ep.approval_status = 'approved'
               AND teacher.status != 'suspended'
             LIMIT 1"
        );
        $class->execute([$user['id'], $classId]);
        $availableClass = $class->fetch();

        if (!$availableClass) {
            flash('error', 'That class is no longer available.');
            redirect('/calendar');
        }

        if ((int) $availableClass['already_enrolled'] === 1) {
            flash('success', 'You already have a seat in this class.');
            redirect('/dashboard');
        }

        if ((int) $availableClass['enrolled_count'] >= (int) $availableClass['capacity']) {
            flash('error', 'That class is currently full.');
            redirect('/calendar');
        }

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
            "SELECT cr.*, cl.capacity,
                    (SELECT COUNT(*) FROM enrollments e WHERE e.class_id = cl.id) AS enrolled_count,
                    EXISTS(SELECT 1 FROM enrollments own_e WHERE own_e.class_id = cl.id AND own_e.student_id = cr.student_id) AS already_enrolled
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

        if ($status === 'approved' && (int) $row['already_enrolled'] !== 1 && (int) $row['enrolled_count'] >= (int) $row['capacity']) {
            flash('error', 'This class is full, so another request cannot be approved.');
            redirect('/teacher/classes');
        }

        $pdo = db();
        $pdo->beginTransaction();
        try {
            $update = $pdo->prepare('UPDATE class_requests SET status = ? WHERE id = ?');
            $update->execute([$status, $requestId]);

            if ($status === 'approved') {
                $enroll = $pdo->prepare(insert_ignore_sql('enrollments', ['class_id', 'student_id']));
                $enroll->execute([$row['class_id'], $row['student_id']]);
            } else {
                $removeEnrollment = $pdo->prepare('DELETE FROM enrollments WHERE class_id = ? AND student_id = ?');
                $removeEnrollment->execute([$row['class_id'], $row['student_id']]);
            }
            $pdo->commit();
        } catch (\Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
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

    private function upcomingClassForProfile(int $classId, int $profileId): ?array
    {
        $statement = db()->prepare(
            'SELECT *
             FROM class_listings
             WHERE id = ? AND educator_id = ? AND start_time >= CURRENT_TIMESTAMP
             LIMIT 1'
        );
        $statement->execute([$classId, $profileId]);

        return $statement->fetch() ?: null;
    }

    private function classFormData(): array
    {
        $title = trim((string) ($_POST['title'] ?? ''));
        $description = trim((string) ($_POST['description'] ?? ''));
        $englishLevel = trim((string) ($_POST['english_level'] ?? ''));
        $start = trim((string) ($_POST['start_time'] ?? ''));
        $startTimestamp = strtotime($start);
        if ($title === '' || strlen($title) > 190 || $description === '' || strlen($description) > 5000 || $englishLevel === '' || strlen($englishLevel) > 80) {
            flash('error', 'Enter a valid title, description, and English level.');
            redirect('/teacher/classes');
        }

        if (!$startTimestamp || $startTimestamp <= time()) {
            flash('error', 'Choose a class start time in the future.');
            redirect('/teacher/classes');
        }

        $duration = min(480, max(15, (int) ($_POST['duration'] ?? 60)));
        $priceCurrency = strtoupper((string) ($_POST['price_currency'] ?? 'USD'));
        if (!in_array($priceCurrency, ['FREE', 'USD', 'VND'], true)) {
            $priceCurrency = 'USD';
        }
        $classType = (string) ($_POST['class_type'] ?? 'private');
        if (!in_array($classType, ['private', 'group'], true)) {
            $classType = 'private';
        }

        $zoomLink = trim((string) ($_POST['zoom_link'] ?? ''));
        if ($zoomLink !== '' && !$this->validZoomLink($zoomLink)) {
            flash('error', 'Use a valid HTTPS Zoom meeting link.');
            redirect('/teacher/classes');
        }

        return [
            'title' => $title,
            'description' => $description,
            'class_type' => $classType,
            'english_level' => $englishLevel,
            'capacity' => min(100, max(1, (int) ($_POST['capacity'] ?? 1))),
            'price' => $priceCurrency === 'FREE' ? 0 : min(999999.99, max(0, (float) ($_POST['price'] ?? 0))),
            'price_currency' => $priceCurrency,
            'zoom_link' => $zoomLink,
            'start_time' => date('Y-m-d H:i:s', $startTimestamp),
            'end_time' => date('Y-m-d H:i:s', $startTimestamp + ($duration * 60)),
            'recurrence_rule' => substr(trim((string) ($_POST['recurrence_rule'] ?? '')), 0, 160),
        ];
    }

    private function validZoomLink(string $url): bool
    {
        if (!filter_var($url, FILTER_VALIDATE_URL) || strtolower((string) parse_url($url, PHP_URL_SCHEME)) !== 'https') {
            return false;
        }

        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        return $host === 'zoom.us' || str_ends_with($host, '.zoom.us') || $host === 'zoom.com' || str_ends_with($host, '.zoom.com');
    }
}
