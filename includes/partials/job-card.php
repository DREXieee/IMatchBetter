<?php
/** @var array $job */
/** @var array<int,bool> $savedJobIds set by the including page; falls back to empty if not wired up */
$employmentLabels = ['full_time' => 'Full-time', 'part_time' => 'Part-time', 'contract' => 'Contract', 'internship' => 'Internship', 'remote' => 'Remote'];
$salary = '';
if (!empty($job['salary_min']) || !empty($job['salary_max'])) {
    $salary = h($job['salary_currency'] ?? 'PHP') . ' ' . number_format((float) ($job['salary_min'] ?? 0));
    if (!empty($job['salary_max'])) {
        $salary .= ' – ' . number_format((float) $job['salary_max']);
    }
}
$savedJobIds ??= [];
$isJobSaved = !empty($savedJobIds[(int) $job['id']]);
?>
<div class="job-card-wrap">
    <?php if (\IMatchBetter\Auth\Auth::check() && \IMatchBetter\Auth\Auth::role() === 'applicant'): ?>
        <form method="post" action="<?= h(base_url('applicant/save-job.php')) ?>" class="job-card-save-form">
            <?= \IMatchBetter\Auth\Csrf::field() ?>
            <input type="hidden" name="job_id" value="<?= (int) $job['id'] ?>">
            <input type="hidden" name="action" value="<?= $isJobSaved ? 'unsave' : 'save' ?>">
            <input type="hidden" name="redirect" value="<?= h($_SERVER['REQUEST_URI'] ?? '') ?>">
            <button type="submit" class="job-card-save-btn" aria-pressed="<?= $isJobSaved ? 'true' : 'false' ?>" title="<?= $isJobSaved ? 'Remove from saved jobs' : 'Save this job' ?>">
                <?= $isJobSaved ? '★ Saved' : '☆ Save' ?>
            </button>
        </form>
    <?php endif; ?>
    <a href="<?= h(base_url('job-view.php?slug=' . urlencode($job['slug']))) ?>" class="card job-card" data-animate="fade-up">
        <h3><?= h($job['title']) ?></h3>
        <p class="job-card-company"><?= h($job['company_name']) ?></p>
        <div class="job-card-meta">
            <?php if (!empty($job['location'])): ?><span><?= h($job['location']) ?></span><?php endif; ?>
            <span><?= h($employmentLabels[$job['employment_type']] ?? $job['employment_type']) ?></span>
            <?php if ($salary): ?><span><?= $salary ?></span><?php endif; ?>
        </div>
    </a>
</div>
