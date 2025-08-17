<!-- select_manga field with robust error handling -->
@php
    // Defensive programming: ensure field has required properties
    $field['name'] = $field['name'] ?? 'manga_id';
    $field['label'] = $field['label'] ?? 'Manga';
    $field['value'] = old($field['name'], $field['value'] ?? '');
    $field['attributes'] = $field['attributes'] ?? [];
    
    // Safely get manga list with error handling
    $mangas = collect();
    try {
        if (class_exists('\Ophim\Core\Models\Manga')) {
            $mangas = \Ophim\Core\Models\Manga::select('id', 'title', 'original_title', 'type', 'status')
                ->orderBy('title')
                ->get();
        }
    } catch (\Exception $e) {
        // Log error but don't break the form
        \Log::warning('Failed to load manga list for select_manga field: ' . $e->getMessage());
    }
@endphp

@include('crud::fields.inc.wrapper_start')
    <label>{!! $field['label'] !!}</label>
    
    @if($mangas->isEmpty())
        {{-- Fallback when no manga available or error occurred --}}
        <div class="alert alert-warning">
            <i class="fa fa-exclamation-triangle"></i>
            No manga available. Please create some manga first or check system configuration.
        </div>
        <input type="hidden" name="{{ $field['name'] }}" value="{{ $field['value'] }}">
    @else
        <select name="{{ $field['name'] }}" 
                class="form-control select2" 
                @include('crud::fields.inc.attributes')
                data-placeholder="Select a manga...">
            <option value="">Select a manga...</option>
            @foreach($mangas as $manga)
                <option value="{{ $manga->id }}" 
                    @if($field['value'] == $manga->id) selected @endif
                    data-type="{{ $manga->type ?? '' }}"
                    data-status="{{ $manga->status ?? '' }}">
                    {{ $manga->title }}
                    @if($manga->original_title && $manga->original_title !== $manga->title)
                        ({{ $manga->original_title }})
                    @endif
                </option>
            @endforeach
        </select>
    @endif

    {{-- HINT --}}
    @if (isset($field['hint']))
        <p class="help-block">{!! $field['hint'] !!}</p>
    @endif
@include('crud::fields.inc.wrapper_end')

@if(!$mangas->isEmpty())
@push('crud_fields_scripts')
<script>
$(document).ready(function() {
    try {
        var $selectElement = $('select[name="{{ $field['name'] }}"]');
        
        // Only initialize select2 if element exists and has options
        if ($selectElement.length && $selectElement.find('option').length > 1) {
            $selectElement.select2({
                theme: 'bootstrap',
                placeholder: 'Select a manga...',
                allowClear: true,
                templateResult: function(manga) {
                    if (!manga.id) return manga.text;
                    
                    var $result = $('<span></span>');
                    $result.text(manga.text);
                    
                    try {
                        if (manga.element && manga.element.dataset) {
                            var type = manga.element.dataset.type;
                            var status = manga.element.dataset.status;
                            
                            if (type && type.trim() !== '') {
                                $result.append(' <small class="text-muted">(' + type + ')</small>');
                            }
                            
                            if (status === 'completed') {
                                $result.prepend('<i class="fa fa-check-circle text-success"></i> ');
                            } else if (status === 'ongoing') {
                                $result.prepend('<i class="fa fa-clock text-warning"></i> ');
                            } else if (status === 'hiatus') {
                                $result.prepend('<i class="fa fa-pause-circle text-info"></i> ');
                            }
                        }
                    } catch (e) {
                        // Silently handle template rendering errors
                        console.warn('Error rendering manga option template:', e);
                    }
                    
                    return $result;
                },
                templateSelection: function(manga) {
                    return manga.text || manga.id;
                }
            });
        }
    } catch (e) {
        console.error('Error initializing select_manga field:', e);
        // Fallback: ensure the select still works as a basic dropdown
        $('select[name="{{ $field['name'] }}"]').addClass('form-control');
    }
});
</script>
@endpush
@endif