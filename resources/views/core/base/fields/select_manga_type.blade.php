<!-- select_manga_type field with robust error handling -->
@php
    // Defensive programming: ensure field has required properties
    $field['name'] = $field['name'] ?? 'type';
    $field['label'] = $field['label'] ?? 'Type';
    $field['value'] = old($field['name'], $field['value'] ?? $field['default'] ?? '');
    $field['attributes'] = $field['attributes'] ?? [];
    
    // Define type options with fallback
    $typeOptions = $field['options'] ?? [
        'manga' => 'Manga (Japanese)',
        'manhwa' => 'Manhwa (Korean)', 
        'manhua' => 'Manhua (Chinese)',
        'webtoon' => 'Webtoon (Digital)',
        'novel' => 'Light Novel',
        'doujinshi' => 'Doujinshi'
    ];
    
    $allowsNull = $field['allows_null'] ?? false;
@endphp

@include('crud::fields.inc.wrapper_start')
    <label>{!! $field['label'] !!}</label>
    
    <select name="{{ $field['name'] }}" 
            class="form-control" 
            @include('crud::fields.inc.attributes')>
        @if($allowsNull)
            <option value="">Select type...</option>
        @endif
        
        @foreach($typeOptions as $value => $label)
            <option value="{{ $value }}" 
                @if($field['value'] == $value) selected @endif
                data-origin="{{ $value === 'manga' ? 'Japan' : ($value === 'manhwa' ? 'Korea' : ($value === 'manhua' ? 'China' : 'Various')) }}">
                {{ $label }}
            </option>
        @endforeach
    </select>

    {{-- HINT --}}
    @if (isset($field['hint']))
        <p class="help-block">{!! $field['hint'] !!}</p>
    @endif
@include('crud::fields.inc.wrapper_end')

@push('crud_fields_scripts')
<script>
$(document).ready(function() {
    try {
        var $selectElement = $('select[name="{{ $field['name'] }}"]');
        
        // Add type-specific information display
        $selectElement.on('change', function() {
            var $selected = $(this).find('option:selected');
            var origin = $selected.data('origin');
            var $wrapper = $(this).closest('.form-group');
            
            // Remove existing origin info
            $wrapper.find('.origin-info').remove();
            
            // Add origin information
            if (origin && origin !== 'Various') {
                var $info = $('<small class="origin-info text-muted d-block mt-1">Origin: ' + origin + '</small>');
                $(this).after($info);
            }
        });
        
        // Trigger change on load
        $selectElement.trigger('change');
    } catch (e) {
        console.error('Error initializing select_manga_type field:', e);
    }
});
</script>
@endpush