<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Ophim\Core\Models\Manga;

class MangaDataTypeHandlingTest extends TestCase
{

    /** @test */
    public function it_handles_array_to_string_conversion_in_other_name_field()
    {
        $manga = new Manga();
        
        // Test setting array value
        $manga->other_name = ['Name 1', 'Name 2', 'Name 3'];
        
        // Should be stored as string
        $this->assertIsString($manga->attributes['other_name']);
        $this->assertEquals('Name 1, Name 2, Name 3', $manga->attributes['other_name']);
    }

    /** @test */
    public function it_returns_appropriate_format_based_on_context()
    {
        $manga = new Manga();
        $manga->setAttribute('other_name', 'Alt Name 1, Alt Name 2');

        // Test programmatic access returns array
        $otherNameArray = $manga->other_name_array;
        $this->assertIsArray($otherNameArray);
        $this->assertEquals(['Alt Name 1', 'Alt Name 2'], $otherNameArray);

        // Test string access
        $otherNameString = $manga->other_name_string;
        $this->assertIsString($otherNameString);
        $this->assertEquals('Alt Name 1, Alt Name 2', $otherNameString);
    }

    /** @test */
    public function it_sanitizes_field_values_for_crud_display()
    {
        $manga = new Manga();
        
        // Test with array input
        $result = $manga->prepareForCrudField(['Test', 'Value'], 'text');
        $this->assertIsString($result);
        $this->assertEquals('Test, Value', $result);

        // Test with string input
        $result = $manga->prepareForCrudField('Simple String', 'text');
        $this->assertEquals('Simple String', $result);

        // Test with null input
        $result = $manga->prepareForCrudField(null, 'text');
        $this->assertEquals('', $result);
    }

    /** @test */
    public function it_validates_and_converts_data_types()
    {
        $manga = new Manga();

        // Test string conversion
        $result = $manga->validateAndConvertType(['a', 'b', 'c'], 'string');
        $this->assertEquals('a, b, c', $result);

        // Test integer conversion
        $result = $manga->validateAndConvertType('123', 'integer');
        $this->assertEquals(123, $result);

        // Test float conversion
        $result = $manga->validateAndConvertType('12.34', 'float');
        $this->assertEquals(12.34, $result);

        // Test boolean conversion
        $result = $manga->validateAndConvertType(['not', 'empty'], 'boolean');
        $this->assertTrue($result);

        $result = $manga->validateAndConvertType([], 'boolean');
        $this->assertFalse($result);
    }

    /** @test */
    public function it_handles_text_field_mutators_consistently()
    {
        $manga = new Manga();

        // Test title field
        $manga->title = ['Title', 'Part', '1'];
        $this->assertEquals('Title, Part, 1', $manga->attributes['title']);

        // Test description field
        $manga->description = ['Line 1', 'Line 2'];
        $this->assertEquals('Line 1, Line 2', $manga->attributes['description']);

        // Test original_title field
        $manga->original_title = ['Original', 'Title'];
        $this->assertEquals('Original, Title', $manga->attributes['original_title']);
    }

    /** @test */
    public function it_validates_model_data_before_save()
    {
        $manga = new Manga([
            'title' => '', // Invalid - required
            'publication_year' => 'not-a-number', // Invalid - should be numeric
            'rating' => 15, // Invalid - should be 0-10
            'type' => 'invalid-type', // Invalid - not in enum
            'status' => 'invalid-status' // Invalid - not in enum
        ]);

        $errors = $manga->validateModelData();

        $this->assertArrayHasKey('title', $errors);
        $this->assertArrayHasKey('publication_year', $errors);
        $this->assertArrayHasKey('rating', $errors);
        $this->assertArrayHasKey('type', $errors);
        $this->assertArrayHasKey('status', $errors);
    }

    /** @test */
    public function it_ensures_data_type_consistency_on_save()
    {
        $manga = new Manga([
            'title' => ['Test', 'Manga'],
            'other_name' => ['Alt', 'Names'],
            'publication_year' => '2023',
            'rating' => '8.5',
            'is_completed' => 'true'
        ]);

        // Trigger validation
        $manga->validateDataTypes();

        // Check that types are properly converted
        $this->assertIsString($manga->attributes['title']);
        $this->assertIsString($manga->attributes['other_name']);
        $this->assertIsInt($manga->attributes['publication_year']);
        $this->assertIsFloat($manga->attributes['rating']);
        $this->assertIsBool($manga->attributes['is_completed']);
    }

    /** @test */
    public function it_provides_safe_field_access_for_crud_operations()
    {
        $manga = new Manga();
        $manga->setAttribute('title', 'Test Manga');
        $manga->setAttribute('other_name', 'Alt Name 1, Alt Name 2');
        $manga->setAttribute('description', 'Test description');

        // Test getting field for CRUD
        $titleForCrud = $manga->getFieldForCrud('title', 'text');
        $this->assertIsString($titleForCrud);

        $otherNameForCrud = $manga->getFieldForCrud('other_name', 'text');
        $this->assertIsString($otherNameForCrud);

        // Test getting all text fields for display
        $textFields = $manga->getTextFieldsForDisplay();
        $this->assertIsArray($textFields);
        $this->assertArrayHasKey('title', $textFields);
        $this->assertArrayHasKey('other_name', $textFields);
        $this->assertArrayHasKey('description', $textFields);
        
        foreach ($textFields as $field => $value) {
            $this->assertIsString($value, "Field {$field} should be string");
        }
    }

    /** @test */
    public function it_handles_null_and_empty_values_gracefully()
    {
        $manga = new Manga();

        // Test null values
        $manga->title = null;
        $this->assertEquals('', $manga->attributes['title']);

        $manga->other_name = null;
        $this->assertEquals('', $manga->attributes['other_name']);

        // Test empty arrays
        $manga->other_name = [];
        $this->assertEquals('', $manga->attributes['other_name']);

        // Test mixed empty values
        $manga->other_name = ['', '  ', null, 'Valid Name'];
        $this->assertEquals('Valid Name', $manga->attributes['other_name']);
    }

    /** @test */
    public function it_detects_crud_context_correctly()
    {
        $manga = new Manga();
        
        // Test default context (should be false in unit tests)
        $reflection = new \ReflectionClass($manga);
        $method = $reflection->getMethod('isInCrudContext');
        $method->setAccessible(true);
        
        $this->assertFalse($method->invoke($manga));
    }
}