<?php
$unitTopics = [
    'Me and my world' => [
        'Greetings' => 'children greeting classroom',
        'Introducing yourself' => 'child introducing self english class',
        'Numbers' => 'children counting numbers',
        'Colors' => 'children learning colors',
        'Shapes' => 'children learning shapes',
        'Family members' => 'happy family children',
        'Friends' => 'children friends classroom',
        'Body parts' => 'kids body parts lesson',
        'Feelings and emotions' => 'children emotions flashcards',
        'Clothes' => 'children clothes lesson',
        'Good manners' => 'children saying thank you',
        'Healthy habits' => 'children healthy habits',
    ],
    'School and home' => [
        'Classroom objects' => 'classroom objects school',
        'School subjects' => 'school subjects books',
        'Days of the week' => 'calendar days of week',
        'Months of the year' => 'calendar months learning',
        'My house' => 'family house children',
        'Rooms in the house' => 'children bedroom home',
        'Furniture' => 'home furniture kids',
        'Technology for kids' => 'children tablet learning',
    ],
    'Food and daily life' => [
        'Weather' => 'children weather lesson',
        'Seasons' => 'children seasons nature',
        'Food' => 'kids food table',
        'Drinks' => 'children drinking water juice',
        'Fruit and vegetables' => 'fruit vegetables children',
        'At the supermarket' => 'children supermarket shopping',
        'In the kitchen' => 'children cooking kitchen',
        'Daily routines' => 'child morning routine',
        'Telling the time' => 'child clock learning time',
    ],
    'Play, sports, and hobbies' => [
        'Hobbies' => 'children hobbies art music',
        'Sports' => 'children sports field',
        'Playing basketball outside' => 'children playing basketball outside',
        'At the park' => 'children park',
        'At the playground' => 'children playground',
        'Animals' => 'children animals learning',
        'Pets' => 'child pet dog',
    ],
    'Community and nature' => [
        'Farm animals' => 'children farm animals',
        'Wild animals' => 'children zoo wild animals',
        'Jobs and occupations' => 'children jobs occupations',
        'Community places' => 'community places town children',
        'At the restaurant' => 'family restaurant children',
        'At the doctor' => 'child doctor visit',
        'Transportation' => 'children transportation bus',
        'Road safety' => 'children road safety crosswalk',
        'Holidays and celebrations' => 'children holiday celebration',
        'Birthday party' => 'children birthday party',
        'Nature' => 'children nature forest',
        'The beach' => 'children beach',
        'Camping' => 'family camping children',
        'Shopping' => 'children shopping bags',
    ],
];

$lessonTopics = [];
foreach ($unitTopics as $unit => $topics) {
    foreach ($topics as $topic => $query) {
        $lessonTopics[] = [
            'unit' => $unit,
            'topic' => $topic,
            'image' => 'https://source.unsplash.com/1200x850/?' . rawurlencode($query),
        ];
    }
}
$firstLesson = $lessonTopics[0];
?>

<section class="section narrow center">
    <span class="eyebrow">Lessons</span>
    <h1>Explore beginner English topics by unit.</h1>
    <p class="lede">Choose a topic on the left and preview the lesson image instantly on the right.</p>
</section>

<section class="lessons-shell" data-lessons>
    <aside class="lesson-sidebar" aria-label="Lesson topics">
        <?php foreach ($unitTopics as $unit => $topics): ?>
            <section class="lesson-unit">
                <h2><?= e($unit) ?></h2>
                <div class="lesson-topic-list">
                    <?php foreach ($topics as $topic => $query): ?>
                        <?php $image = 'https://source.unsplash.com/1200x850/?' . rawurlencode($query); ?>
                        <button
                            class="lesson-topic-button <?= $topic === $firstLesson['topic'] ? 'active' : '' ?>"
                            type="button"
                            data-lesson-topic="<?= e($topic) ?>"
                            data-lesson-unit="<?= e($unit) ?>"
                            data-lesson-image="<?= e($image) ?>"
                        >
                            <?= e($topic) ?>
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
        <img data-lesson-image-preview src="<?= e($firstLesson['image']) ?>" alt="<?= e($firstLesson['topic']) ?> lesson preview">
    </article>
</section>
