<?php

namespace Tests\Unit;

use Tests\TestCase;
use Ophim\Core\Models\Manga;
use Ophim\Core\Models\Author;
use Ophim\Core\Models\Artist;
use Ophim\Core\Models\Publisher;
use Ophim\Core\Models\Category;
use Ophim\Core\Models\Tag;
use Ophim\Core\Models\Origin;
use Ophim\Core\Models\Chapter;
use Ophim\Core\Models\Volume;
use Ophim\Core\Models\ReadingProgress;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MangaModelTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_a_manga()
    {
        $manga = Manga::create([
            'title' => 'Test Manga',
            'slug' => 'test-manga',
            'original_title' => 'テストマンガ',
            'description' => 'A test manga description',
            'type' => 'manga',
            'status' => 'ongoing',
            'demographic' => 'shounen',
            'reading_direction' => 'rtl',
            'publication_year' => 2023,
            'total_chapters' => 100,
            'total_volumes' => 10,
            'rating' => 8.5,
        ]);

        $this->assertInstanceOf(Manga::class, $manga);
        $this->assertEquals('Test Manga', $manga->title);
        $this->assertEquals('test-manga', $manga->slug);
        $this->assertEquals('テストマンガ', $manga->original_title);
        $this->assertEquals('manga', $manga->type);
        $this->assertEquals('ongoing', $manga->status);
        $this->assertEquals('shounen', $manga->demographic);
        $this->assertEquals('rtl', $manga->reading_direction);
        $this->assertEquals(2023, $manga->publication_year);
        $this->assertEquals(100, $manga->total_chapters);
        $this->assertEquals(10, $manga->total_volumes);
        $this->assertEquals(8.5, $manga->rating);
    }

    /** @test */
    public function it_has_many_chapters()
    {
        $manga = Manga::factory()->create();
        $chapter1 = Chapter::factory()->create(['manga_id' => $manga->id, 'chapter_number' => 1.0]);
        $chapter2 = Chapter::factory()->create(['manga_id' => $manga->id, 'chapter_number' => 2.0]);

        $this->assertCount(2, $manga->chapters);
        $this->assertTrue($manga->chapters->contains($chapter1));
        $this->assertTrue($manga->chapters->contains($chapter2));
    }

    /** @test */
    public function it_belongs_to_many_authors()
    {
        $manga = Manga::factory()->create();
        $author1 = Author::factory()->create();
        $author2 = Author::factory()->create();

        $manga->authors()->attach([$author1->id, $author2->id]);

        $this->assertCount(2, $manga->authors);
        $this->assertTrue($manga->authors->contains($author1));
        $this->assertTrue($manga->authors->contains($author2));
    }

    /** @test */
    public function it_belongs_to_many_artists()
    {
        $manga = Manga::factory()->create();
        $artist1 = Artist::factory()->create();
        $artist2 = Artist::factory()->create();

        $manga->artists()->attach([$artist1->id, $artist2->id]);

        $this->assertCount(2, $manga->artists);
        $this->assertTrue($manga->artists->contains($artist1));
        $this->assertTrue($manga->artists->contains($artist2));
    }

    /** @test */
    public function it_belongs_to_many_publishers()
    {
        $manga = Manga::factory()->create();
        $publisher1 = Publisher::factory()->create();
        $publisher2 = Publisher::factory()->create();

        $manga->publishers()->attach([$publisher1->id, $publisher2->id]);

        $this->assertCount(2, $manga->publishers);
        $this->assertTrue($manga->publishers->contains($publisher1));
        $this->assertTrue($manga->publishers->contains($publisher2));
    }

    /** @test */
    public function it_belongs_to_many_categories()
    {
        $manga = Manga::factory()->create();
        $category1 = Category::factory()->create();
        $category2 = Category::factory()->create();

        $manga->categories()->attach([$category1->id, $category2->id]);

        $this->assertCount(2, $manga->categories);
        $this->assertTrue($manga->categories->contains($category1));
        $this->assertTrue($manga->categories->contains($category2));
    }

    /** @test */
    public function it_belongs_to_many_tags()
    {
        $manga = Manga::factory()->create();
        $tag1 = Tag::factory()->create();
        $tag2 = Tag::factory()->create();

        $manga->tags()->attach([$tag1->id, $tag2->id]);

        $this->assertCount(2, $manga->tags);
        $this->assertTrue($manga->tags->contains($tag1));
        $this->assertTrue($manga->tags->contains($tag2));
    }

    /** @test */
    public function it_belongs_to_many_origins()
    {
        $manga = Manga::factory()->create();
        $origin1 = Origin::factory()->create();
        $origin2 = Origin::factory()->create();

        $manga->origins()->attach([$origin1->id, $origin2->id]);

        $this->assertCount(2, $manga->origins);
        $this->assertTrue($manga->origins->contains($origin1));
        $this->assertTrue($manga->origins->contains($origin2));
    }

    /** @test */
    public function it_has_many_volumes()
    {
        $manga = Manga::factory()->create();
        $volume1 = Volume::factory()->create(['manga_id' => $manga->id, 'volume_number' => 1]);
        $volume2 = Volume::factory()->create(['manga_id' => $manga->id, 'volume_number' => 2]);

        $this->assertCount(2, $manga->volumes);
        $this->assertTrue($manga->volumes->contains($volume1));
        $this->assertTrue($manga->volumes->contains($volume2));
    }

    /** @test */
    public function it_has_many_reading_progress()
    {
        $manga = Manga::factory()->create();
        $progress1 = ReadingProgress::factory()->create(['manga_id' => $manga->id]);
        $progress2 = ReadingProgress::factory()->create(['manga_id' => $manga->id]);

        $this->assertCount(2, $manga->readingProgress);
        $this->assertTrue($manga->readingProgress->contains($progress1));
        $this->assertTrue($manga->readingProgress->contains($progress2));
    }

    /** @test */
    public function it_generates_url_correctly()
    {
        $manga = Manga::factory()->create(['slug' => 'test-manga']);

        $url = $manga->getUrl();
        
        $this->assertStringContainsString('test-manga', $url);
    }

    /** @test */
    public function it_gets_cover_url()
    {
        $manga = Manga::factory()->create(['cover_image' => '/storage/covers/test.jpg']);

        $coverUrl = $manga->getCoverUrl();
        
        $this->assertEquals('/storage/covers/test.jpg', $coverUrl);
    }

    /** @test */
    public function it_gets_banner_url()
    {
        $manga = Manga::factory()->create([
            'cover_image' => '/storage/covers/test.jpg',
            'banner_image' => '/storage/banners/test.jpg'
        ]);

        $bannerUrl = $manga->getBannerUrl();
        
        $this->assertEquals('/storage/banners/test.jpg', $bannerUrl);
    }

    /** @test */
    public function it_falls_back_to_cover_when_no_banner()
    {
        $manga = Manga::factory()->create([
            'cover_image' => '/storage/covers/test.jpg',
            'banner_image' => null
        ]);

        $bannerUrl = $manga->getBannerUrl();
        
        $this->assertEquals('/storage/covers/test.jpg', $bannerUrl);
    }

    /** @test */
    public function it_formats_rating_correctly()
    {
        $manga = Manga::factory()->create(['rating' => 8.567]);

        $rating = $manga->getRating();
        
        $this->assertEquals('8.6', $rating);
    }

    /** @test */
    public function it_provides_default_rating_when_zero()
    {
        $manga = Manga::factory()->create(['rating' => 0]);

        $rating = $manga->getRating();
        
        $this->assertEquals('8.0', $rating);
    }

    /** @test */
    public function it_scopes_completed_manga()
    {
        Manga::factory()->create(['status' => 'completed']);
        Manga::factory()->create(['status' => 'ongoing']);
        Manga::factory()->create(['status' => 'completed']);

        $completedManga = Manga::completed()->get();
        
        $this->assertCount(2, $completedManga);
        $completedManga->each(function ($manga) {
            $this->assertEquals('completed', $manga->status);
        });
    }

    /** @test */
    public function it_scopes_ongoing_manga()
    {
        Manga::factory()->create(['status' => 'completed']);
        Manga::factory()->create(['status' => 'ongoing']);
        Manga::factory()->create(['status' => 'ongoing']);

        $ongoingManga = Manga::ongoing()->get();
        
        $this->assertCount(2, $ongoingManga);
        $ongoingManga->each(function ($manga) {
            $this->assertEquals('ongoing', $manga->status);
        });
    }

    /** @test */
    public function it_scopes_by_type()
    {
        Manga::factory()->create(['type' => 'manga']);
        Manga::factory()->create(['type' => 'manhwa']);
        Manga::factory()->create(['type' => 'manga']);

        $mangaType = Manga::byType('manga')->get();
        
        $this->assertCount(2, $mangaType);
        $mangaType->each(function ($manga) {
            $this->assertEquals('manga', $manga->type);
        });
    }

    /** @test */
    public function it_scopes_by_demographic()
    {
        Manga::factory()->create(['demographic' => 'shounen']);
        Manga::factory()->create(['demographic' => 'seinen']);
        Manga::factory()->create(['demographic' => 'shounen']);

        $shounenManga = Manga::byDemographic('shounen')->get();
        
        $this->assertCount(2, $shounenManga);
        $shounenManga->each(function ($manga) {
            $this->assertEquals('shounen', $manga->demographic);
        });
    }

    /** @test */
    public function it_scopes_recommended_manga()
    {
        Manga::factory()->create(['is_recommended' => true]);
        Manga::factory()->create(['is_recommended' => false]);
        Manga::factory()->create(['is_recommended' => true]);

        $recommendedManga = Manga::recommended()->get();
        
        $this->assertCount(2, $recommendedManga);
        $recommendedManga->each(function ($manga) {
            $this->assertTrue($manga->is_recommended);
        });
    }

    /** @test */
    public function it_gets_localized_status()
    {
        $manga = Manga::factory()->create(['status' => 'ongoing']);

        $status = $manga->getStatus();
        
        $this->assertNotEmpty($status);
    }

    /** @test */
    public function it_gets_localized_type()
    {
        $manga = Manga::factory()->create(['type' => 'manga']);

        $type = $manga->getType();
        
        $this->assertNotEmpty($type);
    }

    /** @test */
    public function it_gets_localized_demographic()
    {
        $manga = Manga::factory()->create(['demographic' => 'shounen']);

        $demographic = $manga->getDemographic();
        
        $this->assertNotEmpty($demographic);
    }

    /** @test */
    public function it_gets_localized_reading_direction()
    {
        $manga = Manga::factory()->create(['reading_direction' => 'rtl']);

        $direction = $manga->getReadingDirection();
        
        $this->assertNotEmpty($direction);
    }

    /** @test */
    public function it_handles_other_name_as_array()
    {
        $manga = new Manga();
        $manga->other_name = ['Name 1', 'Name 2', 'Name 3'];

        $this->assertEquals('Name 1, Name 2, Name 3', $manga->attributes['other_name']);
    }

    /** @test */
    public function it_returns_other_name_as_array()
    {
        $manga = Manga::factory()->create(['other_name' => 'Name 1, Name 2, Name 3']);

        $otherNames = $manga->other_name;
        
        $this->assertIsArray($otherNames);
        $this->assertEquals(['Name 1', 'Name 2', 'Name 3'], $otherNames);
    }

    /** @test */
    public function it_returns_empty_array_for_null_other_name()
    {
        $manga = Manga::factory()->create(['other_name' => null]);

        $otherNames = $manga->other_name;
        
        $this->assertIsArray($otherNames);
        $this->assertEmpty($otherNames);
    }

    /** @test */
    public function it_validates_type_constants()
    {
        $expectedTypes = ['manga', 'manhwa', 'manhua', 'webtoon'];
        
        $this->assertEquals($expectedTypes, Manga::TYPES);
    }

    /** @test */
    public function it_validates_status_constants()
    {
        $expectedStatuses = ['ongoing', 'completed', 'hiatus', 'cancelled'];
        
        $this->assertEquals($expectedStatuses, Manga::STATUSES);
    }

    /** @test */
    public function it_validates_demographic_constants()
    {
        $expectedDemographics = ['shounen', 'seinen', 'josei', 'shoujo', 'kodomomuke', 'general'];
        
        $this->assertEquals($expectedDemographics, Manga::DEMOGRAPHICS);
    }

    /** @test */
    public function it_validates_reading_direction_constants()
    {
        $expectedDirections = ['ltr', 'rtl', 'vertical'];
        
        $this->assertEquals($expectedDirections, Manga::READING_DIRECTIONS);
    }

    /** @test */
    public function it_casts_attributes_correctly()
    {
        $manga = Manga::factory()->create([
            'publication_year' => '2023',
            'total_chapters' => '100',
            'total_volumes' => '10',
            'rating' => '8.50',
            'view_count' => '1000',
            'is_completed' => '1',
            'is_recommended' => '0',
            'is_adult_content' => '1'
        ]);

        $this->assertIsInt($manga->publication_year);
        $this->assertIsInt($manga->total_chapters);
        $this->assertIsInt($manga->total_volumes);
        $this->assertIsFloat($manga->rating);
        $this->assertIsInt($manga->view_count);
        $this->assertIsBool($manga->is_completed);
        $this->assertIsBool($manga->is_recommended);
        $this->assertIsBool($manga->is_adult_content);
    }
}