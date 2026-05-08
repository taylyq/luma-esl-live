<section class="profile-hero">
    <img src="<?= e($teacher['profile_photo']) ?>" alt="<?= e($teacher['name']) ?>">
    <div class="profile-copy">
        <div class="profile-title">
            <span class="eyebrow"><?= (int) $teacher['verified'] === 1 ? 'Verified educator' : 'Educator' ?></span>
            <h1><?= e($teacher['name']) ?></h1>
            <p><?= e($teacher['headline']) ?></p>
        </div>
        <div class="profile-stats">
            <div><strong><?= number_format((float) $teacher['rating'], 1) ?></strong><span>rating</span></div>
            <div><strong><?= (int) $teacher['years_experience'] ?> yrs</strong><span>experience</span></div>
            <div><strong>$<?= number_format((float) $teacher['hourly_rate']) ?></strong><span>per hour</span></div>
        </div>
        <?php if (current_user() && current_user()['role'] === 'student'): ?>
            <form class="inline-form" method="post" action="/teachers/message">
                <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="educator_id" value="<?= (int) $teacher['id'] ?>">
                <input type="hidden" name="message_body" value="Can I join your next Zoom class?">
                <button class="button button-dark" type="submit">Message teacher</button>
            </form>
        <?php else: ?>
            <a class="button button-dark" href="/register">Message teacher</a>
        <?php endif; ?>
    </div>
</section>

<section class="profile-layout">
    <article class="panel">
        <h2>Teaching style</h2>
        <p><?= nl2br(e($teacher['bio'])) ?></p>
        <div class="tag-row spacious">
            <?php foreach (array_filter(array_map('trim', explode(',', (string) $teacher['specialties']))) as $specialty): ?>
                <span><?= e($specialty) ?></span>
            <?php endforeach; ?>
        </div>
    </article>

    <aside class="panel">
        <h2>Languages</h2>
        <p><strong>Native:</strong> <?= e($teacher['native_language']) ?></p>
        <p><strong>Teaches:</strong> <?= e($teacher['teaching_languages']) ?></p>
        <p><strong>Timezone:</strong> <?= e($teacher['timezone']) ?></p>
    </aside>
</section>

<section class="section">
    <div class="section-heading">
        <span class="eyebrow">Upcoming classes</span>
        <h2>Request access. Zoom links unlock after approval.</h2>
    </div>
    <div class="class-grid">
        <?php foreach ($classes as $class): ?>
            <article class="class-card">
                <span><?= e($class['class_type']) ?> · <?= e($class['english_level']) ?></span>
                <h3><?= e($class['title']) ?></h3>
                <p><?= e($class['description']) ?></p>
                <div class="meta-row">
                    <span><?= date('M j, g:i A', strtotime($class['start_time'])) ?></span>
                    <span><?= e(format_class_price($class)) ?></span>
                </div>
                <?php if (current_user() && current_user()['role'] === 'student'): ?>
                    <form method="post" action="/classes/request" class="form-stack">
                        <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                        <input type="hidden" name="class_id" value="<?= (int) $class['id'] ?>">
                        <input type="hidden" name="message" value="I would like to join this class.">
                        <button class="button button-dark full" type="submit">Request to join</button>
                    </form>
                <?php else: ?>
                    <a class="button button-light full" href="/register">Request to join</a>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<section class="section">
    <div class="section-heading">
        <span class="eyebrow">Student words</span>
        <h2>Reviews</h2>
    </div>
    <div class="review-grid">
        <?php foreach ($reviews as $review): ?>
            <article class="review-card">
                <strong><?= str_repeat('★', (int) $review['rating']) ?></strong>
                <p><?= e($review['review_text']) ?></p>
                <span><?= e($review['student_name']) ?></span>
            </article>
        <?php endforeach; ?>
    </div>
</section>
