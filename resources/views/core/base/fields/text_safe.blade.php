{{-- Enhanced text field with array data handling --}}
@include('crud::fields.inc.wrapper_start')
    <label>{!! $field['label'] !!}</label>
    @include('crud::fields.inc.translatable_icon')

    @php
        // Defensive type checking - convert arrays to strings before rendering
        $fieldValue = old_empty_or_null($field['name'], '') ?? $field['value'] ?? $field['default'] ?? '';
        
        // Handle array data gracefully
        if (is_array($fieldValue)) {
            $fieldValue = implode(', ', array_filter($fieldValue));
        }
        
        // Ensure we have a string for htmlspecialchars()
        $fieldValue = (string) $fieldValue;
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