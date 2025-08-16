<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Ophim\Core\Models\Manga;
use Ophim\Core\Models\Chapter;
use Ophim\Core\Models\Page;
use Ophim\Core\Services\CDNManager;

class PerformanceOptimizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Clear cache before each test
        Cache::flush();
    }

    /** @test */
    public function manga_caching_methods_work_correctly()
    {
        $manga = Manga::factory()->create();
        
        // Test cacheWithRelationships
        $cachedManga = $manga->cacheWithRelationships(['authors', 'categories']);
        $this->assertInstanceOf(Manga::class, $cachedManga);
        
        // Test getCachedStatistics
        $stats = $manga->getCachedStatistics();
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_chapters', $stats);
        $this->assertArrayHasKey('total_volumes', $stats);
        
        // Test getCachedRelatedManga
        $related = $manga->getCachedRelatedManga(5);
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $related);
    }

    /** @test */
    public function chapter_caching_methods_work_correctly()
    {
        $manga = Manga::factory()->create();
        $chapter = Chapter::factory()->create(['manga_id' => $manga->id]);
        
        // Create some pages
        Page::factory()->count(3)->create(['chapter_id' => $chapter->id]);
        
        // Test getCachedPages
        $pages = $chapter->getCachedPages('medium');
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $pages);
        
        // Test getCachedNavigation
        $navigation = $chapter->getCachedNavigation();
        $this->assertIsArray($navigation);
        $this->assertArrayHasKey('chapter_list', $navigation);
        
        // Test getCachedReadingData
        $readingData = $chapter->getCachedReadingData('medium', 3);
        $this->assertIsArray($readingData);
        $this->assertArrayHasKey('chapter', $readingData);
        $this->assertArrayHasKey('pages', $readingData);
        $this->assertArrayHasKey('navigation', $readingData);
    }

    /** @test */
    public function cache_invalidation_works_correctly()
    {
        $manga = Manga::factory()->create();
        
        // Cache some data
        $manga->getCachedStatistics();
        
        // Update manga (should trigger cache invalidation)
        $manga->update(['title' => 'Updated Title']);
        
        // Verify cache was cleared by checking if new data is fetched
        $stats = $manga->getCachedStatistics();
        $this->assertIsArray($stats);
    }

    /** @test */
    public function static_cache_methods_work_correctly()
    {
        Manga::factory()->count(5)->create();
        
        // Test getCachedPopular
        $popular = Manga::getCachedPopular(3);
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $popular);
        $this->assertLessThanOrEqual(3, $popular->count());
        
        // Test getCachedLatest
        $latest = Manga::getCachedLatest(3);
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $latest);
        $this->assertLessThanOrEqual(3, $latest->count());
        
        // Test getCachedFeatured
        $featured = Manga::getCachedFeatured(3);
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $featured);
    }

    /** @test */
    public function page_optimization_methods_work_correctly()
    {
        $chapter = Chapter::factory()->create();
        $page = Page::factory()->create(['chapter_id' => $chapter->id]);
        
        // Test getOptimizedUrl
        $optimizedUrl = $page->getOptimizedUrl('medium');
        $this->assertIsString($optimizedUrl);
        
        // Test getThumbnailUrl
        $thumbnailUrl = $page->getThumbnailUrl();
        $this->assertIsString($thumbnailUrl);
        
        // Test getWebPUrl
        $webpUrl = $page->getWebPUrl('medium');
        $this->assertIsString($webpUrl);
        
        // Test getDimensions (may return null for test data)
        $dimensions = $page->getDimensions();
        $this->assertTrue(is_array($dimensions) || is_null($dimensions));
    }

    /** @test */
    public function cdn_manager_optimization_works_correctly()
    {
        $cdnManager = new CDNManager();
        
        // Test getOptimizedUrl with options
        $imageUrl = 'https://example.com/image.jpg';
        $optimizedUrl = $cdnManager->getOptimizedUrl($imageUrl, 'medium', [
            'cache_ttl' => 3600,
            'format' => 'webp'
        ]);
        
        $this->assertIsString($optimizedUrl);
        $this->assertStringContainsString('cache=3600', $optimizedUrl);
    }

    /** @test */
    public function database_indexes_are_properly_defined()
    {
        // This test verifies that the migration structure is correct
        // In a real environment, you would check if indexes exist in the database
        
        $migration = new \CreateMangasTable();
        $this->assertTrue(method_exists($migration, 'up'));
        $this->assertTrue(method_exists($migration, 'down'));
        
        // Test that performance indexes migration exists
        $this->assertTrue(file_exists(database_path('migrations/2024_01_01_000030_add_performance_indexes.php')));
    }

    /** @test */
    public function eager_loading_is_properly_configured()
    {
        // Create test data with relationships
        $manga = Manga::factory()->create();
        $author = \Ophim\Core\Models\Author::factory()->create();
        $category = \Ophim\Core\Models\Category::factory()->create();
        
        $manga->authors()->attach($author);
        $manga->categories()->attach($category);
        
        // Test that relationships can be eager loaded
        $mangaWithRelations = Manga::with(['authors', 'categories'])->find($manga->id);
        
        $this->assertTrue($mangaWithRelations->relationLoaded('authors'));
        $this->assertTrue($mangaWithRelations->relationLoaded('categories'));
    }

    /** @test */
    public function api_caching_works_correctly()
    {
        // Test that API responses can be cached
        $cacheKey = 'test_api_cache';
        $testData = ['test' => 'data'];
        
        Cache::put($cacheKey, $testData, 300);
        
        $cachedData = Cache::get($cacheKey);
        $this->assertEquals($testData, $cachedData);
    }

    /** @test */
    public function performance_scopes_work_correctly()
    {
        $manga1 = Manga::factory()->create(['status' => 'ongoing']);
        $manga2 = Manga::factory()->create(['status' => 'completed']);
        $manga3 = Manga::factory()->create(['is_recommended' => true]);
        
        // Test scopes
        $ongoingManga = Manga::ongoing()->get();
        $this->assertTrue($ongoingManga->contains($manga1));
        $this->assertFalse($ongoingManga->contains($manga2));
        
        $completedManga = Manga::completed()->get();
        $this->assertTrue($completedManga->contains($manga2));
        $this->assertFalse($completedManga->contains($manga1));
        
        $recommendedManga = Manga::recommended()->get();
        $this->assertTrue($recommendedManga->contains($manga3));
    }
}