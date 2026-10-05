<?php

namespace App\Services\H5P;

use App\Models\File;
use Illuminate\Support\Facades\Log;
use ZipArchive;

class H5PPackageService
{
    /**
     * Base directory where extracted H5P packages are stored.
     */
    protected string $extractedBasePath;

    /**
     * Directory for central shared H5P libraries.
     */
    protected string $sharedLibrariesPath;

    public function __construct()
    {
        $this->extractedBasePath = storage_path('app/public/h5p/extracted');
        $this->sharedLibrariesPath = storage_path('app/public/h5p/libraries');
    }

    /**
     * Extract an H5P package to the public storage.
     *
     * @param File $file The uploaded .h5p file record
     * @return array Result array with extraction status, path, url and metadata
     */
    public function extractPackage(File $file): array
    {
        $filePath = storage_path('app/public/' . ltrim($file->path, '/\\'));

        if (!file_exists($filePath)) {
            Log::error("H5P: Physical file not found at {$filePath}");
            return [
                'success' => false,
                'message' => "Không tìm thấy tệp vật lý trên máy chủ.",
            ];
        }

        $targetDir = $this->extractedBasePath . DIRECTORY_SEPARATOR . $file->hash;
        $h5pJsonPath = $targetDir . DIRECTORY_SEPARATOR . 'h5p.json';

        // Deduplication: If already extracted and valid, ensure dependencies and return
        if (is_dir($targetDir) && file_exists($h5pJsonPath)) {
            $metadata = $this->readH5pMetadata($h5pJsonPath);
            $this->ensureDependencies($targetDir, $metadata);
            return [
                'success' => true,
                'hash' => $file->hash,
                'public_url' => asset('storage/h5p/extracted/' . $file->hash),
                'relative_url' => '/storage/h5p/extracted/' . $file->hash,
                'title' => $metadata['title'] ?? pathinfo($file->original_name, PATHINFO_FILENAME),
                'main_library' => $metadata['mainLibrary'] ?? null,
                'metadata' => $metadata,
            ];
        }

        // Ensure directory exists
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        // Extract using ZipArchive
        if (!class_exists('ZipArchive')) {
            Log::error("H5P: ZipArchive extension is not enabled in PHP.");
            return [
                'success' => false,
                'message' => "Máy chủ chưa kích hoạt tiện ích ZipArchive của PHP.",
            ];
        }

        $zip = new ZipArchive();
        $res = $zip->open($filePath);

        if ($res !== true) {
            Log::error("H5P: Failed to open zip archive at {$filePath}, code: {$res}");
            return [
                'success' => false,
                'message' => "Không thể giải nén gói .h5p (Mã lỗi: {$res}).",
            ];
        }

        $extracted = $zip->extractTo($targetDir);
        $zip->close();

        if (!$extracted) {
            Log::error("H5P: Failed to extract contents to {$targetDir}");
            return [
                'success' => false,
                'message' => "Có lỗi khi giải nén nội dung gói H5P.",
            ];
        }

        // Handle case where root files are wrapped in a single subfolder
        if (!file_exists($h5pJsonPath)) {
            $subDirs = glob($targetDir . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR);
            foreach ($subDirs as $subDir) {
                if (file_exists($subDir . DIRECTORY_SEPARATOR . 'h5p.json')) {
                    $this->flattenDirectory($subDir, $targetDir);
                    break;
                }
            }
        }

        if (!file_exists($h5pJsonPath)) {
            Log::warning("H5P: h5p.json was not found in {$targetDir}");
        }

        $metadata = file_exists($h5pJsonPath) ? $this->readH5pMetadata($h5pJsonPath) : [];

        // Harvest uploaded libraries and ensure missing dependencies are supplied
        $this->ensureDependencies($targetDir, $metadata);

        Log::info("H5P: Package extracted successfully", [
            'file_id' => $file->id,
            'hash' => $file->hash,
            'title' => $metadata['title'] ?? null,
            'mainLibrary' => $metadata['mainLibrary'] ?? null,
        ]);

        return [
            'success' => true,
            'hash' => $file->hash,
            'public_url' => asset('storage/h5p/extracted/' . $file->hash),
            'relative_url' => '/storage/h5p/extracted/' . $file->hash,
            'title' => $metadata['title'] ?? pathinfo($file->original_name, PATHINFO_FILENAME),
            'main_library' => $metadata['mainLibrary'] ?? null,
            'metadata' => $metadata,
        ];
    }

    /**
     * Get or extract the public URL for an H5P file record.
     */
    public function getExtractedUrl(?File $file): ?string
    {
        if (!$file) {
            return null;
        }

        $targetDir = $this->extractedBasePath . DIRECTORY_SEPARATOR . $file->hash;
        $h5pJsonPath = $targetDir . DIRECTORY_SEPARATOR . 'h5p.json';

        if (!is_dir($targetDir) || !file_exists($h5pJsonPath)) {
            $result = $this->extractPackage($file);
            if (!$result['success']) {
                return null;
            }
        } else {
            // Check and ensure all dependencies exist
            $metadata = $this->readH5pMetadata($h5pJsonPath);
            $this->ensureDependencies($targetDir, $metadata);
        }

        return asset('storage/h5p/extracted/' . $file->hash);
    }

    /**
     * Ensure all required H5P libraries are present in the package directory.
     * Collects existing libraries to shared pool, and supplies missing ones from shared pool or GitHub.
     */
    public function ensureDependencies(string $targetDir, array $metadata): void
    {
        if (!is_dir($this->sharedLibrariesPath)) {
            @mkdir($this->sharedLibrariesPath, 0755, true);
        }

        // 1. Harvest any libraries contained in this package into the shared pool
        $this->harvestLibrariesToSharedPool($targetDir);

        // 2. Check preloadedDependencies
        $dependencies = $metadata['preloadedDependencies'] ?? [];
        foreach ($dependencies as $dep) {
            $machineName = $dep['machineName'] ?? '';
            $version = ($dep['majorVersion'] ?? '') . '.' . ($dep['minorVersion'] ?? '');
            if (!$machineName) continue;

            $folderName = $machineName . '-' . $version;
            $packageLibDir = $targetDir . DIRECTORY_SEPARATOR . $folderName;

            // Check if library already exists in package
            if (!is_dir($packageLibDir) || !file_exists($packageLibDir . DIRECTORY_SEPARATOR . 'library.json')) {
                // Try to find matching library in shared pool
                $sharedMatch = $this->findInSharedPool($machineName, $dep['majorVersion'] ?? null);
                if ($sharedMatch) {
                    $this->copyDirectory($sharedMatch, $packageLibDir);
                    Log::info("H5P: Supplied missing library '{$folderName}' from shared pool.");
                } else {
                    // Try auto-downloading from GitHub
                    $downloaded = $this->downloadLibraryFromGitHub($machineName, $folderName);
                    if ($downloaded) {
                        $this->copyDirectory($downloaded, $packageLibDir);
                        Log::info("H5P: Auto-downloaded library '{$folderName}' from GitHub.");
                    } else {
                        Log::warning("H5P: Library '{$folderName}' could not be found or downloaded.");
                    }
                }
            }

            // Also inspect sub-dependencies of this library if library.json exists
            if (file_exists($packageLibDir . DIRECTORY_SEPARATOR . 'library.json')) {
                $subMeta = $this->readH5pMetadata($packageLibDir . DIRECTORY_SEPARATOR . 'library.json');
                $subDeps = $subMeta['preloadedDependencies'] ?? [];
                foreach ($subDeps as $subDep) {
                    $subName = $subDep['machineName'] ?? '';
                    $subVer = ($subDep['majorVersion'] ?? '') . '.' . ($subDep['minorVersion'] ?? '');
                    if (!$subName) continue;
                    $subFolder = $subName . '-' . $subVer;
                    $subPkgDir = $targetDir . DIRECTORY_SEPARATOR . $subFolder;
                    if (!is_dir($subPkgDir) || !file_exists($subPkgDir . DIRECTORY_SEPARATOR . 'library.json')) {
                        $subSharedMatch = $this->findInSharedPool($subName, $subDep['majorVersion'] ?? null);
                        if ($subSharedMatch) {
                            $this->copyDirectory($subSharedMatch, $subPkgDir);
                        } else {
                            $subDl = $this->downloadLibraryFromGitHub($subName, $subFolder);
                            if ($subDl) {
                                $this->copyDirectory($subDl, $subPkgDir);
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Harvest any library folders from a package into the central shared pool.
     */
    protected function harvestLibrariesToSharedPool(string $dir): void
    {
        if (!is_dir($dir)) return;

        $items = scandir($dir);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..' || $item === 'content') continue;
            $fullPath = $dir . DIRECTORY_SEPARATOR . $item;
            if (is_dir($fullPath) && file_exists($fullPath . DIRECTORY_SEPARATOR . 'library.json')) {
                $sharedTarget = $this->sharedLibrariesPath . DIRECTORY_SEPARATOR . $item;
                if (!is_dir($sharedTarget)) {
                    $this->copyDirectory($fullPath, $sharedTarget);
                    Log::info("H5P: Harvested library '{$item}' to shared pool.");
                }
            }
        }
    }

    /**
     * Find a library in the shared pool by machine name and major version.
     */
    protected function findInSharedPool(string $machineName, ?int $majorVersion = null): ?string
    {
        if (!is_dir($this->sharedLibrariesPath)) return null;

        $candidates = glob($this->sharedLibrariesPath . DIRECTORY_SEPARATOR . $machineName . '*');
        if (empty($candidates)) return null;

        if ($majorVersion !== null) {
            foreach ($candidates as $candidate) {
                if (str_contains(basename($candidate), "-{$majorVersion}.")) {
                    return $candidate;
                }
            }
        }

        return $candidates[0] ?? null;
    }

    /**
     * Download a missing official H5P library from GitHub and place it in shared pool.
     */
    protected function downloadLibraryFromGitHub(string $machineName, string $targetFolderName): ?string
    {
        $repoNameMap = [
            'H5P.Flashcards' => 'h5p-flashcards',
            'H5P.JoubelUI' => 'h5p-joubel-ui',
            'H5P.FontIcons' => 'h5p-font-icons',
            'H5P.Transition' => 'h5p-transition',
            'FontAwesome' => 'h5p-font-awesome',
            'H5P.MultiChoice' => 'h5p-multi-choice',
            'H5P.Question' => 'h5p-question',
            'H5P.InteractiveVideo' => 'h5p-interactive-video',
            'H5P.TrueFalse' => 'h5p-true-false',
            'H5P.DragQuestion' => 'h5p-drag-question',
            'H5P.CoursePresentation' => 'h5p-course-presentation',
            'H5P.Dialogcards' => 'h5p-dialogcards',
            'H5P.MemoryGame' => 'h5p-memory-game',
            'H5P.Summary' => 'h5p-summary',
            'H5P.Accordion' => 'h5p-accordion',
            'H5P.DragText' => 'h5p-drag-text',
            'H5P.Blanks' => 'h5p-blanks',
            'H5P.Column' => 'h5p-column',
            'H5P.Audio' => 'h5p-audio',
            'H5P.Video' => 'h5p-video',
            'H5P.ImageHotspots' => 'h5p-image-hotspots',
            'H5P.FindTheWords' => 'h5p-find-the-words',
            'H5P.Components' => 'h5p-components',
            'H5PEditor.VerticalTabs' => 'h5p-editor-vertical-tabs',
        ];

        $repo = $repoNameMap[$machineName] ?? ('h5p-' . strtolower(str_replace('.', '-', preg_replace('/^H5P\./', '', $machineName))));
        $url = "https://github.com/h5p/{$repo}/archive/refs/heads/master.zip";

        $tempZip = tempnam(sys_get_temp_dir(), 'h5p_lib_') . '.zip';
        $tempExtract = tempnam(sys_get_temp_dir(), 'h5p_ext_');
        @unlink($tempExtract);

        try {
            $context = stream_context_create([
                'http' => [
                    'timeout' => 10,
                    'follow_location' => 1,
                    'header' => "User-Agent: ESL-LMS-Platform\r\n"
                ],
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                ]
            ]);

            $content = @file_get_contents($url, false, $context);
            if (!$content) {
                @unlink($tempZip);
                return null;
            }

            file_put_contents($tempZip, $content);

            $zip = new ZipArchive();
            if ($zip->open($tempZip) === true) {
                mkdir($tempExtract, 0755, true);
                $zip->extractTo($tempExtract);
                $zip->close();
            }

            @unlink($tempZip);

            if (!is_dir($tempExtract)) {
                return null;
            }

            // Find root where library.json resides
            $librarySourceDir = null;
            if (file_exists($tempExtract . DIRECTORY_SEPARATOR . 'library.json')) {
                $librarySourceDir = $tempExtract;
            } else {
                $subDirs = glob($tempExtract . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR);
                foreach ($subDirs as $sd) {
                    if (file_exists($sd . DIRECTORY_SEPARATOR . 'library.json')) {
                        $librarySourceDir = $sd;
                        break;
                    }
                }
            }

            if (!$librarySourceDir) {
                $this->deleteDirectory($tempExtract);
                return null;
            }

            $finalSharedDir = $this->sharedLibrariesPath . DIRECTORY_SEPARATOR . $targetFolderName;
            if (!is_dir($finalSharedDir)) {
                $this->copyDirectory($librarySourceDir, $finalSharedDir);
            }

            $this->deleteDirectory($tempExtract);

            return $finalSharedDir;
        } catch (\Throwable $e) {
            Log::warning("H5P: Exception downloading library '{$machineName}' from GitHub: " . $e->getMessage());
            if (file_exists($tempZip)) @unlink($tempZip);
            if (is_dir($tempExtract)) $this->deleteDirectory($tempExtract);
            return null;
        }
    }

    /**
     * Read and decode h5p.json metadata.
     */
    protected function readH5pMetadata(string $path): array
    {
        try {
            $content = file_get_contents($path);
            return json_decode($content, true) ?: [];
        } catch (\Throwable $e) {
            Log::error("H5P: Error reading metadata from {$path}: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Move contents of a single subfolder up to the parent directory.
     */
    protected function flattenDirectory(string $from, string $to): void
    {
        $items = scandir($from);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $src = $from . DIRECTORY_SEPARATOR . $item;
            $dst = $to . DIRECTORY_SEPARATOR . $item;
            if (file_exists($dst)) {
                if (is_dir($dst)) {
                    $this->deleteDirectory($dst);
                } else {
                    unlink($dst);
                }
            }
            rename($src, $dst);
        }
        @rmdir($from);
    }

    /**
     * Recursively copy a directory.
     */
    protected function copyDirectory(string $src, string $dst): void
    {
        if (!is_dir($src)) return;
        @mkdir($dst, 0755, true);

        $dir = opendir($src);
        while (($file = readdir($dir)) !== false) {
            if ($file === '.' || $file === '..') continue;
            $srcFile = $src . DIRECTORY_SEPARATOR . $file;
            $dstFile = $dst . DIRECTORY_SEPARATOR . $file;
            if (is_dir($srcFile)) {
                $this->copyDirectory($srcFile, $dstFile);
            } else {
                copy($srcFile, $dstFile);
            }
        }
        closedir($dir);
    }

    /**
     * Recursively delete directory.
     */
    public function deleteExtracted(string $hash): bool
    {
        $targetDir = $this->extractedBasePath . DIRECTORY_SEPARATOR . $hash;
        if (is_dir($targetDir)) {
            return $this->deleteDirectory($targetDir);
        }
        return false;
    }

    protected function deleteDirectory(string $dir): bool
    {
        if (!is_dir($dir)) {
            return false;
        }
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            is_dir($path) ? $this->deleteDirectory($path) : @unlink($path);
        }
        return @rmdir($dir);
    }
}
