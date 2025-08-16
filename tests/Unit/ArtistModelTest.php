<?php

namespace Tests\Unit;

use Tests\TestCase;
use Ophim\Core\Models\Artist;
use Ophim\Core\Models\Manga;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ArtistModelTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_an_artist()
    {
        $artist = Artist::create([
            'name' => 'Test Artist',
            'slug' => 'test-artist',
            'description' => 'A test artist description'
        ]);

        $this->assertInstanceOf(Artist::class, $artist);
        $this->assertEquals('Test Artist', $artist->name);
        $this->assertEquals('test-artist', $artist->slug);
        $this->assertEquals('A test artist description', $artist->description);
    }

    /** @test */
    public function it_belongs_to_many_manga()
    {
        $artist = Artist::factory()->create();
        $manga1 = Manga::factory()->create();
        $manga2 = Manga::factory()->create();

        $artist->manga()->attach([$manga1->id, $manga2->id]);

        $this->assertCount(2, $artist->manga);
        $this->assertTrue($artist->manga->contains($manga1));
        $this->assertTrue($artist->manga->contains($manga2));
    }

    /** @test */
    public function it_generates_url_correctly()
    {
        $artist = Artist::factory()->create(['slug' => 'test-artist']);

        $url = $artist->getUrl();
        
        $this->assertStringContainsString('test-artist', $url);
    }

    /** @test */
    public function it_uses_slug_as_primary_cache_key()
    {
        $cacheKey = Artist::primaryCacheKey();
        
        $this->assertEquals('slug', $cacheKey);
    }

    /** @test */
    public function it_implements_required_interfaces()
    {
        $artist = new Artist();
        
        $this->assertInstanceOf(\Ophim\Core\Contracts\TaxonomyInterface::class, $artist);
        $this->assertInstanceOf(\Hacoidev\CachingModel\Contracts\Cacheable::class, $artist);
        $this->assertInstanceOf(\Ophim\Core\Contracts\SeoInterface::class, $artist);
    }

    /** @test */
    public function it_uses_required_traits()
    {
        $artist = new Artist();
        $traits = class_uses_recursive(get_class($artist));
        
        $this->assertContains(\Backpack\CRUD\app\Models\Traits\CrudTrait::class, $traits);
        $this->assertContains(\Ophim\Core\Traits\Sluggable::class, $traits);
        $this->assertContains(\Ophim\Core\Traits\HasUniqueName::class, $traits);
        $this->assertContains(\Ophim\Core\Traits\HasFactory::class, $traits);
        $this->assertContains(\Hacoidev\CachingModel\HasCache::class, $traits);
        $this->assertContains(\Ophim\Core\Traits\HasTitle::class, $traits);
        $this->assertContains(\Ophim\Core\Traits\HasDescription::class, $traits);
        $this->assertContains(\Ophim\Core\Traits\HasKeywords::class, $traits);
    }

    /** @test */
    public function it_has_correct_table_name()
    {
        $artist = new Artist();
        
        $this->assertEquals('artists', $artist->getTable());
    }

    /** @test */
    public function it_guards_id_field()
    {
        $artist = new Artist();
        
        $this->assertContains('id', $artist->getGuarded());
    }

    /** @test */
    public function it_can_generate_seo_tags()
    {
        $artist = Artist::factory()->create([
            'name' => 'Test Artist',
            'description' => 'Test artist description'
        ]);

        // This should not throw an exception
        $artist->generateSeoTags();
        
        $this->assertTrue(true); // If we get here, the method executed successfully
    }

    /** @test */
    public function it_handles_empty_manga_collection_in_seo()
    {
        $artist = Artist::factory()->create();

        // This should not throw an exception even with no manga
        $artist->generateSeoTags();
        
        $this->assertTrue(true);
    }

    /** @test */
    public function it_can_get_title_pattern()
    {
        $artist = new Artist();
        
        // Use reflection to access protected method
        $reflection = new \ReflectionClass($artist);
        $method = $reflection->getMethod('titlePattern');
        $method->setAccessible(true);
        
        $pattern = $method->invoke($artist);
        
        $this->assertIsString($pattern);
    }

    /** @test */
    public function it_can_get_description_pattern()
    {
        $artist = new Artist();
        
        // Use reflection to access protected method
        $reflection = new \ReflectionClass($artist);
        $method = $reflection->getMethod('descriptionPattern');
        $method->setAccessible(true);
        
        $pattern = $method->invoke($artist);
        
        $this->assertIsString($pattern);
    }

    /** @test */
    public function it_can_get_keywords_pattern()
    {
        $artist = new Artist();
        
        // Use reflection to access protected method
        $reflection = new \ReflectionClass($artist);
        $method = $reflection->getMethod('keywordsPattern');
        $method->setAccessible(true);
        
        $pattern = $method->invoke($artist);
        
        $this->assertIsString($pattern);
    }
}