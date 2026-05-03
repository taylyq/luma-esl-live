<?php

declare(strict_types=1);

namespace App\Controllers;

final class AuthController
{
    public function login(): void
    {
        view('auth/login', ['title' => 'Sign in']);
    }

    public function register(): void
    {
        view('auth/register', ['title' => 'Create account']);
    }

    public function authenticate(): void
    {
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        $statement = db()->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $statement->execute([$email]);
        $user = $statement->fetch();

        if (!$user || !password_verify($password, $user['password'])) {
            flash('error', 'Those credentials did not match.');
            redirect('/login');
        }

        $_SESSION['user_id'] = $user['id'];
        redirect('/dashboard');
    }

    public function store(): void
    {
        $name = trim((string) ($_POST['name'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $role = (string) ($_POST['role'] ?? 'student');

        if (!in_array($role, ['student', 'educator'], true) || strlen($name) < 2 || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 8) {
            flash('error', 'Please enter a name, valid email, role, and password with at least 8 characters.');
            redirect('/register');
        }

        $statement = db()->prepare('INSERT INTO users (role, name, email, password, status) VALUES (?, ?, ?, ?, ?)');
        try {
            $statement->execute([
                $role,
                $name,
                $email,
                password_hash($password, PASSWORD_DEFAULT),
                $role === 'educator' ? 'pending' : 'active',
            ]);
        } catch (\Throwable) {
            flash('error', 'An account already exists for that email.');
            redirect('/register');
        }

        $userId = (int) db()->lastInsertId();
        if ($role === 'educator') {
            $profile = db()->prepare(
                "INSERT INTO educator_profiles (user_id, headline, bio, approval_status)
                 VALUES (?, 'New ESL educator', 'Tell students what makes your class clear, kind, and useful.', 'pending')"
            );
            $profile->execute([$userId]);
        }

        $_SESSION['user_id'] = $userId;
        redirect('/dashboard');
    }

    public function logout(): void
    {
        redirect('/dashboard');
    }

    public function destroy(): void
    {
        session_destroy();
        redirect('/');
    }
}
