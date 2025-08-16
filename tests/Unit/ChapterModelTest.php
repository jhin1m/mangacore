<?php

namespace Tests\Unit;

use Tests\TestCase;
use Ophim\Core\Models\Chapter;
use Ophim\Core\Models\Manga;
use Ophim\Core\Models\Volume;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ChapterModelTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_a_chapter()
    {
        $manga = Manga::factory()->create();
        
        $chapter = Chapter::create([
            'manga_id' => $manga->id,
            'title' => 'Test Chapter',
            'chapter_number' => 1.0,
            'page_count' => 20,
            'published_at' => now(),
        ]);

        $this->assertInstanceOf(Chapter::class, $chapter);
        $this->assertEquals('Test Chapter', $chapter->title);
        $this->assertEquals(1.0, $chapter->chapter_number);
        $this->assertEquals(20, $chapter->page_count);
    }

    /** @test */
    public function it_belongs_to_a_manga()
    {
        $manga = Manga::factory()->create();
        $chapter = Chapter::factory()->create(['manga_id' => $manga->id]);

        $this->assertInstanceOf(Manga::class, $chapter->manga);
        $this->assertEquals($manga->id, $chapter->manga->id);
    }

    /** @test */
    public function it_can_belong_to_a_volume()
    {
        $volume = Volume::factory()->create();
        $chapter = Chapter::factory()->create(['volume_id' => $volume->id]);

        $this->assertInstanceOf(Volume::class, $chapter->volume);
        $this->assertEquals($volume->id, $chapter->volume->id);
    }

    /** @test */
    public function it_supports_fractional_chapter_numbers()
    {
        $chapter = Chapter::factory()->create(['chapter_number' => 4.5]);

        $this->assertEquals(4.5, $chapter->chapter_number);
        $this->assertEquals('4.5', $chapter->formatted_chapter_number);
    }

    /** @test */
    public function it_generates_url_correctly()
    {
        $manga = Manga::factory()->create(['slug' => 'test-manga']);
        $chapter = Chapter::factory()->create([
            'manga_id' => $manga->id,
            'slug' => 'test-chapter',
            'chapter_number' => 1.0
        ]);

        $url = $chapter->getUrl();
        
        $this->assertStringContainsString('test-manga', $url);
        $this->assertStringContainsString('test-chapter', $url);
    }

    /** @test */
    public function it_can_get_next_chapter()
    {
        $manga = Manga::factory()->create();
        $chapter1 = Chapter::factory()->create([
            'manga_id' => $manga->id,
            'chapter_number' => 1.0,
            'published_at' => now()->subDay()
        ]);
        $chapter2 = Chapter::factory()->create([
            'manga_id' => $manga->id,
            'chapter_number' => 2.0,
            'published_at' => now()->subDay()
        ]);

        $nextChapter = $chapter1->getNextChapter();
        
        $this->assertInstanceOf(Chapter::class, $nextChapter);
        $this->assertEquals($chapter2->id, $nextChapter->id);
    }

    /** @test */
    public function it_can_get_previous_chapter()
    {
        $manga = Manga::factory()->create();
        $chapter1 = Chapter::factory()->create([
            'manga_id' => $manga->id,
            'chapter_number' => 1.0,
            'published_at' => now()->subDay()
        ]);
        $chapter2 = Chapter::factory()->create([
            'manga_id' => $manga->id,
            'chapter_number' => 2.0,
            'published_at' => now()->subDay()
        ]);

        $prevChapter = $chapter2->getPreviousChapter();
        
        $this->assertInstanceOf(Chapter::class, $prevChapter);
        $this->assertEquals($chapter1->id, $prevChapter->id);
    }

    /** @test */
    public function it_can_check_if_published()
    {
        $publishedChapter = Chapter::factory()->create(['published_at' => now()->subDay()]);
        $unpublishedChapter = Chapter::factory()->create(['published_at' => now()->addDay()]);

        $this->assertTrue($publishedChapter->is_published);
        $this->assertFalse($unpublishedChapter->is_published);
    }

    /** @test */
    public function it_auto_generates_slug_from_title()
    {
        $chapter = new Chapter();
        $chapter->title = 'Test Chapter Title';
        
        $this->assertEquals('test-chapter-title', $chapter->slug);
    }

    /** @test */
    public function it_formats_chapter_number_correctly()
    {
        $chapter = new Chapter();
        $chapter->chapter_number = 4.5;
        
        $this->assertEquals('4.5', $chapter->chapter_number);
    }

    /** @test */
    public function it_can_scope_published_chapters()
    {
        $manga = Manga::factory()->create();
        Chapter::factory()->create([
            'manga_id' => $manga->id,
            'published_at' => now()->subDay()
        ]);
        Chapter::factory()->create([
            'manga_id' => $manga->id,
            'published_at' => now()->addDay()
        ]);

        $publishedChapters = Chapter::published()->get();
        
        $this->assertCount(1, $publishedChapters);
    }

    /** @test */
    public function it_can_scope_premium_chapters()
    {
        Chapter::factory()->premium()->create();
        Chapter::factory()->free()->create();

        $premiumChapters = Chapter::premium()->get();
        $freeChapters = Chapter::free()->get();
        
        $this->assertCount(1, $premiumChapters);
        $this->assertCount(1, $freeChapters);
    }

    /** @test */
    public function it_can_order_by_chapter_number()
    {
        $manga = Manga::factory()->create();
        $chapter3 = Chapter::factory()->create(['manga_id' => $manga->id, 'chapter_number' => 3.0]);
        $chapter1 = Chapter::factory()->create(['manga_id' => $manga->id, 'chapter_number' => 1.0]);
        $chapter2 = Chapter::factory()->create(['manga_id' => $manga->id, 'chapter_number' => 2.0]);

        $orderedChapters = Chapter::orderByChapter()->get();
        
        $this->assertEquals(1.0, $orderedChapters->first()->chapter_number);
        $this->assertEquals(3.0, $orderedChapters->last()->chapter_number);
    }
}