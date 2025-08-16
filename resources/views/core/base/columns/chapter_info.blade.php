@php
    $value = data_get($entry, $column['name']);
    $column['escaped'] = $column['escaped'] ?? true;
    $column['limit'] = $column['limit'] ?? 40;
    $column['suffix'] = $column['suffix'] ?? '';
@endphp

<div>
    <div class="d-flex justify-content-between align-items-start">
        <div class="flex-grow-1">
            <div class="fw-500 text-primary">
                Chapter {{ $entry->chapter_number }}
                @if($entry->title)
                    - {{ Str::limit($entry->title, 30) }}
                @endif
            </div>
            
            <div class="small text-muted">
                <span class="badge badge-info">{{ $entry->manga->title ?? 'No Manga' }}</span>
                
                @if($entry->volume)
                    <span class="badge badge-secondary">Vol. {{ $entry->volume->volume_number }}</span>
                @endif
                
                @if($entry->page_count)
                    <span class="text-muted">{{ $entry->page_count }} pages</span>
                @endif
                
                @if($entry->published_at)
                    <span class="text-muted">{{ $entry->published_at->format('M d, Y') }}</span>
                @endif
            </div>
            
            @if($entry->is_premium)
                <div class="small">
                    <span class="badge badge-warning">Premium</span>
                </div>
            @endif
        </div>
        
        <div class="text-right">
            @if($entry->view_count > 0)
                <small class="text-muted">
                    <i class="la la-eye"></i> {{ number_format($entry->view_count) }}
                </small>
            @endif
        </div>
    </div>
</div>