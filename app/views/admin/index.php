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
