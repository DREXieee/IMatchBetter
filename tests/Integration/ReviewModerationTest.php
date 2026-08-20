<?php

declare(strict_types=1);

namespace IMatchBetter\Tests\Integration;

use IMatchBetter\Models\ApplicantReview;
use IMatchBetter\Models\EmployerReview;

final class ReviewModerationTest extends DatabaseTestCase
{
    public function testEmployerReviewStartsPendingAndHasReviewedPreventsDuplicates(): void
    {
        $employer = $this->makeUser('employer');
        $applicant = $this->makeUser('applicant');

        $this->assertFalse(EmployerReview::hasReviewed($applicant, $employer));

        $reviewId = EmployerReview::create($employer, $applicant, null, 5, 'Great', 'Loved it.');

        $review = EmployerReview::find($reviewId);
        $this->assertSame('pending', $review['status']);
        $this->assertTrue(EmployerReview::hasReviewed($applicant, $employer));
    }

    public function testApprovedEmployerReviewCountsTowardStatsButPendingDoesNot(): void
    {
        $employer = $this->makeUser('employer');
        $applicantA = $this->makeUser('applicant');
        $applicantB = $this->makeUser('applicant');
        $admin = $this->makeUser('admin');

        $pendingId = EmployerReview::create($employer, $applicantA, null, 3, null, 'pending review');
        $approvedId = EmployerReview::create($employer, $applicantB, null, 5, null, 'approved review');
        EmployerReview::moderate($approvedId, 'approved', $admin);

        $stats = EmployerReview::statsForEmployer($employer);
        $this->assertSame(1, (int) $stats['review_count']);
        $this->assertSame(5.0, (float) $stats['avg_rating']);

        $publicList = EmployerReview::forEmployer($employer);
        $this->assertCount(1, $publicList);
        $this->assertSame($approvedId, (int) $publicList[0]['id']);
    }

    public function testLatestApprovedOnlyReturnsApprovedReviewsMostRecentFirst(): void
    {
        $employer = $this->makeUser('employer');
        $this->makeEmployerProfile($employer, 'Acme Robotics');
        $applicantA = $this->makeUser('applicant');
        $applicantB = $this->makeUser('applicant');
        $admin = $this->makeUser('admin');

        $pendingId = EmployerReview::create($employer, $applicantA, null, 3, 'Pending review', 'Not moderated yet.');
        $approvedId = EmployerReview::create($employer, $applicantB, null, 5, 'Great communication', 'Fast responses.');
        EmployerReview::moderate($approvedId, 'approved', $admin);

        $latest = EmployerReview::latestApproved(5);

        $this->assertCount(1, $latest);
        $this->assertSame($approvedId, (int) $latest[0]['id']);
        $this->assertNotContains($pendingId, array_column($latest, 'id'));
        $this->assertSame('Acme Robotics', $latest[0]['company_name']);
    }

    public function testApplicantReviewModerationMirrorsEmployerReview(): void
    {
        $employer = $this->makeUser('employer');
        $applicant = $this->makeUser('applicant');
        $admin = $this->makeUser('admin');

        $this->assertFalse(ApplicantReview::hasReviewed($employer, $applicant));

        $reviewId = ApplicantReview::create($applicant, $employer, null, 4, 'Solid', 'Would hire again.');
        $this->assertTrue(ApplicantReview::hasReviewed($employer, $applicant));

        ApplicantReview::moderate($reviewId, 'rejected', $admin);
        $review = ApplicantReview::find($reviewId);

        $this->assertSame('rejected', $review['status']);
        $this->assertSame($admin, (int) $review['moderated_by']);
        $this->assertNotNull($review['moderated_at']);
    }
}
