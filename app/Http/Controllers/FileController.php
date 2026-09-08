<?php

namespace App\Http\Controllers;

use App\Services\Storage\FileStorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FileController extends Controller
{
    protected FileStorageService $fileStorageService;

    public function __construct(FileStorageService $fileStorageService)
    {
        $this->fileStorageService = $fileStorageService;
    }

    /**
     * Upload a permanent file to storage.
     */
    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|max:51200', // 50MB
            'folder' => 'nullable|string|in:general,avatars,questions/audio,questions/images,courses/thumbnails,activities,submissions',
            'is_private' => 'nullable|boolean',
        ]);

        $folder = $request->input('folder', 'general');
        $isPrivate = $request->boolean('is_private', false);

        $fileRecord = $this->fileStorageService->store($request->file('file'), $folder, $isPrivate);

        return response()->json([
            'success' => true,
            'message' => 'Tải lên tệp tin thành công!',
            'data' => [
                'id' => $fileRecord->id,
                'name' => $fileRecord->original_name,
                'url' => $fileRecord->getUrl(),
                'storage_path' => $fileRecord->storage_path,
                'folder' => $fileRecord->folder,
                'mime_type' => $fileRecord->mime_type,
                'size' => $fileRecord->size,
                'size_formatted' => $fileRecord->readable_size,
                'is_temp' => $fileRecord->is_temp,
            ],
        ]);
    }

    /**
     * Upload a temporary file with a TTL for background processing or staging.
     */
    public function uploadTemp(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|max:51200', // 50MB
            'ttl_hours' => 'nullable|integer|min:1|max:168', // up to 7 days
        ]);

        $ttlHours = (int) $request->input('ttl_hours', 24);
        $fileRecord = $this->fileStorageService->storeTemp($request->file('file'), $ttlHours);

        return response()->json([
            'success' => true,
            'message' => 'Tải lên tệp tạm thời thành công!',
            'data' => [
                'id' => $fileRecord->id,
                'name' => $fileRecord->original_name,
                'url' => $fileRecord->getUrl(),
                'storage_path' => $fileRecord->storage_path,
                'mime_type' => $fileRecord->mime_type,
                'size' => $fileRecord->size,
                'is_temp' => true,
                'expires_at' => $fileRecord->expires_at?->toIso8601String(),
            ],
        ]);
    }
}
