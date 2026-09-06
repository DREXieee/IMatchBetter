<?php

namespace IMatchBetter\Models;

use IMatchBetter\Config\Database;
use PDO;

/**
 * Professional network connections between any two users (applicant-applicant,
 * applicant-employer, etc). A pair is stored once, in whichever direction the
 * request was first sent — statusBetween()/relatedUserIds() check both
 * directions so a reverse request never creates a duplicate row.
 */
class Connection
{
    public static function statusBetween(int $userA, int $userB): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT * FROM connections
             WHERE (requester_id = ? AND recipient_id = ?) OR (requester_id = ? AND recipient_id = ?)'
        );
        $stmt->execute([$userA, $userB, $userB, $userA]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    /**
     * IDs of every user already connected to (or pending with) $userId, in
     * either direction — used to exclude them from suggestion lists.
     *
     * @return int[]
     */
    public static function relatedUserIds(int $userId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT requester_id, recipient_id FROM connections WHERE requester_id = ? OR recipient_id = ?'
        );
        $stmt->execute([$userId, $userId]);

        $ids = [];
        foreach ($stmt->fetchAll() as $row) {
            $ids[] = (int) ($row['requester_id'] == $userId ? $row['recipient_id'] : $row['requester_id']);
        }

        return array_values(array_unique($ids));
    }

    public static function sendRequest(int $requesterId, int $recipientId): ?int
    {
        if ($requesterId === $recipientId || self::statusBetween($requesterId, $recipientId) !== null) {
            return null;
        }

        $stmt = Database::connection()->prepare(
            'INSERT INTO connections (requester_id, recipient_id, status) VALUES (?, ?, ?)'
        );
        $stmt->execute([$requesterId, $recipientId, 'pending']);

        return (int) Database::connection()->lastInsertId();
    }

    public static function respond(int $connectionId, int $recipientId, bool $accept): bool
    {
        $stmt = Database::connection()->prepare(
            'UPDATE connections SET status = ?, responded_at = NOW()
             WHERE id = ? AND recipient_id = ? AND status = ?'
        );
        $stmt->execute([$accept ? 'accepted' : 'declined', $connectionId, $recipientId, 'pending']);

        return $stmt->rowCount() > 0;
    }

    /**
     * Accepted connections for $userId, with the counterpart's display info.
     */
    public static function listAccepted(int $userId): array
    {
        $stmt = Database::connection()->prepare(
            "SELECT c.*, u.id AS other_id, u.full_name AS other_name, u.role AS other_role,
                    ap.headline AS other_headline, ap.photo_path AS other_photo_path, ep.company_name AS other_company
             FROM connections c
             JOIN users u ON u.id = CASE WHEN c.requester_id = ? THEN c.recipient_id ELSE c.requester_id END
             LEFT JOIN applicant_profiles ap ON ap.user_id = u.id
             LEFT JOIN employer_profiles ep ON ep.user_id = u.id
             WHERE (c.requester_id = ? OR c.recipient_id = ?) AND c.status = 'accepted'
             ORDER BY c.responded_at DESC"
        );
        $stmt->execute([$userId, $userId, $userId]);

        return $stmt->fetchAll();
    }

    /**
     * Pending requests sent TO $userId, with the requester's display info.
     */
    public static function pendingIncoming(int $userId): array
    {
        $stmt = Database::connection()->prepare(
            "SELECT c.*, u.id AS other_id, u.full_name AS other_name, u.role AS other_role,
                    ap.headline AS other_headline, ap.photo_path AS other_photo_path, ep.company_name AS other_company
             FROM connections c
             JOIN users u ON u.id = c.requester_id
             LEFT JOIN applicant_profiles ap ON ap.user_id = u.id
             LEFT JOIN employer_profiles ep ON ep.user_id = u.id
             WHERE c.recipient_id = ? AND c.status = 'pending'
             ORDER BY c.created_at DESC"
        );
        $stmt->execute([$userId]);

        return $stmt->fetchAll();
    }

    /**
     * Candidate people for "People you may know" — active, non-admin users the
     * given user isn't already connected/pending with, preferring accounts that
     * share at least one tagged skill.
     */
    public static function suggestionsForUser(int $userId, int $limit = 6): array
    {
        $pdo = Database::connection();
        $excludeIds = self::relatedUserIds($userId);
        $excludeIds[] = $userId;

        $skillIds = Skill::idsForApplicant($userId);
        $preferredIds = empty($skillIds) ? [] : Skill::applicantIdsWithAnySkill($skillIds);
        $preferredIds = array_values(array_diff($preferredIds, $excludeIds));

        $placeholders = implode(',', array_fill(0, count($excludeIds), '?'));
        $orderBy = empty($preferredIds)
            ? 'u.created_at DESC'
            : 'FIELD(u.id, ' . implode(',', array_map('intval', $preferredIds)) . ') DESC, u.created_at DESC';
        $stmt = $pdo->prepare(
            "SELECT u.id, u.full_name, u.role, ap.headline AS applicant_headline, ap.photo_path,
                    ep.company_name
             FROM users u
             LEFT JOIN applicant_profiles ap ON ap.user_id = u.id
             LEFT JOIN employer_profiles ep ON ep.user_id = u.id
             WHERE u.is_active = 1 AND u.role != 'admin' AND u.id NOT IN ({$placeholders})
             ORDER BY {$orderBy}
             LIMIT ?"
        );
        $params = $excludeIds;
        $params[] = $limit;
        $i = 1;
        foreach ($params as $param) {
            $stmt->bindValue($i++, $param, is_int($param) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();

        return $stmt->fetchAll();
    }
}
