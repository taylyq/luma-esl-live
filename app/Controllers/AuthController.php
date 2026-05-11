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
        ensure_user_compliance_columns();

        view('auth/register', [
            'title' => 'Create account',
            'captcha' => $this->captchaPuzzle(),
        ]);
    }

    public function ageConfirmation(): void
    {
        $user = require_auth();
        $user = age_confirmed_user($user) ?: $user;

        if (!empty($user['age_confirmed_at']) && !empty($user['terms_accepted_at'])) {
            redirect('/messages');
        }

        view('auth/age-confirmation', ['title' => 'Adult confirmation']);
    }

    public function forgotPassword(): void
    {
        view('auth/forgot-password', ['title' => 'Reset password']);
    }

    public function resetPassword(): void
    {
        view('auth/reset-password', [
            'title' => 'Choose a new password',
            'token' => (string) ($_GET['token'] ?? ''),
        ]);
    }

    public function settings(): void
    {
        $user = require_auth();
        view('auth/settings', ['title' => 'Settings', 'user' => $user]);
    }

    public function verifyNotice(): void
    {
        $token = (string) ($_GET['token'] ?? '');
        if ($token !== '') {
            $statement = db()->prepare('SELECT id FROM users WHERE email_verification_token = ? LIMIT 1');
            $statement->execute([token_hash($token)]);
            $user = $statement->fetch();

            if ($user) {
                $update = db()->prepare('UPDATE users SET email_verified_at = CURRENT_TIMESTAMP, email_verification_token = NULL, status = IFNULL(NULLIF(status, "pending"), "active") WHERE id = ?');
                $update->execute([$user['id']]);
                $_SESSION['user_id'] = $user['id'];
                flash('success', 'Email verified. Welcome in.');
                redirect('/dashboard');
            }

            flash('error', 'That verification link is invalid or expired.');
        }

        $user = current_user();
        view('auth/verify-email', ['title' => 'Verify email', 'user' => $user]);
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

        if (empty($user['email_verified_at'])) {
            redirect('/email/verify');
        }

        redirect('/dashboard');
    }

    public function store(): void
    {
        ensure_user_compliance_columns();

        $name = trim((string) ($_POST['name'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $role = (string) ($_POST['role'] ?? 'student');
        $adultConfirm = isset($_POST['adult_confirm']);
        $termsAccept = isset($_POST['terms_accept']);

        if (!$this->captchaPassed()) {
            flash('error', 'Complete the drag-and-drop verification before creating an account.');
            redirect('/register');
        }

        if (!$adultConfirm || !$termsAccept) {
            flash('error', 'You must confirm you are at least 18 and accept the Terms and Privacy Policy to create an account.');
            redirect('/register');
        }

        if (!in_array($role, ['student', 'educator'], true) || strlen($name) < 2 || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 8) {
            flash('error', 'Please enter a name, valid email, role, and password with at least 8 characters.');
            redirect('/register');
        }

        $verificationToken = bin2hex(random_bytes(32));
        $confirmedAt = date('Y-m-d H:i:s');
        $statement = db()->prepare('INSERT INTO users (role, name, email, password, email_verification_token, status, age_confirmed_at, terms_accepted_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
        try {
            $statement->execute([
                $role,
                $name,
                $email,
                password_hash($password, PASSWORD_DEFAULT),
                token_hash($verificationToken),
                $role === 'educator' ? 'pending' : 'active',
                $confirmedAt,
                $confirmedAt,
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
        $this->sendVerificationEmail($email, $verificationToken);
        redirect('/email/verify');
    }

    public function confirmAge(): void
    {
        $user = require_auth();
        ensure_user_compliance_columns();

        if (!isset($_POST['adult_confirm']) || !isset($_POST['terms_accept'])) {
            flash('error', 'Confirm you are at least 18 and accept the Terms and Privacy Policy to use messages.');
            redirect('/age-confirmation');
        }

        $statement = db()->prepare('UPDATE users SET age_confirmed_at = CURRENT_TIMESTAMP, terms_accepted_at = CURRENT_TIMESTAMP WHERE id = ?');
        $statement->execute([$user['id']]);

        flash('success', 'Adult confirmation saved.');
        redirect('/messages');
    }

    public function sendResetLink(): void
    {
        $email = trim((string) ($_POST['email'] ?? ''));
        $statement = db()->prepare('SELECT id, email, name FROM users WHERE email = ? LIMIT 1');
        $statement->execute([$email]);
        $user = $statement->fetch();

        if ($user) {
            $token = bin2hex(random_bytes(32));
            $insert = db()->prepare('INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)');
            $insert->execute([$user['id'], token_hash($token), date('Y-m-d H:i:s', time() + 3600)]);

            send_app_mail(
                $user['email'],
                'Reset your Luma ESL password',
                "Hi {$user['name']},\n\nReset your password here:\n" . app_url('/reset-password?token=' . $token) . "\n\nThis link expires in one hour."
            );
        }

        flash('success', 'If that email exists, a reset link has been sent.');
        redirect('/forgot-password');
    }

    public function updatePassword(): void
    {
        $token = (string) ($_POST['token'] ?? '');
        $password = (string) ($_POST['password'] ?? '');
        $confirm = (string) ($_POST['password_confirmation'] ?? '');

        if (strlen($password) < 8 || $password !== $confirm) {
            flash('error', 'Use a matching password with at least 8 characters.');
            redirect('/reset-password?token=' . urlencode($token));
        }

        $statement = db()->prepare(
            'SELECT * FROM password_resets WHERE token = ? AND used_at IS NULL AND expires_at > CURRENT_TIMESTAMP ORDER BY created_at DESC LIMIT 1'
        );
        $statement->execute([token_hash($token)]);
        $reset = $statement->fetch();

        if (!$reset) {
            flash('error', 'That reset link is invalid or expired.');
            redirect('/forgot-password');
        }

        $update = db()->prepare('UPDATE users SET password = ? WHERE id = ?');
        $update->execute([password_hash($password, PASSWORD_DEFAULT), $reset['user_id']]);

        $used = db()->prepare('UPDATE password_resets SET used_at = CURRENT_TIMESTAMP WHERE id = ?');
        $used->execute([$reset['id']]);

        flash('success', 'Password updated. You can sign in now.');
        redirect('/login');
    }

    public function changePassword(): void
    {
        $user = require_auth();
        $currentPassword = (string) ($_POST['current_password'] ?? '');
        $password = (string) ($_POST['password'] ?? '');
        $confirm = (string) ($_POST['password_confirmation'] ?? '');

        $statement = db()->prepare('SELECT id, password FROM users WHERE id = ? LIMIT 1');
        $statement->execute([$user['id']]);
        $freshUser = $statement->fetch();

        if (!$freshUser || !password_verify($currentPassword, $freshUser['password'])) {
            flash('error', 'Current password was not correct.');
            redirect('/settings');
        }

        if (strlen($password) < 8 || $password !== $confirm) {
            flash('error', 'Use a matching new password with at least 8 characters.');
            redirect('/settings');
        }

        if (password_verify($password, $freshUser['password'])) {
            flash('error', 'Choose a new password that is different from your current password.');
            redirect('/settings');
        }

        $update = db()->prepare('UPDATE users SET password = ? WHERE id = ?');
        $update->execute([password_hash($password, PASSWORD_DEFAULT), $user['id']]);
        session_regenerate_id(true);

        flash('success', 'Password changed successfully.');
        redirect('/settings');
    }

    public function verifyEmail(): void
    {
        $token = trim((string) ($_POST['token'] ?? ''));
        redirect('/email/verify?token=' . urlencode($token));
    }

    public function resendVerification(): void
    {
        $user = require_auth();
        if (!empty($user['email_verified_at'])) {
            redirect('/dashboard');
        }

        $token = bin2hex(random_bytes(32));
        $statement = db()->prepare('UPDATE users SET email_verification_token = ? WHERE id = ?');
        $statement->execute([token_hash($token), $user['id']]);
        $this->sendVerificationEmail($user['email'], $token);

        flash('success', 'Verification email sent.');
        redirect('/email/verify');
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

    private function sendVerificationEmail(string $email, string $token): void
    {
        send_app_mail(
            $email,
            'Verify your Luma ESL email',
            "Welcome to Luma ESL.\n\nVerify your email here:\n" . app_url('/email/verify?token=' . $token)
        );
    }

    private function captchaPuzzle(): array
    {
        $puzzles = [
            [
                'instruction' => 'Drag the classroom item into the box.',
                'answer' => 'book',
                'items' => [
                    ['value' => 'book', 'label' => 'Book'],
                    ['value' => 'apple', 'label' => 'Apple'],
                    ['value' => 'clock', 'label' => 'Clock'],
                ],
            ],
            [
                'instruction' => 'Drag the learning place into the box.',
                'answer' => 'school',
                'items' => [
                    ['value' => 'beach', 'label' => 'Beach'],
                    ['value' => 'school', 'label' => 'School'],
                    ['value' => 'market', 'label' => 'Market'],
                ],
            ],
            [
                'instruction' => 'Drag the teacher tool into the box.',
                'answer' => 'pencil',
                'items' => [
                    ['value' => 'pencil', 'label' => 'Pencil'],
                    ['value' => 'shirt', 'label' => 'Shirt'],
                    ['value' => 'banana', 'label' => 'Banana'],
                ],
            ],
        ];

        $puzzle = $puzzles[random_int(0, count($puzzles) - 1)];
        shuffle($puzzle['items']);
        $puzzle['id'] = bin2hex(random_bytes(16));

        $_SESSION['register_captcha'] = [
            'id' => $puzzle['id'],
            'answer' => $puzzle['answer'],
            'expires_at' => time() + 900,
        ];

        return $puzzle;
    }

    private function captchaPassed(): bool
    {
        if (trim((string) ($_POST['website'] ?? '')) !== '') {
            unset($_SESSION['register_captcha']);
            return false;
        }

        $captcha = $_SESSION['register_captcha'] ?? null;
        unset($_SESSION['register_captcha']);

        if (!is_array($captcha) || (int) ($captcha['expires_at'] ?? 0) < time()) {
            return false;
        }

        $id = (string) ($_POST['captcha_id'] ?? '');
        $answer = strtolower(trim((string) ($_POST['captcha_answer'] ?? '')));

        return hash_equals((string) ($captcha['id'] ?? ''), $id)
            && hash_equals((string) ($captcha['answer'] ?? ''), $answer);
    }
}
