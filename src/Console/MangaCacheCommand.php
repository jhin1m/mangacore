<?php

namespace Ophim\Core\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Ophim\Core\Models\Manga;
use Ophim\Core\Models\Chapter;

class MangaCacheCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'manga:cache {action : The cache action (clear, warm, stats)} 
                                        {--type= : Cache type (manga, chapter, all)}
                                        {--id= : Specific ID to target}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage manga and chapter caches';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $action = $this->argument('action');
        $type = $this->option('type') ?? 'all';
        $id = $this->option('id');

        switch ($action) {
            case 'clear':
                return $this->clearCache($type, $id);
            case 'warm':
                return $this->warmCache($type, $id);
            case 'stats':
                return $this->showCacheStats();
            default:
                $this->error("Invalid action: {$action}. Use: clear, warm, or stats");
                return 1;
        }
    }

    /**
     * Clear caches
     */
    protected function clearCache($type, $id)
    {
        $this->info("Clearing {$type} caches...");

        if ($id) {
            return $this->clearSpecificCache($type, $id);
        }

        switch ($type) {
            case 'manga':
                $this->clearMangaCaches();
                break;
            case 'chapter':
                $this->clearChapterCaches();
                break;
            case 'all':
                $this->clearAllCaches();
                break;
            default:
                $this->error("Invalid type: {$type}. Use: manga, chapter, or all");
                return 1;
        }

        $this->info("Cache cleared successfully!");
        return 0;
    }

    /**
     * Warm up caches
     */
    protected function warmCache($type, $id)
    {
        $this->info("Warming up {$type} caches...");

        if ($id) {
            return $this->warmSpecificCache($type, $id);
        }

        switch ($type) {
            case 'manga':
                $this->warmMangaCaches();
                break;
            case 'chapter':
                $this->warmChapterCaches();
                break;
            case 'all':
                $this->warmAllCaches();
                break;
            default:
                $this->error("Invalid type: {$type}. Use: manga, chapter, or all");
                return 1;
        }

        $this->info("Cache warmed up successfully!");
        return 0;
    }

    /**
     * Show cache statistics
     */
    protected function showCacheStats()
    {
        $this->info("Cache Statistics:");
        $this->line("==================");

        // Get cache store info
        $store = Cache::getStore();
        $this->line("Cache Store: " . get_class($store));

        // Count cached items (approximation)
        $mangaCacheCount = $this->countCacheKeys('manga_*');
        $chapterCacheCount = $this->countCacheKeys('chapter_*');
        $apiCacheCount = $this->countCacheKeys('*_api_*');

        $this->table(
            ['Cache Type', 'Approximate Count'],
            [
                ['Manga Caches', $mangaCacheCount],
                ['Chapter Caches', $chapterCacheCount],
                ['API Caches', $apiCacheCount],
            ]
        );

        return 0;
    }

    /**
     * Clear specific cache by ID
     */
    protected function clearSpecificCache($type, $id)
    {
        switch ($type) {
            case 'manga':
                $manga = Manga::find($id);
                if ($manga) {
                    $manga->invalidateCache();
                    $this->info("Cleared cache for manga ID: {$id}");
                } else {
                    $this->error("Manga not found with ID: {$id}");
                    return 1;
                }
                break;
            case 'chapter':
                $chapter = Chapter::find($id);
                if ($chapter) {
                    $chapter->invalidateCache();
                    $this->info("Cleared cache for chapter ID: {$id}");
                } else {
                    $this->error("Chapter not found with ID: {$id}");
                    return 1;
                }
                break;
            default:
                $this->error("Invalid type for specific cache clear: {$type}");
                return 1;
        }

        return 0;
    }

    /**
     * Warm specific cache by ID
     */
    protected function warmSpecificCache($type, $id)
    {
        switch ($type) {
            case 'manga':
                $manga = Manga::find($id);
                if ($manga) {
                    $this->warmMangaCache($manga);
                    $this->info("Warmed cache for manga ID: {$id}");
                } else {
                    $this->error("Manga not found with ID: {$id}");
                    return 1;
                }
                break;
            case 'chapter':
                $chapter = Chapter::find($id);
                if ($chapter) {
                    $this->warmChapterCache($chapter);
                    $this->info("Warmed cache for chapter ID: {$id}");
                } else {
                    $this->error("Chapter not found with ID: {$id}");
                    return 1;
                }
                break;
            default:
                $this->error("Invalid type for specific cache warm: {$type}");
                return 1;
        }

        return 0;
    }

    /**
     * Clear all manga caches
     */
    protected function clearMangaCaches()
    {
        $patterns = [
            'manga_*',
            'cache_manga_*'
        ];

        foreach ($patterns as $pattern) {
            $this->clearCachePattern($pattern);
        }
    }

    /**
     * Clear all chapter caches
     */
    protected function clearChapterCaches()
    {
        $patterns = [
            'chapter_*',
            'cache_chapter_*'
        ];

        foreach ($patterns as $pattern) {
            $this->clearCachePattern($pattern);
        }
    }

    /**
     * Clear all caches
     */
    protected function clearAllCaches()
    {
        Cache::flush();
    }

    /**
     * Warm manga caches
     */
    protected function warmMangaCaches()
    {
        $popularManga = Manga::orderBy('view_count', 'desc')->limit(50)->get();
        
        $bar = $this->output->createProgressBar($popularManga->count());
        $bar->start();

        foreach ($popularManga as $manga) {
            $this->warmMangaCache($manga);
            $bar->advance();
        }

        $bar->finish();
        $this->line('');
    }

    /**
     * Warm chapter caches
     */
    protected function warmChapterCaches()
    {
        $recentChapters = Chapter::orderBy('updated_at', 'desc')->limit(100)->get();
        
        $bar = $this->output->createProgressBar($recentChapters->count());
        $bar->start();

        foreach ($recentChapters as $chapter) {
            $this->warmChapterCache($chapter);
            $bar->advance();
        }

        $bar->finish();
        $this->line('');
    }

    /**
     * Warm all caches
     */
    protected function warmAllCaches()
    {
        $this->warmMangaCaches();
        $this->warmChapterCaches();
        
        // Warm popular lists
        Manga::getCachedPopular(20);
        Manga::getCachedLatest(20);
        Manga::getCachedFeatured(20);
    }

    /**
     * Warm cache for specific manga
     */
    protected function warmMangaCache(Manga $manga)
    {
        $manga->cacheWithRelationships();
        $manga->getCachedStatistics();
        $manga->getCachedChapters();
        $manga->getCachedRelatedManga();
    }

    /**
     * Warm cache for specific chapter
     */
    protected function warmChapterCache(Chapter $chapter)
    {
        $chapter->getCachedPages();
        $chapter->getCachedNavigation();
        $chapter->getCachedReadingData();
        $chapter->getCachedStatistics();
    }

    /**
     * Clear cache pattern (implementation depends on cache driver)
     */
    protected function clearCachePattern($pattern)
    {
        // This is a simplified implementation
        // In production, you might need driver-specific implementations
        try {
            if (method_exists(Cache::getStore(), 'flush')) {
                // For drivers that support pattern clearing
                Cache::getStore()->flush();
            }
        } catch (\Exception $e) {
            $this->warn("Could not clear pattern {$pattern}: " . $e->getMessage());
        }
    }

    /**
     * Count cache keys matching pattern (approximation)
     */
    protected function countCacheKeys($pattern)
    {
        // This is a placeholder - actual implementation depends on cache driver
        // For Redis, you could use KEYS command
        // For file cache, you could scan the cache directory
        return '~';
    }
}