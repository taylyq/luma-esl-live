<section class="app-shell settings-shell">
    <div class="app-heading">
        <span class="eyebrow">Account settings</span>
        <h1>Keep your account secure.</h1>
    </div>

    <div class="settings-grid">
        <article class="panel">
            <h2>Account</h2>
            <div class="settings-summary">
                <div>
                    <span>Name</span>
                    <strong><?= e($user['name']) ?></strong>
                </div>
                <div>
                    <span>Email</span>
                    <strong class="notranslate"><?= e($user['email']) ?></strong>
                </div>
                <div>
                    <span>Account type</span>
                    <strong><?= e(ucfirst($user['role'])) ?></strong>
                </div>
            </div>
        </article>

        <article class="panel">
            <h2>Change password</h2>
            <p class="muted">Use a strong password you do not use on other websites.</p>
            <form method="post" action="/settings/password" class="form-stack">
                <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                <label>Current password
                    <input name="current_password" type="password" required autocomplete="current-password">
                </label>
                <label>New password
                    <input name="password" type="password" required minlength="8" autocomplete="new-password">
                </label>
                <label>Confirm new password
                    <input name="password_confirmation" type="password" required minlength="8" autocomplete="new-password">
                </label>
                <button class="button button-dark full" type="submit">Update password</button>
            </form>
        </article>
    </div>
</section>
