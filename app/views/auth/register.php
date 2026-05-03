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
            <button class="button button-dark full" type="submit">Create account</button>
        </form>
    </div>
</section>
