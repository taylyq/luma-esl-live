<section class="auth-shell">
    <div class="auth-panel">
        <span class="eyebrow">Account recovery</span>
        <h1>Reset your password.</h1>
        <p class="muted">Enter your email and we will send a secure reset link.</p>
        <form method="post" action="/forgot-password" class="form-stack">
            <input type="hidden" name="_token" value="<?= csrf_token() ?>">
            <label>Email<input name="email" type="email" required autocomplete="email"></label>
            <button class="button button-dark full" type="submit">Send reset link</button>
        </form>
    </div>
</section>
