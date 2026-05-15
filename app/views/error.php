<section class="section narrow center">
    <div class="empty-state">
        <span class="eyebrow">Setup</span>
        <h1><?= e($title ?? 'Something needs attention') ?></h1>
        <p><?= e($message ?? 'Please try again.') ?></p>
        <?php if (!empty($details) && is_array($details)): ?>
            <div class="setup-details">
                <?php foreach ($details as $label => $value): ?>
                    <div>
                        <span><?= e(ucwords(str_replace('_', ' ', (string) $label))) ?></span>
                        <strong><?= e((string) $value) ?></strong>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <a class="button button-dark" href="/">Return home</a>
    </div>
</section>
