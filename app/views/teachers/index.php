<section class="page-hero compact">
    <div>
        <span class="eyebrow">Teacher directory</span>
        <h1>Find someone who gets how you learn.</h1>
        <p>Explore approved ESL educators by goal, level, class type, and teaching style.</p>
    </div>
    <div class="page-hero-note"><strong>Message first</strong><span>Get comfortable before you commit.</span></div>
</section>

<section class="directory-layout">
    <aside class="filters">
        <form method="get" action="/teachers" class="form-stack">
            <label>Search<input name="q" placeholder="Interview, beginner, business..." value="<?= e($query) ?>"></label>
            <label>Level
                <select name="level">
                    <option value="">Any level</option>
                    <?php foreach (['Beginner', 'Intermediate', 'All levels'] as $option): ?>
                        <option value="<?= e($option) ?>" <?= $level === $option ? 'selected' : '' ?>><?= e($option) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Class type
                <select name="type">
                    <option value="">Any type</option>
                    <option value="private" <?= $type === 'private' ? 'selected' : '' ?>>Private</option>
                    <option value="group" <?= $type === 'group' ? 'selected' : '' ?>>Group</option>
                </select>
            </label>
            <button class="button button-dark full" type="submit">Apply filters</button>
        </form>
    </aside>
    <div>
        <div class="toolbar">
            <strong><?= count($teachers) ?> educators</strong>
            <span>Verified profiles with upcoming Zoom classes</span>
        </div>
        <div class="teacher-grid directory">
            <?php foreach ($teachers as $teacher): ?>
                <?php require dirname(__DIR__) . '/partials/teacher-card.php'; ?>
            <?php endforeach; ?>
        </div>
        <?php if (!$teachers): ?>
            <div class="empty-state"><h2>No teachers found</h2><p>Try a broader search or remove filters.</p></div>
        <?php endif; ?>
    </div>
</section>
