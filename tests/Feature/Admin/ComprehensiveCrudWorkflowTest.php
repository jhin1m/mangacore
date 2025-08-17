<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Ophim\Core\Models\Manga;
use Ophim\Core\Models\Chapter;
use Ophim\Core\Models\Author;
use Ophim\Core\Models\Artist;
use Ophim\Core\Models\Publisher;
use Ophim\Core\Models\Category;
use Ophim\Core\Models\Tag;
use Ophim\Core\Models\Origin;
use Ophim\Core\Models\User;

class ComprehensiveCrudWorkflowTest extends TestCase
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
    public function it_handles_complete_manga_crud_workflow_with_array_data()
    {
        Storage::fake('public');
        
        // Create related entities
        $author = Author::factory()->create();
        $artist = Artist::factory()->create();
        $category = Category::factory()->create();
        $tag = Tag::factory()->create();
        $origin = Origin::factory()->create();
        $publisher = Publisher::factory()->create();
        
        $coverImage = UploadedFile::fake()->image('cover.jpg', 300, 400);
        
        // Test CREATE with array data in other_name field
        $mangaData = [
            'title' => 'Test Manga Title',
            'original_title' => 'テストマンガ',
            'other_name' => 'Alt Name 1, Alt Name 2, Alt Name 3', // String format
            'description' => 'A comprehensive test manga description',
            'type' => 'manga',
            'status' => 'ongoing',
            'demographic' => 'shounen',
            'reading_direction' => 'rtl',
            'publication_year' => 2023,
            'total_chapters' => 100,
            'total_volumes' => 10,
            'rating' => 8.5,
            'cover_image' => $coverImage,
            'authors' => [$author->id],
            'artists' => [$artist->id],
            'categories' => [$category->id],
            'tags' => [$tag->id],
            'origins' => [$origin->id],
            'publishers' => [$publisher->id],
        ];

        // CREATE operation
        $createResponse = $this->post("/{$this->adminRoute}/manga", $mangaData);
        $createResponse->assertRedirect();
        
        $manga = Manga::where('title', 'Test Manga Title')->first();
        $this->assertNotNull($manga);
        
        // Verify data type handling
        $this->assertIsString($manga->other_name_string);
        $this->assertIsArray($manga->other_name_array);
        $this->assertEquals(['Alt Name 1', 'Alt Name 2', 'Alt Name 3'], $manga->other_name_array);
        
        // Verify relationships
        $this->assertTrue($manga->authors->contains($author));
        $this->assertTrue($manga->artists->contains($artist));
        $this->assertTrue($manga->categories->contains($category));
        $this->assertTrue($manga->tags->contains($tag));
        $this->assertTrue($manga->origins->contains($origin));
        $this->assertTrue($manga->publishers->contains($publisher));

        // Test READ operations
        $listResponse = $this->get("/{$this->adminRoute}/manga");
        $listResponse->assertStatus(200);
        $listResponse->assertSee($manga->title);
        
        $showResponse = $this->get("/{$this->adminRoute}/manga/{$manga->id}/show");
        $showResponse->assertStatus(200);
        $showResponse->assertSee($manga->title);
        
        // Test EDIT form display (should handle array data gracefully)
        $editResponse = $this->get("/{$this->adminRoute}/manga/{$manga->id}/edit");
        $editResponse->assertStatus(200);
        $editResponse->assertSee($manga->title);
        // Should not contain "Array to string conversion" error
        $this->assertStringNotContainsString('Array to string conversion', $editResponse->getContent());

        // Test UPDATE with modified array data
        $updateData = [
            'title' => 'Updated Manga Title',
            'other_name' => 'Updated Alt 1, Updated Alt 2', // Modified other names
            'description' => 'Updated description with special chars & symbols',
            'status' => 'completed',
            'rating' => 9.0,
            'authors' => [$author->id], // Keep existing relationships
            'artists' => [$artist->id],
            'categories' => [$category->id],
            'tags' => [$tag->id],
            'origins' => [$origin->id],
            'publishers' => [$publisher->id],
        ];

        $updateResponse = $this->put("/{$this->adminRoute}/manga/{$manga->id}", $updateData);
        $updateResponse->assertRedirect();
        
        $manga->refresh();
        $this->assertEquals('Updated Manga Title', $manga->title);
        $this->assertEquals('completed', $manga->status);
        $this->assertEquals(['Updated Alt 1', 'Updated Alt 2'], $manga->other_name_array);
        $this->assertEquals(9.0, $manga->rating);

        // Test DELETE operation
        $deleteResponse = $this->delete("/{$this->adminRoute}/manga/{$manga->id}");
        $deleteResponse->assertRedirect();
        $this->assertDatabaseMissing('mangas', ['id' => $manga->id]);
    }

    /** @test */
    public function it_handles_chapter_crud_workflow_with_manga_selection()
    {
        // Create manga for selection
        $manga1 = Manga::factory()->create(['title' => 'Manga One']);
        $manga2 = Manga::factory()->create(['title' => 'Manga Two']);
        
        // Test CREATE form with manga selection field
        $createResponse = $this->get("/{$this->adminRoute}/chapter/create");
        $createResponse->assertStatus(200);
        
        // Should contain manga selection options
        $createResponse->assertSee($manga1->title);
        $createResponse->assertSee($manga2->title);
        
        // Should not have field view resolution errors
        $this->assertStringNotContainsString('View not found', $createResponse->getContent());
        $this->assertStringNotContainsString('select_manga', $createResponse->getContent()); // Field should be resolved

        // Test CREATE operation
        $chapterData = [
            'manga_id' => $manga1->id,
            'title' => 'Test Chapter',
            'chapter_number' => 1.5, // Test fractional numbers
            'volume_number' => 1,
            'published_at' => now()->format('Y-m-d H:i:s'),
        ];

        $createChapterResponse = $this->post("/{$this->adminRoute}/chapter", $chapterData);
        $createChapterResponse->assertRedirect();
        
        $chapter = Chapter::where('title', 'Test Chapter')->first();
        $this->assertNotNull($chapter);
        $this->assertEquals($manga1->id, $chapter->manga_id);
        $this->assertEquals(1.5, $chapter->chapter_number);

        // Test EDIT form
        $editResponse = $this->get("/{$this->adminRoute}/chapter/{$chapter->id}/edit");
        $editResponse->assertStatus(200);
        $editResponse->assertSee($chapter->title);
        $editResponse->assertSee($manga1->title);

        // Test UPDATE operation
        $updateChapterData = [
            'manga_id' => $manga2->id, // Change manga
            'title' => 'Updated Chapter Title',
            'chapter_number' => 1.5, // Keep same number to avoid validation error
        ];

        $updateChapterResponse = $this->put("/{$this->adminRoute}/chapter/{$chapter->id}", $updateChapterData);
        $updateChapterResponse->assertRedirect();
        
        $chapter->refresh();
        $this->assertEquals('Updated Chapter Title', $chapter->title);
        $this->assertEquals($manga2->id, $chapter->manga_id);

        // Test DELETE operation
        $deleteChapterResponse = $this->delete("/{$this->adminRoute}/chapter/{$chapter->id}");
        $deleteChapterResponse->assertRedirect();
        $this->assertDatabaseMissing('chapters', ['id' => $chapter->id]);
    }

    /** @test */
    public function it_handles_bulk_operations_without_errors()
    {
        // Create multiple manga for bulk operations
        $manga1 = Manga::factory()->create(['title' => 'Bulk Test 1']);
        $manga2 = Manga::factory()->create(['title' => 'Bulk Test 2']);
        $manga3 = Manga::factory()->create(['title' => 'Bulk Test 3']);

        // Test bulk delete
        $bulkDeleteResponse = $this->post("/{$this->adminRoute}/manga/bulk-delete", [
            'entries' => [$manga1->id, $manga2->id]
        ]);

        $bulkDeleteResponse->assertRedirect();
        
        $this->assertDatabaseMissing('mangas', ['id' => $manga1->id]);
        $this->assertDatabaseMissing('mangas', ['id' => $manga2->id]);
        $this->assertDatabaseHas('mangas', ['id' => $manga3->id]);
    }

    /** @test */
    public function it_handles_validation_errors_gracefully()
    {
        // Test CREATE with invalid data
        $invalidMangaData = [
            'title' => '', // Required field empty
            'type' => 'invalid_type',
            'status' => 'invalid_status',
            'demographic' => 'invalid_demographic',
            'reading_direction' => 'invalid_direction',
            'publication_year' => 'not-a-year',
            'rating' => 15, // Out of range
        ];

        $response = $this->post("/{$this->adminRoute}/manga", $invalidMangaData);
        
        $response->assertSessionHasErrors([
            'title',
            'type',
            'status',
            'demographic',
            'reading_direction',
            'publication_year',
            'rating'
        ]);

        // Test UPDATE with invalid data
        $manga = Manga::factory()->create();
        
        $invalidUpdateData = [
            'title' => '', // Required field empty
            'rating' => -5, // Invalid rating
        ];

        $updateResponse = $this->put("/{$this->adminRoute}/manga/{$manga->id}", $invalidUpdateData);
        $updateResponse->assertSessionHasErrors(['title', 'rating']);
    }

    /** @test */
    public function it_handles_file_upload_operations()
    {
        Storage::fake('public');
        
        $manga = Manga::factory()->create();
        
        // Test valid image upload
        $validImage = UploadedFile::fake()->image('cover.jpg', 300, 400);
        
        $updateData = [
            'title' => $manga->title,
            'cover_image' => $validImage,
        ];

        $response = $this->put("/{$this->adminRoute}/manga/{$manga->id}", $updateData);
        $response->assertRedirect();
        
        // Test invalid file upload
        $invalidFile = UploadedFile::fake()->create('invalid.txt', 1000);
        
        $invalidUpdateData = [
            'title' => $manga->title,
            'cover_image' => $invalidFile,
        ];

        $invalidResponse = $this->put("/{$this->adminRoute}/manga/{$manga->id}", $invalidUpdateData);
        $invalidResponse->assertSessionHasErrors(['cover_image']);
    }

    /** @test */
    public function it_handles_relationship_operations_correctly()
    {
        $manga = Manga::factory()->create();
        $author1 = Author::factory()->create();
        $author2 = Author::factory()->create();
        $category1 = Category::factory()->create();
        $category2 = Category::factory()->create();

        // Test adding relationships
        $updateData = [
            'title' => $manga->title,
            'authors' => [$author1->id, $author2->id],
            'categories' => [$category1->id],
        ];

        $response = $this->put("/{$this->adminRoute}/manga/{$manga->id}", $updateData);
        $response->assertRedirect();
        
        $manga->refresh();
        $this->assertTrue($manga->authors->contains($author1));
        $this->assertTrue($manga->authors->contains($author2));
        $this->assertTrue($manga->categories->contains($category1));

        // Test removing relationships
        $removeData = [
            'title' => $manga->title,
            'authors' => [$author1->id], // Remove author2
            'categories' => [$category1->id, $category2->id], // Add category2
        ];

        $removeResponse = $this->put("/{$this->adminRoute}/manga/{$manga->id}", $removeData);
        $removeResponse->assertRedirect();
        
        $manga->refresh();
        $this->assertTrue($manga->authors->contains($author1));
        $this->assertFalse($manga->authors->contains($author2));
        $this->assertTrue($manga->categories->contains($category1));
        $this->assertTrue($manga->categories->contains($category2));
    }

    /** @test */
    public function it_handles_search_and_filter_operations()
    {
        // Create test data
        Manga::factory()->create(['title' => 'Naruto', 'type' => 'manga', 'status' => 'completed']);
        Manga::factory()->create(['title' => 'One Piece', 'type' => 'manga', 'status' => 'ongoing']);
        Manga::factory()->create(['title' => 'Tower of God', 'type' => 'manhwa', 'status' => 'ongoing']);

        // Test search functionality
        $searchResponse = $this->get("/{$this->adminRoute}/manga?search=Naruto");
        $searchResponse->assertStatus(200);
        $searchResponse->assertSee('Naruto');

        // Test type filter
        $typeFilterResponse = $this->get("/{$this->adminRoute}/manga?type=manhwa");
        $typeFilterResponse->assertStatus(200);
        $typeFilterResponse->assertSee('Tower of God');

        // Test status filter
        $statusFilterResponse = $this->get("/{$this->adminRoute}/manga?status=completed");
        $statusFilterResponse->assertStatus(200);
        $statusFilterResponse->assertSee('Naruto');

        // Test combined filters
        $combinedFilterResponse = $this->get("/{$this->adminRoute}/manga?type=manga&status=ongoing");
        $combinedFilterResponse->assertStatus(200);
        $combinedFilterResponse->assertSee('One Piece');
    }

    /** @test */
    public function it_handles_pagination_correctly()
    {
        // Create many manga entries
        Manga::factory()->count(25)->create();

        // Test first page
        $firstPageResponse = $this->get("/{$this->adminRoute}/manga");
        $firstPageResponse->assertStatus(200);
        $firstPageResponse->assertSee('pagination'); // Should have pagination

        // Test specific page
        $secondPageResponse = $this->get("/{$this->adminRoute}/manga?page=2");
        $secondPageResponse->assertStatus(200);
    }

    /** @test */
    public function it_handles_data_type_consistency_throughout_workflow()
    {
        // Create manga with mixed data types
        $manga = Manga::factory()->create([
            'other_name' => 'Name 1, Name 2, Name 3',
            'rating' => '8.5', // String that should become float
            'publication_year' => '2023', // String that should become int
            'is_completed' => 'true', // String that should become boolean
        ]);

        // Test that data types are consistent after creation
        $this->assertIsString($manga->other_name_string);
        $this->assertIsArray($manga->other_name_array);
        $this->assertIsFloat($manga->rating);
        $this->assertIsInt($manga->publication_year);
        $this->assertIsBool($manga->is_completed);

        // Test EDIT form displays data correctly
        $editResponse = $this->get("/{$this->adminRoute}/manga/{$manga->id}/edit");
        $editResponse->assertStatus(200);
        
        // Should not have type conversion errors
        $this->assertStringNotContainsString('Array to string conversion', $editResponse->getContent());
        $this->assertStringNotContainsString('htmlspecialchars() expects parameter 1 to be string', $editResponse->getContent());

        // Test UPDATE maintains data type consistency
        $updateData = [
            'title' => $manga->title,
            'other_name' => 'Updated Name 1, Updated Name 2',
            'rating' => '9.0',
            'publication_year' => '2024',
            'is_completed' => 'false',
        ];

        $updateResponse = $this->put("/{$this->adminRoute}/manga/{$manga->id}", $updateData);
        $updateResponse->assertRedirect();
        
        $manga->refresh();
        $this->assertEquals(['Updated Name 1', 'Updated Name 2'], $manga->other_name_array);
        $this->assertEquals(9.0, $manga->rating);
        $this->assertEquals(2024, $manga->publication_year);
        $this->assertFalse($manga->is_completed);
    }

    /** @test */
    public function it_handles_special_characters_and_unicode()
    {
        // Test CREATE with special characters and unicode
        $mangaData = [
            'title' => 'Test Manga with Special Chars & Unicode 漫画',
            'original_title' => 'テストマンガ with émojis 🎌',
            'other_name' => 'Alt Name with & symbols, Unicode 漫画, Émojis 🎯',
            'description' => 'Description with <script>alert("xss")</script> and unicode 漫画',
        ];

        $response = $this->post("/{$this->adminRoute}/manga", $mangaData);
        $response->assertRedirect();
        
        $manga = Manga::where('title', 'Test Manga with Special Chars & Unicode 漫画')->first();
        $this->assertNotNull($manga);
        
        // Verify special characters are preserved
        $this->assertStringContainsString('&', $manga->title);
        $this->assertStringContainsString('漫画', $manga->title);
        $this->assertStringContainsString('🎌', $manga->original_title);
        
        // Verify XSS protection
        $this->assertStringNotContainsString('<script>', $manga->description);

        // Test EDIT form handles special characters
        $editResponse = $this->get("/{$this->adminRoute}/manga/{$manga->id}/edit");
        $editResponse->assertStatus(200);
        $editResponse->assertSee($manga->title);
    }
}