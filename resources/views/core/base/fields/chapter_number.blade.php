<!-- chapter_number field -->
@include('crud::fields.inc.wrapper_start')

<label>{!! $field['label'] !!}</label>
<input type="number" 
       name="{{ $field['name'] }}" 
       value="{{ old($field['name']) ?? $field['value'] ?? $field['default'] ?? '' }}" 
       step="0.1" 
       min="0" 
       @include('crud::fields.inc.attributes')>

{{-- HINT --}}
@if (isset($field['hint']))
    <p class="help-block">{!! $field['hint'] !!}</p>
@endif

@include('crud::fields.inc.wrapper_end')

@if ($crud->fieldTypeNotLoaded($field))
    @php
        $crud->markFieldTypeAsLoaded($field);
    @endphp

    {{-- FIELD EXTRA CSS --}}
    @push('crud_fields_styles')
        <style>
            .chapter-number-field {
                position: relative;
            }
            
            .chapter-number-field .help-text {
                font-size: 0.875rem;
                color: #6c757d;
                margin-top: 0.25rem;
            }
        </style>
    @endpush

    {{-- FIELD EXTRA JS --}}
    @push('crud_fields_scripts')
        <script>
            $(document).ready(function() {
                // Add help text for chapter number field
                $('input[name="{{ $field['name'] }}"]').closest('.form-group').addClass('chapter-number-field');
                
                if (!$('input[name="{{ $field['name'] }}"]').siblings('.help-text').length) {
                    $('input[name="{{ $field['name'] }}"]').after(
                        '<div class="help-text">Supports decimal numbers (e.g., 4.5, 4.6) for special chapters</div>'
                    );
                }
                
                // Validate chapter number format
                $('input[name="{{ $field['name'] }}"]').on('input', function() {
                    const value = $(this).val();
                    const isValid = /^\d+(\.\d)?$/.test(value) || value === '';
                    
                    if (!isValid && value !== '') {
                        $(this).addClass('is-invalid');
                        if (!$(this).siblings('.invalid-feedback').length) {
                            $(this).after('<div class="invalid-feedback">Please enter a valid chapter number (e.g., 1, 1.5, 2.0)</div>');
                        }
                    } else {
                        $(this).removeClass('is-invalid');
                        $(this).siblings('.invalid-feedback').remove();
                    }
                });
            });
        </script>
    @endpush
@endif