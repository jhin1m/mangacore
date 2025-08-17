<!-- select_manga_status field with robust error handling -->
@php
    // Defensive programming: ensure field has required properties
    $field['name'] = $field['name'] ?? 'status';
    $field['label'] = $field['label'] ?? 'Status';
    $field['value'] = old($field['name'], $field['value'] ?? '');
    $field['attributes'] = $field['attributes'] ?? [];
    
    // Define status options with fallback
    $statusOptions = $field['options'] ?? [
        'ongoing' => 'Ongoing',
        'completed' => 'Completed',
        'hiatus' => 'Hiatus',
        'cancelled' => 'Cancelled',
        'draft' => 'Draft'
    ];
@endphp

@include('crud::fields.inc.wrapper_start')
    <label>{!! $field['label'] !!}</label>
    
    <select name="{{ $field['name'] }}" 
            class="form-control" 
            @include('crud::fields.inc.attributes')>
        <option value="">Select status...</option>
        @foreach($statusOptions as $value => $label)
            <option value="{{ $value }}" 
                @if($field['value'] == $value) selected @endif>
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
        
        // Add status-specific styling
        $selectElement.on('change', function() {
            var status = $(this).val();
            var $wrapper = $(this).closest('.form-group');
            
            // Remove existing status classes
            $wrapper.removeClass('status-ongoing status-completed status-hiatus status-cancelled status-draft');
            
            // Add status-specific class
            if (status) {
                $wrapper.addClass('status-' + status);
            }
        });
        
        // Trigger change on load to set initial styling
        $selectElement.trigger('change');
    } catch (e) {
        console.error('Error initializing select_manga_status field:', e);
    }
});
</script>
@endpush

@push('crud_fields_styles')
<style>
.status-ongoing { border-left: 3px solid #28a745; }
.status-completed { border-left: 3px solid #007bff; }
.status-hiatus { border-left: 3px solid #ffc107; }
.status-cancelled { border-left: 3px solid #dc3545; }
.status-draft { border-left: 3px solid #6c757d; }
</style>
@endpush