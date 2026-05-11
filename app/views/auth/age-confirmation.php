<section class="auth-shell">
    <div class="auth-panel wide">
        <span class="eyebrow">Adult confirmation</span>
        <h1>Messages are for adults only.</h1>
        <p class="muted">To reduce legal and safety risk, Luma ESL requires every messaging user to confirm they are at least 18 and agree to the current Terms and Privacy Policy.</p>
        <form method="post" action="/age-confirmation" class="form-stack">
            <input type="hidden" name="_token" value="<?= csrf_token() ?>">
            <label class="check legal-check">
                <input type="checkbox" name="adult_confirm" value="1" required>
                I confirm I am at least 18 years old.
            </label>
            <label class="check legal-check">
                <input type="checkbox" name="terms_accept" value="1" required>
                I agree to the <a href="/terms" target="_blank" rel="noopener">Terms</a> and <a href="/privacy" target="_blank" rel="noopener">Privacy Policy</a>.
            </label>
            <button class="button button-dark full" type="submit">Continue to messages</button>
        </form>
    </div>
</section>
