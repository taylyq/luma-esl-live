<section class="auth-shell">
    <div class="auth-panel">
        <span class="eyebrow">Welcome back</span>
        <h1>Sign in to continue.</h1>
        <form method="post" action="/login" class="form-stack">
            <input type="hidden" name="_token" value="<?= csrf_token() ?>">
            <label>Email<input name="email" type="email" required autocomplete="email" value="<?= post_value('email') ?>"></label>
            <label>Password<input name="password" type="password" required autocomplete="current-password"></label>
            <button class="button button-dark full" type="submit">Sign in</button>
        </form>
        <p class="muted"><a href="/forgot-password">Forgot password?</a></p>
        <p class="muted">Demo password is <strong>password</strong>. Try student, teacher, or admin seeded accounts.</p>
    </div>
</section>
