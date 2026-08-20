<?php

namespace IMatchBetter\Models;

use IMatchBetter\Config\Database;
use PDO;

class Complaint
{
    private const PER_PAGE = 20;

    public static function create(int $complainantId, string $againstType, int $againstId, string $category, string $message): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO complaints (complainant_id, against_type, against_id, category, message) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$complainantId, $againstType, $againstId, $category, $message]);

        return (int) Database::connection()->lastInsertId();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT c.*, u.full_name AS complainant_name, u.email AS complainant_email
             FROM complaints c
             JOIN users u ON u.id = c.complainant_id
             WHERE c.id = ?'
        );
        $stmt->execute([$id]);
        $complaint = $stmt->fetch();

        return $complaint ?: null;
    }

    /**
     * @return array{complaints: array, total: int, page: int, perPage: int, totalPages: int}
     */
    public static function all(?string $status = null, int $page = 1): array
    {
        $whereSql = $status !== null ? ' WHERE c.status = ?' : '';
        $params = $status !== null ? [$status] : [];
        $pdo = Database::connection();
        $perPage = self::PER_PAGE;

        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM complaints c{$whereSql}");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();
        $totalPages = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, $page), $totalPages);
        $offset = ($page - 1) * $perPage;

        $stmt = $pdo->prepare(
            "SELECT c.*, u.full_name AS complainant_name, u.email AS complainant_email
             FROM complaints c
             JOIN users u ON u.id = c.complainant_id
             {$whereSql}
             ORDER BY c.created_at DESC
             LIMIT ? OFFSET ?"
        );
        $i = 1;
        foreach ($params as $param) {
            $stmt->bindValue($i++, $param);
        }
        $stmt->bindValue($i++, $perPage, PDO::PARAM_INT);
        $stmt->bindValue($i++, $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'complaints' => $stmt->fetchAll(),
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => $totalPages,
        ];
    }

    public static function forUser(int $complainantId): array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM complaints WHERE complainant_id = ? ORDER BY created_at DESC');
        $stmt->execute([$complainantId]);

        return $stmt->fetchAll();
    }

    public static function countRecentByUser(int $complainantId, int $hours = 24): int
    {
        $stmt = Database::connection()->prepare(
            'SELECT COUNT(*) FROM complaints WHERE complainant_id = ? AND created_at > DATE_SUB(NOW(), INTERVAL ? HOUR)'
        );
        $stmt->execute([$complainantId, $hours]);

        return (int) $stmt->fetchColumn();
    }

    public static function updateStatus(int $id, string $status, ?string $resolutionNotes, int $resolvedBy): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE complaints SET status = ?, resolution_notes = ?, resolved_by = ?, resolved_at = NOW() WHERE id = ?'
        );
        $stmt->execute([$status, $resolutionNotes, $resolvedBy, $id]);
    }

    /**
     * Resolves a (against_type, against_id) pair to a human-readable label for display.
     * Returns null if the target no longer exists (e.g. the job/application was deleted).
     */
    public static function describeTarget(string $type, int $id): ?string
    {
        $pdo = Database::connection();

        if ($type === 'job') {
            $stmt = $pdo->prepare('SELECT title FROM jobs WHERE id = ?');
            $stmt->execute([$id]);
            $title = $stmt->fetchColumn();

            return $title !== false ? 'Job posting: "' . $title . '"' : null;
        }

        if ($type === 'application') {
            $stmt = $pdo->prepare(
                'SELECT u.full_name AS applicant_name, j.title AS job_title
                 FROM applications a
                 JOIN users u ON u.id = a.applicant_id
                 JOIN jobs j ON j.id = a.job_id
                 WHERE a.id = ?'
            );
            $stmt->execute([$id]);
            $row = $stmt->fetch();

            return $row ? 'Application: ' . $row['applicant_name'] . " \u{2192} " . $row['job_title'] : null;
        }

        if ($type === 'user') {
            $stmt = $pdo->prepare('SELECT full_name, role FROM users WHERE id = ?');
            $stmt->execute([$id]);
            $row = $stmt->fetch();

            return $row ? 'User: ' . $row['full_name'] . ' (' . ucfirst($row['role']) . ')' : null;
        }

        return null;
    }
}
