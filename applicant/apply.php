<?php

require __DIR__ . '/../includes/bootstrap.php';

use IMatchBetter\Auth\Auth;
use IMatchBetter\Auth\Csrf;
use IMatchBetter\Auth\Guard;
use IMatchBetter\Models\Application;
use IMatchBetter\Models\ApplicantProfile;
use IMatchBetter\Models\Job;
use IMatchBetter\Models\Notification;
use IMatchBetter\Models\Resume;
use IMatchBetter\Models\User;
use IMatchBetter\Services\FileUploadService;
use IMatchBetter\Services\Mailer;

Guard::requireRole('applicant');
Guard::requireVerified();

$userId = (int) Auth::id();
$jobId = (int) ($_GET['job_id'] ?? $_POST['job_id'] ?? 0);
$job = Job::find($jobId);

if (!$job || $job['status'] !== 'open') {
    http_response_code(404);
    exit('Job not found or no longer accepting applications.');
}

if (Application::hasApplied($userId, $jobId)) {
    flash('info', 'You already applied to this job.');
    redirect('/applicant/my-applications.php');
}

$profile = ApplicantProfile::findByUserId($userId);
$resumes = Resume::forApplicant($userId);
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::verifyRequestOrFail();

    $coverLetter = trim($_POST['cover_letter'] ?? '');
    $resumeChoice = $_POST['resume_id'] ?? '';
    $resumeId = null;

    if ($resumeChoice === 'new') {
        try {
            $stored = FileUploadService::storeResume($_FILES['resume'] ?? []);
            $resumeId = Resume::create(
                $userId,
                $stored['original_filename'],
                $stored['stored_filename'],
                $stored['file_path'],
                $stored['mime_type'],
                $stored['file_size']
            );
            ApplicantProfile::setCurrentResume($userId, $resumeId);
        } catch (\RuntimeException $e) {
            $errors['resume'] = $e->getMessage();
        }
    } elseif (ctype_digit((string) $resumeChoice) && Resume::isOwnedBy((int) $resumeChoice, $userId)) {
        $resumeId = (int) $resumeChoice;
    } else {
        $errors['resume'] = 'Please choose a resume or upload a new one.';
    }

    if (empty($errors)) {
        $applicationId = Application::create($jobId, $userId, $resumeId, $coverLetter ?: null);

        $employerNotificationId = Notification::create(
            (int) $job['employer_id'],
            'new_application',
            (Auth::fullName() ?? 'An applicant') . ' applied to ' . $job['title'] . '.',
            $applicationId,
            'application'
        );

        $applicantUser = User::findById($userId);
        $employerUser = User::findById((int) $job['employer_id']);

        Mailer::send(
            $applicantUser['email'],
            $applicantUser['full_name'],
            'Application received: ' . $job['title'],
            'application-received',
            ['applicantName' => $applicantUser['full_name'], 'jobTitle' => $job['title']]
        );

        $employerEmailSent = Mailer::send(
            $employerUser['email'],
            $employerUser['full_name'],
            'New applicant for ' . $job['title'],
            'employer-new-applicant',
            ['applicantName' => $applicantUser['full_name'], 'jobTitle' => $job['title'], 'applicationId' => $applicationId]
        );
        if ($employerEmailSent) {
            Notification::markEmailSent($employerNotificationId);
        }

        flash('success', 'Application submitted!');
        redirect('/applicant/my-applications.php');
    }
}

$pageTitle = 'Apply — ' . $job['title'] . ' — IMatchBetter';
require __DIR__ . '/../includes/header.php';
?>
<main class="container" style="padding: 2.5rem 0; max-width: 640px;">
    <h1>Apply to <?= h($job['title']) ?></h1>

    <form method="post" action="<?= h(base_url('applicant/apply.php?job_id=' . $jobId)) ?>" enctype="multipart/form-data" class="card">
        <?= Csrf::field() ?>
        <input type="hidden" name="job_id" value="<?= $jobId ?>">

        <div class="form-group">
            <label class="form-label">Resume</label>
            <?php if (!empty($resumes)): ?>
                <?php foreach ($resumes as $i => $resume): ?>
                    <label style="display:block; font-weight:400; margin-bottom:0.4rem;">
                        <input type="radio" name="resume_id" value="<?= $resume['id'] ?>" <?= ($profile['current_resume_id'] ?? null) == $resume['id'] || $i === 0 ? 'checked' : '' ?>>
                        <?= h($resume['original_filename']) ?> (<?= h(date('M j, Y', strtotime($resume['uploaded_at']))) ?>)
                    </label>
                <?php endforeach; ?>
            <?php endif; ?>
            <label style="display:block; font-weight:400; margin-top:0.5rem;">
                <input type="radio" name="resume_id" value="new" <?= empty($resumes) ? 'checked' : '' ?>>
                Upload a new resume
            </label>
            <input class="form-control" type="file" name="resume" accept=".pdf,.doc,.docx" style="margin-top:0.5rem;">
            <?php if (!empty($errors['resume'])): ?><div class="form-error"><?= h($errors['resume']) ?></div><?php endif; ?>
        </div>

        <div class="form-group">
            <label class="form-label" for="cover_letter">Cover letter (optional)</label>
            <textarea class="form-control" id="cover_letter" name="cover_letter" rows="6" placeholder="Tell the employer why you're a great fit."></textarea>
        </div>

        <button type="submit" class="btn btn-primary">Submit Application</button>
    </form>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>
