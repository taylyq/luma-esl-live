<section class="app-shell">
    <div class="app-heading">
        <span class="eyebrow">Educator onboarding</span>
        <h1>Shape a profile students can trust.</h1>
        <span class="status <?= e($profile['approval_status']) ?>"><?= e($profile['approval_status']) ?></span>
    </div>
    <form method="post" action="/teacher/profile" class="panel form-grid" enctype="multipart/form-data">
        <input type="hidden" name="_token" value="<?= csrf_token() ?>">
        <label>Headline<input name="headline" required value="<?= e($profile['headline']) ?>"></label>
        <label>Years experience<input name="years_experience" type="number" min="0" value="<?= (int) $profile['years_experience'] ?>"></label>
        <label>Hourly rate<input name="hourly_rate" type="number" min="0" step="1" value="<?= e((string) $profile['hourly_rate']) ?>"></label>
        <label>Timezone<input name="timezone" value="<?= e($profile['timezone']) ?>"></label>
        <label>Native language<input name="native_language" value="<?= e($profile['native_language']) ?>"></label>
        <label>Teaching languages<input name="teaching_languages" value="<?= e($profile['teaching_languages']) ?>"></label>
        <label>Specialties<input name="specialties" placeholder="Conversation, Interview prep" value="<?= e($profile['specialties']) ?>"></label>
        <label>Profile photo URL<input name="profile_photo" value="<?= e($profile['profile_photo']) ?>"></label>
        <label>Upload profile photo<input name="profile_photo_upload" type="file" accept="image/jpeg,image/png,image/webp"></label>
        <?php if (!empty($profile['profile_photo'])): ?>
            <div class="photo-preview"><img src="<?= e($profile['profile_photo']) ?>" alt="Current profile photo"></div>
        <?php endif; ?>
        <label class="span-2">Bio<textarea name="bio" rows="7" required><?= e($profile['bio']) ?></textarea></label>
        <button class="button button-dark" type="submit">Save profile</button>
    </form>
</section>
