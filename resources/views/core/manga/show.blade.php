@extends('themes.layout')

@section('title', $manga->getTitle())

@section('content')
<div class="manga-detail-container">
    <div class="container">
        <!-- Manga Header -->
        <div class="manga-header row">
            <div class="col-md-3">
                <div class="manga-cover">
                    <img src="{{ $manga->getCoverUrl() }}" alt="{{ $manga->title }}" class="img-fluid rounded">
                    @if($readingProgress)
                        <div class="reading-progress-badge">
                            <small>{{ ReadingProgress::getProgressPercentage($manga->id) }}% Complete</small>
                        </div>
                    @endif
                </div>
            </div>
            <div class="col-md-9">
                <div class="manga-info">
                    <h1 class="manga-title">{{ $manga->title }}</h1>
                    @if($manga->original_title)
                        <h2 class="original-title text-muted">{{ $manga->original_title }}</h2>
                    @endif
                    
                    <div class="manga-meta">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Type:</strong> {{ $manga->getType() }}</p>
                                <p><strong>Status:</strong> {{ $manga->getStatus() }}</p>
                                <p><strong>Demographic:</strong> {{ $manga->getDemographic() }}</p>
                                @if($manga->publication_year)
                                    <p><strong>Year:</strong> {{ $manga->publication_year }}</p>
                                @endif
                            </div>
                            <div class="col-md-6">
                                <p><strong>Rating:</strong> {{ $manga->getRating() }}/10</p>
                                <p><strong>Views:</strong> {{ number_format($manga->view_count) }}</p>
                                @if($manga->total_chapters)
                                    <p><strong>Chapters:</strong> {{ $manga->total_chapters }}</p>
                                @endif
                                @if($manga->total_volumes)
                                    <p><strong>Volumes:</strong> {{ $manga->total_volumes }}</p>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Authors and Artists -->
                    @if($manga->authors->count() > 0)
                        <p><strong>Authors:</strong> 
                            @foreach($manga->authors as $author)
                                <span class="badge badge-secondary">{{ $author->name }}</span>
                            @endforeach
                        </p>
                    @endif
                    
                    @if($manga->artists->count() > 0)
                        <p><strong>Artists:</strong> 
                            @foreach($manga->artists as $artist)
                                <span class="badge badge-secondary">{{ $artist->name }}</span>
                            @endforeach
                        </p>
                    @endif

                    <!-- Categories and Tags -->
                    @if($manga->categories->count() > 0)
                        <p><strong>Categories:</strong> 
                            @foreach($manga->categories as $category)
                                <span class="badge badge-primary">{{ $category->name }}</span>
                            @endforeach
                        </p>
                    @endif
                    
                    @if($manga->tags->count() > 0)
                        <p><strong>Tags:</strong> 
                            @foreach($manga->tags as $tag)
                                <span class="badge badge-info">{{ $tag->name }}</span>
                            @endforeach
                        </p>
                    @endif

                    <!-- Action Buttons -->
                    <div class="action-buttons mt-3">
                        @if($readingProgress)
                            @php
                                $lastChapter = is_array($readingProgress) 
                                    ? \Ophim\Core\Models\Chapter::find($readingProgress['chapter_id'])
                                    : $readingProgress->chapter;
                            @endphp
                            @if($lastChapter)
                                <a href="{{ $lastChapter->getUrl() }}" class="btn btn-primary">
                                    <i class="fas fa-play"></i> Continue Reading
                                </a>
                            @endif
                        @else
                            @php
                                $firstChapter = $chapters->sortBy('chapter_number')->first();
                            @endphp
                            @if($firstChapter)
                                <a href="{{ $firstChapter->getUrl() }}" class="btn btn-primary">
                                    <i class="fas fa-book-open"></i> Start Reading
                                </a>
                            @endif
                        @endif
                        
                        <button class="btn btn-outline-secondary bookmark-toggle" data-manga-id="{{ $manga->id }}">
                            <i class="fas fa-bookmark"></i> Bookmark
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Description -->
        @if($manga->description)
            <div class="manga-description mt-4">
                <h3>Description</h3>
                <div class="description-content">
                    {!! nl2br(e($manga->description)) !!}
                </div>
            </div>
        @endif

        <!-- Chapter List -->
        <div class="chapter-list-section mt-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3>Chapters</h3>
                <div class="chapter-controls">
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-outline-secondary btn-sm view-toggle active" data-view="volume">
                            <i class="fas fa-layer-group"></i> By Volume
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-sm view-toggle" data-view="list">
                            <i class="fas fa-list"></i> List View
                        </button>
                    </div>
                    <div class="btn-group ml-2" role="group">
                        <button type="button" class="btn btn-outline-secondary btn-sm sort-chapters" data-sort="desc">
                            <i class="fas fa-sort-numeric-down"></i> Latest First
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-sm sort-chapters" data-sort="asc">
                            <i class="fas fa-sort-numeric-up"></i> Oldest First
                        </button>
                    </div>
                </div>
            </div>

            <!-- Volume View -->
            <div class="chapter-list volume-view">
                @if(isset($chaptersByVolume) && count($chaptersByVolume) > 0)
                    @foreach($chaptersByVolume as $group)
                        <div class="volume-group mb-4">
                            @if($group['type'] === 'volume' && $group['volume'])
                                <div class="volume-header">
                                    <h5 class="volume-title">
                                        <i class="fas fa-book"></i> {{ $group['volume']->getFormattedTitle() }}
                                        <small class="text-muted">({{ $group['chapters']->count() }} chapters)</small>
                                    </h5>
                                    @if($group['volume']->published_at)
                                        <small class="text-muted">Published: {{ $group['volume']->published_at->format('M d, Y') }}</small>
                                    @endif
                                </div>
                            @elseif($group['type'] === 'standalone')
                                <div class="volume-header">
                                    <h5 class="volume-title">
                                        <i class="fas fa-file-alt"></i> Individual Chapters
                                        <small class="text-muted">({{ $group['chapters']->count() }} chapters)</small>
                                    </h5>
                                </div>
                            @endif
                            
                            <div class="row">
                                @foreach($group['chapters'] as $chapter)
                                    <div class="col-md-6 col-lg-4 mb-2">
                                        <div class="chapter-item {{ $chapter->is_completed ?? false ? 'completed' : '' }}">
                                            <a href="{{ $chapter->getUrl() }}" class="chapter-link">
                                                <div class="chapter-info">
                                                    <span class="chapter-title">{{ $chapter->getFormattedTitle() }}</span>
                                                    <small class="chapter-meta">
                                                        {{ $chapter->page_count }} pages
                                                        @if($chapter->published_at)
                                                            • {{ $chapter->published_at->format('M d, Y') }}
                                                        @endif
                                                        @if($chapter->is_premium)
                                                            <span class="badge badge-warning">Premium</span>
                                                        @endif
                                                    </small>
                                                </div>
                                                @if($chapter->is_completed ?? false)
                                                    <i class="fas fa-check-circle text-success"></i>
                                                @endif
                                            </a>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> No chapters available yet.
                    </div>
                @endif
            </div>

            <!-- List View -->
            <div class="chapter-list list-view" style="display: none;">
                @if($chapters->count() > 0)
                    <div class="row">
                        @foreach($chapters as $chapter)
                            <div class="col-md-6 col-lg-4 mb-2">
                                <div class="chapter-item {{ $chapter->is_completed ?? false ? 'completed' : '' }}">
                                    <a href="{{ $chapter->getUrl() }}" class="chapter-link">
                                        <div class="chapter-info">
                                            <span class="chapter-title">{{ $chapter->getFormattedTitle() }}</span>
                                            <small class="chapter-meta">
                                                {{ $chapter->page_count }} pages
                                                @if($chapter->published_at)
                                                    • {{ $chapter->published_at->format('M d, Y') }}
                                                @endif
                                                @if($chapter->is_premium)
                                                    <span class="badge badge-warning">Premium</span>
                                                @endif
                                                @if($chapter->volume)
                                                    • Vol. {{ $chapter->volume->volume_number }}
                                                @endif
                                            </small>
                                        </div>
                                        @if($chapter->is_completed ?? false)
                                            <i class="fas fa-check-circle text-success"></i>
                                        @endif
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> No chapters available yet.
                    </div>
                @endif
            </div>

            <!-- Load More Button -->
            @if($chapters->count() >= 20)
                <div class="text-center mt-3">
                    <button class="btn btn-outline-primary load-more-chapters" data-manga-id="{{ $manga->id }}">
                        <i class="fas fa-plus"></i> Load More Chapters
                    </button>
                </div>
            @endif
        </div>

        <!-- Related Manga -->
        @if($relatedManga->count() > 0)
            <div class="related-manga-section mt-5">
                <h3>Related Manga</h3>
                <div class="row">
                    @foreach($relatedManga as $related)
                        <div class="col-md-2 col-sm-3 col-4 mb-3">
                            <div class="related-manga-item">
                                <a href="{{ $related->getUrl() }}">
                                    <img src="{{ $related->getCoverUrl() }}" alt="{{ $related->title }}" class="img-fluid rounded">
                                    <h6 class="mt-2">{{ Str::limit($related->title, 30) }}</h6>
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>
@endsection

@push('styles')
<style>
.manga-detail-container {
    padding: 20px 0;
}

.manga-cover img {
    width: 100%;
    max-width: 300px;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.reading-progress-badge {
    position: absolute;
    top: 10px;
    right: 10px;
    background: rgba(0,123,255,0.9);
    color: white;
    padding: 5px 10px;
    border-radius: 15px;
}

.manga-cover {
    position: relative;
}

.manga-title {
    font-size: 2rem;
    font-weight: bold;
    margin-bottom: 0.5rem;
}

.original-title {
    font-size: 1.2rem;
    margin-bottom: 1rem;
}

.manga-meta p {
    margin-bottom: 0.5rem;
}

.badge {
    margin-right: 5px;
    margin-bottom: 5px;
}

.action-buttons .btn {
    margin-right: 10px;
    margin-bottom: 10px;
}

.description-content {
    line-height: 1.6;
    max-height: 200px;
    overflow: hidden;
    transition: max-height 0.3s ease;
}

.description-content.expanded {
    max-height: none;
}

.chapter-item {
    border: 1px solid #dee2e6;
    border-radius: 5px;
    padding: 10px;
    transition: all 0.2s ease;
}

.chapter-item:hover {
    border-color: #007bff;
    box-shadow: 0 2px 4px rgba(0,123,255,0.1);
}

.chapter-item.completed {
    background-color: #f8f9fa;
    border-color: #28a745;
}

.chapter-link {
    text-decoration: none;
    color: inherit;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.chapter-link:hover {
    text-decoration: none;
    color: inherit;
}

.chapter-title {
    font-weight: 500;
    display: block;
}

.chapter-meta {
    color: #6c757d;
    display: block;
    margin-top: 5px;
}

.related-manga-item img {
    width: 100%;
    height: 200px;
    object-fit: cover;
}

.related-manga-item h6 {
    font-size: 0.9rem;
    text-align: center;
}

.volume-group {
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 15px;
    background-color: #f8f9fa;
}

.volume-header {
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 2px solid #dee2e6;
}

.volume-title {
    margin-bottom: 5px;
    color: #495057;
    font-weight: 600;
}

.volume-title i {
    color: #6c757d;
    margin-right: 8px;
}

.view-toggle.active {
    background-color: #007bff;
    color: white;
    border-color: #007bff;
}

.chapter-controls .btn-group {
    margin-right: 10px;
}

.chapter-controls .btn-group:last-child {
    margin-right: 0;
}

@media (max-width: 768px) {
    .manga-title {
        font-size: 1.5rem;
    }
    
    .original-title {
        font-size: 1rem;
    }
    
    .manga-meta .row {
        margin: 0;
    }
    
    .manga-meta .col-md-6 {
        padding: 0;
    }
    
    .chapter-controls {
        flex-direction: column;
        align-items: stretch;
    }
    
    .chapter-controls .btn-group {
        margin-bottom: 10px;
        margin-right: 0;
    }
    
    .volume-group {
        padding: 10px;
    }
    
    .volume-header {
        margin-bottom: 10px;
    }
}
</style>
@endpush

@push('scripts')
<script>
$(document).ready(function() {
    let currentPage = 1;
    let isLoading = false;
    let currentSort = 'desc';
    let currentView = 'volume';
    
    // View toggle functionality
    $('.view-toggle').on('click', function() {
        const view = $(this).data('view');
        if (view === currentView) return;
        
        currentView = view;
        
        $('.view-toggle').removeClass('active');
        $(this).addClass('active');
        
        if (view === 'volume') {
            $('.volume-view').show();
            $('.list-view').hide();
        } else {
            $('.volume-view').hide();
            $('.list-view').show();
        }
        
        // Save preference to localStorage
        localStorage.setItem('manga_chapter_view', view);
    });
    
    // Load saved view preference
    const savedView = localStorage.getItem('manga_chapter_view');
    if (savedView && savedView !== currentView) {
        $(`.view-toggle[data-view="${savedView}"]`).click();
    }
    
    // Bookmark toggle
    $('.bookmark-toggle').on('click', function() {
        const mangaId = $(this).data('manga-id');
        const button = $(this);
        
        $.ajax({
            url: `/api/manga/${mangaId}/bookmark`,
            method: 'POST',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    if (response.is_bookmarked) {
                        button.removeClass('btn-outline-secondary').addClass('btn-secondary');
                        button.find('i').removeClass('far').addClass('fas');
                    } else {
                        button.removeClass('btn-secondary').addClass('btn-outline-secondary');
                        button.find('i').removeClass('fas').addClass('far');
                    }
                    
                    // Show notification
                    showNotification(response.message, 'success');
                }
            },
            error: function() {
                showNotification('Failed to update bookmark', 'error');
            }
        });
    });
    
    // Chapter sorting
    $('.sort-chapters').on('click', function() {
        const sort = $(this).data('sort');
        if (sort === currentSort) return;
        
        currentSort = sort;
        currentPage = 1;
        
        $('.sort-chapters').removeClass('btn-secondary').addClass('btn-outline-secondary');
        $(this).removeClass('btn-outline-secondary').addClass('btn-secondary');
        
        loadChapters(true);
    });
    
    // Load more chapters
    $('.load-more-chapters').on('click', function() {
        if (!isLoading) {
            currentPage++;
            loadChapters(false);
        }
    });
    
    // Load chapters function
    function loadChapters(replace = false) {
        if (isLoading) return;
        
        isLoading = true;
        const mangaId = $('.load-more-chapters').data('manga-id');
        
        $.ajax({
            url: `/api/manga/${mangaId}/chapters`,
            method: 'GET',
            data: {
                page: currentPage,
                sort: currentSort,
                per_page: 20
            },
            success: function(response) {
                if (response.success) {
                    const chapters = response.data;
                    let html = '';
                    
                    chapters.forEach(function(chapter) {
                        const completedClass = chapter.is_completed ? 'completed' : '';
                        const premiumBadge = chapter.is_premium ? '<span class="badge badge-warning">Premium</span>' : '';
                        const completedIcon = chapter.is_completed ? '<i class="fas fa-check-circle text-success"></i>' : '';
                        
                        html += `
                            <div class="col-md-6 col-lg-4 mb-2">
                                <div class="chapter-item ${completedClass}">
                                    <a href="${chapter.url}" class="chapter-link">
                                        <div class="chapter-info">
                                            <span class="chapter-title">${chapter.title}</span>
                                            <small class="chapter-meta">
                                                ${chapter.page_count} pages
                                                ${chapter.published_at ? '• ' + chapter.published_at : ''}
                                                ${premiumBadge}
                                            </small>
                                        </div>
                                        ${completedIcon}
                                    </a>
                                </div>
                            </div>
                        `;
                    });
                    
                    if (replace) {
                        $('.chapter-list .row').html(html);
                    } else {
                        $('.chapter-list .row').append(html);
                    }
                    
                    // Hide load more button if no more pages
                    if (!response.pagination.has_more) {
                        $('.load-more-chapters').hide();
                    } else {
                        $('.load-more-chapters').show();
                    }
                }
                
                isLoading = false;
            },
            error: function() {
                showNotification('Failed to load chapters', 'error');
                isLoading = false;
            }
        });
    }
    
    // Description expand/collapse
    if ($('.description-content').height() >= 200) {
        $('.description-content').after('<button class="btn btn-link btn-sm expand-description">Show More</button>');
        
        $('.expand-description').on('click', function() {
            const content = $('.description-content');
            const button = $(this);
            
            if (content.hasClass('expanded')) {
                content.removeClass('expanded');
                button.text('Show More');
            } else {
                content.addClass('expanded');
                button.text('Show Less');
            }
        });
    }
    
    // Notification function
    function showNotification(message, type) {
        const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
        const notification = $(`
            <div class="alert ${alertClass} alert-dismissible fade show notification" style="position: fixed; top: 20px; right: 20px; z-index: 9999;">
                ${message}
                <button type="button" class="close" data-dismiss="alert">
                    <span>&times;</span>
                </button>
            </div>
        `);
        
        $('body').append(notification);
        
        setTimeout(function() {
            notification.alert('close');
        }, 3000);
    }
});
</script>
@endpush