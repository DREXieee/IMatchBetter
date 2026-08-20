<?php

declare(strict_types=1);

namespace IMatchBetter\Tests\Integration;

use IMatchBetter\Models\JobMatchQueue;

final class JobMatchQueueTest extends DatabaseTestCase
{
    public function testEnqueuedJobAppearsInPendingUntilMarkedDone(): void
    {
        $employer = $this->makeUser('employer');
        $job = $this->makeJob($employer);

        $queueId = JobMatchQueue::enqueue($job);

        $pendingIds = array_column(JobMatchQueue::pending(), 'id');
        $this->assertContains($queueId, $pendingIds);

        JobMatchQueue::markDone($queueId);

        $pendingIds = array_column(JobMatchQueue::pending(), 'id');
        $this->assertNotContains($queueId, $pendingIds);
    }

    public function testMarkFailedRecordsErrorAndRemovesFromPending(): void
    {
        $employer = $this->makeUser('employer');
        $job = $this->makeJob($employer);
        $queueId = JobMatchQueue::enqueue($job);

        JobMatchQueue::markFailed($queueId, 'Something went wrong.');

        $stmt = $this->pdo->prepare('SELECT status, error FROM job_match_queue WHERE id = ?');
        $stmt->execute([$queueId]);
        $row = $stmt->fetch();

        $this->assertSame('failed', $row['status']);
        $this->assertSame('Something went wrong.', $row['error']);

        $pendingIds = array_column(JobMatchQueue::pending(), 'id');
        $this->assertNotContains($queueId, $pendingIds);
    }
}
