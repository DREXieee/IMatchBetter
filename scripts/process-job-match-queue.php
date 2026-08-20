<?php

/**
 * Drains job_match_queue: for each pending row, scores every visible applicant against the
 * job and writes a "job_match" notification for anyone above the threshold. Meant to run
 * off the request path, on a schedule — see the "How to schedule" note below.
 */

// How to schedule, Windows Task Scheduler (run every 5 minutes):
//   schtasks /create /tn "IMatchBetter Job Match Queue" /tr "C:\xampp\php\php.exe C:\xampp\htdocs\imatchbetter\scripts\process-job-match-queue.php" /sc minute /mo 5
//
// How to schedule, Linux cron (crontab -e), same 5-minute cadence:
//   */5 * * * * php /path/to/imatchbetter/scripts/process-job-match-queue.php >> /path/to/imatchbetter/logs/job-match-queue.log 2>&1

require __DIR__ . '/../includes/bootstrap.php';

use IMatchBetter\Models\Job;
use IMatchBetter\Models\JobMatchQueue;
use IMatchBetter\Models\Notification;
use IMatchBetter\Services\SkillMatchService;

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('This script can only be run from the command line.');
}

$batch = JobMatchQueue::pending(20);

if (empty($batch)) {
    echo "No pending job-match notifications.\n";
    exit(0);
}

foreach ($batch as $row) {
    $queueId = (int) $row['id'];
    $jobId = (int) $row['job_id'];

    try {
        $job = Job::find($jobId);

        if ($job) {
            $matched = SkillMatchService::applicantsAboveThreshold($jobId);

            foreach ($matched as $applicant) {
                Notification::create(
                    $applicant['user_id'],
                    'job_match',
                    'A new job matching your skills was posted: ' . $job['title'],
                    $jobId,
                    'job'
                );
            }

            echo "Queue #{$queueId}: job #{$jobId} matched " . count($matched) . " applicant(s).\n";
        } else {
            echo "Queue #{$queueId}: job #{$jobId} no longer exists, skipping.\n";
        }

        JobMatchQueue::markDone($queueId);
    } catch (\Throwable $e) {
        JobMatchQueue::markFailed($queueId, $e->getMessage());
        fwrite(STDERR, "Queue #{$queueId} failed: {$e->getMessage()}\n");
    }
}
