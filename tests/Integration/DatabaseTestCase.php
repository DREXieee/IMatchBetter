<?php

declare(strict_types=1);

namespace IMatchBetter\Tests\Integration;

use IMatchBetter\Config\Database;
use PDO;
use PHPUnit\Framework\TestCase;

/**
 * Wraps every test in a transaction that's rolled back afterward, so tests never leave
 * data behind in the test database and never touch each other's rows.
 */
abstract class DatabaseTestCase extends TestCase
{
    protected PDO $pdo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pdo = Database::connection();
        $this->pdo->beginTransaction();
    }

    protected function tearDown(): void
    {
        if ($this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }

        parent::tearDown();
    }

    protected function makeUser(string $role, ?string $email = null): int
    {
        $email ??= $role . '_' . bin2hex(random_bytes(4)) . '@test.local';

        $stmt = $this->pdo->prepare(
            'INSERT INTO users (email, password_hash, role, full_name, is_active) VALUES (?, ?, ?, ?, 1)'
        );
        $stmt->execute([$email, password_hash('irrelevant', PASSWORD_DEFAULT), $role, ucfirst($role) . ' Test User']);

        return (int) $this->pdo->lastInsertId();
    }

    protected function makeEmployerProfile(int $userId, string $companyName = 'Test Co'): int
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO employer_profiles (user_id, company_name, approval_status) VALUES (?, ?, 'approved')"
        );
        $stmt->execute([$userId, $companyName]);

        return (int) $this->pdo->lastInsertId();
    }

    protected function makeApplicantProfile(int $userId, string $skills = ''): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO applicant_profiles (user_id, skills) VALUES (?, ?)');
        $stmt->execute([$userId, $skills]);
    }

    protected function makeJob(int $employerId, string $title = 'Test Job'): int
    {
        $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $title)) . '-' . bin2hex(random_bytes(3));

        $stmt = $this->pdo->prepare(
            "INSERT INTO jobs (employer_id, title, slug, description, status) VALUES (?, ?, ?, 'Test description', 'open')"
        );
        $stmt->execute([$employerId, $title, $slug]);

        return (int) $this->pdo->lastInsertId();
    }

    protected function makeResume(int $applicantId): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO resumes (applicant_id, original_filename, stored_filename, file_path, mime_type, file_size)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$applicantId, 'resume.pdf', 'stored.pdf', 'uploads/resumes/stored.pdf', 'application/pdf', 1024]);

        return (int) $this->pdo->lastInsertId();
    }

    protected function makeApplication(int $jobId, int $applicantId): int
    {
        $resumeId = $this->makeResume($applicantId);

        $stmt = $this->pdo->prepare(
            'INSERT INTO applications (job_id, applicant_id, resume_id) VALUES (?, ?, ?)'
        );
        $stmt->execute([$jobId, $applicantId, $resumeId]);

        return (int) $this->pdo->lastInsertId();
    }
}
