<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Ophim\Core\Models\Manga;
use Ophim\Core\Models\Chapter;
use Ophim\Core\Traits\Operations\FieldViewFallback;

class FieldViewHandlingTest extends TestCase
{
    use FieldViewFallback;

    /** @test */
    public function it_handles_select_manga_field_view_resolution()
    {
        // Test field view resolution for select_manga field
        $fieldName = 'select_manga';
        $fieldType = 'select2';
        
        $viewPaths = $this->getFieldViewPaths($fieldName, $fieldType);
        
        $this->assertIsArray($viewPaths);
        $this->assertContains('core.base.fields.select_manga', $viewPaths);
        $this->assertContains('crud::fields.select2', $viewPaths);
    }

    /** @test */
    public function it_provides_fallback_field_views()
    {
        $fieldName = 'non_existent_field';
        $fieldType = 'text';
        
        $viewPaths = $this->getFieldViewPaths($fieldName, $fieldType);
        
        $this->assertIsArray($viewPaths);
        $this->assertContains('crud::fields.text', $viewPaths);
    }

    /** @test */
    public function it_handles_mixed_data_text_field_view()
    {
        $fieldName = 'other_name';
        $fieldType = 'mixed_data_text';
        
        $viewPaths = $this->getFieldViewPaths($fieldName, $fieldType);
        
        $this->assertIsArray($viewPaths);
        $this->assertContains('core.base.fields.mixed_data_text', $viewPaths);
    }

    /** @test */
    public function it_sanitizes_field_data_for_display()
    {
        $manga = new Manga();
        
        // Test array data sanitization
        $arrayData = ['Name 1', 'Name 2', 'Name 3'];
        $sanitized = $manga->sanitizeFieldValue($arrayData, true);
        $this->assertEquals('Name 1, Name 2, Name 3', $sanitized);
        
        // Test string data passthrough
        $stringData = 'Simple String';
        $sanitized = $manga->sanitizeFieldValue($stringData, true);
        $this->assertEquals('Simple String', $sanitized);
        
        // Test null handling
        $nullData = null;
        $sanitized = $manga->sanitizeFieldValue($nullData, true);
        $this->assertEquals('', $sanitized);
    }

    /** @test */
    public function it_prepares_field_data_for_crud_operations()
    {
        $manga = new Manga();
        
        // Test text field preparation
        $result = $manga->prepareForCrudField(['Test', 'Value'], 'text');
        $this->assertIsString($result);
        $this->assertEquals('Test, Value', $result);
        
        // Test textarea field preparation
        $result = $manga->prepareForCrudField(['Line 1', 'Line 2'], 'textarea');
        $this->assertIsString($result);
        $this->assertEquals('Line 1, Line 2', $result);
        
        // Test select field preparation (should remain array)
        $result = $manga->prepareForCrudField(['option1', 'option2'], 'select2_multiple');
        $this->assertIsArray($result);
        $this->assertEquals(['option1', 'option2'], $result);
    }

    /** @test */
    public function it_gets_safe_field_values_for_crud_display()
    {
        $manga = new Manga();
        $manga->setAttribute('title', 'Test Manga');
        $manga->setAttribute('other_name', 'Alt Name 1, Alt Name 2');
        $manga->setAttribute('description', 'Test description');
        
        // Test getting field for CRUD display
        $titleForCrud = $manga->getFieldForCrud('title', 'text');
        $this->assertIsString($titleForCrud);
        $this->assertEquals('Test Manga', $titleForCrud);
        
        $otherNameForCrud = $manga->getFieldForCrud('other_name', 'text');
        $this->assertIsString($otherNameForCrud);
        $this->assertEquals('Alt Name 1, Alt Name 2', $otherNameForCrud);
    }

    /** @test */
    public function it_handles_textarea_column_display()
    {
        $manga = new Manga();
        
        // Test array data in textarea column
        $arrayData = ['Description line 1', 'Description line 2'];
        $result = $manga->prepareForCrudField($arrayData, 'textarea');
        
        $this->assertIsString($result);
        $this->assertEquals('Description line 1, Description line 2', $result);
        $this->assertStringNotContainsString('Array', $result);
    }

    /** @test */
    public function it_validates_field_types_before_processing()
    {
        $manga = new Manga();
        
        // Test valid field types
        $validTypes = ['text', 'textarea', 'select2', 'select2_multiple', 'number', 'email'];
        
        foreach ($validTypes as $type) {
            $result = $manga->prepareForCrudField('test value', $type);
            $this->assertNotNull($result);
        }
    }

    /** @test */
    public function it_handles_empty_and_null_field_values()
    {
        $manga = new Manga();
        
        // Test empty string
        $result = $manga->prepareForCrudField('', 'text');
        $this->assertEquals('', $result);
        
        // Test null value
        $result = $manga->prepareForCrudField(null, 'text');
        $this->assertEquals('', $result);
        
        // Test empty array
        $result = $manga->prepareForCrudField([], 'text');
        $this->assertEquals('', $result);
        
        // Test array with empty values
        $result = $manga->prepareForCrudField(['', null, '  ', 'Valid'], 'text');
        $this->assertEquals('Valid', $result);
    }

    /** @test */
    public function it_preserves_html_entities_in_field_values()
    {
        $manga = new Manga();
        
        // Test HTML entities preservation
        $htmlContent = 'Title with &amp; special chars &lt;script&gt;';
        $result = $manga->prepareForCrudField($htmlContent, 'text');
        
        $this->assertEquals($htmlContent, $result);
        $this->assertStringContainsString('&amp;', $result);
        $this->assertStringContainsString('&lt;', $result);
    }

    /** @test */
    public function it_handles_numeric_field_conversion()
    {
        $manga = new Manga();
        
        // Test numeric string conversion
        $result = $manga->validateAndConvertType('123', 'integer');
        $this->assertIsInt($result);
        $this->assertEquals(123, $result);
        
        // Test float conversion
        $result = $manga->validateAndConvertType('12.34', 'float');
        $this->assertIsFloat($result);
        $this->assertEquals(12.34, $result);
        
        // Test invalid numeric conversion
        $result = $manga->validateAndConvertType('not-a-number', 'integer');
        $this->assertEquals(0, $result);
    }

    /** @test */
    public function it_handles_boolean_field_conversion()
    {
        $manga = new Manga();
        
        // Test truthy values
        $truthyValues = [true, 'true', '1', 1, 'yes', 'on'];
        foreach ($truthyValues as $value) {
            $result = $manga->validateAndConvertType($value, 'boolean');
            $this->assertTrue($result, "Value '{$value}' should convert to true");
        }
        
        // Test falsy values
        $falsyValues = [false, 'false', '0', 0, '', null, 'no', 'off'];
        foreach ($falsyValues as $value) {
            $result = $manga->validateAndConvertType($value, 'boolean');
            $this->assertFalse($result, "Value '{$value}' should convert to false");
        }
    }

    /** @test */
    public function it_detects_crud_context_correctly()
    {
        $manga = new Manga();
        
        // Mock request to simulate admin context
        $reflection = new \ReflectionClass($manga);
        $method = $reflection->getMethod('isInCrudContext');
        $method->setAccessible(true);
        
        // In unit tests, should return false by default
        $this->assertFalse($method->invoke($manga));
    }

    /** @test */
    public function it_provides_text_fields_for_display()
    {
        $manga = new Manga([
            'title' => 'Test Manga',
            'other_name' => 'Alt Name 1, Alt Name 2',
            'description' => 'Test description',
            'original_title' => 'Original Title'
        ]);
        
        $textFields = $manga->getTextFieldsForDisplay();
        
        $this->assertIsArray($textFields);
        $this->assertArrayHasKey('title', $textFields);
        $this->assertArrayHasKey('other_name', $textFields);
        $this->assertArrayHasKey('description', $textFields);
        $this->assertArrayHasKey('original_title', $textFields);
        
        // All values should be strings
        foreach ($textFields as $field => $value) {
            $this->assertIsString($value, "Field {$field} should be string");
        }
    }

    /**
     * Helper method to get field view paths (mocked implementation)
     */
    protected function getFieldViewPaths($fieldName, $fieldType)
    {
        $paths = [];
        
        // Add specific field view paths
        $paths[] = "core.base.fields.{$fieldName}";
        $paths[] = "core.base.fields.{$fieldType}";
        
        // Add fallback paths
        $paths[] = "crud::fields.{$fieldType}";
        $paths[] = "crud::fields.text"; // Ultimate fallback
        
        return array_unique($paths);
    }
}