<section class="app-shell">
    <div class="app-heading">
        <span class="eyebrow">Class manager</span>
        <h1>Publish Zoom classes and approve students.</h1>
    </div>
    <div class="dashboard-grid">
        <form method="post" action="/teacher/classes" class="panel form-stack">
            <input type="hidden" name="_token" value="<?= csrf_token() ?>">
            <h2>New class</h2>
            <label>Title<input name="title" required></label>
            <label>Description<textarea name="description" rows="4" required></textarea></label>
            <label>Class type<select name="class_type"><option value="private">Private</option><option value="group">Group</option></select></label>
            <label>Level<input name="english_level" value="Intermediate" required></label>
            <label>Capacity<input name="capacity" type="number" min="1" value="4"></label>
            <label>Price type<select name="price_currency"><option value="FREE">Free</option><option value="USD" selected>USD</option><option value="VND">VND</option></select></label>
            <label>Price<input name="price" type="number" min="0" step="1" value="15" placeholder="Use 0 for free classes"></label>
            <label>Start time<input name="start_time" type="datetime-local" required></label>
            <label>Duration minutes<input name="duration" type="number" min="15" value="60"></label>
            <label>Zoom link<input name="zoom_link" placeholder="Hidden until approval"></label>
            <label>Recurrence<input name="recurrence_rule" placeholder="Weekly"></label>
            <button class="button button-dark full" type="submit">Publish class</button>
        </form>
        <article class="panel large">
            <h2>Classes</h2>
            <?php foreach ($classes as $class): ?>
                <div class="list-row">
                    <div><strong><?= e($class['title']) ?></strong><span><?= date('M j, g:i A', strtotime($class['start_time'])) ?> · <?= e($class['english_level']) ?></span></div>
                    <div class="class-row-actions">
                        <span><?= e(format_class_price($class)) ?></span>
                        <?php if (strtotime($class['start_time']) >= time()): ?>
                            <a class="button button-light button-small" href="/teacher/classes/edit?id=<?= (int) $class['id'] ?>">Edit</a>
                        <?php else: ?>
                            <span class="status passed">Passed</span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </article>
        <article class="panel large">
            <h2>Join requests</h2>
            <?php foreach ($requests as $request): ?>
                <div class="approval-row">
                    <div><strong><?= e($request['student_name']) ?></strong><span><?= e($request['title']) ?> · <?= e($request['status']) ?></span><p><?= e($request['message']) ?></p></div>
                    <?php if ($request['status'] === 'pending'): ?>
                        <form method="post" action="/teacher/requests/update" class="inline-form">
                            <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                            <input type="hidden" name="request_id" value="<?= (int) $request['id'] ?>">
                            <button name="status" value="approved" class="button button-dark" type="submit">Approve</button>
                            <button name="status" value="rejected" class="button button-light" type="submit">Reject</button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </article>
    </div>
</section>
