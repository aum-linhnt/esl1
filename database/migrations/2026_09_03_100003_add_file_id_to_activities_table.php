<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Step 1: Add file_id column to activities
        Schema::table('activities', function (Blueprint $table) {
            $table->foreignId('file_id')->nullable()->after('time_limit_minutes')
                  ->constrained('files')->nullOnDelete();
        });

        // Step 2: Migrate existing file data into the files table
        $activitiesWithFiles = DB::table('activities')
            ->whereNotNull('file_path')
            ->where('file_path', '!=', '')
            ->get();

        foreach ($activitiesWithFiles as $activity) {
            $disk = 'public';
            $filePath = $activity->file_path;

            // Compute hash if file exists on disk, otherwise use path as fallback hash
            $fullPath = storage_path('app/public/' . $filePath);
            if (file_exists($fullPath)) {
                $hash = hash_file('sha256', $fullPath);
            } else {
                $hash = hash('sha256', $filePath); // fallback for missing files
            }

            // Check if file with same hash already exists
            $existingFile = DB::table('files')->where('hash', $hash)->first();

            if ($existingFile) {
                // Reuse existing file record, increment reference
                DB::table('files')->where('id', $existingFile->id)
                    ->increment('reference_count');
                $fileId = $existingFile->id;
            } else {
                // Create new file record
                $extension = pathinfo($activity->file_original_name ?? $filePath, PATHINFO_EXTENSION);
                $mimeType = match (strtolower($extension)) {
                    'pdf' => 'application/pdf',
                    'doc', 'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'ppt', 'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                    'xls', 'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'zip' => 'application/zip',
                    'rar' => 'application/x-rar-compressed',
                    'mp3' => 'audio/mpeg',
                    'wav' => 'audio/wav',
                    'ogg' => 'audio/ogg',
                    'mp4' => 'video/mp4',
                    'jpg', 'jpeg' => 'image/jpeg',
                    'png' => 'image/png',
                    'gif' => 'image/gif',
                    'webp' => 'image/webp',
                    default => 'application/octet-stream',
                };

                $fileId = DB::table('files')->insertGetId([
                    'hash' => $hash,
                    'disk' => $disk,
                    'path' => $filePath,
                    'original_name' => $activity->file_original_name ?? basename($filePath),
                    'mime_type' => $mimeType,
                    'extension' => strtolower($extension),
                    'size' => $activity->file_size ?? 0,
                    'reference_count' => 1,
                    'uploaded_by' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Link activity to file record
            DB::table('activities')->where('id', $activity->id)
                ->update(['file_id' => $fileId]);
        }

        // Step 3: Drop old file columns from activities
        Schema::table('activities', function (Blueprint $table) {
            $table->dropColumn(['file_path', 'file_size', 'file_original_name']);
        });
    }

    public function down(): void
    {
        // Restore old columns
        Schema::table('activities', function (Blueprint $table) {
            $table->string('file_path')->nullable()->after('time_limit_minutes');
            $table->integer('file_size')->nullable()->after('file_path');
            $table->string('file_original_name')->nullable()->after('file_size');
        });

        // Migrate data back from files table
        $activitiesWithFiles = DB::table('activities')
            ->whereNotNull('file_id')
            ->join('files', 'activities.file_id', '=', 'files.id')
            ->select('activities.id', 'files.path', 'files.size', 'files.original_name')
            ->get();

        foreach ($activitiesWithFiles as $activity) {
            DB::table('activities')->where('id', $activity->id)->update([
                'file_path' => $activity->path,
                'file_size' => $activity->size,
                'file_original_name' => $activity->original_name,
            ]);
        }

        // Drop file_id FK and column
        Schema::table('activities', function (Blueprint $table) {
            $table->dropConstrainedForeignId('file_id');
        });
    }
};
