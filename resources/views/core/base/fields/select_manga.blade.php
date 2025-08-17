<!-- select_manga field -->
@include('crud::fields.inc.wrapper_start')
    <label>{!! $field['label'] !!}</label>
    <select name="{{ $field['name'] }}" 
            class="form-control select2" 
            @include('crud::fields.inc.attributes')
            data-placeholder="Select a manga...">
        <option value="">Select a manga...</option>
        @foreach(\Ophim\Core\Models\Manga::orderBy('title')->get() as $manga)
            <option value="{{ $manga->id }}" 
                @if(old($field['name'], $field['value'] ?? '') == $manga->id) selected @endif
                data-type="{{ $manga->type }}"
                data-status="{{ $manga->status }}">
                {{ $manga->title }}
                @if($manga->original_title && $manga->original_title !== $manga->title)
                    ({{ $manga->original_title }})
                @endif
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
    $('select[name="{{ $field['name'] }}"]').select2({
        theme: 'bootstrap',
        placeholder: 'Select a manga...',
        allowClear: true,
        templateResult: function(manga) {
            if (!manga.id) return manga.text;
            
            var $result = $('<span></span>');
            $result.text(manga.text);
            
            if (manga.element && manga.element.dataset) {
                var type = manga.element.dataset.type;
                var status = manga.element.dataset.status;
                
                if (type) {
                    $result.append(' <small class="text-muted">(' + type + ')</small>');
                }
                
                if (status === 'completed') {
                    $result.prepend('<i class="fa fa-check-circle text-success"></i> ');
                } else if (status === 'ongoing') {
                    $result.prepend('<i class="fa fa-clock text-warning"></i> ');
                }
            }
            
            return $result;
        }
    });
});
</script>
@endpush