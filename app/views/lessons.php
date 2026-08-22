<?php
$unitTopics = [];
foreach ($lessonTopics as $lesson) {
    $unitTopics[$lesson['unit']][] = $lesson;
}
$firstLesson = $lessonTopics[0] ?? null;
?>

<section class="section lessons-intro">
    <div>
        <span class="eyebrow">Visual lesson library</span>
        <h1>Learn it. See it. Use it.</h1>
        <p class="lede">Pick a topic and jump straight into a visual English lesson.</p>
    </div>
    <div class="lessons-intro-stats" aria-label="Lesson library summary">
        <div><strong><?= count($lessonTopics) ?></strong><span>topics</span></div>
        <div><strong><?= count($unitTopics) ?></strong><span>units</span></div>
        <div><strong>A2-B1</strong><span>level</span></div>
    </div>
</section>

<?php if (!$firstLesson): ?>
    <section class="section narrow">
        <div class="empty-state">No lesson topics are available yet.</div>
    </section>
<?php else: ?>
    <section class="lessons-shell" data-lessons>
        <aside class="lesson-sidebar" aria-label="Lesson topics">
            <div class="lesson-sidebar-heading">
                <strong>Topics</strong>
                <span><?= count($lessonTopics) ?> lessons</span>
            </div>
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
                <span class="lesson-key-hint">Use ↑ ↓ to browse</span>
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
