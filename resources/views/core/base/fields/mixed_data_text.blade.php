{{-- Mixed data text field - handles both arrays and strings gracefully --}}
@include('crud::fields.inc.wrapper_start')
    <label>{!! $field['label'] !!}</label>
    @include('crud::fields.inc.translatable_icon')

    @php
        // Get the field value with comprehensive fallback handling
        $fieldValue = old_empty_or_null($field['name'], '') ?? $field['value'] ?? $field['default'] ?? '';
        
        // Handle different data types gracefully
        if (is_array($fieldValue)) {
            // Convert array to comma-separated string
            $fieldValue = implode(', ', array_filter(array_map('trim', $fieldValue)));
        } elseif (is_object($fieldValue)) {
            // Handle objects (like collections) by converting to string
            $fieldValue = method_exists($fieldValue, '__toString') ? (string) $fieldValue : '';
        } elseif (is_null($fieldValue)) {
            $fieldValue = '';
        } else {
            // Ensure we have a string
            $fieldValue = (string) $fieldValue;
        }
        
        // Trim whitespace
        $fieldValue = trim($fieldValue);
    @endphp

    <input
        type="text"
        name="{{ $field['name'] }}"
        value="{{ $fieldValue }}"
        @include('crud::fields.inc.attributes')
    >

    {{-- HINT --}}
    @if (isset($field['hint']))
        <p class="help-block">{!! $field['hint'] !!}</p>
    @endif
@include('crud::fields.inc.wrapper_end')

{{-- Add JavaScript for enhanced validation --}}
@push('crud_fields_scripts')
<script>
$(document).ready(function() {
    // Add validation for comma-separated values if needed
    $('input[name="{{ $field['name'] }}"]').on('blur', function() {
        var value = $(this).val().trim();
        if (value) {
            // Clean up multiple commas and spaces
            value = value.replace(/,+/g, ',').replace(/,\s*,/g, ',').replace(/^\s*,|,\s*$/g, '');
            $(this).val(value);
        }
    });
});
</script>
@endpush