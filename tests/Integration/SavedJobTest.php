<?php

declare(strict_types=1);

namespace IMatchBetter\Tests\Integration;

use IMatchBetter\Models\SavedJob;

final class SavedJobTest extends DatabaseTestCase
{
    public function testSaveAndUnsaveTogglesMembership(): void
    {
        $employer = $this->makeUser('employer');
        $applicant = $this->makeUser('applicant');
        $job = $this->makeJob($employer);

        $this->assertFalse(SavedJob::isSaved($applicant, $job));

        SavedJob::save($applicant, $job);
        $this->assertTrue(SavedJob::isSaved($applicant, $job));

        SavedJob::unsave($applicant, $job);
        $this->assertFalse(SavedJob::isSaved($applicant, $job));
    }

    public function testSavingTwiceDoesNotError(): void
    {
        $employer = $this->makeUser('employer');
        $this->makeEmployerProfile($employer);
        $applicant = $this->makeUser('applicant');
        $job = $this->makeJob($employer);

        SavedJob::save($applicant, $job);
        SavedJob::save($applicant, $job);

        $this->assertTrue(SavedJob::isSaved($applicant, $job));
        $this->assertCount(1, SavedJob::forApplicant($applicant));
    }

    public function testForApplicantOnlyReturnsOwnSavedJobs(): void
    {
        $employer = $this->makeUser('employer');
        $this->makeEmployerProfile($employer);
        $applicantA = $this->makeUser('applicant');
        $applicantB = $this->makeUser('applicant');
        $job = $this->makeJob($employer);

        SavedJob::save($applicantA, $job);

        $this->assertCount(1, SavedJob::forApplicant($applicantA));
        $this->assertCount(0, SavedJob::forApplicant($applicantB));
    }

    public function testSavedJobIdSetIsKeyedByJobId(): void
    {
        $employer = $this->makeUser('employer');
        $applicant = $this->makeUser('applicant');
        $jobA = $this->makeJob($employer);
        $jobB = $this->makeJob($employer);

        SavedJob::save($applicant, $jobA);

        $set = SavedJob::savedJobIdSet($applicant);

        $this->assertTrue($set[$jobA] ?? false);
        $this->assertFalse($set[$jobB] ?? false);
    }
}
