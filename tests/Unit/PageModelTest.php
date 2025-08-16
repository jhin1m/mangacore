<?php

namespace Tests\Unit;

use Tests\TestCase;
use Ophim\Core\Models\Page;
use Ophim\Core\Models\Chapter;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PageModelTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_a_page()
    {
        $chapter = Chapter::factory()->create();
        
        $page = Page::factory()->create([
            'chapter_id' => $chapter->id,
            'page_number' => 1,
            'image_url' => '/storage/manga/test.jpg'
        ]);

        $this->assertInstanceOf(Page::class, $page);
        $this->assertEquals(1, $page->page_number);
        $this->assertEquals('/storage/manga/test.jpg', $page->image_url);
        $this->assertEquals($chapter->id, $page->chapter_id);
    }

    /** @test */
    public function it_belongs_to_a_chapter()
    {
        $chapter = Chapter::factory()->create();
        $page = Page::factory()->create(['chapter_id' => $chapter->id]);

        $this->assertInstanceOf(Chapter::class, $page->chapter);
        $this->assertEquals($chapter->id, $page->chapter->id);
    }

    /** @test */
    public function it_can_get_optimized_url()
    {
        $page = Page::factory()->create([
            'image_url' => '/storage/manga/test.jpg'
        ]);

        $optimizedUrl = $page->getOptimizedUrl('medium');
        
        // Should return original URL when proxy is disabled
        $this->assertEquals('/storage/manga/test.jpg', $optimizedUrl);
    }

    /** @test */
    public function it_can_get_thumbnail_url()
    {
        $page = Page::factory()->create([
            'image_url' => '/storage/manga/test.jpg'
        ]);

        $thumbnailUrl = $page->getThumbnailUrl();
        
        // Should return optimized URL with low quality
        $this->assertNotEmpty($thumbnailUrl);
    }

    /** @test */
    public function it_can_get_webp_url()
    {
        $page = Page::factory()->create([
            'image_url' => '/storage/manga/test.jpg'
        ]);

        $webpUrl = $page->getWebPUrl();
        
        // Should fallback to original when WebP doesn't exist
        $this->assertEquals('/storage/manga/test.jpg', $webpUrl);
    }

    /** @test */
    public function it_formats_page_number_with_padding()
    {
        $page = Page::factory()->create(['page_number' => 5]);

        $this->assertEquals('Page 05', $page->formatted_page_number);
    }

    /** @test */
    public function it_can_check_if_first_page()
    {
        $chapter = Chapter::factory()->create(['page_count' => 10]);
        $firstPage = Page::factory()->create([
            'chapter_id' => $chapter->id,
            'page_number' => 1
        ]);
        $secondPage = Page::factory()->create([
            'chapter_id' => $chapter->id,
            'page_number' => 2
        ]);

        $this->assertTrue($firstPage->is_first_page);
        $this->assertFalse($secondPage->is_first_page);
    }

    /** @test */
    public function it_can_check_if_last_page()
    {
        $chapter = Chapter::factory()->create(['page_count' => 10]);
        $lastPage = Page::factory()->create([
            'chapter_id' => $chapter->id,
            'page_number' => 10
        ]);
        $secondToLastPage = Page::factory()->create([
            'chapter_id' => $chapter->id,
            'page_number' => 9
        ]);

        $this->assertTrue($lastPage->is_last_page);
        $this->assertFalse($secondToLastPage->is_last_page);
    }

    /** @test */
    public function it_validates_image_url_format()
    {
        $validPage = Page::factory()->make([
            'image_url' => '/storage/manga/test.jpg'
        ]);
        $invalidPage = Page::factory()->make([
            'image_url' => 'invalid-url'
        ]);

        $this->assertTrue($validPage->validateImageUrl());
        $this->assertFalse($invalidPage->validateImageUrl());
    }

    /** @test */
    public function it_validates_page_numbering()
    {
        $chapter = Chapter::factory()->create();
        $page1 = Page::factory()->create([
            'chapter_id' => $chapter->id,
            'page_number' => 1
        ]);

        $page2 = Page::factory()->make([
            'chapter_id' => $chapter->id,
            'page_number' => 1
        ]);

        $this->assertTrue($page1->validatePageNumbering());
        $this->assertFalse($page2->validatePageNumbering());
    }

    /** @test */
    public function it_ensures_positive_page_number()
    {
        $page = Page::factory()->make();
        $page->page_number = -1;

        $this->assertEquals(1, $page->page_number);
    }

    /** @test */
    public function it_cleans_image_url()
    {
        $page = Page::factory()->make();
        $page->image_url = '  /storage/manga/test.jpg  ';

        $this->assertEquals('/storage/manga/test.jpg', $page->image_url);
    }

    /** @test */
    public function it_can_scope_by_page_range()
    {
        $chapter = Chapter::factory()->create();
        Page::factory()->create(['chapter_id' => $chapter->id, 'page_number' => 1]);
        Page::factory()->create(['chapter_id' => $chapter->id, 'page_number' => 5]);
        Page::factory()->create(['chapter_id' => $chapter->id, 'page_number' => 10]);

        $pagesInRange = Page::where('chapter_id', $chapter->id)
            ->inRange(1, 5)
            ->get();

        $this->assertCount(2, $pagesInRange);
    }

    /** @test */
    public function it_can_order_by_page_number()
    {
        $chapter = Chapter::factory()->create();
        Page::factory()->create(['chapter_id' => $chapter->id, 'page_number' => 3]);
        Page::factory()->create(['chapter_id' => $chapter->id, 'page_number' => 1]);
        Page::factory()->create(['chapter_id' => $chapter->id, 'page_number' => 2]);

        $orderedPages = Page::where('chapter_id', $chapter->id)
            ->orderByPage()
            ->get();

        $this->assertEquals(1, $orderedPages->first()->page_number);
        $this->assertEquals(3, $orderedPages->last()->page_number);
    }
}