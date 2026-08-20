<?php

declare(strict_types=1);

namespace IMatchBetter\Tests\Integration;

use IMatchBetter\Models\Interview;

final class InterviewOwnershipTest extends DatabaseTestCase
{
    public function testIsForApplicantAndIsForEmployerDistinguishOwnership(): void
    {
        $employer = $this->makeUser('employer');
        $otherEmployer = $this->makeUser('employer');
        $applicant = $this->makeUser('applicant');
        $otherApplicant = $this->makeUser('applicant');

        $job = $this->makeJob($employer);
        $applicationId = $this->makeApplication($job, $applicant);

        $interviewId = Interview::create($applicationId, '2026-09-01 10:00:00', 'video', 'https://example.test', null, $employer);

        $this->assertTrue(Interview::isForApplicant($interviewId, $applicant));
        $this->assertFalse(Interview::isForApplicant($interviewId, $otherApplicant));

        $this->assertTrue(Interview::isForEmployer($interviewId, $employer));
        $this->assertFalse(Interview::isForEmployer($interviewId, $otherEmployer));
    }

    public function testUpdateStatusSetsRespondedAtOnlyWhenRequested(): void
    {
        $employer = $this->makeUser('employer');
        $applicant = $this->makeUser('applicant');
        $job = $this->makeJob($employer);
        $applicationId = $this->makeApplication($job, $applicant);
        $interviewId = Interview::create($applicationId, '2026-09-01 10:00:00', 'video', null, null, $employer);

        Interview::updateStatus($interviewId, 'confirmed', true);

        $interview = Interview::find($interviewId);
        $this->assertSame('confirmed', $interview['status']);
        $this->assertNotNull($interview['responded_at']);
    }

    public function testRescheduleResetsStatusToProposed(): void
    {
        $employer = $this->makeUser('employer');
        $applicant = $this->makeUser('applicant');
        $job = $this->makeJob($employer);
        $applicationId = $this->makeApplication($job, $applicant);
        $interviewId = Interview::create($applicationId, '2026-09-01 10:00:00', 'video', null, null, $employer);
        Interview::updateStatus($interviewId, 'confirmed', true);

        Interview::reschedule($interviewId, '2026-09-05 14:00:00', 'onsite', 'HQ', 'bring ID');

        $interview = Interview::find($interviewId);
        $this->assertSame('proposed', $interview['status']);
        $this->assertNull($interview['responded_at']);
        $this->assertSame('onsite', $interview['mode']);
    }
}
