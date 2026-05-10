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
        <div class="auth-links">
            <a href="/forgot-password">Forgot password?</a>
            <span>New to Luma ESL? <a href="/register">Create an account</a></span>
        </div>
    </div>
</section>
