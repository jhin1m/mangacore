<?php

namespace Tests\Unit;

use Tests\TestCase;
use Ophim\Core\Models\User;
use Ophim\Core\Models\Manga;
use Ophim\Core\Models\Chapter;
use Ophim\Core\Models\ReadingProgress;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserMangaFeaturesTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $manga;
    protected $chapter;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test data
        $this->user = User::factory()->create([
            'reading_mode' => 'single',
            'image_quality' => 'medium'
        ]);
        
        $this->manga = Manga::factory()->create([
            'title' => 'Test Manga',
            'total_chapters' => 10
        ]);
        
        $this->chapter = Chapter::factory()->create([
            'manga_id' => $this->manga->id,
            'chapter_number' => 1,
            'page_count' => 20
        ]);
    }

    /** @test */
    public function it_can_create_user_with_reading_preferences()
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'reading_mode' => 'vertical',
            'image_quality' => 'high'
        ]);

        $this->assertEquals('vertical', $user->reading_mode);
        $this->assertEquals('high', $user->image_quality);
    }

    /** @test */
    public function it_can_update_reading_preferences()
    {
        $this->user->updateReadingPreferences([
            'auto_bookmark' => true,
            'preload_pages' => 5
        ]);

        $this->assertTrue($this->user->getReadingPreference('auto_bookmark'));
        $this->assertEquals(5, $this->user->getReadingPreference('preload_pages'));
    }

    /** @test */
    public function it_can_add_bookmark_to_reading_progress()
    {
        $progress = ReadingProgress::addBookmark(
            $this->manga->id,
            $this->chapter->id,
            10,
            'Great chapter!',
            $this->user->id
        );

        $this->assertNotNull($progress);
        $this->assertTrue($progress->is_bookmarked);
        $this->assertEquals('Great chapter!', $progress->bookmark_note);
        $this->assertEquals(10, $progress->page_number);
    }

    /** @test */
    public function it_can_remove_bookmark()
    {
        // First add a bookmark
        ReadingProgress::addBookmark(
            $this->manga->id,
            $this->chapter->id,
            10,
            'Test bookmark',
            $this->user->id
        );

        // Then remove it
        $result = ReadingProgress::removeBookmark($this->manga->id, $this->user->id);

        $this->assertTrue($result);
        
        $progress = ReadingProgress::getProgress($this->manga->id, $this->user->id);
        $this->assertFalse($progress->is_bookmarked);
        $this->assertNull($progress->bookmark_note);
    }

    /** @test */
    public function it_can_get_user_bookmarks()
    {
        // Create multiple bookmarks
        $manga2 = Manga::factory()->create(['title' => 'Test Manga 2']);
        $chapter2 = Chapter::factory()->create(['manga_id' => $manga2->id]);

        ReadingProgress::addBookmark($this->manga->id, $this->chapter->id, 5, 'Bookmark 1', $this->user->id);
        ReadingProgress::addBookmark($manga2->id, $chapter2->id, 10, 'Bookmark 2', $this->user->id);

        $bookmarks = ReadingProgress::getUserBookmarks($this->user->id);

        $this->assertCount(2, $bookmarks);
        $this->assertTrue($bookmarks->every(fn($bookmark) => $bookmark->is_bookmarked));
    }

    /** @test */
    public function it_can_rate_manga()
    {
        $this->user->rateManga($this->manga->id, 8.5, 'Great manga!');

        $rating = $this->user->getRatingFor($this->manga->id);
        $this->assertEquals(8.5, $rating);

        $ratedManga = $this->user->ratedMangas()->first();
        $this->assertEquals('Great manga!', $ratedManga->pivot->review);
    }

    /** @test */
    public function it_can_add_to_favorites()
    {
        $this->user->addToFavorites($this->manga->id);

        $this->assertTrue($this->user->hasFavorited($this->manga->id));
        $this->assertCount(1, $this->user->favoriteMangas);
    }

    /** @test */
    public function it_can_remove_from_favorites()
    {
        $this->user->addToFavorites($this->manga->id);
        $this->user->removeFromFavorites($this->manga->id);

        $this->assertFalse($this->user->hasFavorited($this->manga->id));
    }

    /** @test */
    public function it_can_get_reading_statistics()
    {
        // Create some reading progress
        ReadingProgress::updateProgress($this->manga->id, $this->chapter->id, 10, $this->user->id);
        $this->user->rateManga($this->manga->id, 9.0);
        $this->user->addToFavorites($this->manga->id);

        $stats = $this->user->getReadingStats();

        $this->assertEquals(1, $stats['total_read']);
        $this->assertEquals(1, $stats['total_favorites']);
        $this->assertEquals(9.0, $stats['average_rating']);
        $this->assertGreaterThanOrEqual(0, $stats['recent_activity']);
    }

    /** @test */
    public function it_can_get_favorite_genres()
    {
        // This would require categories to be set up
        // For now, just test that the method exists and returns a collection
        $genres = $this->user->getFavoriteGenres();
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $genres);
    }

    /** @test */
    public function it_can_get_recently_read_manga()
    {
        ReadingProgress::updateProgress($this->manga->id, $this->chapter->id, 5, $this->user->id);

        $recentlyRead = $this->user->getRecentlyRead();

        $this->assertCount(1, $recentlyRead);
        $this->assertEquals($this->manga->id, $recentlyRead->first()->manga_id);
    }

    /** @test */
    public function it_can_get_continue_reading_list()
    {
        ReadingProgress::updateProgress($this->manga->id, $this->chapter->id, 5, $this->user->id);

        $continueReading = $this->user->getContinueReading();

        $this->assertCount(1, $continueReading);
        $this->assertEquals($this->manga->id, $continueReading->first()->manga_id);
    }

    /** @test */
    public function manga_can_get_average_user_rating()
    {
        // Create multiple users and ratings
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();

        $this->user->rateManga($this->manga->id, 8.0);
        $user2->rateManga($this->manga->id, 9.0);
        $user3->rateManga($this->manga->id, 7.0);

        $averageRating = $this->manga->getAverageUserRating();
        $this->assertEquals(8.0, $averageRating); // (8+9+7)/3 = 8.0

        $ratingCount = $this->manga->getUserRatingCount();
        $this->assertEquals(3, $ratingCount);
    }

    /** @test */
    public function manga_can_get_favorite_count()
    {
        $user2 = User::factory()->create();

        $this->user->addToFavorites($this->manga->id);
        $user2->addToFavorites($this->manga->id);

        $favoriteCount = $this->manga->getFavoriteCount();
        $this->assertEquals(2, $favoriteCount);
    }

    /** @test */
    public function manga_can_check_if_rated_by_user()
    {
        $this->assertFalse($this->manga->isRatedByUser($this->user->id));

        $this->user->rateManga($this->manga->id, 8.5);

        $this->assertTrue($this->manga->isRatedByUser($this->user->id));
    }

    /** @test */
    public function manga_can_check_if_favorited_by_user()
    {
        $this->assertFalse($this->manga->isFavoritedByUser($this->user->id));

        $this->user->addToFavorites($this->manga->id);

        $this->assertTrue($this->manga->isFavoritedByUser($this->user->id));
    }

    /** @test */
    public function manga_can_update_overall_rating()
    {
        $user2 = User::factory()->create();

        $this->user->rateManga($this->manga->id, 8.0);
        $user2->rateManga($this->manga->id, 9.0);

        $this->manga->updateOverallRating();

        $this->manga->refresh();
        $this->assertEquals(8.5, $this->manga->rating); // (8+9)/2 = 8.5
        $this->assertEquals(2, $this->manga->rating_count);
    }

    /** @test */
    public function reading_progress_can_track_history()
    {
        ReadingProgress::updateProgress($this->manga->id, $this->chapter->id, 5, $this->user->id);
        ReadingProgress::updateProgress($this->manga->id, $this->chapter->id, 10, $this->user->id);

        $history = ReadingProgress::getReadingHistory($this->user->id);

        $this->assertCount(1, $history); // Should be 1 record (updated, not created new)
        $this->assertEquals(10, $history->first()->page_number);
    }

    /** @test */
    public function reading_progress_can_check_if_bookmarked()
    {
        $this->assertFalse(ReadingProgress::isBookmarked($this->manga->id, $this->user->id));

        ReadingProgress::addBookmark($this->manga->id, $this->chapter->id, 5, null, $this->user->id);

        $this->assertTrue(ReadingProgress::isBookmarked($this->manga->id, $this->user->id));
    }
}
</content>
</file>