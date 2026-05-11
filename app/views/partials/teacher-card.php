<article class="teacher-card">
    <a class="teacher-photo" href="/teachers/<?= (int) $teacher['id'] ?>">
        <img src="<?= e($teacher['profile_photo'] ?: 'https://images.unsplash.com/photo-1522202176988-66273c2fd55f?auto=format&fit=crop&w=900&q=80') ?>" alt="<?= e($teacher['name']) ?>">
    </a>
    <div class="teacher-card-body">
        <div class="teacher-card-top">
            <div>
                <h3><?= e($teacher['name']) ?></h3>
                <p><?= e($teacher['headline']) ?></p>
            </div>
            <?php if ((int) $teacher['verified'] === 1): ?>
                <span class="badge">Verified</span>
            <?php endif; ?>
        </div>
        <div class="meta-row">
            <span><?= number_format((float) $teacher['rating'], 1) ?> rating</span>
            <?php if (show_teacher_hourly_rates()): ?>
                <span>$<?= number_format((float) $teacher['hourly_rate']) ?>/hr</span>
            <?php endif; ?>
        </div>
        <div class="tag-row">
            <?php foreach (array_slice(array_filter(array_map('trim', explode(',', (string) $teacher['specialties']))), 0, 3) as $specialty): ?>
                <span><?= e($specialty) ?></span>
            <?php endforeach; ?>
        </div>
        <div class="card-actions">
            <a class="button button-dark" href="/teachers/<?= (int) $teacher['id'] ?>">View profile</a>
            <?php if (current_user() && current_user()['role'] === 'student'): ?>
                <form method="post" action="/teachers/message">
                    <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="educator_id" value="<?= (int) $teacher['id'] ?>">
                    <input type="hidden" name="message_body" value="I am interested in your next Zoom class.">
                    <button class="button button-light" type="submit">Message</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</article>
