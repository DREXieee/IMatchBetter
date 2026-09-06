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

        $stmt = Database::connection()->prepare(
            'INSERT INTO jobs (employer_id, title, slug, description, requirements, location, employment_type, salary_min, salary_max, salary_currency, category, offers_training, career_growth_notes, status, posted_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['employer_id'],
            $data['title'],
            $slug,
            $data['description'],
            $data['requirements'] ?? null,
            $data['location'] ?? null,
            $data['employment_type'],
            $data['salary_min'] ?: null,
            $data['salary_max'] ?: null,
            $data['salary_currency'] ?? 'PHP',
            $data['category'] ?? null,
            !empty($data['offers_training']) ? 1 : 0,
            $data['career_growth_notes'] ?? null,
            $data['status'],
            $data['status'] === 'open' ? date('Y-m-d H:i:s') : null,
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $current = self::find($id);
        $slug = ($current && $current['title'] === $data['title']) ? $current['slug'] : self::uniqueSlug($data['title'], $id);
        $postedAt = ($data['status'] === 'open' && empty($current['posted_at'])) ? date('Y-m-d H:i:s') : $current['posted_at'];

        $stmt = Database::connection()->prepare(
            'UPDATE jobs SET title=?, slug=?, description=?, requirements=?, location=?, employment_type=?, salary_min=?, salary_max=?, salary_currency=?, category=?, offers_training=?, career_growth_notes=?, status=?, posted_at=?
             WHERE id = ?'
        );
        $stmt->execute([
            $data['title'],
            $slug,
            $data['description'],
            $data['requirements'] ?? null,
            $data['location'] ?? null,
            $data['employment_type'],
            $data['salary_min'] ?: null,
            $data['salary_max'] ?: null,
            $data['salary_currency'] ?? 'PHP',
            $data['category'] ?? null,
            !empty($data['offers_training']) ? 1 : 0,
            $data['career_growth_notes'] ?? null,
            $data['status'],
            $postedAt,
            $id,
        ]);
    }

    public static function close(int $id): void
    {
        $stmt = Database::connection()->prepare("UPDATE jobs SET status = 'closed', closed_at = NOW() WHERE id = ?");
        $stmt->execute([$id]);
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
