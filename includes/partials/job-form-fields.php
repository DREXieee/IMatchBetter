<?php
/** @var array $job */
$job = $job ?? [];
$employmentTypes = ['full_time' => 'Full-time', 'part_time' => 'Part-time', 'contract' => 'Contract', 'internship' => 'Internship', 'remote' => 'Remote'];
?>
<div class="form-group">
    <label class="form-label" for="title">Job title</label>
    <input class="form-control" type="text" id="title" name="title" value="<?= h($job['title'] ?? '') ?>" required>
</div>

<div class="form-group">
    <label class="form-label" for="description">Description</label>
    <textarea class="form-control" id="description" name="description" rows="6" required><?= h($job['description'] ?? '') ?></textarea>
</div>

<div class="form-group">
    <label class="form-label" for="requirements">Requirements (optional)</label>
    <textarea class="form-control" id="requirements" name="requirements" rows="4"><?= h($job['requirements'] ?? '') ?></textarea>
</div>

<div class="form-group">
    <label class="form-label" for="required_skills">Required skills (comma-separated)</label>
    <input class="form-control" type="text" id="required_skills" name="required_skills" value="<?= h($job['required_skills'] ?? '') ?>" placeholder="PHP, MySQL, REST APIs">
    <p class="form-hint">Used to match and rank applicants — separate from the free-text requirements above.</p>
</div>

<div class="form-group">
    <label class="form-label" for="preferred_skills">Preferred skills (optional, comma-separated)</label>
    <input class="form-control" type="text" id="preferred_skills" name="preferred_skills" value="<?= h($job['preferred_skills'] ?? '') ?>" placeholder="Docker, AWS">
</div>

<div class="grid grid-2">
    <div class="form-group">
        <label class="form-label" for="location">Location</label>
        <input class="form-control" type="text" id="location" name="location" value="<?= h($job['location'] ?? '') ?>" placeholder="e.g. Manila, Philippines or Remote">
    </div>
    <div class="form-group">
        <label class="form-label" for="employment_type">Employment type</label>
        <select class="form-control" id="employment_type" name="employment_type">
            <?php foreach ($employmentTypes as $value => $label): ?>
                <option value="<?= h($value) ?>" <?= ($job['employment_type'] ?? 'full_time') === $value ? 'selected' : '' ?>><?= h($label) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
</div>

<div class="grid grid-2">
    <div class="form-group">
        <label class="form-label" for="salary_min">Minimum salary (optional)</label>
        <input class="form-control" type="number" id="salary_min" name="salary_min" min="0" value="<?= h((string) ($job['salary_min'] ?? '')) ?>">
    </div>
    <div class="form-group">
        <label class="form-label" for="salary_max">Maximum salary (optional)</label>
        <input class="form-control" type="number" id="salary_max" name="salary_max" min="0" value="<?= h((string) ($job['salary_max'] ?? '')) ?>">
    </div>
</div>

<div class="form-group">
    <label class="form-label" for="category">Category (optional)</label>
    <input class="form-control" type="text" id="category" name="category" value="<?= h($job['category'] ?? '') ?>" placeholder="e.g. Software Development">
</div>

<div class="form-group">
    <label class="form-label">
        <input type="checkbox" name="offers_training" value="1" <?= !empty($job['offers_training']) ? 'checked' : '' ?>>
        This role offers training, promotions, or long-term career growth
    </label>
</div>

<div class="form-group">
    <label class="form-label" for="career_growth_notes">Career growth details (optional)</label>
    <textarea class="form-control" id="career_growth_notes" name="career_growth_notes" rows="2" placeholder="e.g. Structured mentorship track with a promotion review every 6 months."><?= h($job['career_growth_notes'] ?? '') ?></textarea>
</div>

<div class="form-group">
    <label class="form-label" for="status">Status</label>
    <select class="form-control" id="status" name="status">
        <option value="draft" <?= ($job['status'] ?? 'draft') === 'draft' ? 'selected' : '' ?>>Save as draft (not visible to applicants)</option>
        <option value="open" <?= ($job['status'] ?? '') === 'open' ? 'selected' : '' ?>>Publish (open for applications)</option>
        <?php if (($job['status'] ?? '') === 'closed'): ?>
            <option value="closed" selected>Closed (not accepting applications)</option>
        <?php endif; ?>
    </select>
</div>
