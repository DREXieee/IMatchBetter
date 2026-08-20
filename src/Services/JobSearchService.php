<?php

namespace IMatchBetter\Services;

use IMatchBetter\Config\Database;
use PDO;

class JobSearchService
{
    private const PER_PAGE = 20;

    /**
     * @return array{jobs: array, total: int, page: int, perPage: int, totalPages: int}
     */
    public static function search(array $filters): array
    {
        $q = trim($filters['q'] ?? '');
        $location = trim($filters['location'] ?? '');
        $type = trim($filters['type'] ?? '');
        $category = trim($filters['category'] ?? '');
        $page = max(1, (int) ($filters['page'] ?? 1));
        $perPage = self::PER_PAGE;

        $where = ["j.status = 'open'"];
        $params = [];

        if ($q !== '') {
            if (mb_strlen($q) >= 3) {
                $where[] = 'MATCH(j.title, j.description) AGAINST (? IN BOOLEAN MODE)';
                $params[] = $q . '*';
            } else {
                $where[] = '(j.title LIKE ? OR j.description LIKE ?)';
                $params[] = "%{$q}%";
                $params[] = "%{$q}%";
            }
        }

        if ($location !== '') {
            $where[] = 'j.location LIKE ?';
            $params[] = "%{$location}%";
        }

        if ($type !== '') {
            $where[] = 'j.employment_type = ?';
            $params[] = $type;
        }

        if ($category !== '') {
            $where[] = 'j.category = ?';
            $params[] = $category;
        }

        if (!empty($filters['careerGrowth'])) {
            $where[] = "(j.offers_training = 1 OR (j.career_growth_notes IS NOT NULL AND j.career_growth_notes <> ''))";
        }

        $whereSql = implode(' AND ', $where);
        $pdo = Database::connection();

        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM jobs j WHERE {$whereSql}");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();
        $totalPages = max(1, (int) ceil($total / $perPage));
        $page = min($page, $totalPages);
        $offset = ($page - 1) * $perPage;

        $sql = "SELECT j.*, ep.company_name
                FROM jobs j
                JOIN employer_profiles ep ON ep.user_id = j.employer_id
                WHERE {$whereSql}
                ORDER BY j.posted_at DESC
                LIMIT ? OFFSET ?";
        $stmt = $pdo->prepare($sql);
        $i = 1;
        foreach ($params as $param) {
            $stmt->bindValue($i++, $param);
        }
        $stmt->bindValue($i++, $perPage, PDO::PARAM_INT);
        $stmt->bindValue($i++, $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'jobs' => $stmt->fetchAll(),
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => $totalPages,
        ];
    }

    public static function featured(int $limit = 6): array
    {
        $stmt = Database::connection()->prepare(
            "SELECT j.*, ep.company_name
             FROM jobs j
             JOIN employer_profiles ep ON ep.user_id = j.employer_id
             WHERE j.status = 'open'
             ORDER BY j.posted_at DESC
             LIMIT ?"
        );
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }
}
