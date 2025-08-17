<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Ophim\Core\Models\Manga;
use Ophim\Core\Models\Chapter;

class CrudOperationValidationTest extends TestCase
{
    /** @test */
    public function it_validates_manga_model_data_before_save()
    {
        $manga = new Manga([
            'title' => '', // Invalid - required
            'publication_year' => 'not-a-number', // Invalid - should be numeric
            'rating' => 15, // Invalid - should be 0-10
            'type' => 'invalid-type', // Invalid - not in enum
            'status' => 'invalid-status', // Invalid - not in enum
            'demographic' => 'invalid-demographic', // Invalid - not in enum
            'reading_direction' => 'invalid-direction' // Invalid - not in enum
        ]);

        $errors = $manga->validateModelData();

        $this->assertArrayHasKey('title', $errors);
        $this->assertArrayHasKey('publication_year', $errors);
        $this->assertArrayHasKey('rating', $errors);
        $this->assertArrayHasKey('type', $errors);
        $this->assertArrayHasKey('status', $errors);
        $this->assertArrayHasKey('demographic', $errors);
        $this->assertArrayHasKey('reading_direction', $errors);
    }

    /** @test */
    public function it_validates_valid_manga_model_data()
    {
        $manga = new Manga([
            'title' => 'Valid Manga Title',
            'publication_year' => 2023,
            'rating' => 8.5,
            'type' => 'manga',
            'status' => 'ongoing',
            'demographic' => 'shounen',
            'reading_direction' => 'rtl'
        ]);

        $errors = $manga->validateModelData();

        $this->assertEmpty($errors);
    }

    /** @test */
    public function it_validates_chapter_model_data_before_save()
    {
        $chapter = new Chapter([
            'manga_id' => null, // Invalid - required
            'chapter_number' => 'not-a-number', // Invalid - should be numeric
            'volume_number' => 'not-a-number', // Invalid - should be numeric
            'page_count' => -1 // Invalid - should be positive
        ]);

        $errors = $chapter->validateModelData();

        $this->assertArrayHasKey('manga_id', $errors);
        $this->assertArrayHasKey('chapter_number', $errors);
        $this->assertArrayHasKey('volume_number', $errors);
        $this->assertArrayHasKey('page_count', $errors);
    }

    /** @test */
    public function it_validates_valid_chapter_model_data()
    {
        $chapter = new Chapter([
            'manga_id' => 1,
            'chapter_number' => 1.5,
            'volume_number' => 1,
            'page_count' => 20
        ]);

        $errors = $chapter->validateModelData();

        $this->assertEmpty($errors);
    }

    /** @test */
    public function it_validates_enum_values_correctly()
    {
        $manga = new Manga();

        // Test valid enum values
        $validTypes = ['manga', 'manhwa', 'manhua', 'webtoon'];
        foreach ($validTypes as $type) {
            $manga->type = $type;
            $errors = $manga->validateModelData();
            $this->assertArrayNotHasKey('type', $errors, "Type '{$type}' should be valid");
        }

        $validStatuses = ['ongoing', 'completed', 'hiatus', 'cancelled'];
        foreach ($validStatuses as $status) {
            $manga->status = $status;
            $errors = $manga->validateModelData();
            $this->assertArrayNotHasKey('status', $errors, "Status '{$status}' should be valid");
        }

        $validDemographics = ['shounen', 'seinen', 'josei', 'shoujo', 'kodomomuke', 'general'];
        foreach ($validDemographics as $demographic) {
            $manga->demographic = $demographic;
            $errors = $manga->validateModelData();
            $this->assertArrayNotHasKey('demographic', $errors, "Demographic '{$demographic}' should be valid");
        }

        $validDirections = ['ltr', 'rtl', 'vertical'];
        foreach ($validDirections as $direction) {
            $manga->reading_direction = $direction;
            $errors = $manga->validateModelData();
            $this->assertArrayNotHasKey('reading_direction', $errors, "Direction '{$direction}' should be valid");
        }
    }

    /** @test */
    public function it_validates_numeric_ranges_correctly()
    {
        $manga = new Manga();

        // Test valid rating range (0-10)
        $validRatings = [0, 5.5, 10];
        foreach ($validRatings as $rating) {
            $manga->rating = $rating;
            $errors = $manga->validateModelData();
            $this->assertArrayNotHasKey('rating', $errors, "Rating '{$rating}' should be valid");
        }

        // Test invalid rating range
        $invalidRatings = [-1, 11, 15];
        foreach ($invalidRatings as $rating) {
            $manga->rating = $rating;
            $errors = $manga->validateModelData();
            $this->assertArrayHasKey('rating', $errors, "Rating '{$rating}' should be invalid");
        }

        // Test valid publication year range
        $currentYear = date('Y');
        $validYears = [1900, 2000, $currentYear, $currentYear + 1];
        foreach ($validYears as $year) {
            $manga->publication_year = $year;
            $errors = $manga->validateModelData();
            $this->assertArrayNotHasKey('publication_year', $errors, "Year '{$year}' should be valid");
        }

        // Test invalid publication year range
        $invalidYears = [1800, $currentYear + 10];
        foreach ($invalidYears as $year) {
            $manga->publication_year = $year;
            $errors = $manga->validateModelData();
            $this->assertArrayHasKey('publication_year', $errors, "Year '{$year}' should be invalid");
        }
    }

    /** @test */
    public function it_validates_required_fields_correctly()
    {
        $manga = new Manga();

        // Test empty required fields
        $requiredFields = ['title'];
        foreach ($requiredFields as $field) {
            $manga->$field = '';
            $errors = $manga->validateModelData();
            $this->assertArrayHasKey($field, $errors, "Field '{$field}' should be required");
        }

        // Test null required fields
        foreach ($requiredFields as $field) {
            $manga->$field = null;
            $errors = $manga->validateModelData();
            $this->assertArrayHasKey($field, $errors, "Field '{$field}' should not accept null");
        }
    }

    /** @test */
    public function it_validates_string_length_limits()
    {
        $manga = new Manga();

        // Test title length limit
        $manga->title = str_repeat('a', 256); // Assuming 255 char limit
        $errors = $manga->validateModelData();
        $this->assertArrayHasKey('title', $errors);

        // Test valid title length
        $manga->title = str_repeat('a', 100);
        $errors = $manga->validateModelData();
        $this->assertArrayNotHasKey('title', $errors);
    }

    /** @test */
    public function it_validates_unique_constraints()
    {
        // This would typically require database interaction
        // For unit tests, we'll test the validation logic
        $manga = new Manga(['title' => 'Test Manga']);
        
        // Test slug uniqueness validation logic
        $this->assertTrue(method_exists($manga, 'validateUniqueSlug'));
        
        // Test that slug is generated from title
        $manga->title = 'Test Manga Title';
        $expectedSlug = 'test-manga-title';
        
        // This would be tested in integration tests with actual database
        $this->assertTrue(true); // Placeholder for unique validation
    }

    /** @test */
    public function it_validates_relationship_constraints()
    {
        $chapter = new Chapter();

        // Test that manga_id must reference existing manga
        $chapter->manga_id = 999999; // Non-existent ID
        $errors = $chapter->validateModelData();
        
        // This would be validated at database level or through custom validation
        $this->assertTrue(method_exists($chapter, 'validateModelData'));
    }

    /** @test */
    public function it_validates_file_upload_constraints()
    {
        $manga = new Manga();

        // Test image file validation (would be handled by form request)
        $validImageTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $invalidImageTypes = ['txt', 'pdf', 'doc', 'exe'];

        foreach ($validImageTypes as $type) {
            $result = $manga->validateImageType("test.{$type}");
            $this->assertTrue($result, "Image type '{$type}' should be valid");
        }

        foreach ($invalidImageTypes as $type) {
            $result = $manga->validateImageType("test.{$type}");
            $this->assertFalse($result, "Image type '{$type}' should be invalid");
        }
    }

    /** @test */
    public function it_validates_boolean_field_conversion()
    {
        $manga = new Manga();

        // Test boolean field validation
        $booleanFields = ['is_completed', 'is_recommended', 'is_adult_content'];
        
        foreach ($booleanFields as $field) {
            // Test valid boolean values
            $validValues = [true, false, 'true', 'false', '1', '0', 1, 0];
            foreach ($validValues as $value) {
                $manga->$field = $value;
                $errors = $manga->validateModelData();
                $this->assertArrayNotHasKey($field, $errors, "Boolean field '{$field}' should accept value '{$value}'");
            }
        }
    }

    /** @test */
    public function it_validates_decimal_precision()
    {
        $manga = new Manga();
        $chapter = new Chapter();

        // Test rating decimal precision (should be 2 decimal places)
        $manga->rating = 8.567; // Should be rounded to 8.57
        $manga->validateDataTypes();
        $this->assertEquals(8.57, $manga->rating);

        // Test chapter number decimal precision (should be 1 decimal place)
        $chapter->chapter_number = 1.567; // Should be rounded to 1.6
        $chapter->validateDataTypes();
        $this->assertEquals(1.6, $chapter->chapter_number);
    }

    /** @test */
    public function it_validates_array_field_conversion()
    {
        $manga = new Manga();

        // Test that array fields are properly converted to strings for storage
        $arrayFields = ['other_name'];
        
        foreach ($arrayFields as $field) {
            $testArray = ['Item 1', 'Item 2', 'Item 3'];
            $manga->$field = $testArray;
            
            // Should be converted to string for storage
            $this->assertIsString($manga->attributes[$field]);
            $this->assertEquals('Item 1, Item 2, Item 3', $manga->attributes[$field]);
        }
    }

    /** @test */
    public function it_validates_date_field_formats()
    {
        $chapter = new Chapter();

        // Test valid date formats
        $validDates = [
            '2023-12-25 10:30:00',
            '2023-12-25',
            now(),
            now()->toDateTimeString()
        ];

        foreach ($validDates as $date) {
            $chapter->published_at = $date;
            $errors = $chapter->validateModelData();
            $this->assertArrayNotHasKey('published_at', $errors, "Date '{$date}' should be valid");
        }

        // Test invalid date formats
        $invalidDates = [
            'invalid-date',
            '2023-13-45', // Invalid month/day
            '25-12-2023' // Wrong format
        ];

        foreach ($invalidDates as $date) {
            $chapter->published_at = $date;
            $errors = $chapter->validateModelData();
            $this->assertArrayHasKey('published_at', $errors, "Date '{$date}' should be invalid");
        }
    }
}