<section class="hero home-hero">
    <div class="hero-copy">
        <span class="eyebrow">Speak first. Join with confidence.</span>
        <h1>Find an English teacher you can talk to before class.</h1>
        <p>Luma ESL helps students discover verified English educators, ask questions in a calm chat, and join approved Zoom classes with clear expectations.</p>
        <div class="hero-actions">
            <a class="button button-dark" href="/teachers">Browse teachers</a>
            <a class="button button-light" href="/calendar">View class calendar</a>
        </div>
        <div class="hero-proof">
            <div><strong><?= (int) ($stats['educators'] ?? 0) ?></strong><span>approved educators</span></div>
            <div><strong><?= (int) ($stats['classes'] ?? 0) ?></strong><span>upcoming classes</span></div>
            <div><strong><?= (int) ($stats['reviews'] ?? 0) ?></strong><span>published reviews</span></div>
        </div>
    </div>
    <div class="hero-media home-hero-media">
        <img src="https://images.unsplash.com/photo-1543269865-cbf427effbad?auto=format&fit=crop&w=1200&q=80" alt="Students learning English together" width="960" height="1200" decoding="async" fetchpriority="high">
        <div class="floating-card">
            <span>Student flow</span>
            <strong>Browse -> Message -> Join</strong>
            <small>Zoom links stay protected until class access is approved.</small>
        </div>
    </div>
</section>

<section class="section home-intro">
    <div class="section-heading compact">
        <span class="eyebrow">Why Luma ESL</span>
        <h2>Designed for learners who want clarity before they commit.</h2>
    </div>
    <div class="benefit-grid">
        <article>
            <span>01</span>
            <h3>Choose by fit</h3>
            <p>Compare teaching style, specialties, class format, level, availability, and student reviews before reaching out.</p>
        </article>
        <article>
            <span>02</span>
            <h3>Message safely</h3>
            <p>Ask about your goals, schedule, and level before joining. Reporting and blocking tools are built into the message experience.</p>
        </article>
        <article>
            <span>03</span>
            <h3>Join with confidence</h3>
            <p>Teachers approve class requests first, then students can access the Zoom class from their dashboard.</p>
        </article>
    </div>
</section>

<section class="section audience-band">
    <article>
        <span class="eyebrow">For students</span>
        <h2>Find the right teacher for real life English.</h2>
        <p>Practice conversation, prepare for interviews, build confidence for school or work, and message teachers before joining a class.</p>
        <a class="button button-dark" href="/teachers">Start browsing</a>
    </article>
    <article>
        <span class="eyebrow">For teachers</span>
        <h2>List classes and meet motivated learners.</h2>
        <p>Create a polished educator profile, publish available Zoom classes, manage requests, and keep student communication organized.</p>
        <a class="button button-light" href="/register">Become a teacher</a>
    </article>
</section>

<section class="section">
    <div class="section-heading">
        <span class="eyebrow">Featured educators</span>
        <h2>Clear profiles. Real availability. No guesswork.</h2>
    </div>
    <?php if ($teachers): ?>
        <div class="teacher-grid">
            <?php foreach ($teachers as $teacher): ?>
                <?php require __DIR__ . '/partials/teacher-card.php'; ?>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="empty-state">Approved teacher profiles will appear here soon.</div>
    <?php endif; ?>
</section>

<section class="section split-band home-flow">
    <div>
        <span class="eyebrow">How it works</span>
        <h2>A simple path from interest to class.</h2>
        <p>Students get confidence before joining. Teachers get context before accepting. Luma ESL keeps the process organized and easy to understand.</p>
    </div>
    <div class="steps">
        <div><span>1</span><strong>Discover</strong><p>Search teachers and upcoming classes by goal, level, class type, and availability.</p></div>
        <div><span>2</span><strong>Connect</strong><p>Use focused messages to ask about teaching style, schedule, and class fit.</p></div>
        <div><span>3</span><strong>Join</strong><p>Request access and join approved Zoom classes from your dashboard.</p></div>
    </div>
</section>

<section class="section trust-band official-trust">
    <div>
        <span class="eyebrow">Built for trust</span>
        <h2>Marketplace controls that help protect students, teachers, and class access.</h2>
    </div>
    <div class="trust-grid">
        <div>Admin-reviewed educators</div>
        <div>Adult-only messaging</div>
        <div>Email verification</div>
        <div>Hidden Zoom links</div>
        <div>Message reports</div>
        <div>Role-based dashboards</div>
        <div>Class request approval</div>
        <div>Teacher photo profiles</div>
    </div>
</section>
