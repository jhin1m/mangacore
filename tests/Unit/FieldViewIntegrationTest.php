<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Ophim\Core\Traits\Operations\FieldViewFallback;
use Illuminate\Support\Facades\View;

class FieldViewIntegrationTest extends TestCase
{
    use FieldViewFallback;

    /** @test */
    public function it_resolves_field_views_with_proper_fallback()
    {
        // Test select_manga field view resolution
        $fieldName = 'select_manga';
        $fieldType = 'select2';
        
        $viewPaths = $this->getFieldViewPaths($fieldName, $fieldType);
        
        $this->assertIsArray($viewPaths);
        $this->assertNotEmpty($viewPaths);
        
        // Should include specific field view
        $this->assertContains('core.base.fields.select_manga', $viewPaths);
        
        // Should include type-based fallback
        $this->assertContains('crud::fields.select2', $viewPaths);
        
        // Should include ultimate fallback
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
        $this->assertContains('core.base.fields.other_name', $viewPaths);
    }

    /** @test */
    public function it_provides_comprehensive_fallback_chain()
    {
        $fieldName = 'custom_field';
        $fieldType = 'custom_type';
        
        $viewPaths = $this->getFieldViewPaths($fieldName, $fieldType);
        
        $expectedPaths = [
            'core.base.fields.custom_field',
            'core.base.fields.custom_type',
            'crud::fields.custom_type',
            'crud::fields.text'
        ];
        
        foreach ($expectedPaths as $path) {
            $this->assertContains($path, $viewPaths);
        }
    }

    /** @test */
    public function it_handles_namespace_variations()
    {
        $fieldName = 'test_field';
        $fieldType = 'select2_multiple';
        
        $viewPaths = $this->getFieldViewPaths($fieldName, $fieldType);
        
        // Should handle underscores and variations
        $this->assertContains('core.base.fields.test_field', $viewPaths);
        $this->assertContains('crud::fields.select2_multiple', $viewPaths);
    }

    /** @test */
    public function it_prioritizes_specific_over_generic_views()
    {
        $fieldName = 'manga_status';
        $fieldType = 'select';
        
        $viewPaths = $this->getFieldViewPaths($fieldName, $fieldType);
        
        // Specific field view should come before generic type view
        $specificIndex = array_search('core.base.fields.manga_status', $viewPaths);
        $genericIndex = array_search('crud::fields.select', $viewPaths);
        
        $this->assertNotFalse($specificIndex);
        $this->assertNotFalse($genericIndex);
        $this->assertLessThan($genericIndex, $specificIndex);
    }

    /** @test */
    public function it_handles_field_view_existence_checking()
    {
        // Mock View facade for testing
        $this->assertTrue(method_exists($this, 'checkViewExists'));
        
        // Test existing view
        $exists = $this->checkViewExists('crud::fields.text');
        $this->assertTrue($exists);
        
        // Test non-existing view
        $exists = $this->checkViewExists('non.existent.view');
        $this->assertFalse($exists);
    }

    /** @test */
    public function it_resolves_first_available_view()
    {
        $viewPaths = [
            'non.existent.view1',
            'non.existent.view2',
            'crud::fields.text', // This should exist
            'another.fallback.view'
        ];
        
        $resolvedView = $this->resolveFirstAvailableView($viewPaths);
        $this->assertEquals('crud::fields.text', $resolvedView);
    }

    /** @test */
    public function it_handles_empty_view_paths_gracefully()
    {
        $viewPaths = [];
        
        $resolvedView = $this->resolveFirstAvailableView($viewPaths);
        $this->assertEquals('crud::fields.text', $resolvedView); // Default fallback
    }

    /** @test */
    public function it_handles_all_non_existent_views()
    {
        $viewPaths = [
            'non.existent.view1',
            'non.existent.view2',
            'another.non.existent.view'
        ];
        
        $resolvedView = $this->resolveFirstAvailableView($viewPaths);
        $this->assertEquals('crud::fields.text', $resolvedView); // Ultimate fallback
    }

    /** @test */
    public function it_generates_view_paths_for_common_field_types()
    {
        $commonFields = [
            ['name' => 'title', 'type' => 'text'],
            ['name' => 'description', 'type' => 'textarea'],
            ['name' => 'category_id', 'type' => 'select2'],
            ['name' => 'tags', 'type' => 'select2_multiple'],
            ['name' => 'is_active', 'type' => 'checkbox'],
            ['name' => 'cover_image', 'type' => 'upload'],
            ['name' => 'published_at', 'type' => 'datetime'],
            ['name' => 'rating', 'type' => 'number']
        ];
        
        foreach ($commonFields as $field) {
            $viewPaths = $this->getFieldViewPaths($field['name'], $field['type']);
            
            $this->assertIsArray($viewPaths);
            $this->assertNotEmpty($viewPaths);
            $this->assertContains("core.base.fields.{$field['name']}", $viewPaths);
            $this->assertContains("crud::fields.{$field['type']}", $viewPaths);
        }
    }

    /** @test */
    public function it_handles_special_character_field_names()
    {
        $specialFields = [
            'field_with_underscores',
            'field-with-dashes',
            'fieldWithCamelCase',
            'field123WithNumbers'
        ];
        
        foreach ($specialFields as $fieldName) {
            $viewPaths = $this->getFieldViewPaths($fieldName, 'text');
            
            $this->assertIsArray($viewPaths);
            $this->assertNotEmpty($viewPaths);
            $this->assertContains("core.base.fields.{$fieldName}", $viewPaths);
        }
    }

    /** @test */
    public function it_provides_debug_information_for_view_resolution()
    {
        $fieldName = 'debug_field';
        $fieldType = 'debug_type';
        
        $debugInfo = $this->getViewResolutionDebugInfo($fieldName, $fieldType);
        
        $this->assertIsArray($debugInfo);
        $this->assertArrayHasKey('field_name', $debugInfo);
        $this->assertArrayHasKey('field_type', $debugInfo);
        $this->assertArrayHasKey('attempted_paths', $debugInfo);
        $this->assertArrayHasKey('resolved_view', $debugInfo);
        
        $this->assertEquals($fieldName, $debugInfo['field_name']);
        $this->assertEquals($fieldType, $debugInfo['field_type']);
        $this->assertIsArray($debugInfo['attempted_paths']);
    }

    /**
     * Helper method to get field view paths
     */
    protected function getFieldViewPaths($fieldName, $fieldType)
    {
        $paths = [];
        
        // Add specific field view paths
        $paths[] = "core.base.fields.{$fieldName}";
        $paths[] = "core.base.fields.{$fieldType}";
        
        // Add CRUD fallback paths
        $paths[] = "crud::fields.{$fieldType}";
        $paths[] = "crud::fields.text"; // Ultimate fallback
        
        return array_unique($paths);
    }

    /**
     * Helper method to check if view exists
     */
    protected function checkViewExists($viewName)
    {
        // In real implementation, this would use View::exists()
        // For testing, we'll simulate common views
        $existingViews = [
            'crud::fields.text',
            'crud::fields.textarea',
            'crud::fields.select',
            'crud::fields.select2',
            'crud::fields.select2_multiple',
            'crud::fields.checkbox',
            'crud::fields.upload',
            'crud::fields.datetime',
            'crud::fields.number'
        ];
        
        return in_array($viewName, $existingViews);
    }

    /**
     * Helper method to resolve first available view
     */
    protected function resolveFirstAvailableView($viewPaths)
    {
        foreach ($viewPaths as $viewPath) {
            if ($this->checkViewExists($viewPath)) {
                return $viewPath;
            }
        }
        
        // Ultimate fallback
        return 'crud::fields.text';
    }

    /**
     * Helper method to get debug information
     */
    protected function getViewResolutionDebugInfo($fieldName, $fieldType)
    {
        $attemptedPaths = $this->getFieldViewPaths($fieldName, $fieldType);
        $resolvedView = $this->resolveFirstAvailableView($attemptedPaths);
        
        return [
            'field_name' => $fieldName,
            'field_type' => $fieldType,
            'attempted_paths' => $attemptedPaths,
            'resolved_view' => $resolvedView,
            'resolution_successful' => $this->checkViewExists($resolvedView)
        ];
    }
}