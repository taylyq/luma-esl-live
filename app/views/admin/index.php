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
        <h2>Lesson topics</h2>
        <form method="post" action="/admin/lessons/create" class="form-grid admin-lesson-form" enctype="multipart/form-data">
            <input type="hidden" name="_token" value="<?= csrf_token() ?>">
            <label>Unit<input name="unit" list="lesson-units" required></label>
            <label>Topic<input name="topic" required></label>
            <label>Sort order<input name="sort_order" type="number" min="0" value="<?= count($lessonTopics) + 1 ?>"></label>
            <label>Image URL or search phrase<input name="image_url" placeholder="/assets/img/lessons/example.jpeg" required></label>
            <label class="span-2">Upload image<input name="image_upload" type="file" accept="image/jpeg,image/png,image/webp"></label>
            <button class="button button-dark span-2" type="submit">Create lesson topic</button>
        </form>
        <datalist id="lesson-units">
            <option value="Me and my world">
            <option value="School and home">
            <option value="Food and daily life">
            <option value="Play, sports, and hobbies">
            <option value="Community and nature">
        </datalist>
        <div class="admin-lesson-list">
            <?php foreach ($lessonTopics as $topic): ?>
                <form method="post" action="/admin/lessons/update" class="admin-lesson-row" enctype="multipart/form-data">
                    <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="topic_id" value="<?= (int) $topic['id'] ?>">
                    <img src="<?= e(lesson_image_url((string) $topic['image_url'])) ?>" alt="<?= e($topic['topic']) ?>">
                    <label>Unit<input name="unit" value="<?= e($topic['unit']) ?>" required></label>
                    <label>Topic<input name="topic" value="<?= e($topic['topic']) ?>" required></label>
                    <label>Sort<input name="sort_order" type="number" min="0" value="<?= (int) $topic['sort_order'] ?>"></label>
                    <label>Image<input name="image_url" value="<?= e($topic['image_url']) ?>" required></label>
                    <label>Replace<input name="image_upload" type="file" accept="image/jpeg,image/png,image/webp"></label>
                    <button class="button button-dark" type="submit">Save</button>
                </form>
                <form method="post" action="/admin/lessons/delete" class="admin-lesson-delete">
                    <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="topic_id" value="<?= (int) $topic['id'] ?>">
                    <button class="link-button danger" type="submit" onclick="return confirm('Delete this lesson topic?')">Delete <?= e($topic['topic']) ?></button>
                </form>
            <?php endforeach; ?>
        </div>
    </article>
    <article class="panel">
        <h2>Recent message reports</h2>
        <?php foreach ($reports as $report): ?>
            <div class="list-row simple">
                <strong><?= e($report['reporter_name']) ?> reported <?= e($report['reported_name']) ?></strong>
                <span><?= e($report['reason']) ?> · <?= e($report['message_body'] ?: $report['details'] ?: 'Conversation report') ?> · <?= date('M j, g:i A', strtotime($report['created_at'])) ?></span>
            </div>
        <?php endforeach; ?>
        <?php if (!$reports): ?><div class="empty-state small">No message reports yet.</div><?php endif; ?>
    </article>
    <article class="panel">
        <h2>Student management</h2>
        <?php foreach ($students as $student): ?>
            <form method="post" action="/admin/students/update" class="admin-row">
                <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="student_id" value="<?= (int) $student['id'] ?>">
                <div><strong><?= e($student['name']) ?></strong><span><?= e($student['email']) ?> · <?= e($student['status']) ?></span></div>
                <select name="status">
                    <?php foreach (['active', 'suspended'] as $status): ?>
                        <option value="<?= e($status) ?>" <?= $student['status'] === $status ? 'selected' : '' ?>><?= e($status) ?></option>
                    <?php endforeach; ?>
                </select>
                <button class="button button-dark" type="submit">Save</button>
            </form>
            <form method="post" action="/teachers/message" class="admin-message-row">
                <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="recipient_id" value="<?= (int) $student['id'] ?>">
                <input name="message_body" value="Hi <?= e($student['name']) ?>, this is the Luma ESL admin team." aria-label="Message to <?= e($student['name']) ?>">
                <button class="button button-light" type="submit">Message student</button>
            </form>
        <?php endforeach; ?>
    </article>
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
            <form method="post" action="/teachers/message" class="admin-message-row">
                <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="recipient_id" value="<?= (int) $educator['user_id'] ?>">
                <input name="message_body" value="Hi <?= e($educator['name']) ?>, this is the Luma ESL admin team." aria-label="Message to <?= e($educator['name']) ?>">
                <button class="button button-light" type="submit">Message teacher</button>
            </form>
        <?php endforeach; ?>
    </article>
</section>
