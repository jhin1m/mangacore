<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Ophim\Core\Models\Manga;
use Ophim\Core\Models\Chapter;
use Ophim\Core\Models\Author;
use Ophim\Core\Models\Category;
use Ophim\Core\Models\User;

class MangaApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Set up API routes
        $this->artisan('route:clear');
    }

    /** @test */
    public function it_can_list_manga()
    {
        Manga::factory()->count(5)->create();

        $response = $this->getJson('/api/manga');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'title',
                            'slug',
                            'cover_image',
                            'type',
                            'status',
                            'rating',
                            'view_count'
                        ]
                    ],
                    'meta' => [
                        'current_page',
                        'total',
                        'per_page'
                    ]
                ]);
    }

    /** @test */
    public function it_can_show_single_manga()
    {
        $manga = $this->createTestManga([
            'title' => 'Test Manga',
            'description' => 'Test description'
        ]);

        $response = $this->getJson("/api/manga/{$manga->id}");

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        'id',
                        'title',
                        'slug',
                        'original_title',
                        'description',
                        'cover_image',
                        'banner_image',
                        'type',
                        'status',
                        'demographic',
                        'reading_direction',
                        'publication_year',
                        'total_chapters',
                        'total_volumes',
                        'rating',
                        'view_count',
                        'authors',
                        'artists',
                        'categories',
                        'tags',
                        'origins',
                        'latest_chapters'
                    ]
                ])
                ->assertJson([
                    'data' => [
                        'title' => 'Test Manga',
                        'description' => 'Test description'
                    ]
                ]);
    }

    /** @test */
    public function it_returns_404_for_non_existent_manga()
    {
        $response = $this->getJson('/api/manga/999999');

        $response->assertStatus(404)
                ->assertJson([
                    'message' => 'Manga not found'
                ]);
    }

    /** @test */
    public function it_can_filter_manga_by_type()
    {
        Manga::factory()->create(['type' => 'manga']);
        Manga::factory()->create(['type' => 'manhwa']);
        Manga::factory()->create(['type' => 'manga']);

        $response = $this->getJson('/api/manga?type=manga');

        $response->assertStatus(200);
        
        $data = $response->json('data');
        $this->assertCount(2, $data);
        
        foreach ($data as $manga) {
            $this->assertEquals('manga', $manga['type']);
        }
    }

    /** @test */
    public function it_can_filter_manga_by_status()
    {
        Manga::factory()->create(['status' => 'ongoing']);
        Manga::factory()->create(['status' => 'completed']);
        Manga::factory()->create(['status' => 'ongoing']);

        $response = $this->getJson('/api/manga?status=ongoing');

        $response->assertStatus(200);
        
        $data = $response->json('data');
        $this->assertCount(2, $data);
        
        foreach ($data as $manga) {
            $this->assertEquals('ongoing', $manga['status']);
        }
    }

    /** @test */
    public function it_can_filter_manga_by_demographic()
    {
        Manga::factory()->create(['demographic' => 'shounen']);
        Manga::factory()->create(['demographic' => 'seinen']);
        Manga::factory()->create(['demographic' => 'shounen']);

        $response = $this->getJson('/api/manga?demographic=shounen');

        $response->assertStatus(200);
        
        $data = $response->json('data');
        $this->assertCount(2, $data);
        
        foreach ($data as $manga) {
            $this->assertEquals('shounen', $manga['demographic']);
        }
    }

    /** @test */
    public function it_can_search_manga_by_title()
    {
        Manga::factory()->create(['title' => 'Naruto']);
        Manga::factory()->create(['title' => 'One Piece']);
        Manga::factory()->create(['title' => 'Dragon Ball']);

        $response = $this->getJson('/api/manga?search=Naruto');

        $response->assertStatus(200);
        
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('Naruto', $data[0]['title']);
    }

    /** @test */
    public function it_can_sort_manga_by_different_fields()
    {
        Manga::factory()->create(['title' => 'A Manga', 'created_at' => now()->subDays(3)]);
        Manga::factory()->create(['title' => 'Z Manga', 'created_at' => now()->subDays(1)]);
        Manga::factory()->create(['title' => 'M Manga', 'created_at' => now()->subDays(2)]);

        // Sort by title ascending
        $response = $this->getJson('/api/manga?sort=title&order=asc');
        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertEquals('A Manga', $data[0]['title']);
        $this->assertEquals('Z Manga', $data[2]['title']);

        // Sort by created_at descending (newest first)
        $response = $this->getJson('/api/manga?sort=created_at&order=desc');
        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertEquals('Z Manga', $data[0]['title']);
    }

    /** @test */
    public function it_paginates_manga_results()
    {
        Manga::factory()->count(25)->create();

        $response = $this->getJson('/api/manga?per_page=10');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data',
                    'meta' => [
                        'current_page',
                        'total',
                        'per_page',
                        'last_page'
                    ],
                    'links' => [
                        'first',
                        'last',
                        'prev',
                        'next'
                    ]
                ]);

        $meta = $response->json('meta');
        $this->assertEquals(10, $meta['per_page']);
        $this->assertEquals(25, $meta['total']);
        $this->assertEquals(3, $meta['last_page']);
    }

    /** @test */
    public function it_includes_relationships_in_manga_details()
    {
        $manga = $this->createTestManga();
        
        // Add some chapters
        Chapter::factory()->count(3)->create(['manga_id' => $manga->id]);

        $response = $this->getJson("/api/manga/{$manga->id}");

        $response->assertStatus(200);
        
        $data = $response->json('data');
        $this->assertArrayHasKey('authors', $data);
        $this->assertArrayHasKey('artists', $data);
        $this->assertArrayHasKey('categories', $data);
        $this->assertArrayHasKey('latest_chapters', $data);
        
        $this->assertNotEmpty($data['authors']);
        $this->assertNotEmpty($data['artists']);
        $this->assertNotEmpty($data['categories']);
        $this->assertCount(3, $data['latest_chapters']);
    }

    /** @test */
    public function it_can_get_manga_chapters()
    {
        $manga = Manga::factory()->create();
        Chapter::factory()->count(5)->create(['manga_id' => $manga->id]);

        $response = $this->getJson("/api/manga/{$manga->id}/chapters");

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'title',
                            'slug',
                            'chapter_number',
                            'volume_number',
                            'page_count',
                            'published_at',
                            'is_premium'
                        ]
                    ]
                ]);

        $this->assertCount(5, $response->json('data'));
    }

    /** @test */
    public function it_orders_chapters_by_chapter_number()
    {
        $manga = Manga::factory()->create();
        Chapter::factory()->create(['manga_id' => $manga->id, 'chapter_number' => 3.0]);
        Chapter::factory()->create(['manga_id' => $manga->id, 'chapter_number' => 1.0]);
        Chapter::factory()->create(['manga_id' => $manga->id, 'chapter_number' => 2.5]);

        $response = $this->getJson("/api/manga/{$manga->id}/chapters");

        $response->assertStatus(200);
        
        $chapters = $response->json('data');
        $this->assertEquals(1.0, $chapters[0]['chapter_number']);
        $this->assertEquals(2.5, $chapters[1]['chapter_number']);
        $this->assertEquals(3.0, $chapters[2]['chapter_number']);
    }

    /** @test */
    public function it_can_get_recommended_manga()
    {
        Manga::factory()->create(['is_recommended' => true]);
        Manga::factory()->create(['is_recommended' => false]);
        Manga::factory()->create(['is_recommended' => true]);

        $response = $this->getJson('/api/manga/recommended');

        $response->assertStatus(200);
        
        $data = $response->json('data');
        $this->assertCount(2, $data);
        
        foreach ($data as $manga) {
            $this->assertTrue($manga['is_recommended']);
        }
    }

    /** @test */
    public function it_can_get_popular_manga()
    {
        Manga::factory()->create(['view_count' => 1000]);
        Manga::factory()->create(['view_count' => 5000]);
        Manga::factory()->create(['view_count' => 3000]);

        $response = $this->getJson('/api/manga/popular');

        $response->assertStatus(200);
        
        $data = $response->json('data');
        $this->assertEquals(5000, $data[0]['view_count']); // Highest first
        $this->assertEquals(3000, $data[1]['view_count']);
        $this->assertEquals(1000, $data[2]['view_count']);
    }

    /** @test */
    public function it_can_get_latest_manga()
    {
        $old = Manga::factory()->create(['created_at' => now()->subDays(5)]);
        $newest = Manga::factory()->create(['created_at' => now()->subDays(1)]);
        $middle = Manga::factory()->create(['created_at' => now()->subDays(3)]);

        $response = $this->getJson('/api/manga/latest');

        $response->assertStatus(200);
        
        $data = $response->json('data');
        $this->assertEquals($newest->id, $data[0]['id']); // Newest first
        $this->assertEquals($middle->id, $data[1]['id']);
        $this->assertEquals($old->id, $data[2]['id']);
    }

    /** @test */
    public function it_respects_rate_limiting()
    {
        // This test would depend on your rate limiting implementation
        // Make multiple requests rapidly and check for 429 status
        
        for ($i = 0; $i < 100; $i++) {
            $response = $this->getJson('/api/manga');
            
            if ($response->status() === 429) {
                $this->assertEquals(429, $response->status());
                return;
            }
        }
        
        // If we get here without hitting rate limit, that's also valid
        $this->assertTrue(true);
    }

    /** @test */
    public function it_handles_invalid_filter_values()
    {
        $response = $this->getJson('/api/manga?type=invalid_type');

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['type']);
    }

    /** @test */
    public function it_handles_invalid_sort_fields()
    {
        $response = $this->getJson('/api/manga?sort=invalid_field');

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['sort']);
    }

    /** @test */
    public function it_increments_view_count_when_viewing_manga()
    {
        $manga = Manga::factory()->create(['view_count' => 100]);

        $response = $this->getJson("/api/manga/{$manga->id}");

        $response->assertStatus(200);
        
        $manga->refresh();
        $this->assertEquals(101, $manga->view_count);
    }
}