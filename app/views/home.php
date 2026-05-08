<section class="hero">
    <div class="hero-copy">
        <span class="eyebrow">Verified online English classes</span>
        <h1>Find an English teacher who fits your life.</h1>
        <p>Speak first. Join with confidence. Browse polished educator profiles, message before you commit, and enter approved Zoom classes from one calm dashboard.</p>
        <div class="hero-actions">
            <a class="button button-dark" href="/teachers">Browse teachers</a>
            <a class="button button-light" href="/register">Become a teacher</a>
        </div>
        <div class="hero-proof">
            <div><strong>20-50</strong><span>launch teacher target</span></div>
            <div><strong>3 steps</strong><span>browse, message, join</span></div>
            <div><strong>Verified</strong><span>admin-reviewed educators</span></div>
        </div>
    </div>
    <div class="hero-media">
        <img src="https://images.unsplash.com/photo-1543269865-cbf427effbad?auto=format&fit=crop&w=1200&q=80" alt="Students learning English together">
        <div class="floating-card">
            <span>Next class</span>
            <strong>Conversation Circle</strong>
            <small>Today · 7:30 PM ICT · Zoom ready</small>
        </div>
    </div>
</section>

<section class="section">
    <div class="section-heading">
        <span class="eyebrow">Featured educators</span>
        <h2>Clear profiles. Real availability. No guesswork.</h2>
    </div>
    <div class="teacher-grid">
        <?php foreach ($teachers as $teacher): ?>
            <?php require __DIR__ . '/partials/teacher-card.php'; ?>
        <?php endforeach; ?>
    </div>
</section>

<section class="section split-band">
    <div>
        <span class="eyebrow">How it works</span>
        <h2>Message before you commit.</h2>
        <p>Students get confidence before joining. Teachers get context before accepting. The platform keeps class access protected until a request is approved.</p>
    </div>
    <div class="steps">
        <div><span>1</span><strong>Discover</strong><p>Search by goal, level, class type, rate, and availability.</p></div>
        <div><span>2</span><strong>Connect</strong><p>Use clean chat prompts to ask about fit before joining.</p></div>
        <div><span>3</span><strong>Join</strong><p>Approved students see Zoom links from their dashboard.</p></div>
    </div>
</section>

<section class="section trust-band">
    <span class="eyebrow">Built for trust</span>
    <h2>Verified educators only, with admin review and report-ready operations.</h2>
    <div class="trust-grid">
        <div>Identity review</div>
        <div>Hidden Zoom links</div>
        <div>Role-based dashboards</div>
        <div>Class request approval</div>
    </div>
</section>
