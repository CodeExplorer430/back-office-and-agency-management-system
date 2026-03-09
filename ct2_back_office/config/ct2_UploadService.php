<?php

declare(strict_types=1);

final class CT2_UploadService
{
    private const CT2_ALLOWED_MIME_TYPES = [
        'application/pdf' => 'pdf',
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'application/msword' => 'doc',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
    ];

    public function storeUploadedFile(array $ct2UploadedFile, string $ct2EntityType, int $ct2EntityId, int $ct2MaxFileSizeMb): array
    {
        if (($ct2UploadedFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new InvalidArgumentException('Document upload failed. Please try again with a valid file.');
        }

        $ct2TemporaryPath = (string) ($ct2UploadedFile['tmp_name'] ?? '');
        if ($ct2TemporaryPath === '' || !is_uploaded_file($ct2TemporaryPath)) {
            throw new InvalidArgumentException('Uploaded document could not be verified.');
        }

        $ct2FileSizeBytes = (int) ($ct2UploadedFile['size'] ?? 0);
        $ct2MaxFileSizeBytes = max(1, $ct2MaxFileSizeMb) * 1024 * 1024;
        if ($ct2FileSizeBytes < 1 || $ct2FileSizeBytes > $ct2MaxFileSizeBytes) {
            throw new InvalidArgumentException('Uploaded document exceeds the allowed file size limit.');
        }

        $ct2Finfo = new finfo(FILEINFO_MIME_TYPE);
        $ct2MimeType = (string) ($ct2Finfo->file($ct2TemporaryPath) ?: '');
        if (!isset(self::CT2_ALLOWED_MIME_TYPES[$ct2MimeType])) {
            throw new InvalidArgumentException('Uploaded document type is not allowed.');
        }

        $ct2OriginalName = trim((string) ($ct2UploadedFile['name'] ?? 'document'));
        $ct2Extension = self::CT2_ALLOWED_MIME_TYPES[$ct2MimeType];
        $ct2StorageDirectory = CT2_BASE_PATH . '/storage/uploads/' . $this->ct2SanitizePathSegment($ct2EntityType) . '/' . $ct2EntityId;

        if (!is_dir($ct2StorageDirectory) && !mkdir($ct2StorageDirectory, 0775, true) && !is_dir($ct2StorageDirectory)) {
            throw new RuntimeException('Unable to create the CT2 upload directory.');
        }

        $ct2StoredName = bin2hex(random_bytes(16)) . '.' . $ct2Extension;
        $ct2StoredPath = $ct2StorageDirectory . '/' . $ct2StoredName;
        if (!move_uploaded_file($ct2TemporaryPath, $ct2StoredPath)) {
            throw new RuntimeException('Unable to store the uploaded CT2 document.');
        }

        return [
            'file_name' => $ct2OriginalName,
            'file_path' => 'storage/uploads/' . $this->ct2SanitizePathSegment($ct2EntityType) . '/' . $ct2EntityId . '/' . $ct2StoredName,
            'mime_type' => $ct2MimeType,
            'file_size_bytes' => $ct2FileSizeBytes,
        ];
    }

    private function ct2SanitizePathSegment(string $ct2Value): string
    {
        $ct2SanitizedValue = preg_replace('/[^a-z0-9_-]+/i', '_', strtolower($ct2Value));
        return $ct2SanitizedValue !== null && $ct2SanitizedValue !== '' ? $ct2SanitizedValue : 'documents';
    }
}
