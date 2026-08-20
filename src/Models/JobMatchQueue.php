<?php

namespace IMatchBetter\Models;

use IMatchBetter\Config\Database;
use PDO;

/**
 * A tiny DB-backed queue: publishing a job enqueues a row here instead of scoring every
 * applicant inline in the same HTTP request. A separate worker (scripts/process-job-match-queue.php)
 * drains it, so a job-post request stays fast no matter how large the applicant pool grows.
 */
class JobMatchQueue
{
    public static function enqueue(int $jobId): int
    {
        $stmt = Database::connection()->prepare('INSERT INTO job_match_queue (job_id) VALUES (?)');
        $stmt->execute([$jobId]);

        return (int) Database::connection()->lastInsertId();
    }

    public static function pending(int $limit = 20): array
    {
        $stmt = Database::connection()->prepare(
            "SELECT * FROM job_match_queue WHERE status = 'pending' ORDER BY created_at ASC LIMIT ?"
        );
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public static function markDone(int $id): void
    {
        $stmt = Database::connection()->prepare("UPDATE job_match_queue SET status = 'done', processed_at = NOW() WHERE id = ?");
        $stmt->execute([$id]);
    }

    public static function markFailed(int $id, string $error): void
    {
        $stmt = Database::connection()->prepare(
            "UPDATE job_match_queue SET status = 'failed', error = ?, processed_at = NOW() WHERE id = ?"
        );
        $stmt->execute([substr($error, 0, 500), $id]);
    }
}
