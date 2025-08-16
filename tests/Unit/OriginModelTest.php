<?php

namespace Tests\Unit;

use Tests\TestCase;
use Ophim\Core\Models\Origin;
use Ophim\Core\Models\Manga;
use Illuminate\Foundation\Testing\RefreshDatabase;

class OriginModelTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_an_origin()
    {
        $origin = Origin::create([
            'name' => 'Test Origin',
            'slug' => 'test-origin',
            'description' => 'A test origin description'
        ]);

        $this->assertInstanceOf(Origin::class, $origin);
        $this->assertEquals('Test Origin', $origin->name);
        $this->assertEquals('test-origin', $origin->slug);
        $this->assertEquals('A test origin description', $origin->description);
    }

    /** @test */
    public function it_belongs_to_many_manga()
    {
        $origin = Origin::factory()->create();
        $manga1 = Manga::factory()->create();
        $manga2 = Manga::factory()->create();

        $origin->manga()->attach([$manga1->id, $manga2->id]);

        $this->assertCount(2, $origin->manga);
        $this->assertTrue($origin->manga->contains($manga1));
        $this->assertTrue($origin->manga->contains($manga2));
    }

    /** @test */
    public function it_generates_url_correctly()
    {
        $origin = Origin::factory()->create(['slug' => 'test-origin']);

        $url = $origin->getUrl();
        
        $this->assertStringContainsString('test-origin', $url);
    }

    /** @test */
    public function it_can_generate_url_without_domain()
    {
        $origin = Origin::factory()->create(['slug' => 'test-origin']);

        $url = $origin->getUrl(false);
        
        $this->assertStringContainsString('test-origin', $url);
        $this->assertStringNotContainsString('http', $url);
    }

    /** @test */
    public function it_uses_slug_as_primary_cache_key()
    {
        $cacheKey = Origin::primaryCacheKey();
        
        $this->assertEquals('slug', $cacheKey);
    }

    /** @test */
    public function it_implements_required_interfaces()
    {
        $origin = new Origin();
        
        $this->assertInstanceOf(\Ophim\Core\Contracts\TaxonomyInterface::class, $origin);
        $this->assertInstanceOf(\Hacoidev\CachingModel\Contracts\Cacheable::class, $origin);
        $this->assertInstanceOf(\Ophim\Core\Contracts\SeoInterface::class, $origin);
    }

    /** @test */
    public function it_uses_required_traits()
    {
        $origin = new Origin();
        $traits = class_uses_recursive(get_class($origin));
        
        $this->assertContains(\Backpack\CRUD\app\Models\Traits\CrudTrait::class, $traits);
        $this->assertContains(\Ophim\Core\Traits\ActorLog::class, $traits);
        $this->assertContains(\Ophim\Core\Traits\Sluggable::class, $traits);
        $this->assertContains(\Ophim\Core\Traits\HasFactory::class, $traits);
        $this->assertContains(\Hacoidev\CachingModel\HasCache::class, $traits);
        $this->assertContains(\Ophim\Core\Traits\HasTitle::class, $traits);
        $this->assertContains(\Ophim\Core\Traits\HasDescription::class, $traits);
        $this->assertContains(\Ophim\Core\Traits\HasKeywords::class, $traits);
    }

    /** @test */
    public function it_has_correct_table_name()
    {
        $origin = new Origin();
        
        $this->assertEquals('origins', $origin->getTable());
    }

    /** @test */
    public function it_guards_id_field()
    {
        $origin = new Origin();
        
        $this->assertContains('id', $origin->getGuarded());
    }

    /** @test */
    public function it_can_generate_seo_tags()
    {
        $origin = Origin::factory()->create([
            'name' => 'Test Origin',
            'description' => 'Test origin description'
        ]);

        // This should not throw an exception
        $origin->generateSeoTags();
        
        $this->assertTrue(true); // If we get here, the method executed successfully
    }

    /** @test */
    public function it_handles_empty_manga_collection_in_seo()
    {
        $origin = Origin::factory()->create();

        // This should not throw an exception even with no manga
        $origin->generateSeoTags();
        
        $this->assertTrue(true);
    }

    /** @test */
    public function it_can_get_title_pattern()
    {
        $origin = new Origin();
        
        // Use reflection to access protected method
        $reflection = new \ReflectionClass($origin);
        $method = $reflection->getMethod('titlePattern');
        $method->setAccessible(true);
        
        $pattern = $method->invoke($origin);
        
        $this->assertIsString($pattern);
    }

    /** @test */
    public function it_can_get_description_pattern()
    {
        $origin = new Origin();
        
        // Use reflection to access protected method
        $reflection = new \ReflectionClass($origin);
        $method = $reflection->getMethod('descriptionPattern');
        $method->setAccessible(true);
        
        $pattern = $method->invoke($origin);
        
        $this->assertIsString($pattern);
    }

    /** @test */
    public function it_can_get_keywords_pattern()
    {
        $origin = new Origin();
        
        // Use reflection to access protected method
        $reflection = new \ReflectionClass($origin);
        $method = $reflection->getMethod('keywordsPattern');
        $method->setAccessible(true);
        
        $pattern = $method->invoke($origin);
        
        $this->assertIsString($pattern);
    }
}