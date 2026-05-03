<?php

declare(strict_types=1);

namespace App\Controllers;

final class AdminController
{
    public function index(): void
    {
        require_auth('admin');

        $stats = [
            'students' => db()->query("SELECT COUNT(*) AS total FROM users WHERE role = 'student'")->fetch()['total'],
            'educators' => db()->query("SELECT COUNT(*) AS total FROM users WHERE role = 'educator'")->fetch()['total'],
            'classes' => db()->query("SELECT COUNT(*) AS total FROM class_listings")->fetch()['total'],
            'messages' => db()->query("SELECT COUNT(*) AS total FROM messages")->fetch()['total'],
        ];

        $educators = db()->query(
            "SELECT ep.*, u.name, u.email
             FROM educator_profiles ep
             JOIN users u ON u.id = ep.user_id
             ORDER BY CASE ep.approval_status WHEN 'pending' THEN 1 WHEN 'rejected' THEN 2 WHEN 'approved' THEN 3 ELSE 4 END, ep.created_at DESC"
        )->fetchAll();

        view('admin/index', ['title' => 'Admin', 'stats' => $stats, 'educators' => $educators]);
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
}
