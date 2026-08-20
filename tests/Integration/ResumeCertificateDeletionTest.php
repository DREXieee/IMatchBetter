<?php

declare(strict_types=1);

namespace IMatchBetter\Tests\Integration;

use IMatchBetter\Models\Certificate;
use IMatchBetter\Models\Resume;

final class ResumeCertificateDeletionTest extends DatabaseTestCase
{
    public function testUnusedResumeCanBeDeleted(): void
    {
        $applicant = $this->makeUser('applicant');
        $resumeId = $this->makeResume($applicant);

        $this->assertFalse(Resume::isInUse($resumeId));
        $this->assertTrue(Resume::delete($resumeId));
        $this->assertNull(Resume::find($resumeId));
    }

    public function testResumeSubmittedWithAnApplicationCannotBeDeleted(): void
    {
        $employer = $this->makeUser('employer');
        $applicant = $this->makeUser('applicant');
        $job = $this->makeJob($employer);

        // makeApplication() creates and attaches its own resume.
        $applicationId = $this->makeApplication($job, $applicant);
        $application = $this->pdo->prepare('SELECT resume_id FROM applications WHERE id = ?');
        $application->execute([$applicationId]);
        $resumeId = (int) $application->fetchColumn();

        $this->assertTrue(Resume::isInUse($resumeId));
        $this->assertFalse(Resume::delete($resumeId));
        $this->assertNotNull(Resume::find($resumeId));
    }

    public function testCertificateCanAlwaysBeDeleted(): void
    {
        $applicant = $this->makeUser('applicant');
        $certificateId = Certificate::create($applicant, 'cert.pdf', 'stored.pdf', 'uploads/certificates/stored.pdf', 'application/pdf', 100);

        Certificate::delete($certificateId);

        $this->assertNull(Certificate::find($certificateId));
    }
}
