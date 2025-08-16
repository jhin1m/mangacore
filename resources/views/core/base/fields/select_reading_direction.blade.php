<!-- select_reading_direction field -->
@include('crud::fields.inc.wrapper_start')

<label>{!! $field['label'] !!}</label>
<select name="{{ $field['name'] }}" @include('crud::fields.inc.attributes')>
    @if (isset($field['allows_null']) && $field['allows_null']==true)
        <option value="">-</option>
    @endif

    @php
        $reading_directions = [
            'ltr' => 'Left to Right (Manga)',
            'rtl' => 'Right to Left (Traditional Manga)', 
            'vertical' => 'Vertical (Webtoon)'
        ];
        
        $selected = old($field['name']) ?? $field['value'] ?? $field['default'] ?? 'ltr';
    @endphp

    @foreach ($reading_directions as $key => $value)
        <option value="{{ $key }}" @if ($selected == $key) selected @endif>{{ $value }}</option>
    @endforeach
</select>

{{-- HINT --}}
@if (isset($field['hint']))
    <p class="help-block">{!! $field['hint'] !!}</p>
@endif

@include('crud::fields.inc.wrapper_end')