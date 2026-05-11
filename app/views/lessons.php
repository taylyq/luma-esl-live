<?php
$unitTopics = [];
foreach ($lessonTopics as $lesson) {
    $unitTopics[$lesson['unit']][] = $lesson;
}
$firstLesson = $lessonTopics[0] ?? null;
?>

<section class="section narrow center">
    <span class="eyebrow">Lessons</span>
    <h1>Explore beginner English topics by unit.</h1>
    <p class="lede">Choose a topic on the left and preview the lesson image instantly on the right.</p>
</section>

<?php if (!$firstLesson): ?>
    <section class="section narrow">
        <div class="empty-state">No lesson topics are available yet.</div>
    </section>
<?php else: ?>
    <section class="lessons-shell" data-lessons>
        <aside class="lesson-sidebar" aria-label="Lesson topics">
            <?php foreach ($unitTopics as $unit => $topics): ?>
                <section class="lesson-unit">
                    <h2><?= e($unit) ?></h2>
                    <div class="lesson-topic-list">
                        <?php foreach ($topics as $lesson): ?>
                            <?php $image = lesson_image_url((string) $lesson['image_url']); ?>
                            <button
                                class="lesson-topic-button <?= (int) $lesson['id'] === (int) $firstLesson['id'] ? 'active' : '' ?>"
                                type="button"
                                data-lesson-topic="<?= e($lesson['topic']) ?>"
                                data-lesson-unit="<?= e($lesson['unit']) ?>"
                                data-lesson-image="<?= e($image) ?>"
                            >
                                <?= e($lesson['topic']) ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endforeach; ?>
        </aside>

        <article class="lesson-preview">
            <div class="lesson-preview-copy">
                <span class="eyebrow" data-lesson-unit-label><?= e($firstLesson['unit']) ?></span>
                <h2 data-lesson-title><?= e($firstLesson['topic']) ?></h2>
            </div>
            <div class="lesson-image-wrap">
                <div class="lesson-image-frame" data-lesson-image-frame>
                    <img data-lesson-image-preview src="<?= e(lesson_image_url((string) $firstLesson['image_url'])) ?>" alt="<?= e($firstLesson['topic']) ?> lesson preview" decoding="async" fetchpriority="high">
                </div>
                <div class="lesson-nav-buttons" aria-label="Browse lessons">
                    <button type="button" data-lesson-prev aria-label="Previous lesson">↑</button>
                    <button type="button" data-lesson-next aria-label="Next lesson">↓</button>
                </div>
            </div>
        </article>
    </section>
<?php endif; ?>
