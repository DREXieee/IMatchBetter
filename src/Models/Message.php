<?php

namespace IMatchBetter\Models;

use IMatchBetter\Config\Database;
use PDO;

/**
 * Plain 1:1 direct messages. There is no separate "conversations" table —
 * a conversation is simply the set of messages between two user IDs, and the
 * conversation list is derived by grouping on the counterpart per user.
 */
class Message
{
    public static function send(int $senderId, int $recipientId, string $body): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO messages (sender_id, recipient_id, body) VALUES (?, ?, ?)'
        );
        $stmt->execute([$senderId, $recipientId, $body]);

        return (int) Database::connection()->lastInsertId();
    }

    public static function threadBetween(int $userA, int $userB, int $limit = 200): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT * FROM messages
             WHERE (sender_id = ? AND recipient_id = ?) OR (sender_id = ? AND recipient_id = ?)
             ORDER BY created_at ASC, id ASC
             LIMIT ?'
        );
        $stmt->bindValue(1, $userA, PDO::PARAM_INT);
        $stmt->bindValue(2, $userB, PDO::PARAM_INT);
        $stmt->bindValue(3, $userB, PDO::PARAM_INT);
        $stmt->bindValue(4, $userA, PDO::PARAM_INT);
        $stmt->bindValue(5, $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Messages received after $afterId in the thread between $userA and $userB —
     * used by the polling endpoint to fetch only new messages.
     */
    public static function threadSince(int $userA, int $userB, int $afterId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT * FROM messages
             WHERE ((sender_id = ? AND recipient_id = ?) OR (sender_id = ? AND recipient_id = ?)) AND id > ?
             ORDER BY created_at ASC, id ASC'
        );
        $stmt->execute([$userA, $userB, $userB, $userA, $afterId]);

        return $stmt->fetchAll();
    }

    /**
     * One row per counterpart the user has exchanged messages with, with the
     * latest message and an unread count, newest conversation first.
     */
    public static function conversationsForUser(int $userId): array
    {
        $stmt = Database::connection()->prepare(
            "SELECT
                other.id AS other_id, other.full_name AS other_name, other.role AS other_role,
                latest.body AS last_body, latest.created_at AS last_at, latest.sender_id AS last_sender_id,
                (SELECT COUNT(*) FROM messages m2 WHERE m2.sender_id = other.id AND m2.recipient_id = ? AND m2.is_read = 0) AS unread_count
             FROM (
                SELECT
                    CASE WHEN sender_id = ? THEN recipient_id ELSE sender_id END AS other_id,
                    MAX(id) AS latest_id
                FROM messages
                WHERE sender_id = ? OR recipient_id = ?
                GROUP BY other_id
             ) t
             JOIN users other ON other.id = t.other_id
             JOIN messages latest ON latest.id = t.latest_id
             ORDER BY latest.created_at DESC"
        );
        $stmt->execute([$userId, $userId, $userId, $userId]);

        return $stmt->fetchAll();
    }

    public static function markThreadRead(int $userId, int $counterpartId): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE messages SET is_read = 1 WHERE sender_id = ? AND recipient_id = ? AND is_read = 0'
        );
        $stmt->execute([$counterpartId, $userId]);
    }

    public static function unreadCount(int $userId): int
    {
        $stmt = Database::connection()->prepare(
            'SELECT COUNT(*) FROM messages WHERE recipient_id = ? AND is_read = 0'
        );
        $stmt->execute([$userId]);

        return (int) $stmt->fetchColumn();
    }

    /**
     * Whether $userA is allowed to message $userB: either an accepted
     * connection, or an existing job application linking an applicant and
     * that job's employer (in either direction) — prevents an open
     * messaging free-for-all with no relationship gate.
     */
    public static function canMessage(int $userA, int $userB): bool
    {
        $connection = Connection::statusBetween($userA, $userB);
        if ($connection !== null && $connection['status'] === 'accepted') {
            return true;
        }

        $stmt = Database::connection()->prepare(
            'SELECT 1 FROM applications a
             JOIN jobs j ON j.id = a.job_id
             WHERE (a.applicant_id = ? AND j.employer_id = ?) OR (a.applicant_id = ? AND j.employer_id = ?)
             LIMIT 1'
        );
        $stmt->execute([$userA, $userB, $userB, $userA]);

        return (bool) $stmt->fetchColumn();
    }
}
