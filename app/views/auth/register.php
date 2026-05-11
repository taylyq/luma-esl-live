<section class="auth-shell">
    <div class="auth-panel wide">
        <span class="eyebrow">Create your account</span>
        <h1>Start with the right role.</h1>
        <form method="post" action="/register" class="form-stack">
            <input type="hidden" name="_token" value="<?= csrf_token() ?>">
            <div class="role-picker">
                <label><input type="radio" name="role" value="student" checked><span>Student</span><small>Browse, message, and request Zoom class access.</small></label>
                <label><input type="radio" name="role" value="educator"><span>Educator</span><small>Create a profile, publish classes, and manage inquiries.</small></label>
            </div>
            <label>Name<input name="name" required value="<?= post_value('name') ?>"></label>
            <label>Email<input name="email" type="email" required value="<?= post_value('email') ?>"></label>
            <label>Password<input name="password" type="password" required minlength="8"></label>
            <input class="hp-field" type="text" name="website" value="" tabindex="-1" autocomplete="off" aria-hidden="true">
            <div class="captcha-card" data-captcha>
                <input type="hidden" name="captcha_id" value="<?= e($captcha['id'] ?? '') ?>">
                <input type="hidden" name="captcha_answer" value="" data-captcha-answer>
                <div>
                    <span class="captcha-label">Security check</span>
                    <p><?= e($captcha['instruction'] ?? 'Drag the correct tile into the box.') ?></p>
                </div>
                <div class="captcha-area">
                    <div class="captcha-tiles" aria-label="Verification choices">
                        <?php foreach (($captcha['items'] ?? []) as $item): ?>
                            <button class="captcha-tile" type="button" draggable="true" data-captcha-tile data-captcha-value="<?= e($item['value']) ?>">
                                <?= e($item['label']) ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                    <div class="captcha-drop" data-captcha-drop role="button" tabindex="0" aria-label="Drop verification tile here">
                        Drop here
                    </div>
                </div>
                <small>Complete this puzzle, then check your email to verify your account.</small>
            </div>
            <label class="check legal-check">
                <input type="checkbox" name="adult_confirm" value="1" required>
                I confirm I am at least 18 years old. Luma ESL accounts and messaging are for adults only.
            </label>
            <label class="check legal-check">
                <input type="checkbox" name="terms_accept" value="1" required>
                I agree to the <a href="/terms" target="_blank" rel="noopener">Terms</a> and <a href="/privacy" target="_blank" rel="noopener">Privacy Policy</a>.
            </label>
            <button class="button button-dark full" type="submit">Create account</button>
        </form>
    </div>
</section>
