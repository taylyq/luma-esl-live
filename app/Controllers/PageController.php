<?php

declare(strict_types=1);

namespace App\Controllers;

final class PageController
{
    public function home(): void
    {
        $teachers = db()->query(
            "SELECT ep.*, u.name,
                COALESCE(AVG(r.rating), 0) AS rating,
                COUNT(DISTINCT r.id) AS review_count
             FROM educator_profiles ep
             JOIN users u ON u.id = ep.user_id
             LEFT JOIN reviews r ON r.educator_id = ep.id AND r.status = 'published'
             WHERE ep.approval_status = 'approved'
             GROUP BY ep.id, u.name
             ORDER BY ep.verified DESC, rating DESC
             LIMIT 3"
        )->fetchAll();

        view('home', ['title' => 'Premium ESL teacher marketplace', 'teachers' => $teachers]);
    }

    public function pricing(): void
    {
        view('pricing', ['title' => 'Pricing']);
    }
}
