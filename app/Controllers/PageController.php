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
        $statement = db()->query(
            "SELECT cl.*, ep.id AS educator_id, ep.headline, ep.profile_photo, ep.hourly_rate, ep.verified,
                    u.id AS educator_user_id, u.name AS teacher_name,
                    COALESCE((SELECT COUNT(*) FROM enrollments e WHERE e.class_id = cl.id), 0) AS enrolled_count
             FROM class_listings cl
             JOIN educator_profiles ep ON ep.id = cl.educator_id
             JOIN users u ON u.id = ep.user_id
             WHERE cl.status = 'published'
                AND ep.approval_status = 'approved'
                AND cl.start_time >= CURRENT_TIMESTAMP
                AND cl.start_time < DATE_ADD(CURRENT_TIMESTAMP, INTERVAL 7 DAY)
             ORDER BY cl.start_time ASC"
        );

        $classesByDay = [];
        foreach ($statement->fetchAll() as $class) {
            $dayKey = date('Y-m-d', strtotime($class['start_time']));
            $classesByDay[$dayKey][] = $class;
        }

        view('pricing', [
            'title' => 'Class calendar',
            'classesByDay' => $classesByDay,
            'startDate' => new \DateTimeImmutable('today'),
        ]);
    }
}
