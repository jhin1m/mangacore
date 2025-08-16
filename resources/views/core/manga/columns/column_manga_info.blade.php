@php
    $value = data_get($entry, $column['name']);
    $column['escaped'] = $column['escaped'] ?? true;
    $column['limit'] = $column['limit'] ?? 40;
    $column['suffix'] = $column['suffix'] ?? '';

    if(!empty($value)) {
        $value = Str::limit(strip_tags($value), $column['limit'], $column['suffix']);
    }
@endphp

<div>
    <div class="d-flex">
        <div class="flex-grow-1">
            <div class="fw-500 text-primary">
                {{ $entry->title }}
                @if($entry->original_title && $entry->original_title != $entry->title)
                    <small class="text-muted">{{ $entry->original_title }}</small>
                @endif
            </div>
            
            <div class="small text-muted">
                <span class="badge badge-secondary">{{ $entry->getType() }}</span>
                <span class="badge badge-{{ $entry->status == 'completed' ? 'success' : ($entry->status == 'ongoing' ? 'primary' : 'warning') }}">
                    {{ $entry->getStatus() }}
                </span>
                <span class="badge badge-info">{{ $entry->getDemographic() }}</span>
                
                @if($entry->publication_year)
                    <span class="text-muted">{{ $entry->publication_year }}</span>
                @endif
                
                @if($entry->total_chapters)
                    <span class="text-muted">{{ $entry->total_chapters }} chương</span>
                @endif
                
                @if($entry->total_volumes)
                    <span class="text-muted">{{ $entry->total_volumes }} tập</span>
                @endif
            </div>
            
            @if($entry->other_name && is_array($entry->other_name) && count($entry->other_name) > 0)
                <div class="small text-muted">
                    <strong>Tên khác:</strong> {{ implode(', ', array_slice($entry->other_name, 0, 2)) }}
                    @if(count($entry->other_name) > 2)
                        <span class="text-muted">...</span>
                    @endif
                </div>
            @endif
        </div>
        
        <div class="text-right">
            @if($entry->is_recommended)
                <i class="la la-star text-warning" title="Đề cử"></i>
            @endif
            @if($entry->is_adult_content)
                <i class="la la-exclamation-triangle text-danger" title="Nội dung người lớn"></i>
            @endif
        </div>
    </div>
</div>