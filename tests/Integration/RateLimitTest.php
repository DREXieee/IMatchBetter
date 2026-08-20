<?php

declare(strict_types=1);

namespace IMatchBetter\Tests\Integration;

use IMatchBetter\Models\Complaint;
use IMatchBetter\Models\EmployerReview;

final class RateLimitTest extends DatabaseTestCase
{
    public function testComplaintCountRecentByUserOnlyCountsThatUser(): void
    {
        $userA = $this->makeUser('applicant');
        $userB = $this->makeUser('applicant');
        $employer = $this->makeUser('employer');
        $job = $this->makeJob($employer);

        $this->assertSame(0, Complaint::countRecentByUser($userA));

        Complaint::create($userA, 'job', $job, 'other', 'First.');
        Complaint::create($userA, 'job', $job, 'other', 'Second.');
        Complaint::create($userB, 'job', $job, 'other', 'From someone else.');

        $this->assertSame(2, Complaint::countRecentByUser($userA));
        $this->assertSame(1, Complaint::countRecentByUser($userB));
    }

    public function testComplaintCountRecentByUserRespectsWindow(): void
    {
        $userId = $this->makeUser('applicant');
        $employer = $this->makeUser('employer');
        $job = $this->makeJob($employer);

        $complaintId = Complaint::create($userId, 'job', $job, 'other', 'Old one.');
        $this->pdo->prepare('UPDATE complaints SET created_at = DATE_SUB(NOW(), INTERVAL 25 HOUR) WHERE id = ?')
            ->execute([$complaintId]);

        $this->assertSame(0, Complaint::countRecentByUser($userId, 24));
    }

    public function testEmployerReviewCountRecentByAuthor(): void
    {
        $applicant = $this->makeUser('applicant');
        $employerA = $this->makeUser('employer');
        $employerB = $this->makeUser('employer');

        $this->assertSame(0, EmployerReview::countRecentByAuthor($applicant));

        EmployerReview::create($employerA, $applicant, null, 5, null, 'Great.');
        EmployerReview::create($employerB, $applicant, null, 4, null, 'Also great.');

        $this->assertSame(2, EmployerReview::countRecentByAuthor($applicant));
    }
}
