<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Ophim\Core\Models\Chapter;
use Ophim\Core\Models\Manga;
use Ophim\Core\Models\Volume;
use Ophim\Core\Models\Page;
use Ophim\Core\Models\User;
use ZipArchive;

class ChapterCrudTest extends TestCase
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
    public function it_can_display_chapter_list()
    {
        Chapter::factory()->count(3)->create();

        $response = $this->get("/{$this->adminRoute}/chapter");

        $response->assertStatus(200);
        $response->assertViewIs('crud::list');
        $response->assertViewHas('crud');
    }

    /** @test */
    public function it_can_display_chapter_create_form()
    {
        $response = $this->get("/{$this->adminRoute}/chapter/create");

        $response->assertStatus(200);
        $response->assertViewIs('crud::create');
        $response->assertViewHas('crud');
    }

    /** @test */
    public function it_can_create_a_chapter()
    {
        $manga = Manga::factory()->create();
        $volume = Volume::factory()->create(['manga_id' => $manga->id]);

        $chapterData = [
            'manga_id' => $manga->id,
            'volume_id' => $volume->id,
            'title' => 'Test Chapter',
            'chapter_number' => 1.0,
            'volume_number' => 1,
            'published_at' => now()->format('Y-m-d H:i:s'),
        ];

        $response = $this->post("/{$this->adminRoute}/chapter", $chapterData);

        $response->assertRedirect();
        
        $this->assertDatabaseHas('chapters', [
            'manga_id' => $manga->id,
            'volume_id' => $volume->id,
            'title' => 'Test Chapter',
            'chapter_number' => 1.0,
            'volume_number' => 1,
        ]);
    }

    /** @test */
    public function it_validates_required_fields_on_create()
    {
        $response = $this->post("/{$this->adminRoute}/chapter", []);

        $response->assertSessionHasErrors(['manga_id', 'chapter_number']);
    }

    /** @test */
    public function it_validates_chapter_number_format()
    {
        $manga = Manga::factory()->create();

        $response = $this->post("/{$this->adminRoute}/chapter", [
            'manga_id' => $manga->id,
            'chapter_number' => 'invalid'
        ]);

        $response->assertSessionHasErrors(['chapter_number']);
    }

    /** @test */
    public function it_supports_fractional_chapter_numbers()
    {
        $manga = Manga::factory()->create();

        $chapterData = [
            'manga_id' => $manga->id,
            'title' => 'Test Chapter 4.5',
            'chapter_number' => 4.5,
        ];

        $response = $this->post("/{$this->adminRoute}/chapter", $chapterData);

        $response->assertRedirect();
        
        $this->assertDatabaseHas('chapters', [
            'manga_id' => $manga->id,
            'chapter_number' => 4.5,
        ]);
    }

    /** @test */
    public function it_prevents_duplicate_chapter_numbers_for_same_manga()
    {
        $manga = Manga::factory()->create();
        Chapter::factory()->create([
            'manga_id' => $manga->id,
            'chapter_number' => 1.0
        ]);

        $chapterData = [
            'manga_id' => $manga->id,
            'title' => 'Duplicate Chapter',
            'chapter_number' => 1.0,
        ];

        $response = $this->post("/{$this->adminRoute}/chapter", $chapterData);

        $response->assertSessionHasErrors(['chapter_number']);
    }

    /** @test */
    public function it_can_display_chapter_edit_form()
    {
        $chapter = Chapter::factory()->create();

        $response = $this->get("/{$this->adminRoute}/chapter/{$chapter->id}/edit");

        $response->assertStatus(200);
        $response->assertViewIs('crud::edit');
        $response->assertViewHas('crud');
    }

    /** @test */
    public function it_can_update_a_chapter()
    {
        $chapter = Chapter::factory()->create([
            'title' => 'Original Title',
            'chapter_number' => 1.0
        ]);

        $updateData = [
            'manga_id' => $chapter->manga_id,
            'title' => 'Updated Title',
            'chapter_number' => 1.0, // Keep same number to avoid validation error
        ];

        $response = $this->put("/{$this->adminRoute}/chapter/{$chapter->id}", $updateData);

        $response->assertRedirect();
        
        $this->assertDatabaseHas('chapters', [
            'id' => $chapter->id,
            'title' => 'Updated Title',
        ]);
    }

    /** @test */
    public function it_can_display_chapter_show_page()
    {
        $chapter = Chapter::factory()->create();

        $response = $this->get("/{$this->adminRoute}/chapter/{$chapter->id}/show");

        $response->assertStatus(200);
        $response->assertViewIs('crud::show');
        $response->assertViewHas('crud');
    }

    /** @test */
    public function it_can_delete_a_chapter()
    {
        $chapter = Chapter::factory()->create();

        $response = $this->delete("/{$this->adminRoute}/chapter/{$chapter->id}");

        $response->assertRedirect();
        $this->assertDatabaseMissing('chapters', ['id' => $chapter->id]);
    }

    /** @test */
    public function it_deletes_associated_pages_when_chapter_is_deleted()
    {
        $chapter = Chapter::factory()->create();
        $page1 = Page::factory()->create(['chapter_id' => $chapter->id]);
        $page2 = Page::factory()->create(['chapter_id' => $chapter->id]);

        $response = $this->delete("/{$this->adminRoute}/chapter/{$chapter->id}");

        $response->assertRedirect();
        $this->assertDatabaseMissing('chapters', ['id' => $chapter->id]);
        $this->assertDatabaseMissing('pages', ['id' => $page1->id]);
        $this->assertDatabaseMissing('pages', ['id' => $page2->id]);
    }

    /** @test */
    public function it_can_bulk_delete_chapters()
    {
        $chapter1 = Chapter::factory()->create();
        $chapter2 = Chapter::factory()->create();
        $chapter3 = Chapter::factory()->create();

        $response = $this->post("/{$this->adminRoute}/chapter/bulk-delete", [
            'entries' => [$chapter1->id, $chapter2->id]
        ]);

        $response->assertRedirect();
        
        $this->assertDatabaseMissing('chapters', ['id' => $chapter1->id]);
        $this->assertDatabaseMissing('chapters', ['id' => $chapter2->id]);
        $this->assertDatabaseHas('chapters', ['id' => $chapter3->id]);
    }

    /** @test */
    public function it_can_upload_single_page_images()
    {
        Storage::fake('public');
        
        $chapter = Chapter::factory()->create();
        $image1 = UploadedFile::fake()->image('page1.jpg', 800, 1200);
        $image2 = UploadedFile::fake()->image('page2.jpg', 800, 1200);

        $response = $this->post("/{$this->adminRoute}/chapter/{$chapter->id}/upload-pages", [
            'pages' => [$image1, $image2]
        ]);

        $response->assertRedirect();
        
        $this->assertDatabaseHas('pages', [
            'chapter_id' => $chapter->id,
            'page_number' => 1
        ]);
        $this->assertDatabaseHas('pages', [
            'chapter_id' => $chapter->id,
            'page_number' => 2
        ]);
    }

    /** @test */
    public function it_can_upload_pages_from_zip_file()
    {
        Storage::fake('public');
        
        $chapter = Chapter::factory()->create();
        
        // Create a temporary ZIP file with images
        $zipPath = storage_path('app/temp_test.zip');
        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::CREATE);
        
        // Add some dummy image content
        $zip->addFromString('001.jpg', 'fake image content 1');
        $zip->addFromString('002.jpg', 'fake image content 2');
        $zip->addFromString('003.jpg', 'fake image content 3');
        $zip->close();

        $zipFile = new UploadedFile($zipPath, 'chapter.zip', 'application/zip', null, true);

        $response = $this->post("/{$this->adminRoute}/chapter/{$chapter->id}/upload-zip", [
            'zip_file' => $zipFile
        ]);

        $response->assertRedirect();
        
        // Clean up
        unlink($zipPath);
    }

    /** @test */
    public function it_validates_zip_file_upload()
    {
        $chapter = Chapter::factory()->create();
        $invalidFile = UploadedFile::fake()->create('invalid.txt', 100);

        $response = $this->post("/{$this->adminRoute}/chapter/{$chapter->id}/upload-zip", [
            'zip_file' => $invalidFile
        ]);

        $response->assertSessionHasErrors(['zip_file']);
    }

    /** @test */
    public function it_can_reorder_pages()
    {
        $chapter = Chapter::factory()->create();
        $page1 = Page::factory()->create(['chapter_id' => $chapter->id, 'page_number' => 1]);
        $page2 = Page::factory()->create(['chapter_id' => $chapter->id, 'page_number' => 2]);
        $page3 = Page::factory()->create(['chapter_id' => $chapter->id, 'page_number' => 3]);

        $response = $this->post("/{$this->adminRoute}/chapter/{$chapter->id}/reorder-pages", [
            'page_order' => [$page3->id, $page1->id, $page2->id]
        ]);

        $response->assertRedirect();
        
        $page1->refresh();
        $page2->refresh();
        $page3->refresh();
        
        $this->assertEquals(2, $page1->page_number);
        $this->assertEquals(3, $page2->page_number);
        $this->assertEquals(1, $page3->page_number);
    }

    /** @test */
    public function it_can_filter_chapters_by_manga()
    {
        $manga1 = Manga::factory()->create();
        $manga2 = Manga::factory()->create();
        
        Chapter::factory()->create(['manga_id' => $manga1->id]);
        Chapter::factory()->create(['manga_id' => $manga2->id]);
        Chapter::factory()->create(['manga_id' => $manga1->id]);

        $response = $this->get("/{$this->adminRoute}/chapter?manga_id={$manga1->id}");

        $response->assertStatus(200);
        // Additional assertions would depend on the actual filter implementation
    }

    /** @test */
    public function it_can_search_chapters_by_title()
    {
        Chapter::factory()->create(['title' => 'Chapter One']);
        Chapter::factory()->create(['title' => 'Chapter Two']);
        Chapter::factory()->create(['title' => 'Special Chapter']);

        $response = $this->get("/{$this->adminRoute}/chapter?search=Special");

        $response->assertStatus(200);
        // Additional assertions would depend on the actual search implementation
    }

    /** @test */
    public function it_updates_chapter_page_count_when_pages_added()
    {
        Storage::fake('public');
        
        $chapter = Chapter::factory()->create(['page_count' => 0]);
        $image1 = UploadedFile::fake()->image('page1.jpg');
        $image2 = UploadedFile::fake()->image('page2.jpg');

        $response = $this->post("/{$this->adminRoute}/chapter/{$chapter->id}/upload-pages", [
            'pages' => [$image1, $image2]
        ]);

        $response->assertRedirect();
        
        $chapter->refresh();
        $this->assertEquals(2, $chapter->page_count);
    }

    /** @test */
    public function it_auto_generates_slug_from_title()
    {
        $manga = Manga::factory()->create();

        $chapterData = [
            'manga_id' => $manga->id,
            'title' => 'Test Chapter Title',
            'chapter_number' => 1.0,
        ];

        $response = $this->post("/{$this->adminRoute}/chapter", $chapterData);

        $response->assertRedirect();
        
        $chapter = Chapter::where('title', 'Test Chapter Title')->first();
        $this->assertNotNull($chapter);
        $this->assertEquals('test-chapter-title', $chapter->slug);
    }

    /** @test */
    public function it_can_schedule_chapter_publication()
    {
        $manga = Manga::factory()->create();
        $futureDate = now()->addDays(7);

        $chapterData = [
            'manga_id' => $manga->id,
            'title' => 'Scheduled Chapter',
            'chapter_number' => 1.0,
            'published_at' => $futureDate->format('Y-m-d H:i:s'),
        ];

        $response = $this->post("/{$this->adminRoute}/chapter", $chapterData);

        $response->assertRedirect();
        
        $chapter = Chapter::where('title', 'Scheduled Chapter')->first();
        $this->assertNotNull($chapter);
        $this->assertEquals($futureDate->format('Y-m-d H:i'), $chapter->published_at->format('Y-m-d H:i'));
    }
}