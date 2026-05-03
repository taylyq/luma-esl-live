<section class="auth-shell">
    <div class="auth-panel">
        <span class="eyebrow">Account recovery</span>
        <h1>Choose a new password.</h1>
        <form method="post" action="/reset-password" class="form-stack">
            <input type="hidden" name="_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="token" value="<?= e($token) ?>">
            <label>New password<input name="password" type="password" required minlength="8" autocomplete="new-password"></label>
            <label>Confirm password<input name="password_confirmation" type="password" required minlength="8" autocomplete="new-password"></label>
            <button class="button button-dark full" type="submit">Update password</button>
        </form>
    </div>
</section>
