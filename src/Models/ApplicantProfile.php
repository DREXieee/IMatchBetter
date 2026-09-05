<?php

namespace IMatchBetter\Models;

use IMatchBetter\Config\Database;
use PDO;

class ApplicantProfile
{
    public static function findByUserId(int $userId): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM applicant_profiles WHERE user_id = ?');
        $stmt->execute([$userId]);
        $profile = $stmt->fetch();

        return $profile ?: null;
    }

    public static function create(int $userId): int
    {
        $stmt = Database::connection()->prepare('INSERT INTO applicant_profiles (user_id) VALUES (?)');
        $stmt->execute([$userId]);

        return (int) Database::connection()->lastInsertId();
    }

    /**
     * Updates any subset of profile fields by column name. Only keys present in
     * $fields are written — callers pass just the fields their form/step collects.
     *
     * Recognized keys: headline, bio, location, skills, school, degree,
     * field_of_study, graduation_year, profile_visibility, date_of_birth, gender,
     * street_address, city, province, zip_code, experience_level,
     * expected_salary_min, expected_salary_max.
     */
    public static function update(int $userId, array $fields): void
    {
        $allowedColumns = [
            'headline', 'bio', 'location', 'skills', 'school', 'degree',
            'field_of_study', 'graduation_year', 'profile_visibility',
            'date_of_birth', 'gender', 'street_address', 'city', 'province', 'zip_code',
            'experience_level', 'expected_salary_min', 'expected_salary_max',
        ];

        $columns = array_values(array_intersect($allowedColumns, array_keys($fields)));
        if (empty($columns)) {
            return;
        }

        $setClause = implode(', ', array_map(static fn (string $col): string => "{$col} = ?", $columns));
        $values = array_map(static fn (string $col) => $fields[$col], $columns);
        $values[] = $userId;

        $stmt = Database::connection()->prepare(
            "UPDATE applicant_profiles SET {$setClause} WHERE user_id = ?"
        );
        $stmt->execute($values);
    }

    public static function setCurrentResume(int $userId, int $resumeId): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE applicant_profiles SET current_resume_id = ? WHERE user_id = ?'
        );
        $stmt->execute([$resumeId, $userId]);
    }

    public static function updatePhoto(int $userId, string $photoPath): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE applicant_profiles SET photo_path = ? WHERE user_id = ?'
        );
        $stmt->execute([$photoPath, $userId]);
    }

    public static function setVisibility(int $userId, bool $visible): void
    {
        $stmt = Database::connection()->prepare('UPDATE applicant_profiles SET profile_visibility = ? WHERE user_id = ?');
        $stmt->execute([$visible ? 1 : 0, $userId]);
    }

    private const PER_PAGE = 20;

    /**
     * Searches applicant profiles that have opted into the employer talent database,
     * filtered by any combination of school/degree/field/graduation year/skills/location.
     *
     * @return array{graduates: array, total: int, page: int, perPage: int, totalPages: int}
     */
    public static function searchGraduates(array $filters): array
    {
        $where = ['ap.profile_visibility = 1'];

        return self::paginatedGraduateQuery($where, $filters);
    }

    /**
     * Same as searchGraduates() but ignores profile_visibility, for admin oversight of
     * every profile with education info filled in (visible or hidden).
     *
     * @return array{graduates: array, total: int, page: int, perPage: int, totalPages: int}
     */
    public static function allWithEducation(array $filters): array
    {
        $where = ["ap.school IS NOT NULL", "ap.school <> ''"];

        return self::paginatedGraduateQuery($where, $filters);
    }

    private static function paginatedGraduateQuery(array $where, array $filters): array
    {
        $params = [];

        if (!empty($filters['school'])) {
            $where[] = 'ap.school LIKE ?';
            $params[] = '%' . $filters['school'] . '%';
        }
        if (!empty($filters['degree'])) {
            $where[] = 'ap.degree LIKE ?';
            $params[] = '%' . $filters['degree'] . '%';
        }
        if (!empty($filters['field_of_study'])) {
            $where[] = 'ap.field_of_study LIKE ?';
            $params[] = '%' . $filters['field_of_study'] . '%';
        }
        if (!empty($filters['graduation_year'])) {
            $where[] = 'ap.graduation_year = ?';
            $params[] = (int) $filters['graduation_year'];
        }
        if (!empty($filters['skills'])) {
            $skillNames = Skill::parseList($filters['skills']);
            $skillIds = Skill::idsForExistingNames($skillNames);

            // At least one searched skill has never been tagged on any profile — no applicant
            // could possibly match, so stop here instead of running a query that must return 0.
            if (count($skillIds) < count($skillNames)) {
                return ['graduates' => [], 'total' => 0, 'page' => 1, 'perPage' => self::PER_PAGE, 'totalPages' => 1];
            }

            if (!empty($skillIds)) {
                $placeholders = implode(',', array_fill(0, count($skillIds), '?'));
                $where[] = "ap.user_id IN (
                    SELECT applicant_id FROM applicant_skills
                    WHERE skill_id IN ({$placeholders})
                    GROUP BY applicant_id
                    HAVING COUNT(DISTINCT skill_id) = ?
                )";
                $params = array_merge($params, $skillIds, [count($skillIds)]);
            }
        }
        if (!empty($filters['location'])) {
            $where[] = 'ap.location LIKE ?';
            $params[] = '%' . $filters['location'] . '%';
        }

        $whereSql = implode(' AND ', $where);
        $pdo = Database::connection();
        $perPage = self::PER_PAGE;

        $countStmt = $pdo->prepare(
            "SELECT COUNT(*) FROM applicant_profiles ap JOIN users u ON u.id = ap.user_id WHERE {$whereSql}"
        );
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();
        $totalPages = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, (int) ($filters['page'] ?? 1)), $totalPages);
        $offset = ($page - 1) * $perPage;

        $stmt = $pdo->prepare(
            "SELECT ap.*, u.full_name, u.email
             FROM applicant_profiles ap
             JOIN users u ON u.id = ap.user_id
             WHERE {$whereSql}
             ORDER BY ap.graduation_year DESC, ap.updated_at DESC
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
            'graduates' => $stmt->fetchAll(),
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => $totalPages,
        ];
    }
}
