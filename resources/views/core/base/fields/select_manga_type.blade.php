<!-- select_manga_type field -->
@include('crud::fields.inc.wrapper_start')

<label>{!! $field['label'] !!}</label>
<select name="{{ $field['name'] }}" @include('crud::fields.inc.attributes')>
    @if (isset($field['allows_null']) && $field['allows_null']==true)
        <option value="">-</option>
    @endif

    @php
        $manga_types = [
            'manga' => 'Manga (Japanese)',
            'manhwa' => 'Manhwa (Korean)', 
            'manhua' => 'Manhua (Chinese)',
            'webtoon' => 'Webtoon (Digital)'
        ];
        
        $selected = old($field['name']) ?? $field['value'] ?? $field['default'] ?? 'manga';
    @endphp

    @foreach ($manga_types as $key => $value)
        <option value="{{ $key }}" @if ($selected == $key) selected @endif>{{ $value }}</option>
    @endforeach
</select>

{{-- HINT --}}
@if (isset($field['hint']))
    <p class="help-block">{!! $field['hint'] !!}</p>
@endif

@include('crud::fields.inc.wrapper_end')