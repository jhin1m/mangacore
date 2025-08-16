<?php

namespace Tests\Unit;

use Tests\TestCase;
use Ophim\Core\Models\Author;
use Ophim\Core\Models\Manga;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AuthorModelTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_an_author()
    {
        $author = Author::create([
            'name' => 'Test Author',
            'slug' => 'test-author',
            'description' => 'A test author description'
        ]);

        $this->assertInstanceOf(Author::class, $author);
        $this->assertEquals('Test Author', $author->name);
        $this->assertEquals('test-author', $author->slug);
        $this->assertEquals('A test author description', $author->description);
    }

    /** @test */
    public function it_belongs_to_many_manga()
    {
        $author = Author::factory()->create();
        $manga1 = Manga::factory()->create();
        $manga2 = Manga::factory()->create();

        $author->manga()->attach([$manga1->id, $manga2->id]);

        $this->assertCount(2, $author->manga);
        $this->assertTrue($author->manga->contains($manga1));
        $this->assertTrue($author->manga->contains($manga2));
    }

    /** @test */
    public function it_generates_url_correctly()
    {
        $author = Author::factory()->create(['slug' => 'test-author']);

        $url = $author->getUrl();
        
        $this->assertStringContainsString('test-author', $url);
    }

    /** @test */
    public function it_uses_slug_as_primary_cache_key()
    {
        $cacheKey = Author::primaryCacheKey();
        
        $this->assertEquals('slug', $cacheKey);
    }

    /** @test */
    public function it_implements_required_interfaces()
    {
        $author = new Author();
        
        $this->assertInstanceOf(\Ophim\Core\Contracts\TaxonomyInterface::class, $author);
        $this->assertInstanceOf(\Hacoidev\CachingModel\Contracts\Cacheable::class, $author);
        $this->assertInstanceOf(\Ophim\Core\Contracts\SeoInterface::class, $author);
    }

    /** @test */
    public function it_uses_required_traits()
    {
        $author = new Author();
        $traits = class_uses_recursive(get_class($author));
        
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
        $author = new Author();
        
        $this->assertEquals('authors', $author->getTable());
    }

    /** @test */
    public function it_guards_id_field()
    {
        $author = new Author();
        
        $this->assertContains('id', $author->getGuarded());
    }

    /** @test */
    public function it_can_generate_seo_tags()
    {
        $author = Author::factory()->create([
            'name' => 'Test Author',
            'description' => 'Test author description'
        ]);

        // This should not throw an exception
        $author->generateSeoTags();
        
        $this->assertTrue(true); // If we get here, the method executed successfully
    }

    /** @test */
    public function it_handles_empty_manga_collection_in_seo()
    {
        $author = Author::factory()->create();

        // This should not throw an exception even with no manga
        $author->generateSeoTags();
        
        $this->assertTrue(true);
    }

    /** @test */
    public function it_can_get_title_pattern()
    {
        $author = new Author();
        
        // Use reflection to access protected method
        $reflection = new \ReflectionClass($author);
        $method = $reflection->getMethod('titlePattern');
        $method->setAccessible(true);
        
        $pattern = $method->invoke($author);
        
        $this->assertIsString($pattern);
    }

    /** @test */
    public function it_can_get_description_pattern()
    {
        $author = new Author();
        
        // Use reflection to access protected method
        $reflection = new \ReflectionClass($author);
        $method = $reflection->getMethod('descriptionPattern');
        $method->setAccessible(true);
        
        $pattern = $method->invoke($author);
        
        $this->assertIsString($pattern);
    }

    /** @test */
    public function it_can_get_keywords_pattern()
    {
        $author = new Author();
        
        // Use reflection to access protected method
        $reflection = new \ReflectionClass($author);
        $method = $reflection->getMethod('keywordsPattern');
        $method->setAccessible(true);
        
        $pattern = $method->invoke($author);
        
        $this->assertIsString($pattern);
    }
}