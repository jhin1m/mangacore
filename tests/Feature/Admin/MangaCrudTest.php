<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Ophim\Core\Models\Manga;
use Ophim\Core\Models\Author;
use Ophim\Core\Models\Artist;
use Ophim\Core\Models\Publisher;
use Ophim\Core\Models\Category;
use Ophim\Core\Models\Tag;
use Ophim\Core\Models\Origin;
use Ophim\Core\Models\User;

class MangaCrudTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $adminRoute;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->adminRoute = config('backpack.base.route_prefix', 'admin');
        
        // Mock authentication
        $this->actingAs($this->user);
    }

    /** @test */
    public function it_can_display_manga_list()
    {
        Manga::factory()->count(3)->create();

        $response = $this->get("/{$this->adminRoute}/manga");

        $response->assertStatus(200);
        $response->assertViewIs('crud::list');
        $response->assertViewHas('crud');
    }

    /** @test */
    public function it_can_display_manga_create_form()
    {
        $response = $this->get("/{$this->adminRoute}/manga/create");

        $response->assertStatus(200);
        $response->assertViewIs('crud::create');
        $response->assertViewHas('crud');
    }

    /** @test */
    public function it_can_create_a_manga()
    {
        Storage::fake('public');
        
        $author = Author::factory()->create();
        $artist = Artist::factory()->create();
        $category = Category::factory()->create();
        
        $coverImage = UploadedFile::fake()->image('cover.jpg', 300, 400);
        $bannerImage = UploadedFile::fake()->image('banner.jpg', 800, 400);

        $mangaData = [
            'title' => 'Test Manga',
            'original_title' => 'テストマンガ',
            'other_name' => 'Test Comic, Test Series',
            'description' => 'A test manga description',
            'type' => 'manga',
            'status' => 'ongoing',
            'demographic' => 'shounen',
            'reading_direction' => 'rtl',
            'publication_year' => 2023,
            'total_chapters' => 100,
            'total_volumes' => 10,
            'cover_image' => $coverImage,
            'banner_image' => $bannerImage,
            'authors' => [$author->id],
            'artists' => [$artist->id],
            'categories' => [$category->id],
        ];

        $response = $this->post("/{$this->adminRoute}/manga", $mangaData);

        $response->assertRedirect();
        
        $this->assertDatabaseHas('mangas', [
            'title' => 'Test Manga',
            'original_title' => 'テストマンガ',
            'type' => 'manga',
            'status' => 'ongoing',
            'demographic' => 'shounen',
            'reading_direction' => 'rtl',
            'publication_year' => 2023,
            'total_chapters' => 100,
            'total_volumes' => 10,
        ]);

        $manga = Manga::where('title', 'Test Manga')->first();
        $this->assertNotNull($manga);
        $this->assertTrue($manga->authors->contains($author));
        $this->assertTrue($manga->artists->contains($artist));
        $this->assertTrue($manga->categories->contains($category));
    }

    /** @test */
    public function it_validates_required_fields_on_create()
    {
        $response = $this->post("/{$this->adminRoute}/manga", []);

        $response->assertSessionHasErrors(['title']);
    }

    /** @test */
    public function it_validates_type_field()
    {
        $response = $this->post("/{$this->adminRoute}/manga", [
            'title' => 'Test Manga',
            'type' => 'invalid_type'
        ]);

        $response->assertSessionHasErrors(['type']);
    }

    /** @test */
    public function it_validates_status_field()
    {
        $response = $this->post("/{$this->adminRoute}/manga", [
            'title' => 'Test Manga',
            'status' => 'invalid_status'
        ]);

        $response->assertSessionHasErrors(['status']);
    }

    /** @test */
    public function it_validates_demographic_field()
    {
        $response = $this->post("/{$this->adminRoute}/manga", [
            'title' => 'Test Manga',
            'demographic' => 'invalid_demographic'
        ]);

        $response->assertSessionHasErrors(['demographic']);
    }

    /** @test */
    public function it_validates_reading_direction_field()
    {
        $response = $this->post("/{$this->adminRoute}/manga", [
            'title' => 'Test Manga',
            'reading_direction' => 'invalid_direction'
        ]);

        $response->assertSessionHasErrors(['reading_direction']);
    }

    /** @test */
    public function it_can_display_manga_edit_form()
    {
        $manga = Manga::factory()->create();

        $response = $this->get("/{$this->adminRoute}/manga/{$manga->id}/edit");

        $response->assertStatus(200);
        $response->assertViewIs('crud::edit');
        $response->assertViewHas('crud');
    }

    /** @test */
    public function it_can_update_a_manga()
    {
        $manga = Manga::factory()->create([
            'title' => 'Original Title',
            'status' => 'ongoing'
        ]);

        $updateData = [
            'title' => 'Updated Title',
            'status' => 'completed',
            'description' => 'Updated description'
        ];

        $response = $this->put("/{$this->adminRoute}/manga/{$manga->id}", $updateData);

        $response->assertRedirect();
        
        $this->assertDatabaseHas('mangas', [
            'id' => $manga->id,
            'title' => 'Updated Title',
            'status' => 'completed',
            'description' => 'Updated description'
        ]);
    }

    /** @test */
    public function it_can_update_manga_relationships()
    {
        $manga = Manga::factory()->create();
        $author = Author::factory()->create();
        $artist = Artist::factory()->create();
        $category = Category::factory()->create();

        $updateData = [
            'title' => $manga->title,
            'authors' => [$author->id],
            'artists' => [$artist->id],
            'categories' => [$category->id],
        ];

        $response = $this->put("/{$this->adminRoute}/manga/{$manga->id}", $updateData);

        $response->assertRedirect();
        
        $manga->refresh();
        $this->assertTrue($manga->authors->contains($author));
        $this->assertTrue($manga->artists->contains($artist));
        $this->assertTrue($manga->categories->contains($category));
    }

    /** @test */
    public function it_can_display_manga_show_page()
    {
        $manga = Manga::factory()->create();

        $response = $this->get("/{$this->adminRoute}/manga/{$manga->id}/show");

        $response->assertStatus(200);
        $response->assertViewIs('crud::show');
        $response->assertViewHas('crud');
    }

    /** @test */
    public function it_can_delete_a_manga()
    {
        $manga = Manga::factory()->create();

        $response = $this->delete("/{$this->adminRoute}/manga/{$manga->id}");

        $response->assertRedirect();
        $this->assertDatabaseMissing('mangas', ['id' => $manga->id]);
    }

    /** @test */
    public function it_can_bulk_delete_manga()
    {
        $manga1 = Manga::factory()->create();
        $manga2 = Manga::factory()->create();
        $manga3 = Manga::factory()->create();

        $response = $this->post("/{$this->adminRoute}/manga/bulk-delete", [
            'entries' => [$manga1->id, $manga2->id]
        ]);

        $response->assertRedirect();
        
        $this->assertDatabaseMissing('mangas', ['id' => $manga1->id]);
        $this->assertDatabaseMissing('mangas', ['id' => $manga2->id]);
        $this->assertDatabaseHas('mangas', ['id' => $manga3->id]);
    }

    /** @test */
    public function it_can_filter_manga_by_type()
    {
        Manga::factory()->create(['type' => 'manga']);
        Manga::factory()->create(['type' => 'manhwa']);
        Manga::factory()->create(['type' => 'manga']);

        $response = $this->get("/{$this->adminRoute}/manga?type=manga");

        $response->assertStatus(200);
        // Additional assertions would depend on the actual filter implementation
    }

    /** @test */
    public function it_can_filter_manga_by_status()
    {
        Manga::factory()->create(['status' => 'ongoing']);
        Manga::factory()->create(['status' => 'completed']);
        Manga::factory()->create(['status' => 'ongoing']);

        $response = $this->get("/{$this->adminRoute}/manga?status=ongoing");

        $response->assertStatus(200);
        // Additional assertions would depend on the actual filter implementation
    }

    /** @test */
    public function it_can_search_manga_by_title()
    {
        Manga::factory()->create(['title' => 'Naruto']);
        Manga::factory()->create(['title' => 'One Piece']);
        Manga::factory()->create(['title' => 'Dragon Ball']);

        $response = $this->get("/{$this->adminRoute}/manga?search=Naruto");

        $response->assertStatus(200);
        // Additional assertions would depend on the actual search implementation
    }

    /** @test */
    public function it_handles_image_upload_errors_gracefully()
    {
        Storage::fake('public');
        
        // Create a file that's too large or invalid format
        $invalidImage = UploadedFile::fake()->create('invalid.txt', 1000);

        $mangaData = [
            'title' => 'Test Manga',
            'cover_image' => $invalidImage,
        ];

        $response = $this->post("/{$this->adminRoute}/manga", $mangaData);

        $response->assertSessionHasErrors(['cover_image']);
    }

    /** @test */
    public function it_can_handle_other_name_as_string()
    {
        $mangaData = [
            'title' => 'Test Manga',
            'other_name' => 'Name 1, Name 2, Name 3',
        ];

        $response = $this->post("/{$this->adminRoute}/manga", $mangaData);

        $response->assertRedirect();
        
        $manga = Manga::where('title', 'Test Manga')->first();
        $this->assertNotNull($manga);
        $this->assertEquals(['Name 1', 'Name 2', 'Name 3'], $manga->other_name);
    }

    /** @test */
    public function it_auto_generates_slug_from_title()
    {
        $mangaData = [
            'title' => 'Test Manga Title',
        ];

        $response = $this->post("/{$this->adminRoute}/manga", $mangaData);

        $response->assertRedirect();
        
        $manga = Manga::where('title', 'Test Manga Title')->first();
        $this->assertNotNull($manga);
        $this->assertEquals('test-manga-title', $manga->slug);
    }

    /** @test */
    public function it_prevents_duplicate_slugs()
    {
        Manga::factory()->create(['slug' => 'test-manga']);

        $mangaData = [
            'title' => 'Test Manga',
        ];

        $response = $this->post("/{$this->adminRoute}/manga", $mangaData);

        $response->assertRedirect();
        
        $manga = Manga::where('title', 'Test Manga')->first();
        $this->assertNotNull($manga);
        $this->assertNotEquals('test-manga', $manga->slug);
        $this->assertStringStartsWith('test-manga', $manga->slug);
    }
}