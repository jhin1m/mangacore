<?php

namespace Tests\Unit;

use Tests\TestCase;
use Ophim\Core\Models\Volume;
use Ophim\Core\Models\Manga;
use Ophim\Core\Models\Chapter;
use Illuminate\Foundation\Testing\RefreshDatabase;

class VolumeModelTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_a_volume()
    {
        $manga = Manga::factory()->create();
        
        $volume = Volume::factory()->create([
            'manga_id' => $manga->id,
            'volume_number' => 1,
            'title' => 'Test Volume'
        ]);

        $this->assertInstanceOf(Volume::class, $volume);
        $this->assertEquals(1, $volume->volume_number);
        $this->assertEquals('Test Volume', $volume->title);
        $this->assertEquals($manga->id, $volume->manga_id);
    }

    /** @test */
    public function it_belongs_to_a_manga()
    {
        $manga = Manga::factory()->create();
        $volume = Volume::factory()->create(['manga_id' => $manga->id]);

        $this->assertInstanceOf(Manga::class, $volume->manga);
        $this->assertEquals($manga->id, $volume->manga->id);
    }

    /** @test */
    public function it_has_many_chapters()
    {
        $volume = Volume::factory()->create();
        $chapters = Chapter::factory()->count(3)->create(['volume_id' => $volume->id]);

        $this->assertCount(3, $volume->chapters);
        $this->assertInstanceOf(Chapter::class, $volume->chapters->first());
    }

    /** @test */
    public function it_can_get_formatted_title()
    {
        $volume = Volume::factory()->create([
            'volume_number' => 1,
            'title' => 'The Beginning'
        ]);

        $this->assertEquals('Volume 1: The Beginning', $volume->getFormattedTitle());
    }

    /** @test */
    public function it_can_get_formatted_title_without_title()
    {
        $volume = Volume::factory()->create([
            'volume_number' => 2,
            'title' => null
        ]);

        $this->assertEquals('Volume 2', $volume->getFormattedTitle());
    }

    /** @test */
    public function it_can_check_if_published()
    {
        $publishedVolume = Volume::factory()->published()->create();
        $upcomingVolume = Volume::factory()->upcoming()->create();

        $this->assertTrue($publishedVolume->isPublished());
        $this->assertFalse($upcomingVolume->isPublished());
    }

    /** @test */
    public function it_can_update_chapter_count()
    {
        $volume = Volume::factory()->create(['chapter_count' => 0]);
        Chapter::factory()->count(5)->create(['volume_id' => $volume->id]);

        $count = $volume->updateChapterCount();

        $this->assertEquals(5, $count);
        $this->assertEquals(5, $volume->fresh()->chapter_count);
    }

    /** @test */
    public function it_validates_volume_numbering()
    {
        $manga = Manga::factory()->create();
        $volume1 = Volume::factory()->create([
            'manga_id' => $manga->id,
            'volume_number' => 1
        ]);

        $volume2 = Volume::factory()->make([
            'manga_id' => $manga->id,
            'volume_number' => 1
        ]);

        $this->assertTrue($volume1->validateVolumeNumbering());
        $this->assertFalse($volume2->validateVolumeNumbering());
    }

    /** @test */
    public function it_can_get_total_pages()
    {
        $volume = Volume::factory()->create();
        Chapter::factory()->create(['volume_id' => $volume->id, 'page_count' => 20]);
        Chapter::factory()->create(['volume_id' => $volume->id, 'page_count' => 25]);

        $totalPages = $volume->getTotalPages();

        $this->assertEquals(45, $totalPages);
    }

    /** @test */
    public function it_formats_volume_number_with_padding()
    {
        $volume = Volume::factory()->create(['volume_number' => 5]);

        $this->assertEquals('05', $volume->formatted_volume_number);
    }

    /** @test */
    public function it_ensures_positive_volume_number()
    {
        $volume = Volume::factory()->make();
        $volume->volume_number = -1;

        $this->assertEquals(1, $volume->volume_number);
    }
}