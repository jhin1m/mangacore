<!-- select_manga_status field -->
@include('crud::fields.inc.wrapper_start')

<label>{!! $field['label'] !!}</label>
<select name="{{ $field['name'] }}" @include('crud::fields.inc.attributes')>
    @if (isset($field['allows_null']) && $field['allows_null']==true)
        <option value="">-</option>
    @endif

    @php
        $manga_statuses = [
            'ongoing' => 'Ongoing (Đang cập nhật)',
            'completed' => 'Completed (Hoàn thành)', 
            'hiatus' => 'Hiatus (Tạm dừng)',
            'cancelled' => 'Cancelled (Đã hủy)'
        ];
        
        $selected = old($field['name']) ?? $field['value'] ?? $field['default'] ?? 'ongoing';
    @endphp

    @foreach ($manga_statuses as $key => $value)
        <option value="{{ $key }}" @if ($selected == $key) selected @endif>{{ $value }}</option>
    @endforeach
</select>

{{-- HINT --}}
@if (isset($field['hint']))
    <p class="help-block">{!! $field['hint'] !!}</p>
@endif

@include('crud::fields.inc.wrapper_end')