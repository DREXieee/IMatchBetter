<?php

namespace IMatchBetter\Models;

use IMatchBetter\Config\Database;
use PDO;

class Job
{
    public static function slugify(string $title): string
    {
        $slug = strtolower(trim($title));
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        $slug = trim($slug, '-');

        return $slug !== '' ? $slug : 'job';
    }

    /**
     * Generates a unique slug for a job title, appending -2, -3, etc. on collision.
     */
    public static function uniqueSlug(string $title, ?int $excludeJobId = null): string
    {
        $base = self::slugify($title);
        $slug = $base;
        $suffix = 2;
        $pdo = Database::connection();

        while (true) {
            $sql = 'SELECT id FROM jobs WHERE slug = ?';
            $params = [$slug];
            if ($excludeJobId !== null) {
                $sql .= ' AND id != ?';
                $params[] = $excludeJobId;
            }
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            if (!$stmt->fetch()) {
                return $slug;
            }

            $slug = $base . '-' . $suffix;
            $suffix++;
        }
    }

    public static function create(array $data): int
    {
        $slug = self::uniqueSlug($data['title']);

        // A brand-new job never goes live immediately — if the employer asked to publish it,
        // it's held as a draft pending admin approval (see admin/jobs/approve.php).
        $wantsOpen = $data['status'] === 'open';
        $storedStatus = $wantsOpen ? 'draft' : $data['status'];
        $approvalStatus = $wantsOpen ? 'pending' : 'not_required';

        $stmt = Database::connection()->prepare(
            'INSERT INTO jobs (employer_id, title, slug, description, requirements, employment_process, scheduling_process, location, employment_type, salary_min, salary_max, salary_currency, category, offers_training, career_growth_notes, status, approval_status, approval_requested_at, posted_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['employer_id'],
            $data['title'],
            $slug,
            $data['description'],
            $data['requirements'] ?? null,
            $data['employment_process'] ?? null,
            $data['scheduling_process'] ?? null,
            $data['location'] ?? null,
            $data['employment_type'],
            $data['salary_min'] ?: null,
            $data['salary_max'] ?: null,
            $data['salary_currency'] ?? 'PHP',
            $data['category'] ?? null,
            !empty($data['offers_training']) ? 1 : 0,
            $data['career_growth_notes'] ?? null,
            $storedStatus,
            $approvalStatus,
            $wantsOpen ? date('Y-m-d H:i:s') : null,
            null,
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $current = self::find($id);
        $slug = ($current && $current['title'] === $data['title']) ? $current['slug'] : self::uniqueSlug($data['title'], $id);

        $alreadyApproved = $current && $current['approval_status'] === 'approved';
        $wantsOpen = $data['status'] === 'open';

        if ($wantsOpen && !$alreadyApproved) {
            // First-time publish attempt, or resubmission after rejection — stays hidden
            // from the public listings until an admin approves it.
            $storedStatus = 'draft';
            $approvalStatus = 'pending';
            $approvalRequestedAt = date('Y-m-d H:i:s');
            $rejectionReason = null;
        } elseif ($data['status'] === 'draft' && $current && $current['approval_status'] === 'pending') {
            // Employer withdrew a pending submission by saving it back as a draft.
            $storedStatus = 'draft';
            $approvalStatus = 'not_required';
            $approvalRequestedAt = $current['approval_requested_at'];
            $rejectionReason = $current['rejection_reason'];
        } else {
            // Already approved before (routine edit to a live job), or staying in draft/closed.
            $storedStatus = $data['status'];
            $approvalStatus = $current['approval_status'] ?? 'not_required';
            $approvalRequestedAt = $current['approval_requested_at'] ?? null;
            $rejectionReason = $current['rejection_reason'] ?? null;
        }

        $postedAt = ($storedStatus === 'open' && empty($current['posted_at'])) ? date('Y-m-d H:i:s') : $current['posted_at'];

        $stmt = Database::connection()->prepare(
            'UPDATE jobs SET title=?, slug=?, description=?, requirements=?, employment_process=?, scheduling_process=?, location=?, employment_type=?, salary_min=?, salary_max=?, salary_currency=?, category=?, offers_training=?, career_growth_notes=?, status=?, approval_status=?, approval_requested_at=?, rejection_reason=?, posted_at=?
             WHERE id = ?'
        );
        $stmt->execute([
            $data['title'],
            $slug,
            $data['description'],
            $data['requirements'] ?? null,
            $data['employment_process'] ?? null,
            $data['scheduling_process'] ?? null,
            $data['location'] ?? null,
            $data['employment_type'],
            $data['salary_min'] ?: null,
            $data['salary_max'] ?: null,
            $data['salary_currency'] ?? 'PHP',
            $data['category'] ?? null,
            !empty($data['offers_training']) ? 1 : 0,
            $data['career_growth_notes'] ?? null,
            $storedStatus,
            $approvalStatus,
            $approvalRequestedAt,
            $rejectionReason,
            $postedAt,
            $id,
        ]);
    }

    public static function close(int $id): void
    {
        $stmt = Database::connection()->prepare("UPDATE jobs SET status = 'closed', closed_at = NOW() WHERE id = ?");
        $stmt->execute([$id]);
    }

    public static function pendingApproval(int $limit = 100, int $offset = 0): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT j.*, ep.company_name
             FROM jobs j
             JOIN employer_profiles ep ON ep.user_id = j.employer_id
             WHERE j.approval_status = "pending"
             ORDER BY j.approval_requested_at ASC
             LIMIT ? OFFSET ?'
        );
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->bindValue(2, $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public static function approve(int $id, int $adminId): void
    {
        $stmt = Database::connection()->prepare(
            "UPDATE jobs SET status = 'open', approval_status = 'approved', reviewed_by = ?, reviewed_at = NOW(), rejection_reason = NULL, posted_at = COALESCE(posted_at, NOW()) WHERE id = ?"
        );
        $stmt->execute([$adminId, $id]);
    }

    public static function reject(int $id, int $adminId, string $reason): void
    {
        $stmt = Database::connection()->prepare(
            "UPDATE jobs SET status = 'draft', approval_status = 'rejected', reviewed_by = ?, reviewed_at = NOW(), rejection_reason = ? WHERE id = ?"
        );
        $stmt->execute([$adminId, $reason, $id]);
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM jobs WHERE id = ?');
        $stmt->execute([$id]);
        $job = $stmt->fetch();

        return $job ?: null;
    }

    public static function findBySlug(string $slug): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT j.*, ep.company_name, ep.logo_path
             FROM jobs j
             JOIN employer_profiles ep ON ep.user_id = j.employer_id
             WHERE j.slug = ?'
        );
        $stmt->execute([$slug]);
        $job = $stmt->fetch();

        return $job ?: null;
    }

    public static function forEmployer(int $employerId): array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM jobs WHERE employer_id = ? ORDER BY created_at DESC');
        $stmt->execute([$employerId]);

        return $stmt->fetchAll();
    }

    public static function isOwnedBy(int $jobId, int $employerId): bool
    {
        $job = self::find($jobId);

        return $job !== null && (int) $job['employer_id'] === $employerId;
    }

    /**
     * @param string $query  matched against job title/company name, case-insensitive substring
     * @param string $status 'open'|'closed'|'draft', or '' for any status
     */
    public static function adminList(int $limit = 100, int $offset = 0, string $query = '', string $status = ''): array
    {
        $where = [];
        $params = [];

        if ($query !== '') {
            $where[] = '(j.title LIKE ? OR ep.company_name LIKE ?)';
            $params[] = '%' . $query . '%';
            $params[] = '%' . $query . '%';
        }
        if (in_array($status, ['open', 'closed', 'draft'], true)) {
            $where[] = 'j.status = ?';
            $params[] = $status;
        }

        $whereSql = empty($where) ? '' : 'WHERE ' . implode(' AND ', $where);
        $stmt = Database::connection()->prepare(
            "SELECT j.*, ep.company_name
             FROM jobs j
             JOIN employer_profiles ep ON ep.user_id = j.employer_id
             {$whereSql}
             ORDER BY j.created_at DESC
             LIMIT ? OFFSET ?"
        );

        $i = 1;
        foreach ($params as $param) {
            $stmt->bindValue($i++, $param);
        }
        $stmt->bindValue($i++, $limit, PDO::PARAM_INT);
        $stmt->bindValue($i++, $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public static function adminRemove(int $id): void
    {
        $stmt = Database::connection()->prepare('DELETE FROM jobs WHERE id = ?');
        $stmt->execute([$id]);
    }

    public static function distinctCategories(): array
    {
        $stmt = Database::connection()->query(
            "SELECT DISTINCT category FROM jobs WHERE status = 'open' AND category IS NOT NULL AND category <> '' ORDER BY category ASC"
        );

        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}
