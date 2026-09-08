<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class File extends Model
{
    protected $fillable = [
        'hash', 'disk', 'path', 'folder', 'original_name',
        'mime_type', 'extension', 'size',
        'reference_count', 'is_temp', 'expires_at', 'metadata',
        'uploaded_by',
    ];

    protected function casts(): array
    {
        return [
            'size' => 'integer',
            'reference_count' => 'integer',
            'is_temp' => 'boolean',
            'expires_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    // ─── Scopes ───

    public function scopeTemp($query)
    {
        return $query->where('is_temp', true);
    }

    public function scopePermanent($query)
    {
        return $query->where('is_temp', false);
    }

    public function scopeExpired($query)
    {
        return $query->where('is_temp', true)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now());
    }

    public function scopeByFolder($query, string $folder)
    {
        return $query->where('folder', $folder);
    }

    // ─── Relationships ───

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class);
    }

    public function assignmentSubmissions(): HasMany
    {
        return $this->hasMany(AssignmentSubmission::class);
    }

    // ─── Helpers ───

    /**
     * Get the full public URL for this file (Cloud/Local storage compatible).
     */
    public function getUrl(): string
    {
        if ($this->disk === 'public') {
            return asset('storage/' . ltrim($this->path, '/'));
        }
        return \Illuminate\Support\Facades\Storage::disk($this->disk)->url($this->path);
    }

    /**
     * Mark a temporary file as permanent.
     */
    public function markAsPermanent(string $targetFolder): void
    {
        $this->update([
            'is_temp' => false,
            'folder' => $targetFolder,
            'expires_at' => null,
        ]);
    }

    /**
     * Check if this temporary file has expired.
     */
    public function isExpired(): bool
    {
        return $this->is_temp && $this->expires_at && $this->expires_at->isPast();
    }

    public function getStoragePathAttribute(): string
    {
        return $this->path ?? '';
    }

    public function getReadableSizeAttribute(): string
    {
        return $this->getSizeFormatted();
    }

    /**
     * Get human-readable file size.
     */
    public function getSizeFormatted(): string
    {

        $bytes = $this->size;
        if ($bytes >= 1073741824) {
            return round($bytes / 1073741824, 2) . ' GB';
        }
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 1) . ' MB';
        }
        if ($bytes >= 1024) {
            return round($bytes / 1024, 1) . ' KB';
        }
        return $bytes . ' B';
    }

    /**
     * Check if this file is an image.
     */
    public function isImage(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }

    /**
     * Check if this file is audio.
     */
    public function isAudio(): bool
    {
        return str_starts_with($this->mime_type, 'audio/');
    }

    /**
     * Check if this file is video.
     */
    public function isVideo(): bool
    {
        return str_starts_with($this->mime_type, 'video/');
    }

    /**
     * Check if this file is a PDF document.
     */
    public function isPdf(): bool
    {
        return $this->mime_type === 'application/pdf';
    }

    /**
     * Check if this file has no remaining references.
     */
    public function isOrphan(): bool
    {
        return $this->reference_count <= 0;
    }

    /**
     * Get icon emoji for this file type.
     */
    public function getIcon(): string
    {
        if ($this->isPdf()) return '📑';
        if ($this->isImage()) return '🖼️';
        if ($this->isAudio()) return '🎵';
        if ($this->isVideo()) return '🎬';

        return match ($this->extension) {
            'doc', 'docx' => '📝',
            'ppt', 'pptx' => '📊',
            'xls', 'xlsx' => '📈',
            'zip', 'rar', '7z' => '📦',
            default => '📄',
        };
    }
}
