<?php

namespace Ophim\Core\Traits\Operations;

use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Log;

trait FieldViewFallback
{
    /**
     * Add field view namespaces for manga-specific fields
     */
    protected function setupFieldViewNamespaces()
    {
        // Add our custom field view namespace
        $this->crud->addViewNamespaceFor('fields', 'ophim::base.fields');
        
        // Add fallback namespaces
        $this->crud->addViewNamespaceFor('fields', 'resources.views.vendor.hacoidev.crud.fields');
    }

    /**
     * Add a field with fallback view resolution
     * 
     * @param array $field
     * @return void
     */
    protected function addFieldWithFallback(array $field)
    {
        // Ensure field has required properties
        $field = $this->normalizeFieldDefinition($field);
        
        // Check if the field view exists, provide fallback if not
        $field = $this->ensureFieldViewExists($field);
        
        // Add the field to CRUD
        $this->crud->addField($field);
    }

    /**
     * Normalize field definition with defaults
     * 
     * @param array $field
     * @return array
     */
    protected function normalizeFieldDefinition(array $field)
    {
        // Set defaults
        $field['name'] = $field['name'] ?? 'unknown_field';
        $field['type'] = $field['type'] ?? 'text';
        $field['label'] = $field['label'] ?? ucfirst(str_replace('_', ' ', $field['name']));
        
        // Ensure attributes is an array
        if (!isset($field['attributes']) || !is_array($field['attributes'])) {
            $field['attributes'] = [];
        }
        
        return $field;
    }

    /**
     * Ensure field view exists, provide fallback if not
     * 
     * @param array $field
     * @return array
     */
    protected function ensureFieldViewExists(array $field)
    {
        $fieldType = $field['type'];
        $viewNamespaces = $this->crud->getViewNamespacesFor('fields');
        
        // Check if any view exists for this field type
        $viewExists = false;
        foreach ($viewNamespaces as $namespace) {
            $viewPath = $namespace . '.' . $fieldType;
            if (View::exists($viewPath)) {
                $viewExists = true;
                break;
            }
        }
        
        if (!$viewExists) {
            Log::warning("Field view '{$fieldType}' not found, applying fallback", [
                'field_name' => $field['name'],
                'field_type' => $fieldType,
                'searched_namespaces' => $viewNamespaces
            ]);
            
            // Apply fallback based on field type
            $field = $this->applyFieldViewFallback($field);
        }
        
        return $field;
    }

    /**
     * Apply fallback view for missing field types
     * 
     * @param array $field
     * @return array
     */
    protected function applyFieldViewFallback(array $field)
    {
        $fieldType = $field['type'];
        
        // Define fallback mappings
        $fallbackMappings = [
            'select_manga' => 'select_from_table',
            'select_manga_status' => 'select_from_array',
            'select_manga_type' => 'select_from_array',
            'manga_chapters' => 'relationship',
            'manga_tags' => 'select_multiple',
            'manga_categories' => 'select_multiple',
        ];
        
        if (isset($fallbackMappings[$fieldType])) {
            $fallbackType = $fallbackMappings[$fieldType];
            
            // Configure fallback field based on original type
            $field = $this->configureFallbackField($field, $fallbackType);
            
            Log::info("Applied fallback view for field", [
                'original_type' => $fieldType,
                'fallback_type' => $fallbackType,
                'field_name' => $field['name']
            ]);
        } else {
            // Ultimate fallback to text field
            $field['type'] = 'text';
            $field['hint'] = $field['hint'] ?? "Field type '{$fieldType}' not available, using text input.";
            
            Log::warning("No specific fallback for field type '{$fieldType}', using text field", [
                'field_name' => $field['name']
            ]);
        }
        
        return $field;
    }

    /**
     * Configure fallback field based on original type
     * 
     * @param array $field
     * @param string $fallbackType
     * @return array
     */
    protected function configureFallbackField(array $field, string $fallbackType)
    {
        $originalType = $field['type'];
        $field['type'] = $fallbackType;
        
        switch ($originalType) {
            case 'select_manga':
                if ($fallbackType === 'select_from_table') {
                    $field['entity'] = 'manga';
                    $field['model'] = \Ophim\Core\Models\Manga::class;
                    $field['attribute'] = 'title';
                    $field['placeholder'] = 'Select a manga...';
                }
                break;
                
            case 'select_manga_status':
                if ($fallbackType === 'select_from_array') {
                    $field['options'] = [
                        'ongoing' => 'Ongoing',
                        'completed' => 'Completed',
                        'hiatus' => 'Hiatus',
                        'cancelled' => 'Cancelled'
                    ];
                }
                break;
                
            case 'select_manga_type':
                if ($fallbackType === 'select_from_array') {
                    $field['options'] = [
                        'manga' => 'Manga',
                        'manhwa' => 'Manhwa',
                        'manhua' => 'Manhua',
                        'webtoon' => 'Webtoon',
                        'novel' => 'Light Novel'
                    ];
                }
                break;
                
            case 'manga_chapters':
                if ($fallbackType === 'relationship') {
                    $field['type'] = 'relationship';
                    $field['name'] = $field['name'] ?? 'chapters';
                    $field['entity'] = 'chapters';
                    $field['model'] = \Ophim\Core\Models\Chapter::class;
                    $field['attribute'] = 'title';
                    $field['multiple'] = true;
                }
                break;
                
            case 'manga_tags':
                if ($fallbackType === 'select_multiple') {
                    $field['entity'] = 'tags';
                    $field['model'] = \Ophim\Core\Models\Tag::class;
                    $field['attribute'] = 'name';
                    $field['pivot'] = true;
                }
                break;
                
            case 'manga_categories':
                if ($fallbackType === 'select_multiple') {
                    $field['entity'] = 'categories';
                    $field['model'] = \Ophim\Core\Models\Category::class;
                    $field['attribute'] = 'name';
                    $field['pivot'] = true;
                }
                break;
        }
        
        return $field;
    }

    /**
     * Setup field view fallbacks in the controller
     * Call this in your setupCreateOperation() or setupUpdateOperation()
     */
    protected function setupFieldViewFallbacks()
    {
        $this->setupFieldViewNamespaces();
    }
}