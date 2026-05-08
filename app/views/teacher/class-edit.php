<section class="app-shell">
    <div class="app-heading">
        <span class="eyebrow">Edit class</span>
        <h1>Update this upcoming Zoom class.</h1>
    </div>

    <form method="post" action="/teacher/classes/update" class="panel form-grid">
        <input type="hidden" name="_token" value="<?= csrf_token() ?>">
        <input type="hidden" name="class_id" value="<?= (int) $class['id'] ?>">

        <label>Title<input name="title" value="<?= e($class['title']) ?>" required></label>
        <label>Class type
            <select name="class_type">
                <option value="private" <?= $class['class_type'] === 'private' ? 'selected' : '' ?>>Private</option>
                <option value="group" <?= $class['class_type'] === 'group' ? 'selected' : '' ?>>Group</option>
            </select>
        </label>
        <label class="span-2">Description<textarea name="description" rows="5" required><?= e($class['description']) ?></textarea></label>
        <label>Level<input name="english_level" value="<?= e($class['english_level']) ?>" required></label>
        <label>Capacity<input name="capacity" type="number" min="1" value="<?= (int) $class['capacity'] ?>"></label>
        <label>Price type
            <select name="price_currency">
                <option value="FREE" <?= ($class['price_currency'] ?? 'USD') === 'FREE' ? 'selected' : '' ?>>Free</option>
                <option value="USD" <?= ($class['price_currency'] ?? 'USD') === 'USD' ? 'selected' : '' ?>>USD</option>
                <option value="VND" <?= ($class['price_currency'] ?? 'USD') === 'VND' ? 'selected' : '' ?>>VND</option>
            </select>
        </label>
        <label>Price<input name="price" type="number" min="0" step="1" value="<?= e((string) (float) $class['price']) ?>" placeholder="Use 0 for free classes"></label>
        <label>Start time<input name="start_time" type="datetime-local" value="<?= e(date('Y-m-d\TH:i', strtotime($class['start_time']))) ?>" required></label>
        <label>Duration minutes<input name="duration" type="number" min="15" value="<?= (int) $duration ?>"></label>
        <label class="span-2">Zoom link<input name="zoom_link" value="<?= e($class['zoom_link']) ?>" placeholder="Hidden until approval"></label>
        <label class="span-2">Recurrence<input name="recurrence_rule" value="<?= e($class['recurrence_rule']) ?>" placeholder="Weekly"></label>

        <div class="inline-form span-2">
            <button class="button button-dark" type="submit">Save changes</button>
            <a class="button button-light" href="/teacher/classes">Cancel</a>
        </div>
    </form>
</section>
