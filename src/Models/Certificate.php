<?php

namespace IMatchBetter\Models;

use IMatchBetter\Config\Database;

class Certificate
{
    public static function create(int $applicantId, string $originalFilename, string $storedFilename, string $filePath, string $mimeType, int $fileSize): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO certificates (applicant_id, original_filename, stored_filename, file_path, mime_type, file_size) VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$applicantId, $originalFilename, $storedFilename, $filePath, $mimeType, $fileSize]);

        return (int) Database::connection()->lastInsertId();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM certificates WHERE id = ?');
        $stmt->execute([$id]);
        $certificate = $stmt->fetch();

        return $certificate ?: null;
    }

    public static function forApplicant(int $applicantId): array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM certificates WHERE applicant_id = ? ORDER BY uploaded_at DESC');
        $stmt->execute([$applicantId]);

        return $stmt->fetchAll();
    }

    public static function isOwnedBy(int $certificateId, int $applicantId): bool
    {
        $certificate = self::find($certificateId);

        return $certificate !== null && (int) $certificate['applicant_id'] === $applicantId;
    }

    public static function delete(int $certificateId): void
    {
        $stmt = Database::connection()->prepare('DELETE FROM certificates WHERE id = ?');
        $stmt->execute([$certificateId]);
    }
}
