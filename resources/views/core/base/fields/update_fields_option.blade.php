@php
$value = isset($field) ? old_empty_or_null($field['name'], '') ?? ($field['value'] ?? null) : null;
@endphp

@php
$progress = [
    'chapters' => 'Chapter mới',
    'status' => 'Trạng thái manga',
    'total_chapters' => 'Tổng số chapter',
    'total_volumes' => 'Tổng số volume',
    'publication_year' => 'Năm xuất bản',
];

$info = [
    'title' => 'Tên manga',
    'original_title' => 'Tên gốc manga',
    'description' => 'Mô tả nội dung manga',
    'cover_image' => 'Ảnh bìa',
    'banner_image' => 'Ảnh banner',
    'type' => 'Loại manga',
    'demographic' => 'Đối tượng độc giả',
    'reading_direction' => 'Hướng đọc',
    'rating' => 'Đánh giá',
    'is_completed' => 'Đánh dấu hoàn thành',
    'is_recommended' => 'Đánh dấu đề xuất',
    'is_adult_content' => 'Nội dung người lớn',
];

$relations = [
    'type' => 'Loại manga',
    'authors' => 'Tác giả',
    'artists' => 'Họa sĩ',
    'categories' => 'Thể loại',
    'origins' => 'Xuất xứ',
    'tags' => 'Từ khóa',
    'publishers' => 'Nhà xuất bản',
];
@endphp

<div class="px-3 py-2">
    <div class="row mb-3">
        <div class="col-12 px-0">
            <input class="checkall" data-target="progress-checkbox" id="progress-all" type="checkbox">
            <label for="progress-all">Tiến độ manga</label>
        </div>
        @foreach ($progress as $key => $option)
            <div class="col-12 col-md-6 form-check checkbox">
                <input class="form-check-input progress-checkbox" id="progress-{{ $loop->index }}" type="checkbox"
                    name="fields[]" value="{{ $key }}" @if (is_null($value) || in_array($key, $value)) checked @endif>
                <label class="d-inline" for="progress-{{ $loop->index }}">{{ $option }}</label>
            </div>
        @endforeach
    </div>
    <div class="row mb-3">
        <div class="col-12 px-0">
            <input class="checkall" data-target="info-checkbox" id="info-all" type="checkbox">
            <label for="info-all">Thông tin manga</label>
        </div>
        @foreach ($info as $key => $option)
            <div class="col-12 col-md-6 form-check checkbox">
                <input class="form-check-input info-checkbox" id="info-{{ $loop->index }}" type="checkbox"
                    name="fields[]" value="{{ $key }}" @if (is_null($value) || in_array($key, $value)) checked @endif>
                <label class="d-inline" for="info-{{ $loop->index }}">{{ $option }}</label>
            </div>
        @endforeach
    </div>
    <div class="row mb-3">
        <div class="col-12 px-0">
            <input class="checkall" data-target="relation-checkbox" id="relation-all" type="checkbox">
            <label for="relation-all">Phân loại</label>
        </div>
        @foreach ($relations as $key => $option)
            <div class="col-12 col-md-6 form-check">
                <input class="form-check-input relation-checkbox" id="relation-{{ $loop->index }}" type="checkbox"
                    name="fields[]" value="{{ $key }}" @if (is_null($value) || in_array($key, $value)) checked @endif>
                <label class="d-inline" for="relation-{{ $loop->index }}">{{ $option }}</label>
            </div>
        @endforeach
    </div>

</div>

{{-- FIELD JS - will be loaded in the after_scripts section --}}
@push('after_scripts')
    <script>
        $('.checkall').change(function() {
            $(`.${$(this).data('target')}`).prop('checked', this.checked);
        })
    </script>
@endpush
