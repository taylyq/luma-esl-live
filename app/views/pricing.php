<?php
$viewer = current_user();
$days = [];
for ($i = 0; $i < 7; $i++) {
    $date = $startDate->modify('+' . $i . ' days');
    $days[$date->format('Y-m-d')] = $date;
}
$historyDays = [];
foreach (array_keys($classesByDay['passed'] ?? []) as $dayKey) {
    $historyDays[$dayKey] = new DateTimeImmutable($dayKey);
}
krsort($historyDays);

$calendarSections = [
    'upcoming' => [
        'eyebrow' => 'Next 7 days',
        'title' => 'Upcoming classes',
        'empty' => 'No upcoming classes yet.',
        'days' => $days,
    ],
    'passed' => [
        'eyebrow' => 'History',
        'title' => 'History',
        'empty' => 'No class history yet.',
        'days' => $historyDays,
    ],
];
?>

<section class="section narrow center">
    <span class="eyebrow">Class calendar</span>
    <h1>Find a class happening this week.</h1>
    <p class="lede">Browse upcoming Zoom classes from approved LumaESL educators. Sign in to message teachers or request a seat.</p>
</section>

<?php foreach ($calendarSections as $sectionKey => $section): ?>
    <section class="section calendar-shell <?= $sectionKey === 'passed' ? 'calendar-shell-passed' : '' ?>">
        <div class="section-heading calendar-heading">
            <div>
                <span class="eyebrow"><?= e($section['eyebrow']) ?></span>
                <h2><?= e($section['title']) ?></h2>
            </div>
            <?php if ($sectionKey === 'upcoming' && !$viewer): ?>
                <a class="button button-dark" href="/login">Sign in to join</a>
            <?php endif; ?>
        </div>

        <div class="calendar-list">
            <?php if (!$section['days']): ?>
                <div class="empty-state small"><?= e($section['empty']) ?></div>
            <?php endif; ?>

            <?php foreach ($section['days'] as $key => $date): ?>
                <?php $classes = $classesByDay[$sectionKey][$key] ?? []; ?>
                <section class="calendar-day">
                    <div class="calendar-date">
                        <span><?= e($date->format('D')) ?></span>
                        <strong><?= e($date->format('M j')) ?></strong>
                    </div>

                    <div class="calendar-classes">
                        <?php if (!$classes && $sectionKey !== 'passed'): ?>
                            <div class="empty-state small"><?= e($section['empty']) ?></div>
                        <?php endif; ?>

                        <?php foreach ($classes as $class): ?>
                            <?php
                            $starts = strtotime($class['start_time']);
                            $ends = strtotime($class['end_time']);
                            $spotsLeft = max(0, (int) $class['capacity'] - (int) $class['enrolled_count']);
                            $hasPassed = $sectionKey === 'passed';
                            ?>
                            <article class="calendar-card <?= $hasPassed ? 'passed' : '' ?>">
                                <div class="calendar-class-main">
                                    <img class="calendar-teacher-photo" src="<?= e($class['profile_photo'] ?: 'https://images.unsplash.com/photo-1522202176988-66273c2fd55f?auto=format&fit=crop&w=300&q=80') ?>" alt="<?= e($class['teacher_name']) ?>">
                                    <div>
                                        <div class="calendar-meta">
                                            <span><?= e(date('g:i A', $starts)) ?><?= $ends ? ' - ' . e(date('g:i A', $ends)) : '' ?></span>
                                            <span><?= e($class['class_type']) ?> · <?= e($class['english_level']) ?></span>
                                            <?php if ($hasPassed): ?><span class="passed-pill">Passed</span><?php endif; ?>
                                        </div>
                                        <h3><?= e($class['title']) ?></h3>
                                        <p><?= e($class['description']) ?></p>
                                        <div class="tag-row">
                                            <span><?= e($class['teacher_name']) ?></span>
                                            <?php if ((int) $class['verified'] === 1): ?><span>Verified</span><?php endif; ?>
                                            <span><?= e(format_class_price($class)) ?></span>
                                            <?php if ($hasPassed): ?>
                                                <span>Class passed</span>
                                            <?php else: ?>
                                                <span><?= $spotsLeft ?> seats left</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>

                                <div class="calendar-actions">
                                    <a class="button button-light" href="/teachers/<?= (int) $class['educator_id'] ?>">Profile</a>
                                    <?php if ($hasPassed): ?>
                                        <span class="status passed">Passed</span>
                                    <?php elseif (!$viewer): ?>
                                        <a class="button button-dark" href="/login">Sign in</a>
                                    <?php else: ?>
                                        <?php if ((int) $viewer['id'] !== (int) $class['educator_user_id']): ?>
                                            <form method="post" action="/teachers/message">
                                                <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                                                <input type="hidden" name="recipient_id" value="<?= (int) $class['educator_user_id'] ?>">
                                                <input type="hidden" name="message_body" value="I am interested in <?= e($class['title']) ?> on <?= e(date('M j, g:i A', $starts)) ?>.">
                                                <button class="button button-light" type="submit">Message</button>
                                            </form>
                                        <?php endif; ?>
                                        <?php if ($viewer['role'] === 'student'): ?>
                                            <form method="post" action="/classes/request">
                                                <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                                                <input type="hidden" name="class_id" value="<?= (int) $class['id'] ?>">
                                                <input type="hidden" name="message" value="I would like to join <?= e($class['title']) ?>.">
                                                <button class="button button-dark" type="submit">Request seat</button>
                                            </form>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endforeach; ?>
        </div>
    </section>
<?php endforeach; ?>
