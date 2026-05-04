<section class="app-shell">
    <div class="app-heading">
        <span class="eyebrow">Admin operations</span>
        <h1>Keep the marketplace trustworthy.</h1>
    </div>
    <div class="stats-grid">
        <?php foreach ($stats as $label => $value): ?>
            <div class="stat-card"><strong><?= (int) $value ?></strong><span><?= e(ucfirst($label)) ?></span></div>
        <?php endforeach; ?>
    </div>
    <div class="dashboard-grid">
        <article class="panel">
            <h2>Create admin account</h2>
            <form method="post" action="/admin/admins/create" class="form-stack">
                <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                <label>Name<input name="name" required></label>
                <label>Email<input name="email" type="email" required></label>
                <label>Password<input name="password" type="password" minlength="8" required></label>
                <label>Confirm password<input name="password_confirmation" type="password" minlength="8" required></label>
                <button class="button button-dark full" type="submit">Create admin</button>
            </form>
        </article>
        <article class="panel">
            <h2>Admin accounts</h2>
            <?php foreach ($admins as $admin): ?>
                <div class="list-row simple">
                    <strong><?= e($admin['name']) ?></strong>
                    <span><?= e($admin['email']) ?> · <?= e($admin['status']) ?></span>
                </div>
            <?php endforeach; ?>
        </article>
    </div>
    <article class="panel">
        <h2>Educator review queue</h2>
        <?php foreach ($educators as $educator): ?>
            <form method="post" action="/admin/educators/update" class="admin-row">
                <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="profile_id" value="<?= (int) $educator['id'] ?>">
                <div><strong><?= e($educator['name']) ?></strong><span><?= e($educator['email']) ?> · <?= e($educator['headline']) ?></span></div>
                <label class="check"><input type="checkbox" name="verified" <?= (int) $educator['verified'] === 1 ? 'checked' : '' ?>> Verified</label>
                <select name="approval_status">
                    <?php foreach (['pending', 'approved', 'rejected'] as $status): ?>
                        <option value="<?= e($status) ?>" <?= $educator['approval_status'] === $status ? 'selected' : '' ?>><?= e($status) ?></option>
                    <?php endforeach; ?>
                </select>
                <button class="button button-dark" type="submit">Save</button>
            </form>
        <?php endforeach; ?>
    </article>
</section>
