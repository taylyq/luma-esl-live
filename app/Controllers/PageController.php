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
        view('pricing', ['title' => 'Pricing']);
    }
}
