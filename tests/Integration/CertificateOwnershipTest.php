<?php

declare(strict_types=1);

namespace IMatchBetter\Tests\Integration;

use IMatchBetter\Models\Certificate;

final class CertificateOwnershipTest extends DatabaseTestCase
{
    public function testOwnerCanBeIdentified(): void
    {
        $owner = $this->makeUser('applicant');
        $stranger = $this->makeUser('applicant');

        $certId = Certificate::create($owner, 'cert.pdf', 'stored.pdf', 'uploads/certificates/stored.pdf', 'application/pdf', 2048);

        $this->assertTrue(Certificate::isOwnedBy($certId, $owner));
        $this->assertFalse(Certificate::isOwnedBy($certId, $stranger));
    }

    public function testIsOwnedByIsFalseForNonexistentCertificate(): void
    {
        $someone = $this->makeUser('applicant');

        $this->assertFalse(Certificate::isOwnedBy(999999, $someone));
    }

    public function testForApplicantOnlyReturnsThatApplicantsCertificates(): void
    {
        $applicantA = $this->makeUser('applicant');
        $applicantB = $this->makeUser('applicant');

        Certificate::create($applicantA, 'a.pdf', 'a-stored.pdf', 'uploads/certificates/a-stored.pdf', 'application/pdf', 100);
        Certificate::create($applicantB, 'b.pdf', 'b-stored.pdf', 'uploads/certificates/b-stored.pdf', 'application/pdf', 100);

        $results = Certificate::forApplicant($applicantA);

        $this->assertCount(1, $results);
        $this->assertSame('a.pdf', $results[0]['original_filename']);
    }
}
