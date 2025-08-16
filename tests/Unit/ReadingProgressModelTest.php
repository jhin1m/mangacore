<?php

namespace Tests\Unit;

use Tests\TestCase;
use Ophim\Core\Models\ReadingProgress;
use Ophim\Core\Models\User;
use Ophim\Core\Models\Manga;
use Ophim\Core\Models\Chapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;

class ReadingProgressModelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test data
        $this->user = User::factory()->create();
        $this->manga = Manga::factory()->create(['total_chapters' => 10]);
        $this->chapter = Chapter::factory()->create([
            'manga_id' => $this->manga->id,
            'chapter_number' => 1.0,
            'page_count' => 20
        ]);
    }

    /** @test */
    public function it_has_correct_fillable_attributes()
    {
        $fillable = [
            'user_id',
            'manga_id',
            'chapter_id',
            'page_number',
            'completed_at'
        ];

        $this->assertEquals($fillable, (new ReadingProgress())->getFillable());
    }

    /** @test */
    public function it_belongs_to_user()
    {
        $progress = ReadingProgress::factory()->create([
            'user_id' => $this->user->id
        ]);

        $this->assertInstanceOf(User::class, $progress->user);
        $this->assertEquals($this->user->id, $progress->user->id);
    }

    /** @test */
    public function it_belongs_to_manga()
    {
        $progress = ReadingProgress::factory()->create([
            'manga_id' => $this->manga->id
        ]);

        $this->assertInstanceOf(Manga::class, $progress->manga);
        $this->assertEquals($this->manga->id, $progress->manga->id);
    }

    /** @test */
    public function it_belongs_to_chapter()
    {
        $progress = ReadingProgress::factory()->create([
            'chapter_id' => $this->chapter->id
        ]);

        $this->assertInstanceOf(Chapter::class, $progress->chapter);
        $this->assertEquals($this->chapter->id, $progress->chapter->id);
    }

    /** @test */
    public function it_can_update_progress_for_authenticated_user()
    {
        Auth::login($this->user);

        $progress = ReadingProgress::updateProgress(
            $this->manga->id,
            $this->chapter->id,
            5
        );

        $this->assertInstanceOf(ReadingProgress::class, $progress);
        $this->assertEquals($this->user->id, $progress->user_id);
        $this->assertEquals($this->manga->id, $progress->manga_id);
        $this->assertEquals($this->chapter->id, $progress->chapter_id);
        $this->assertEquals(5, $progress->page_number);
        $this->assertNotNull($progress->completed_at);
    }

    /** @test */
    public function it_can_update_progress_for_specific_user()
    {
        $progress = ReadingProgress::updateProgress(
            $this->manga->id,
            $this->chapter->id,
            10,
            $this->user->id
        );

        $this->assertInstanceOf(ReadingProgress::class, $progress);
        $this->assertEquals($this->user->id, $progress->user_id);
        $this->assertEquals(10, $progress->page_number);
    }

    /** @test */
    public function it_saves_guest_progress_to_session()
    {
        Session::flush(); // Clear any existing session data

        $result = ReadingProgress::updateProgress(
            $this->manga->id,
            $this->chapter->id,
            7
        );

        $this->assertNull($result); // Should return null for guest users
        
        $sessionProgress = Session::get('reading_progress');
        $this->assertIsArray($sessionProgress);
        $this->assertArrayHasKey($this->manga->id, $sessionProgress);
        $this->assertEquals($this->chapter->id, $sessionProgress[$this->manga->id]['chapter_id']);
        $this->assertEquals(7, $sessionProgress[$this->manga->id]['page_number']);
    }

    /** @test */
    public function it_can_get_progress_for_authenticated_user()
    {
        Auth::login($this->user);
        
        ReadingProgress::factory()->create([
            'user_id' => $this->user->id,
            'manga_id' => $this->manga->id,
            'chapter_id' => $this->chapter->id,
            'page_number' => 15
        ]);

        $progress = ReadingProgress::getProgress($this->manga->id);

        $this->assertInstanceOf(ReadingProgress::class, $progress);
        $this->assertEquals(15, $progress->page_number);
    }

    /** @test */
    public function it_can_get_guest_progress_from_session()
    {
        Session::put('reading_progress', [
            $this->manga->id => [
                'manga_id' => $this->manga->id,
                'chapter_id' => $this->chapter->id,
                'page_number' => 12,
                'completed_at' => now()->toISOString()
            ]
        ]);

        $progress = ReadingProgress::getProgress($this->manga->id);

        $this->assertIsArray($progress);
        $this->assertEquals($this->manga->id, $progress['manga_id']);
        $this->assertEquals(12, $progress['page_number']);
    }

    /** @test */
    public function it_can_mark_chapter_as_completed()
    {
        Auth::login($this->user);

        $progress = ReadingProgress::markChapterCompleted(
            $this->manga->id,
            $this->chapter->id
        );

        $this->assertInstanceOf(ReadingProgress::class, $progress);
        $this->assertEquals($this->chapter->page_count, $progress->page_number);
    }

    /** @test */
    public function it_can_check_if_chapter_is_completed_for_user()
    {
        ReadingProgress::factory()->create([
            'user_id' => $this->user->id,
            'manga_id' => $this->manga->id,
            'chapter_id' => $this->chapter->id,
            'page_number' => $this->chapter->page_count // Last page
        ]);

        $isCompleted = ReadingProgress::isChapterCompleted(
            $this->manga->id,
            $this->chapter->id,
            $this->user->id
        );

        $this->assertTrue($isCompleted);
    }

    /** @test */
    public function it_can_check_if_chapter_is_completed_for_guest()
    {
        Session::put('reading_progress', [
            $this->manga->id => [
                'manga_id' => $this->manga->id,
                'chapter_id' => $this->chapter->id,
                'page_number' => $this->chapter->page_count,
                'completed_at' => now()->toISOString()
            ]
        ]);

        $isCompleted = ReadingProgress::isChapterCompleted(
            $this->manga->id,
            $this->chapter->id
        );

        $this->assertTrue($isCompleted);
    }

    /** @test */
    public function it_can_calculate_progress_percentage()
    {
        $chapter2 = Chapter::factory()->create([
            'manga_id' => $this->manga->id,
            'chapter_number' => 2.0
        ]);

        ReadingProgress::factory()->create([
            'user_id' => $this->user->id,
            'manga_id' => $this->manga->id,
            'chapter_id' => $chapter2->id,
            'page_number' => 10
        ]);

        $percentage = ReadingProgress::getProgressPercentage(
            $this->manga->id,
            $this->user->id
        );

        $this->assertEquals(20.0, $percentage); // Chapter 2 of 10 = 20%
    }

    /** @test */
    public function it_can_migrate_guest_progress_to_user_account()
    {
        // Set up guest progress in session
        Session::put('reading_progress', [
            $this->manga->id => [
                'manga_id' => $this->manga->id,
                'chapter_id' => $this->chapter->id,
                'page_number' => 8,
                'completed_at' => now()->toISOString()
            ]
        ]);

        $migratedCount = ReadingProgress::migrateGuestProgress($this->user->id);

        $this->assertEquals(1, $migratedCount);
        
        // Check that progress was saved to database
        $progress = ReadingProgress::where('user_id', $this->user->id)
            ->where('manga_id', $this->manga->id)
            ->first();
        
        $this->assertNotNull($progress);
        $this->assertEquals(8, $progress->page_number);
        
        // Check that session was cleared
        $this->assertEmpty(Session::get('reading_progress', []));
    }

    /** @test */
    public function it_does_not_migrate_if_user_already_has_progress()
    {
        // Create existing progress for user
        ReadingProgress::factory()->create([
            'user_id' => $this->user->id,
            'manga_id' => $this->manga->id,
            'page_number' => 15
        ]);

        // Set up guest progress in session
        Session::put('reading_progress', [
            $this->manga->id => [
                'manga_id' => $this->manga->id,
                'chapter_id' => $this->chapter->id,
                'page_number' => 8,
                'completed_at' => now()->toISOString()
            ]
        ]);

        $migratedCount = ReadingProgress::migrateGuestProgress($this->user->id);

        $this->assertEquals(0, $migratedCount);
        
        // Check that existing progress wasn't overwritten
        $progress = ReadingProgress::where('user_id', $this->user->id)
            ->where('manga_id', $this->manga->id)
            ->first();
        
        $this->assertEquals(15, $progress->page_number);
    }

    /** @test */
    public function it_can_get_all_progress_for_user()
    {
        $manga2 = Manga::factory()->create();
        $chapter2 = Chapter::factory()->create(['manga_id' => $manga2->id]);

        ReadingProgress::factory()->create([
            'user_id' => $this->user->id,
            'manga_id' => $this->manga->id
        ]);
        
        ReadingProgress::factory()->create([
            'user_id' => $this->user->id,
            'manga_id' => $manga2->id
        ]);

        $allProgress = ReadingProgress::getAllProgress($this->user->id);

        $this->assertCount(2, $allProgress);
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $allProgress);
    }

    /** @test */
    public function it_can_get_all_guest_progress_from_session()
    {
        $manga2 = Manga::factory()->create();
        
        Session::put('reading_progress', [
            $this->manga->id => [
                'manga_id' => $this->manga->id,
                'chapter_id' => $this->chapter->id,
                'page_number' => 5,
                'completed_at' => now()->toISOString()
            ],
            $manga2->id => [
                'manga_id' => $manga2->id,
                'chapter_id' => $this->chapter->id,
                'page_number' => 10,
                'completed_at' => now()->toISOString()
            ]
        ]);

        $allProgress = ReadingProgress::getAllProgress();

        $this->assertIsArray($allProgress);
        $this->assertCount(2, $allProgress);
        $this->assertArrayHasKey($this->manga->id, $allProgress);
        $this->assertArrayHasKey($manga2->id, $allProgress);
    }

    /** @test */
    public function it_can_cleanup_old_progress_records()
    {
        // Create old progress record
        ReadingProgress::factory()->create([
            'user_id' => $this->user->id,
            'updated_at' => now()->subDays(400)
        ]);

        // Create recent progress record
        ReadingProgress::factory()->create([
            'user_id' => $this->user->id,
            'updated_at' => now()->subDays(30)
        ]);

        $deletedCount = ReadingProgress::cleanupOldProgress(365);

        $this->assertEquals(1, $deletedCount);
        $this->assertEquals(1, ReadingProgress::count());
    }
}