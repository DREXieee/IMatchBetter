<?php

namespace IMatchBetter\Services;

use RuntimeException;

class FileUploadService
{
    private const RESUME_MIMES = [
        'application/pdf' => 'pdf',
        'application/msword' => 'doc',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
    ];
    private const RESUME_MAX_SIZE = 5 * 1024 * 1024; // 5MB

    private const LOGO_MIMES = [
        'image/png' => 'png',
        'image/jpeg' => 'jpg',
        'image/webp' => 'webp',
    ];
    private const LOGO_MAX_SIZE = 2 * 1024 * 1024; // 2MB

    private const CERTIFICATE_MIMES = [
        'application/pdf' => 'pdf',
        'application/msword' => 'doc',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
        'image/png' => 'png',
        'image/jpeg' => 'jpg',
    ];
    private const CERTIFICATE_MAX_SIZE = 5 * 1024 * 1024; // 5MB

    /**
     * @return array{original_filename:string, stored_filename:string, file_path:string, mime_type:string, file_size:int}
     */
    public static function storeResume(array $file): array
    {
        return self::store($file, 'resumes', self::RESUME_MIMES, self::RESUME_MAX_SIZE);
    }

    public static function storeLogo(array $file): array
    {
        return self::store($file, 'logos', self::LOGO_MIMES, self::LOGO_MAX_SIZE);
    }

    public static function storeCertificate(array $file): array
    {
        return self::store($file, 'certificates', self::CERTIFICATE_MIMES, self::CERTIFICATE_MAX_SIZE);
    }

    private static function store(array $file, string $subdir, array $allowedMimes, int $maxSize): array
    {
        if (empty($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            throw new RuntimeException('Please choose a file to upload.');
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException('There was a problem uploading the file. Please try again.');
        }

        if ($file['size'] <= 0 || $file['size'] > $maxSize) {
            $maxMb = (int) ($maxSize / 1024 / 1024);
            throw new RuntimeException("File is too large or empty. Maximum size is {$maxMb}MB.");
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!isset($allowedMimes[$mimeType])) {
            $allowed = implode(', ', array_values($allowedMimes));
            throw new RuntimeException("Unsupported file type. Allowed types: {$allowed}.");
        }

        $extension = $allowedMimes[$mimeType];
        $storedFilename = bin2hex(random_bytes(16)) . '.' . $extension;
        $destinationDir = BASE_PATH . '/uploads/' . $subdir;

        if (!is_dir($destinationDir)) {
            mkdir($destinationDir, 0755, true);
        }

        $destination = $destinationDir . '/' . $storedFilename;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            throw new RuntimeException('Could not save the uploaded file. Please try again.');
        }

        return [
            'original_filename' => basename($file['name']),
            'stored_filename' => $storedFilename,
            'file_path' => 'uploads/' . $subdir . '/' . $storedFilename,
            'mime_type' => $mimeType,
            'file_size' => (int) $file['size'],
        ];
    }
}
