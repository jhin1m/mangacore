<?php

namespace Tests\Unit;

use Tests\TestCase;
use Ophim\Core\Models\Publisher;
use Ophim\Core\Models\Manga;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PublisherModelTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_a_publisher()
    {
        $publisher = Publisher::create([
            'name' => 'Test Publisher',
            'slug' => 'test-publisher',
            'description' => 'A test publisher description'
        ]);

        $this->assertInstanceOf(Publisher::class, $publisher);
        $this->assertEquals('Test Publisher', $publisher->name);
        $this->assertEquals('test-publisher', $publisher->slug);
        $this->assertEquals('A test publisher description', $publisher->description);
    }

    /** @test */
    public function it_belongs_to_many_manga()
    {
        $publisher = Publisher::factory()->create();
        $manga1 = Manga::factory()->create();
        $manga2 = Manga::factory()->create();

        $publisher->manga()->attach([$manga1->id, $manga2->id]);

        $this->assertCount(2, $publisher->manga);
        $this->assertTrue($publisher->manga->contains($manga1));
        $this->assertTrue($publisher->manga->contains($manga2));
    }

    /** @test */
    public function it_generates_url_correctly()
    {
        $publisher = Publisher::factory()->create(['slug' => 'test-publisher']);

        $url = $publisher->getUrl();
        
        $this->assertStringContainsString('test-publisher', $url);
    }

    /** @test */
    public function it_uses_slug_as_primary_cache_key()
    {
        $cacheKey = Publisher::primaryCacheKey();
        
        $this->assertEquals('slug', $cacheKey);
    }

    /** @test */
    public function it_implements_required_interfaces()
    {
        $publisher = new Publisher();
        
        $this->assertInstanceOf(\Ophim\Core\Contracts\TaxonomyInterface::class, $publisher);
        $this->assertInstanceOf(\Hacoidev\CachingModel\Contracts\Cacheable::class, $publisher);
        $this->assertInstanceOf(\Ophim\Core\Contracts\SeoInterface::class, $publisher);
    }

    /** @test */
    public function it_uses_required_traits()
    {
        $publisher = new Publisher();
        $traits = class_uses_recursive(get_class($publisher));
        
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
        $publisher = new Publisher();
        
        $this->assertEquals('publishers', $publisher->getTable());
    }

    /** @test */
    public function it_guards_id_field()
    {
        $publisher = new Publisher();
        
        $this->assertContains('id', $publisher->getGuarded());
    }

    /** @test */
    public function it_can_generate_seo_tags()
    {
        $publisher = Publisher::factory()->create([
            'name' => 'Test Publisher',
            'description' => 'Test publisher description'
        ]);

        // This should not throw an exception
        $publisher->generateSeoTags();
        
        $this->assertTrue(true); // If we get here, the method executed successfully
    }

    /** @test */
    public function it_can_get_title_pattern()
    {
        $publisher = new Publisher();
        
        // Use reflection to access protected method
        $reflection = new \ReflectionClass($publisher);
        $method = $reflection->getMethod('titlePattern');
        $method->setAccessible(true);
        
        $pattern = $method->invoke($publisher);
        
        $this->assertIsString($pattern);
    }

    /** @test */
    public function it_can_get_description_pattern()
    {
        $publisher = new Publisher();
        
        // Use reflection to access protected method
        $reflection = new \ReflectionClass($publisher);
        $method = $reflection->getMethod('descriptionPattern');
        $method->setAccessible(true);
        
        $pattern = $method->invoke($publisher);
        
        $this->assertIsString($pattern);
    }

    /** @test */
    public function it_can_get_keywords_pattern()
    {
        $publisher = new Publisher();
        
        // Use reflection to access protected method
        $reflection = new \ReflectionClass($publisher);
        $method = $reflection->getMethod('keywordsPattern');
        $method->setAccessible(true);
        
        $pattern = $method->invoke($publisher);
        
        $this->assertIsString($pattern);
    }
}