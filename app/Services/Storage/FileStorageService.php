<?php

namespace App\Services\Storage;

use App\Models\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class FileStorageService
{
    /**
     * Allowed MIME types grouped by category.
     */
    public static array $allowedTypes = [
        'document' => [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'text/plain',
            'text/csv',
        ],
        'archive' => [
            'application/zip',
            'application/x-rar-compressed',
            'application/x-7z-compressed',
            'application/gzip',
        ],
        'audio' => [
            'audio/mpeg',
            'audio/wav',
            'audio/ogg',
            'audio/mp4',
            'audio/x-m4a',
            'audio/webm',
        ],
        'video' => [
            'video/mp4',
            'video/webm',
            'video/ogg',
        ],
        'image' => [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
            'image/svg+xml',
        ],
    ];

    /**
     * Max file size in bytes (50 MB).
     */
    public const MAX_FILE_SIZE = 50 * 1024 * 1024;

    /**
     * The filesystem disk to use.
     */
    protected string $disk = 'public';

    /**
     * Store an uploaded file with automatic deduplication and folder partitioning.
     *
     * Flow:
     * 1. Compute SHA-256 hash of file content
     * 2. Check if a file with same hash exists in DB
     * 3. If YES → increment reference_count, promote if previously temp, return existing record
     * 4. If NO  → store file on disk with sharded path under specified folder, create new record
     */
    public function store(
        UploadedFile $uploadedFile,
        int|string|null $uploadedByOrFolder = null,
        string $folder = 'general',
        bool $isPrivate = false,
        array $metadata = []
    ): File {
        $uploadedBy = null;
        if (is_string($uploadedByOrFolder)) {
            $folder = $uploadedByOrFolder;
        } elseif (is_int($uploadedByOrFolder)) {
            $uploadedBy = $uploadedByOrFolder;
        }

        $hash = $this->computeHash($uploadedFile);
        $disk = $isPrivate ? 'local' : $this->disk;


        // Check for existing file with same content
        $existingFile = $this->findByHash($hash);

        if ($existingFile) {
            $this->incrementReference($existingFile);

            // If existing file was temporary and now being stored permanently
            if ($existingFile->is_temp) {
                $existingFile->update([
                    'is_temp' => false,
                    'folder' => $folder,
                    'expires_at' => null,
                ]);
            }

            Log::info('FileStorage: Dedup hit', [
                'hash' => $hash,
                'original_name' => $uploadedFile->getClientOriginalName(),
                'existing_id' => $existingFile->id,
                'references' => $existingFile->reference_count,
            ]);

            return $existingFile->fresh();
        }

        // New file — store on disk with folder-partitioned sharded directory structure
        $extension = strtolower($uploadedFile->getClientOriginalExtension()) ?: 'bin';
        $storagePath = $this->buildShardedPath($folder, $hash, $extension);

        // Extract media metadata
        $mimeType = $uploadedFile->getMimeType() ?? 'application/octet-stream';
        if (str_starts_with($mimeType, 'image/')) {
            $imgInfo = @getimagesize($uploadedFile->getRealPath());
            if ($imgInfo) {
                $metadata['width'] = $imgInfo[0];
                $metadata['height'] = $imgInfo[1];
            }
        }

        // Ensure directory exists and store file
        $directory = dirname($storagePath);
        Storage::disk($disk)->makeDirectory($directory);
        Storage::disk($disk)->putFileAs($directory, $uploadedFile, basename($storagePath));

        $file = File::create([
            'hash' => $hash,
            'disk' => $disk,
            'path' => $storagePath,
            'folder' => $folder,
            'original_name' => $uploadedFile->getClientOriginalName(),
            'mime_type' => $mimeType,
            'extension' => $extension,
            'size' => $uploadedFile->getSize(),
            'reference_count' => 1,
            'is_temp' => false,
            'expires_at' => null,
            'metadata' => !empty($metadata) ? $metadata : null,
            'uploaded_by' => $uploadedBy,
        ]);

        Log::info('FileStorage: New file stored', [
            'id' => $file->id,
            'folder' => $folder,
            'hash' => $hash,
            'path' => $storagePath,
            'size' => $file->getSizeFormatted(),
        ]);

        return $file;
    }

    /**
     * Store an uploaded file temporarily (for draft, preview, audio test).
     * Marked with is_temp = true and expires_at.
     */
    public function storeTemp(UploadedFile $uploadedFile, ?int $uploadedByOrTtl = null, int $ttlHours = 24): File
    {
        $uploadedBy = null;
        if (func_num_args() === 2 && is_int($uploadedByOrTtl)) {
            $ttlHours = $uploadedByOrTtl;
        } elseif (func_num_args() >= 3) {
            $uploadedBy = $uploadedByOrTtl;
        } elseif (is_int($uploadedByOrTtl)) {
            $ttlHours = $uploadedByOrTtl;
        }

        $hash = $this->computeHash($uploadedFile);
        $disk = $this->disk;


        // Check if file already exists in DB
        $existingFile = $this->findByHash($hash);
        if ($existingFile) {
            $this->incrementReference($existingFile);
            return $existingFile->fresh();
        }

        $extension = strtolower($uploadedFile->getClientOriginalExtension()) ?: 'bin';
        $storagePath = $this->buildShardedPath('temp', $hash, $extension);

        $directory = dirname($storagePath);
        Storage::disk($disk)->makeDirectory($directory);
        Storage::disk($disk)->putFileAs($directory, $uploadedFile, basename($storagePath));

        $file = File::create([
            'hash' => $hash,
            'disk' => $disk,
            'path' => $storagePath,
            'folder' => 'temp',
            'original_name' => $uploadedFile->getClientOriginalName(),
            'mime_type' => $uploadedFile->getMimeType() ?? 'application/octet-stream',
            'extension' => $extension,
            'size' => $uploadedFile->getSize(),
            'reference_count' => 1,
            'is_temp' => true,
            'expires_at' => now()->addHours($ttlHours),
            'metadata' => null,
            'uploaded_by' => $uploadedBy,
        ]);

        Log::info('FileStorage: Temp file stored', [
            'id' => $file->id,
            'path' => $storagePath,
            'expires_at' => $file->expires_at,
        ]);

        return $file;
    }

    /**
     * Promote a temporary file to permanent in a specific folder.
     */
    public function promoteTemp(File $file, string $targetFolder): File
    {
        if (!$file->is_temp) {
            return $file;
        }

        $oldPath = $file->path;
        $newPath = $this->buildShardedPath($targetFolder, $file->hash, $file->extension);

        if ($oldPath !== $newPath && Storage::disk($file->disk)->exists($oldPath)) {
            $directory = dirname($newPath);
            Storage::disk($file->disk)->makeDirectory($directory);
            Storage::disk($file->disk)->move($oldPath, $newPath);
        }

        $file->update([
            'path' => $newPath,
            'folder' => $targetFolder,
            'is_temp' => false,
            'expires_at' => null,
        ]);

        Log::info('FileStorage: Temp file promoted', [
            'id' => $file->id,
            'from' => $oldPath,
            'to' => $newPath,
            'folder' => $targetFolder,
        ]);

        return $file->fresh();
    }

    /**
     * Compute SHA-256 hash of an uploaded file's content.
     */
    public function computeHash(UploadedFile $file): string
    {
        return hash_file('sha256', $file->getRealPath());
    }

    /**
     * Find a file record by its content hash.
     */
    public function findByHash(string $hash): ?File
    {
        return File::where('hash', $hash)->first();
    }

    /**
     * Increment the reference count of a file.
     */
    public function incrementReference(File $file): void
    {
        $file->increment('reference_count');
    }

    /**
     * Decrement the reference count.
     * If it reaches 0, delete the physical file from disk.
     */
    public function decrementReference(File $file): void
    {
        $file->decrement('reference_count');
        $file->refresh();

        if ($file->reference_count <= 0) {
            $this->deletePhysicalFile($file);
            $file->delete();

            Log::info('FileStorage: Orphan file cleaned up', [
                'id' => $file->id,
                'hash' => $file->hash,
                'path' => $file->path,
            ]);
        }
    }

    /**
     * Delete or decrement reference of a file record.
     * Returns true if the file was completely deleted from DB and disk, false if references remain.
     */
    public function delete(File $file): bool
    {
        $id = $file->id;
        $this->decrementReference($file);
        return !File::where('id', $id)->exists();
    }


    /**
     * Delete the physical file from the disk.
     */
    public function deletePhysicalFile(File $file): bool
    {
        if (Storage::disk($file->disk)->exists($file->path)) {
            return Storage::disk($file->disk)->delete($file->path);
        }
        return false;
    }

    /**
     * Build a sharded storage path with folder partitioning:
     * {folder}/{ab}/{cd}/{full_hash}.{ext}
     * or for temp: temp/{date}/{full_hash}.{ext}
     */
    public function buildShardedPath(string $folder, string $hash, string $extension): string
    {
        $folder = trim($folder, '/');

        if ($folder === 'temp') {
            $date = date('Y-m-d');
            return "temp/{$date}/{$hash}.{$extension}";
        }

        $shard1 = substr($hash, 0, 2);
        $shard2 = substr($hash, 2, 2);
        return "{$folder}/{$shard1}/{$shard2}/{$hash}.{$extension}";
    }

    /**
     * Clean up all expired or stale temporary files.
     * Removes both database records and physical files.
     *
     * @param int $hours Maximum age in hours for temporary files
     * @return int Number of files cleaned up
     */
    public function cleanupTemp(int $hours = 24): int
    {
        $cutoff = now()->subHours($hours);
        $tempFiles = File::where('is_temp', true)
            ->where(function ($q) use ($cutoff) {
                $q->where('expires_at', '<=', now())
                  ->orWhere('created_at', '<=', $cutoff);
            })
            ->get();

        $count = 0;
        foreach ($tempFiles as $file) {
            $this->deletePhysicalFile($file);
            $file->delete();
            $count++;
        }

        // Clean up empty directories in temp/
        $tempDirs = Storage::disk($this->disk)->directories('temp');
        foreach ($tempDirs as $dir) {
            $filesInDir = Storage::disk($this->disk)->files($dir);
            if (empty($filesInDir)) {
                Storage::disk($this->disk)->deleteDirectory($dir);
            }
        }

        if ($count > 0) {
            Log::info("FileStorage: Cleaned up {$count} temporary file(s).");
        }

        return $count;
    }

    /**
     * Clean up all orphan files (reference_count <= 0).
     * Returns number of files cleaned up.
     */
    public function cleanupOrphans(): int
    {
        $orphans = File::where('reference_count', '<=', 0)->get();
        $count = 0;

        foreach ($orphans as $orphan) {
            $this->deletePhysicalFile($orphan);
            $orphan->delete();
            $count++;
        }

        if ($count > 0) {
            Log::info("FileStorage: Cleaned up {$count} orphan file(s).");
        }

        return $count;
    }

    /**
     * Get all allowed MIME types as a flat array.
     */
    public static function getAllowedMimeTypes(): array
    {
        return array_merge(...array_values(self::$allowedTypes));
    }

    /**
     * Get allowed extensions as a flat array.
     */
    public static function getAllowedExtensions(): array
    {
        return [
            'pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'txt', 'csv',
            'zip', 'rar', '7z', 'gz',
            'mp3', 'wav', 'ogg', 'm4a', 'webm',
            'mp4',
            'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg',
        ];
    }

    /**
     * Validate that an uploaded file meets our requirements.
     */
    public function validate(UploadedFile $file): array
    {
        $errors = [];

        if ($file->getSize() > self::MAX_FILE_SIZE) {
            $maxMb = self::MAX_FILE_SIZE / 1024 / 1024;
            $errors[] = "File vượt quá kích thước tối đa cho phép ({$maxMb} MB).";
        }

        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, self::getAllowedExtensions())) {
            $errors[] = "Loại file '.{$extension}' không được hỗ trợ.";
        }

        return $errors;
    }

    /**
     * Get storage statistics.
     */
    public function getStats(): array
    {
        $totalFiles = File::count();
        $totalSize = File::sum('size');
        $totalReferences = File::sum('reference_count');
        $orphanCount = File::where('reference_count', '<=', 0)->count();
        $savedByDedup = $totalReferences > $totalFiles
            ? File::where('reference_count', '>', 1)
                ->selectRaw('SUM(size * (reference_count - 1)) as saved')
                ->value('saved') ?? 0
            : 0;

        return [
            'total_files' => $totalFiles,
            'total_size' => $totalSize,
            'total_size_formatted' => $this->formatBytes($totalSize),
            'total_references' => $totalReferences,
            'orphan_count' => $orphanCount,
            'space_saved_by_dedup' => $savedByDedup,
            'space_saved_formatted' => $this->formatBytes($savedByDedup),
        ];
    }

    /**
     * Format bytes to human-readable string.
     */
    protected function formatBytes(int $bytes): string
    {
        if ($bytes >= 1073741824) return round($bytes / 1073741824, 2) . ' GB';
        if ($bytes >= 1048576) return round($bytes / 1048576, 1) . ' MB';
        if ($bytes >= 1024) return round($bytes / 1024, 1) . ' KB';
        return $bytes . ' B';
    }
}
