# Field View Fallback System

## Overview

The Field View Fallback System provides robust error handling and fallback mechanisms for CRUD field views in the manga management system. This system ensures that forms continue to work even when custom field views are missing or fail to load.

## Features

### 1. Automatic Field View Resolution
- Searches multiple view namespaces for field views
- Provides intelligent fallbacks for missing field types
- Logs warnings when fallbacks are applied

### 2. Error Recovery
- Graceful handling of missing field views
- Defensive programming with type checking
- JavaScript error handling for enhanced fields

### 3. Custom Field Types
- `select_manga`: Enhanced manga selection with search and status indicators
- `select_manga_status`: Manga status selection
- `select_manga_type`: Manga type selection
- `manga_chapters`: Chapter relationship management
- `manga_tags`: Tag selection with pivot support
- `manga_categories`: Category selection with pivot support

## Usage

### In Controllers

```php
use Ophim\Core\Traits\Operations\FieldViewFallback;

class YourCrudController extends CrudController
{
    use FieldViewFallback;
    
    protected function setupCreateOperation()
    {
        // Setup field view fallbacks
        $this->setupFieldViewFallbacks();
        
        // Add fields with fallback support
        $this->addFieldWithFallback([
            'name' => 'manga_id',
            'label' => 'Manga',
            'type' => 'select_manga',
            'tab' => 'Basic Info'
        ]);
    }
}
```

### Field View Namespaces

The system searches for field views in the following order:
1. `ophim::base.fields` - Custom manga-specific fields
2. `crud::fields` - Standard Backpack CRUD fields

### Fallback Mappings

| Original Field Type | Fallback Type | Configuration |
|-------------------|---------------|---------------|
| `select_manga` | `select_from_table` | Uses Manga model with title attribute |
| `select_manga_status` | `select_from_array` | Predefined status options |
| `select_manga_type` | `select_from_array` | Predefined type options |
| `manga_chapters` | `relationship` | Chapter relationship with multiple selection |
| `manga_tags` | `select_multiple` | Tag model with pivot support |
| `manga_categories` | `select_multiple` | Category model with pivot support |
| Unknown types | `text` | Basic text input with warning hint |

## Error Handling

### View Level
- Defensive programming with null checks
- Graceful degradation when data is unavailable
- User-friendly error messages

### JavaScript Level
- Try-catch blocks around Select2 initialization
- Fallback to basic select when enhanced features fail
- Console warnings for debugging

### Controller Level
- Automatic fallback field type resolution
- Logging of fallback applications
- Validation of field definitions

## Configuration

### Service Provider Registration

The field view namespaces are automatically registered in `OphimServiceProvider`:

```php
protected function registerFieldViewNamespaces()
{
    $existingNamespaces = config('backpack.crud.view_namespaces.fields', []);
    
    $customNamespaces = array_merge([
        'ophim::base.fields',
    ], $existingNamespaces);
    
    config(['backpack.crud.view_namespaces.fields' => $customNamespaces]);
}
```

### Custom Field Views

Place custom field views in:
- `resources/views/core/base/fields/` (published to vendor directory)
- `resources/views/vendor/hacoidev/crud/fields/` (direct override)

## Testing

Run the field view resolution tests:

```bash
php artisan test tests/Unit/FieldViewResolutionTest.php
```

## Troubleshooting

### Field View Not Found
1. Check if the field view file exists in the correct location
2. Verify the view namespace is registered
3. Check the logs for fallback application messages

### JavaScript Errors
1. Check browser console for Select2 initialization errors
2. Verify jQuery and Select2 are loaded
3. Check for conflicting JavaScript

### Fallback Not Working
1. Verify the FieldViewFallback trait is used in the controller
2. Check that `setupFieldViewFallbacks()` is called
3. Use `addFieldWithFallback()` instead of direct `CRUD::addField()`

## Logging

The system logs the following events:
- Warning when field views are not found
- Info when fallbacks are applied
- Warning when no specific fallback exists

Check logs at `storage/logs/laravel.log` for troubleshooting information.