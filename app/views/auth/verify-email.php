<section class="auth-shell">
    <div class="auth-panel">
        <span class="eyebrow">One last step</span>
        <h1>Verify your email.</h1>
        <p class="muted">We sent a verification link to <?= e($user['email'] ?? 'your email') ?>. In local demo mode, emails are written to <strong>storage/mail.log</strong>.</p>
        <form method="post" action="/email/verify" class="form-stack">
            <input type="hidden" name="_token" value="<?= csrf_token() ?>">
            <label>Verification token<input name="token" placeholder="Paste token from the email link"></label>
            <button class="button button-dark full" type="submit">Verify email</button>
        </form>
        <?php if ($user): ?>
            <form method="post" action="/email/resend" class="form-stack top-gap">
                <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                <button class="button button-light full" type="submit">Resend verification email</button>
            </form>
        <?php endif; ?>
    </div>
</section>
