<!-- select_demographic field -->
@include('crud::fields.inc.wrapper_start')

<label>{!! $field['label'] !!}</label>
<select name="{{ $field['name'] }}" @include('crud::fields.inc.attributes')>
    @if (isset($field['allows_null']) && $field['allows_null']==true)
        <option value="">-</option>
    @endif

    @php
        $demographics = [
            'general' => 'General (Tổng quát)',
            'shounen' => 'Shounen (Thiếu niên nam)',
            'seinen' => 'Seinen (Thanh niên nam)', 
            'shoujo' => 'Shoujo (Thiếu niên nữ)',
            'josei' => 'Josei (Thanh niên nữ)',
            'kodomomuke' => 'Kodomomuke (Trẻ em)'
        ];
        
        $selected = old($field['name']) ?? $field['value'] ?? $field['default'] ?? 'general';
    @endphp

    @foreach ($demographics as $key => $value)
        <option value="{{ $key }}" @if ($selected == $key) selected @endif>{{ $value }}</option>
    @endforeach
</select>

{{-- HINT --}}
@if (isset($field['hint']))
    <p class="help-block">{!! $field['hint'] !!}</p>
@endif

@include('crud::fields.inc.wrapper_end')