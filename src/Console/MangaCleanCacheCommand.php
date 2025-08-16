<?php

namespace Ophim\Core\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Ophim\Core\Models\Manga;
use Ophim\Core\Models\Chapter;

class MangaCleanCacheCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'manga:clean-cache 
                            {--type=all : Cache type to clean (all, model, view, image, session, database)}
                            {--manga-id= : Clean cache for specific manga ID}
                            {--older-than= : Clean cache older than specified time (e.g., 1d, 2h, 30m)}
                            {--force : Force clean without confirmation}
                            {--dry-run : Show what would be cleaned without actually cleaning}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean manga-related cache data';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $type = $this->option('type');
        $mangaId = $this->option('manga-id');
        $olderThan = $this->option('older-than');
        $force = $this->option('force');
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('DRY RUN MODE - No actual cleaning will be performed');
        }

        $this->info('Starting cache cleanup...');
        $this->info("Type: {$type}");
        
        if ($mangaId) {
            $this->info("Manga ID: {$mangaId}");
        }
        
        if ($olderThan) {
            $this->info("Older than: {$olderThan}");
        }

        if (!$force && !$dryRun) {
            if (!$this->confirm('Are you sure you want to clean the cache?')) {
                $this->info('Cache cleanup cancelled.');
                return 0;
            }
        }

        $totalCleaned = 0;

        try {
            if ($type === 'all' || $type === 'model') {
                $cleaned = $this->cleanModelCache($mangaId, $olderThan, $dryRun);
                $totalCleaned += $cleaned;
            }

            if ($type === 'all' || $type === 'view') {
                $cleaned = $this->cleanViewCache($mangaId, $dryRun);
                $totalCleaned += $cleaned;
            }

            if ($type === 'all' || $type === 'image') {
                $cleaned = $this->cleanImageCache($mangaId, $olderThan, $dryRun);
                $totalCleaned += $cleaned;
            }

            if ($type === 'all' || $type === 'session') {
                $cleaned = $this->cleanSessionCache($olderThan, $dryRun);
                $totalCleaned += $cleaned;
            }

            if ($type === 'all' || $type === 'database') {
                $cleaned = $this->cleanDatabaseCache($dryRun);
                $totalCleaned += $cleaned;
            }

            if ($dryRun) {
                $this->info("DRY RUN: Would clean {$totalCleaned} cache entries");
            } else {
                $this->info("Cache cleanup completed! Cleaned {$totalCleaned} entries");
            }

            return 0;

        } catch (\Exception $e) {
            $this->error("Cache cleanup failed: " . $e->getMessage());
            return 1;
        }
    }

    /**
     * Clean model cache
     */
    protected function cleanModelCache($mangaId, $olderThan, $dryRun)
    {
        $this->info('Cleaning model cache...');
        $cleaned = 0;

        // Cache keys patterns for manga-related models
        $patterns = [
            'manga:*',
            'chapter:*',
            'page:*',
            'reading_progress:*',
            'manga_list:*',
            'chapter_list:*',
            'popular_manga:*',
            'recent_manga:*',
            'manga_categories:*',
            'manga_tags:*'
        ];

        if ($mangaId) {
            $patterns = [
                "manga:{$mangaId}:*",
                "chapter:manga_{$mangaId}:*",
                "manga_chapters:{$mangaId}",
                "manga_details:{$mangaId}"
            ];
        }

        foreach ($patterns as $pattern) {
            $keys = $this->getCacheKeysByPattern($pattern);
            
            foreach ($keys as $key) {
                if ($this->shouldCleanCacheKey($key, $olderThan)) {
                    if (!$dryRun) {
                        Cache::forget($key);
                    }
                    $cleaned++;
                }
            }
        }

        $this->info("Model cache: {$cleaned} entries " . ($dryRun ? 'would be' : '') . " cleaned");
        return $cleaned;
    }

    /**
     * Clean view cache
     */
    protected function cleanViewCache($mangaId, $dryRun)
    {
        $this->info('Cleaning view cache...');
        $cleaned = 0;

        $viewCachePath = storage_path('framework/views');
        
        if (!is_dir($viewCachePath)) {
            $this->info('No view cache directory found');
            return 0;
        }

        $files = glob($viewCachePath . '/*.php');
        
        foreach ($files as $file) {
            // Check if it's a manga-related view cache file
            $content = file_get_contents($file);
            
            if (strpos($content, 'manga') !== false || 
                strpos($content, 'chapter') !== false || 
                strpos($content, 'reader') !== false) {
                
                if (!$dryRun) {
                    unlink($file);
                }
                $cleaned++;
            }
        }

        $this->info("View cache: {$cleaned} files " . ($dryRun ? 'would be' : '') . " cleaned");
        return $cleaned;
    }

    /**
     * Clean image cache
     */
    protected function cleanImageCache($mangaId, $olderThan, $dryRun)
    {
        $this->info('Cleaning image cache...');
        $cleaned = 0;

        // Clean temporary image processing files
        $tempPaths = [
            'temp/image_processing',
            'temp/thumbnails',
            'temp/optimized',
            'temp/chapter_import'
        ];

        foreach ($tempPaths as $path) {
            $fullPath = storage_path('app/' . $path);
            
            if (is_dir($fullPath)) {
                $files = $this->getFilesOlderThan($fullPath, $olderThan);
                
                foreach ($files as $file) {
                    if (!$dryRun) {
                        if (is_dir($file)) {
                            $this->deleteDirectory($file);
                        } else {
                            unlink($file);
                        }
                    }
                    $cleaned++;
                }
            }
        }

        // Clean CDN cache entries
        $cdnCacheKeys = $this->getCacheKeysByPattern('cdn:*');
        foreach ($cdnCacheKeys as $key) {
            if ($this->shouldCleanCacheKey($key, $olderThan)) {
                if (!$dryRun) {
                    Cache::forget($key);
                }
                $cleaned++;
            }
        }

        $this->info("Image cache: {$cleaned} entries " . ($dryRun ? 'would be' : '') . " cleaned");
        return $cleaned;
    }

    /**
     * Clean session cache
     */
    protected function cleanSessionCache($olderThan, $dryRun)
    {
        $this->info('Cleaning session cache...');
        $cleaned = 0;

        // Clean reading progress session data
        $sessionKeys = $this->getCacheKeysByPattern('reading_progress:session:*');
        
        foreach ($sessionKeys as $key) {
            if ($this->shouldCleanCacheKey($key, $olderThan)) {
                if (!$dryRun) {
                    Cache::forget($key);
                }
                $cleaned++;
            }
        }

        // Clean temporary user preferences
        $prefKeys = $this->getCacheKeysByPattern('user_preferences:*');
        
        foreach ($prefKeys as $key) {
            if ($this->shouldCleanCacheKey($key, $olderThan)) {
                if (!$dryRun) {
                    Cache::forget($key);
                }
                $cleaned++;
            }
        }

        $this->info("Session cache: {$cleaned} entries " . ($dryRun ? 'would be' : '') . " cleaned");
        return $cleaned;
    }

    /**
     * Clean database cache
     */
    protected function cleanDatabaseCache($dryRun)
    {
        $this->info('Cleaning database cache...');
        $cleaned = 0;

        // Clear query cache
        if (!$dryRun) {
            DB::statement('RESET QUERY CACHE');
        }
        $cleaned++;

        // Clear Laravel's database query cache
        $dbCacheKeys = $this->getCacheKeysByPattern('laravel_cache:*');
        
        foreach ($dbCacheKeys as $key) {
            if (!$dryRun) {
                Cache::forget($key);
            }
            $cleaned++;
        }

        $this->info("Database cache: {$cleaned} entries " . ($dryRun ? 'would be' : '') . " cleaned");
        return $cleaned;
    }

    /**
     * Get cache keys by pattern
     */
    protected function getCacheKeysByPattern($pattern)
    {
        // This is a simplified implementation
        // In production, you might want to use Redis SCAN or similar
        $keys = [];
        
        try {
            // For file-based cache, scan cache directory
            $cacheDir = storage_path('framework/cache/data');
            
            if (is_dir($cacheDir)) {
                $files = glob($cacheDir . '/*');
                
                foreach ($files as $file) {
                    $key = basename($file);
                    
                    // Simple pattern matching
                    $regexPattern = str_replace('*', '.*', preg_quote($pattern, '/'));
                    
                    if (preg_match('/^' . $regexPattern . '$/', $key)) {
                        $keys[] = $key;
                    }
                }
            }
        } catch (\Exception $e) {
            // Fallback to common cache keys
            $commonKeys = [
                'manga:list', 'manga:popular', 'manga:recent',
                'chapter:list', 'reading_progress:stats'
            ];
            
            foreach ($commonKeys as $key) {
                if (Cache::has($key)) {
                    $keys[] = $key;
                }
            }
        }

        return $keys;
    }

    /**
     * Check if cache key should be cleaned based on age
     */
    protected function shouldCleanCacheKey($key, $olderThan)
    {
        if (!$olderThan) {
            return true;
        }

        try {
            // Get cache file modification time
            $cacheFile = storage_path('framework/cache/data/' . $key);
            
            if (file_exists($cacheFile)) {
                $fileTime = filemtime($cacheFile);
                $threshold = $this->parseTimeString($olderThan);
                
                return (time() - $fileTime) > $threshold;
            }
        } catch (\Exception $e) {
            // If we can't determine age, clean it
            return true;
        }

        return true;
    }

    /**
     * Parse time string to seconds
     */
    protected function parseTimeString($timeString)
    {
        $unit = substr($timeString, -1);
        $value = (int) substr($timeString, 0, -1);

        switch (strtolower($unit)) {
            case 'm':
                return $value * 60;
            case 'h':
                return $value * 3600;
            case 'd':
                return $value * 86400;
            case 'w':
                return $value * 604800;
            default:
                return $value; // Assume seconds
        }
    }

    /**
     * Get files older than specified time
     */
    protected function getFilesOlderThan($directory, $olderThan)
    {
        $files = [];
        
        if (!$olderThan) {
            // Return all files if no time specified
            return glob($directory . '/*');
        }

        $threshold = time() - $this->parseTimeString($olderThan);
        $allFiles = glob($directory . '/*');

        foreach ($allFiles as $file) {
            if (filemtime($file) < $threshold) {
                $files[] = $file;
            }
        }

        return $files;
    }

    /**
     * Delete directory recursively
     */
    protected function deleteDirectory($dir)
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), array('.', '..'));
        
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            
            if (is_dir($path)) {
                $this->deleteDirectory($path);
            } else {
                unlink($path);
            }
        }
        
        rmdir($dir);
    }
}