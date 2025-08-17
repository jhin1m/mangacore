<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Support\Facades\View;
use Ophim\Core\Traits\Operations\FieldViewFallback;
use Backpack\CRUD\app\Http\Controllers\CrudController;

class FieldViewResolutionTest extends TestCase
{
    use FieldViewFallback;

    /** @test */
    public function it_can_resolve_select_manga_field_view()
    {
        // Test that the select_manga field view exists
        $this->assertTrue(
            View::exists('ophim::base.fields.select_manga') || 
            View::exists('crud::fields.select_manga'),
            'select_manga field view should exist in either ophim or crud namespace'
        );
    }

    /** @test */
    public function it_normalizes_field_definition_correctly()
    {
        $field = [
            'name' => 'manga_id',
            'type' => 'select_manga'
        ];

        $normalized = $this->normalizeFieldDefinition($field);

        $this->assertEquals('manga_id', $normalized['name']);
        $this->assertEquals('select_manga', $normalized['type']);
        $this->assertEquals('Manga id', $normalized['label']);
        $this->assertIsArray($normalized['attributes']);
    }

    /** @test */
    public function it_applies_fallback_for_missing_field_types()
    {
        $field = [
            'name' => 'manga_id',
            'type' => 'select_manga',
            'label' => 'Manga'
        ];

        $fallbackField = $this->applyFieldViewFallback($field);

        // Should fallback to select_from_table
        $this->assertEquals('select_from_table', $fallbackField['type']);
        $this->assertEquals('manga', $fallbackField['entity']);
        $this->assertEquals(\Ophim\Core\Models\Manga::class, $fallbackField['model']);
        $this->assertEquals('title', $fallbackField['attribute']);
    }

    /** @test */
    public function it_handles_manga_status_field_fallback()
    {
        $field = [
            'name' => 'status',
            'type' => 'select_manga_status',
            'label' => 'Status'
        ];

        $fallbackField = $this->applyFieldViewFallback($field);

        $this->assertEquals('select_from_array', $fallbackField['type']);
        $this->assertArrayHasKey('options', $fallbackField);
        $this->assertArrayHasKey('ongoing', $fallbackField['options']);
        $this->assertArrayHasKey('completed', $fallbackField['options']);
    }

    /** @test */
    public function it_handles_unknown_field_types_with_text_fallback()
    {
        $field = [
            'name' => 'unknown_field',
            'type' => 'unknown_field_type',
            'label' => 'Unknown Field'
        ];

        $fallbackField = $this->applyFieldViewFallback($field);

        $this->assertEquals('text', $fallbackField['type']);
        $this->assertStringContains('unknown_field_type', $fallbackField['hint']);
    }

    /** @test */
    public function it_configures_manga_chapters_relationship_fallback()
    {
        $field = [
            'name' => 'chapters',
            'type' => 'manga_chapters',
            'label' => 'Chapters'
        ];

        $fallbackField = $this->applyFieldViewFallback($field);

        $this->assertEquals('relationship', $fallbackField['type']);
        $this->assertEquals('chapters', $fallbackField['entity']);
        $this->assertEquals(\Ophim\Core\Models\Chapter::class, $fallbackField['model']);
        $this->assertTrue($fallbackField['multiple']);
    }
}