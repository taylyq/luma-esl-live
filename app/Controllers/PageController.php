<?php

declare(strict_types=1);

namespace App\Controllers;

final class PageController
{
    public function home(): void
    {
        if (current_user()) {
            redirect('/dashboard');
        }

        $this->pricing();
    }

    public function pricing(): void
    {
        ensure_class_price_currency_column();
        $viewer = current_user();
        $viewerId = (int) ($viewer['id'] ?? 0);
        $startDate = new \DateTimeImmutable('today');
        $endDate = $startDate->modify('+7 days');
        $historyCondition = '0 = 1';
        $params = [$viewerId, $startDate->format('Y-m-d H:i:s'), $endDate->format('Y-m-d H:i:s')];

        if (($viewer['role'] ?? '') === 'admin') {
            $historyCondition = 'cl.end_time < CURRENT_TIMESTAMP';
        } elseif (($viewer['role'] ?? '') === 'educator') {
            $historyCondition = 'cl.end_time < CURRENT_TIMESTAMP AND ep.user_id = ?';
            $params[] = $viewerId;
        } elseif (($viewer['role'] ?? '') === 'student') {
            $historyCondition = 'cl.end_time < CURRENT_TIMESTAMP AND EXISTS (
                SELECT 1 FROM enrollments history_e WHERE history_e.class_id = cl.id AND history_e.student_id = ?
            )';
            $params[] = $viewerId;
        }

        $statement = db()->prepare(
            "SELECT cl.*, ep.id AS educator_id, ep.headline, ep.profile_photo, ep.hourly_rate, ep.verified,
                    u.id AS educator_user_id, u.name AS teacher_name,
                    COALESCE((SELECT COUNT(*) FROM enrollments e WHERE e.class_id = cl.id), 0) AS enrolled_count,
                    EXISTS(SELECT 1 FROM enrollments own_e WHERE own_e.class_id = cl.id AND own_e.student_id = ?) AS viewer_enrolled
             FROM class_listings cl
             JOIN educator_profiles ep ON ep.id = cl.educator_id
             JOIN users u ON u.id = ep.user_id
             WHERE cl.status = 'published'
                AND ep.approval_status = 'approved'
                AND u.status != 'suspended'
                AND (
                    (cl.start_time >= ? AND cl.start_time < ?)
                    OR ({$historyCondition})
                )
             ORDER BY cl.start_time ASC"
        );
        $statement->execute($params);

        $classesByDay = [
            'upcoming' => [],
            'passed' => [],
        ];
        $now = time();
        foreach ($statement->fetchAll() as $class) {
            $dayKey = date('Y-m-d', strtotime($class['start_time']));
            $endTime = !empty($class['end_time']) ? strtotime($class['end_time']) : strtotime($class['start_time']);
            $section = $endTime < $now ? 'passed' : 'upcoming';
            if ($section === 'passed' && !$this->canViewPassedClass($viewer, $class)) {
                continue;
            }
            $classesByDay[$section][$dayKey][] = $class;
        }

        view('pricing', [
            'title' => 'Class calendar',
            'classesByDay' => $classesByDay,
            'startDate' => $startDate,
        ]);
    }

    public function lessons(): void
    {
        ensure_lesson_topics_table();
        $topics = db()->query('SELECT * FROM lesson_topics ORDER BY sort_order ASC, id ASC')->fetchAll();
        view('lessons', ['title' => 'Lessons', 'lessonTopics' => $topics]);
    }

    public function privacy(): void
    {
        view('privacy', ['title' => 'Privacy policy']);
    }

    public function terms(): void
    {
        view('terms', ['title' => 'Terms of service']);
    }

    private function canViewPassedClass(?array $viewer, array $class): bool
    {
        if (!$viewer) {
            return false;
        }

        if ($viewer['role'] === 'admin') {
            return true;
        }

        if ($viewer['role'] === 'educator') {
            return (int) $viewer['id'] === (int) $class['educator_user_id'];
        }

        return $viewer['role'] === 'student' && (int) ($class['viewer_enrolled'] ?? 0) === 1;
    }
}
