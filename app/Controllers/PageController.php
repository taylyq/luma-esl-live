<?php

declare(strict_types=1);

namespace App\Controllers;

final class PageController
{
    public function home(): void
    {
        $teachers = db()->query(
            "SELECT ep.*, u.name,
                COALESCE((SELECT AVG(r.rating) FROM reviews r WHERE r.educator_id = ep.id AND r.status = 'published'), 0) AS rating,
                (SELECT COUNT(*) FROM reviews r WHERE r.educator_id = ep.id AND r.status = 'published') AS review_count
             FROM educator_profiles ep
             JOIN users u ON u.id = ep.user_id
             WHERE ep.approval_status = 'approved'
             ORDER BY ep.verified DESC, rating DESC
             LIMIT 3"
        )->fetchAll();

        view('home', ['title' => 'Premium ESL teacher marketplace', 'teachers' => $teachers]);
    }

    public function pricing(): void
    {
        ensure_class_price_currency_column();
        $viewer = current_user();
        $viewerId = (int) ($viewer['id'] ?? 0);

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
                AND (
                    (cl.start_time >= CURRENT_DATE AND cl.start_time < DATE_ADD(CURRENT_DATE, INTERVAL 7 DAY))
                    OR cl.end_time < CURRENT_TIMESTAMP
                )
             ORDER BY cl.start_time ASC"
        );
        $statement->execute([$viewerId]);

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
            'startDate' => new \DateTimeImmutable('today'),
        ]);
    }

    public function lessons(): void
    {
        view('lessons', ['title' => 'Lessons']);
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
