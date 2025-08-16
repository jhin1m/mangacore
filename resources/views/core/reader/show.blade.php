@extends('themes.layout')

@section('title', $chapter->getTitle())

@section('content')
<div id="manga-reader" class="manga-reader-container" data-chapter-id="{{ $chapter->id }}" data-manga-id="{{ $manga->id }}">
    <!-- Reader Header -->
    <div class="reader-header">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-md-4">
                    <div class="manga-info">
                        <a href="{{ $manga->getUrl() }}" class="manga-title">{{ $manga->title }}</a>
                        <span class="chapter-title">{{ $chapter->getFormattedTitle() }}</span>
                    </div>
                </div>
                <div class="col-md-4 text-center">
                    <div class="chapter-navigation">
                        @if($navigationData['previous_chapter'])
                            <a href="{{ $navigationData['previous_chapter']['url'] }}" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-chevron-left"></i> Previous
                            </a>
                        @endif
                        
                        <div class="chapter-selector dropdown d-inline-block mx-2">
                            <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-toggle="dropdown">
                                Chapter {{ $chapter->chapter_number }}
                            </button>
                            <div class="dropdown-menu chapter-list">
                                @foreach($navigationData['chapter_list'] as $chapterItem)
                                    <a class="dropdown-item {{ $chapterItem['id'] == $chapter->id ? 'active' : '' }}" 
                                       href="{{ $chapterItem['url'] }}">
                                        {{ $chapterItem['title'] }}
                                    </a>
                                @endforeach
                            </div>
                        </div>

                        @if($navigationData['next_chapter'])
                            <a href="{{ $navigationData['next_chapter']['url'] }}" class="btn btn-outline-primary btn-sm">
                                Next <i class="fas fa-chevron-right"></i>
                            </a>
                        @endif
                    </div>
                </div>
                <div class="col-md-4 text-right">
                    <div class="reader-controls">
                        <div class="reading-mode-selector dropdown d-inline-block">
                            <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-toggle="dropdown">
                                <i class="fas fa-cog"></i> Settings
                            </button>
                            <div class="dropdown-menu dropdown-menu-right">
                                <h6 class="dropdown-header">Reading Mode</h6>
                                <a class="dropdown-item reading-mode-option" data-mode="single">
                                    <i class="fas fa-file"></i> Single Page
                                </a>
                                <a class="dropdown-item reading-mode-option" data-mode="double">
                                    <i class="fas fa-columns"></i> Double Page
                                </a>
                                <a class="dropdown-item reading-mode-option" data-mode="vertical">
                                    <i class="fas fa-arrows-alt-v"></i> Vertical Scroll
                                </a>
                                <a class="dropdown-item reading-mode-option" data-mode="horizontal">
                                    <i class="fas fa-arrows-alt-h"></i> Horizontal Swipe
                                </a>
                                <div class="dropdown-divider"></div>
                                <h6 class="dropdown-header">Image Quality</h6>
                                <a class="dropdown-item quality-option" data-quality="low">Low</a>
                                <a class="dropdown-item quality-option" data-quality="medium">Medium</a>
                                <a class="dropdown-item quality-option" data-quality="high">High</a>
                            </div>
                        </div>
                        <button class="btn btn-outline-secondary btn-sm fullscreen-toggle">
                            <i class="fas fa-expand"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Reader Content -->
    <div class="reader-content">
        <!-- Loading Indicator -->
        <div class="reader-loading text-center py-5" style="display: none;">
            <div class="spinner-border" role="status">
                <span class="sr-only">Loading...</span>
            </div>
            <p class="mt-2">Loading pages...</p>
        </div>

        <!-- Error Message -->
        <div class="reader-error text-center py-5" style="display: none;">
            <div class="alert alert-danger">
                <h5>Error Loading Chapter</h5>
                <p>Unable to load chapter pages. Please try again.</p>
                <button class="btn btn-primary retry-loading">Retry</button>
            </div>
        </div>

        <!-- Single/Double Page Mode -->
        <div class="reader-pages single-page-mode">
            <div class="page-container">
                @foreach($chapter->pages as $page)
                    <div class="page-wrapper" data-page="{{ $page->page_number }}">
                        <img class="manga-page" 
                             src="{{ $page->getOptimizedUrl($readingSettings['image_quality']) }}"
                             data-webp="{{ $page->getWebPUrl() }}"
                             data-low="{{ $page->getOptimizedUrl('low') }}"
                             data-medium="{{ $page->getOptimizedUrl('medium') }}"
                             data-high="{{ $page->getOptimizedUrl('high') }}"
                             alt="Page {{ $page->page_number }}"
                             loading="lazy">
                        <div class="page-number">{{ $page->page_number }} / {{ $chapter->page_count }}</div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Vertical Scroll Mode -->
        <div class="reader-vertical" style="display: none;">
            @foreach($chapter->pages as $page)
                <div class="vertical-page" data-page="{{ $page->page_number }}">
                    <img class="manga-page-vertical" 
                         src="{{ $page->getOptimizedUrl($readingSettings['image_quality']) }}"
                         data-webp="{{ $page->getWebPUrl() }}"
                         alt="Page {{ $page->page_number }}"
                         loading="lazy">
                </div>
            @endforeach
        </div>
    </div>

    <!-- Reader Footer -->
    <div class="reader-footer">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-md-4">
                    <div class="page-info">
                        <span class="current-page">1</span> / <span class="total-pages">{{ $chapter->page_count }}</span>
                    </div>
                </div>
                <div class="col-md-4 text-center">
                    <div class="page-navigation">
                        <button class="btn btn-outline-primary btn-sm prev-page" disabled>
                            <i class="fas fa-chevron-left"></i> Previous Page
                        </button>
                        <button class="btn btn-outline-primary btn-sm next-page">
                            Next Page <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                </div>
                <div class="col-md-4 text-right">
                    <div class="progress-info">
                        @if($readingProgress)
                            <small class="text-muted">
                                Last read: Page {{ is_array($readingProgress) ? $readingProgress['page_number'] : $readingProgress->page_number }}
                            </small>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Page Progress Bar -->
    <div class="page-progress">
        <div class="progress-bar" style="width: 0%"></div>
    </div>
</div>

<!-- Keyboard Shortcuts Help Modal -->
<div class="modal fade" id="shortcutsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Keyboard Shortcuts</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-6">
                        <h6>Navigation</h6>
                        <ul class="list-unstyled">
                            <li><kbd>←</kbd> Previous page</li>
                            <li><kbd>→</kbd> Next page</li>
                            <li><kbd>Space</kbd> Next page</li>
                            <li><kbd>Shift + Space</kbd> Previous page</li>
                        </ul>
                    </div>
                    <div class="col-6">
                        <h6>Controls</h6>
                        <ul class="list-unstyled">
                            <li><kbd>F</kbd> Fullscreen</li>
                            <li><kbd>H</kbd> Show/hide UI</li>
                            <li><kbd>?</kbd> Show shortcuts</li>
                            <li><kbd>Esc</kbd> Exit fullscreen</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('vendor/ophim-core/css/reader.css') }}">
@endpush

@push('scripts')
<script>
// Pass data to JavaScript
window.readerData = {
    manga: @json($navigationData['manga']),
    chapter: @json($navigationData['current_chapter']),
    navigation: @json($navigationData),
    settings: @json($readingSettings),
    readingProgress: @json($readingProgress),
    apiUrls: {
        chapterData: '{{ route("api.chapters.data", $chapter->id) }}',
        saveProgress: '{{ route("api.reading.progress") }}',
        preloadPages: '{{ route("api.chapters.preload") }}',
        updateSettings: '{{ route("api.reading.settings") }}'
    }
};
</script>
<script src="{{ asset('vendor/ophim-core/js/manga-reader.js') }}"></script>
@endpush