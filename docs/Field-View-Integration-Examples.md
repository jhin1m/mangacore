# Field View Integration Examples

## Basic Usage in Controllers

### MangaCrudController Example

```php
<?php

namespace Ophim\Core\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use Ophim\Core\Traits\Operations\FieldViewFallback;

class MangaCrudController extends CrudController
{
    use FieldViewFallback;
    
    protected function setupCreateOperation()
    {
        // Setup field view fallbacks
        $this->setupFieldViewFallbacks();
        
        // Basic manga information
        $this->addFieldWithFallback([
            'name' => 'title',
            'label' => 'Title',
            'type' => 'text',
            'tab' => 'Basic Info'
        ]);
        
        $this->addFieldWithFallback([
            'name' => 'type',
            'label' => 'Type',
            'type' => 'select_manga_type',
            'tab' => 'Basic Info'
        ]);
        
        $this->addFieldWithFallback([
            'name' => 'status',
            'label' => 'Status',
            'type' => 'select_manga_status',
            'tab' => 'Basic Info'
        ]);
        
        // Relationships with fallback
        $this->addFieldWithFallback([
            'name' => 'categories',
            'label' => 'Categories',
            'type' => 'manga_categories',
            'tab' => 'Relationships'
        ]);
        
        $this->addFieldWithFallback([
            'name' => 'tags',
            'label' => 'Tags',
            'type' => 'manga_tags',
            'tab' => 'Relationships'
        ]);
    }
}
```

### ChapterCrudController Example

```php
<?php

namespace Ophim\Core\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use Ophim\Core\Traits\Operations\FieldViewFallback;

class ChapterCrudController extends CrudController
{
    use FieldViewFallback;
    
    protected function setupCreateOperation()
    {
        $this->setupFieldViewFallbacks();
        
        // Manga selection with fallback
        $this->addFieldWithFallback([
            'name' => 'manga_id',
            'label' => 'Manga',
            'type' => 'select_manga',
            'tab' => 'Basic Info',
            'hint' => 'Select the manga this chapter belongs to'
        ]);
        
        // Chapter management with fallback
        $this->addFieldWithFallback([
            'name' => 'pages',
            'label' => 'Pages',
            'type' => 'manga_chapters', // Will fallback to relationship
            'tab' => 'Content'
        ]);
    }
}
```

## Custom Field View Creation

### Creating a New Field View

1. Create the field view file:
```php
<!-- resources/views/core/base/fields/my_custom_field.blade.php -->
@php
    // Defensive programming
    $field['name'] = $field['name'] ?? 'custom_field';
    $field['label'] = $field['label'] ?? 'Custom Field';
    $field['value'] = old($field['name'], $field['value'] ?? '');
@endphp

@include('crud::fields.inc.wrapper_start')
    <label>{!! $field['label'] !!}</label>
    
    <!-- Your custom field implementation -->
    <input type="text" 
           name="{{ $field['name'] }}" 
           value="{{ $field['value'] }}"
           class="form-control"
           @include('crud::fields.inc.attributes')>
    
    @if (isset($field['hint']))
        <p class="help-block">{!! $field['hint'] !!}</p>
    @endif
@include('crud::fields.inc.wrapper_end')
```

2. Add fallback mapping to the trait:
```php
// In FieldViewFallback trait
$fallbackMappings = [
    'my_custom_field' => 'text', // Fallback to text field
    // ... other mappings
];
```

## Error Handling Patterns

### View Level Error Handling

```php
@php
    // Safe data retrieval
    $items = collect();
    try {
        if (class_exists('\App\Models\MyModel')) {
            $items = \App\Models\MyModel::all();
        }
    } catch (\Exception $e) {
        \Log::warning('Failed to load items: ' . $e->getMessage());
    }
@endphp

@if($items->isEmpty())
    <div class="alert alert-warning">
        <i class="fa fa-exclamation-triangle"></i>
        No items available.
    </div>
    <input type="hidden" name="{{ $field['name'] }}" value="{{ $field['value'] }}">
@else
    <!-- Normal field rendering -->
@endif
```

### JavaScript Error Handling

```javascript
@push('crud_fields_scripts')
<script>
$(document).ready(function() {
    try {
        var $element = $('select[name="{{ $field['name'] }}"]');
        
        if ($element.length) {
            $element.select2({
                // configuration
            });
        }
    } catch (e) {
        console.error('Error initializing field:', e);
        // Fallback behavior
        $('select[name="{{ $field['name'] }}"]').addClass('form-control');
    }
});
</script>
@endpush
```

## Testing Field Views

### Manual Testing Checklist

1. **Field Rendering**
   - [ ] Field displays correctly with data
   - [ ] Field displays correctly without data
   - [ ] Field handles null/empty values gracefully

2. **Error Scenarios**
   - [ ] Field works when model class doesn't exist
   - [ ] Field works when database is unavailable
   - [ ] Field works when JavaScript fails to load

3. **Fallback Behavior**
   - [ ] Fallback field type is applied correctly
   - [ ] Fallback configuration is appropriate
   - [ ] User can still complete the form

### Browser Testing

```javascript
// Test in browser console
// 1. Check if field is initialized
$('select[name="manga_id"]').data('select2');

// 2. Test error handling
$('select[name="manga_id"]').select2('destroy');

// 3. Check fallback behavior
// Remove field view file and reload page
```

## Performance Considerations

### Optimizing Field Views

1. **Lazy Loading**
```php
@php
    // Only load data when needed
    $items = isset($field['load_data']) && $field['load_data'] 
        ? \App\Models\MyModel::all() 
        : collect();
@endphp
```

2. **Caching**
```php
@php
    $cacheKey = 'field_data_' . $field['name'];
    $items = cache()->remember($cacheKey, 300, function() {
        return \App\Models\MyModel::all();
    });
@endphp
```

3. **Pagination for Large Datasets**
```php
@php
    $items = \App\Models\MyModel::limit(100)->get();
@endphp
```

## Troubleshooting

### Common Issues

1. **Field View Not Found**
   - Check file path and naming
   - Verify namespace registration
   - Check logs for fallback messages

2. **JavaScript Errors**
   - Check browser console
   - Verify jQuery/Select2 loading
   - Test fallback behavior

3. **Data Not Loading**
   - Check model class exists
   - Verify database connection
   - Check error logs

### Debug Mode

Enable debug logging in your controller:
```php
protected function setupCreateOperation()
{
    // Enable debug logging
    \Log::info('Setting up create operation for ' . static::class);
    
    $this->setupFieldViewFallbacks();
    
    // ... field definitions
}
```