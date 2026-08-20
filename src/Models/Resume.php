<?php

namespace IMatchBetter\Models;

use IMatchBetter\Config\Database;

class Resume
{
    public static function create(int $applicantId, string $originalFilename, string $storedFilename, string $filePath, string $mimeType, int $fileSize): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO resumes (applicant_id, original_filename, stored_filename, file_path, mime_type, file_size) VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$applicantId, $originalFilename, $storedFilename, $filePath, $mimeType, $fileSize]);

        return (int) Database::connection()->lastInsertId();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM resumes WHERE id = ?');
        $stmt->execute([$id]);
        $resume = $stmt->fetch();

        return $resume ?: null;
    }

    public static function forApplicant(int $applicantId): array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM resumes WHERE applicant_id = ? ORDER BY uploaded_at DESC');
        $stmt->execute([$applicantId]);

        return $stmt->fetchAll();
    }

    public static function isOwnedBy(int $resumeId, int $applicantId): bool
    {
        $resume = self::find($resumeId);

        return $resume !== null && (int) $resume['applicant_id'] === $applicantId;
    }

    public static function isInUse(int $resumeId): bool
    {
        $stmt = Database::connection()->prepare('SELECT 1 FROM applications WHERE resume_id = ?');
        $stmt->execute([$resumeId]);

        return (bool) $stmt->fetchColumn();
    }

    /**
     * Deletes the DB row only if it hasn't been submitted with an application (the
     * applications.resume_id foreign key is ON DELETE RESTRICT — deleting an in-use resume
     * would break the application's record of what was actually submitted). Returns false,
     * without deleting anything, if the resume is in use.
     */
    public static function delete(int $resumeId): bool
    {
        if (self::isInUse($resumeId)) {
            return false;
        }

        $stmt = Database::connection()->prepare('DELETE FROM resumes WHERE id = ?');
        $stmt->execute([$resumeId]);

        return true;
    }
}
