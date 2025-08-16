{{-- Custom admin layout for manga terminology --}}
@extends(backpack_view('layouts.top_left'))

@section('before_breadcrumbs_widgets')
    @include('core.base.inc.breadcrumbs')
@endsection

@push('after_scripts')
<script>
$(document).ready(function() {
    // Update page titles and headings for manga context
    updateMangaTerminology();
    
    // Update on AJAX content changes
    $(document).ajaxComplete(function() {
        updateMangaTerminology();
    });
    
    function updateMangaTerminology() {
        // Update page titles
        $('title').each(function() {
            var title = $(this).text();
            title = title.replace(/Movies?/g, 'Manga')
                        .replace(/Episodes?/g, 'Chapters')
                        .replace(/Actors?/g, 'Authors')
                        .replace(/Directors?/g, 'Artists')
                        .replace(/Studios?/g, 'Publishers')
                        .replace(/Regions?/g, 'Origins');
            $(this).text(title);
        });
        
        // Update headings
        $('h1, h2, h3, h4, h5, h6').each(function() {
            var heading = $(this).html();
            heading = heading.replace(/\bMovies?\b/g, 'Manga')
                            .replace(/\bEpisodes?\b/g, 'Chapters')
                            .replace(/\bActors?\b/g, 'Authors')
                            .replace(/\bDirectors?\b/g, 'Artists')
                            .replace(/\bStudios?\b/g, 'Publishers')
                            .replace(/\bRegions?\b/g, 'Origins');
            $(this).html(heading);
        });
        
        // Update form labels
        $('label').each(function() {
            var label = $(this).html();
            label = label.replace(/\bMovies?\b/g, 'Manga')
                         .replace(/\bEpisodes?\b/g, 'Chapters')
                         .replace(/\bActors?\b/g, 'Authors')
                         .replace(/\bDirectors?\b/g, 'Artists')
                         .replace(/\bStudios?\b/g, 'Publishers')
                         .replace(/\bRegions?\b/g, 'Origins');
            $(this).html(label);
        });
        
        // Update table headers
        $('th').each(function() {
            var header = $(this).html();
            header = header.replace(/\bMovies?\b/g, 'Manga')
                          .replace(/\bEpisodes?\b/g, 'Chapters')
                          .replace(/\bActors?\b/g, 'Authors')
                          .replace(/\bDirectors?\b/g, 'Artists')
                          .replace(/\bStudios?\b/g, 'Publishers')
                          .replace(/\bRegions?\b/g, 'Origins');
            $(this).html(header);
        });
        
        // Update button text
        $('button, .btn').each(function() {
            var btnText = $(this).html();
            btnText = btnText.replace(/\bMovies?\b/g, 'Manga')
                            .replace(/\bEpisodes?\b/g, 'Chapters')
                            .replace(/\bActors?\b/g, 'Authors')
                            .replace(/\bDirectors?\b/g, 'Artists')
                            .replace(/\bStudios?\b/g, 'Publishers')
                            .replace(/\bRegions?\b/g, 'Origins');
            $(this).html(btnText);
        });
        
        // Update placeholder text
        $('input[placeholder], textarea[placeholder]').each(function() {
            var placeholder = $(this).attr('placeholder');
            if (placeholder) {
                placeholder = placeholder.replace(/\bMovies?\b/g, 'Manga')
                                        .replace(/\bEpisodes?\b/g, 'Chapters')
                                        .replace(/\bActors?\b/g, 'Authors')
                                        .replace(/\bDirectors?\b/g, 'Artists')
                                        .replace(/\bStudios?\b/g, 'Publishers')
                                        .replace(/\bRegions?\b/g, 'Origins');
                $(this).attr('placeholder', placeholder);
            }
        });
        
        // Update help text and hints
        $('.help-block, .form-text, .text-muted').each(function() {
            var helpText = $(this).html();
            helpText = helpText.replace(/\bMovies?\b/g, 'Manga')
                              .replace(/\bEpisodes?\b/g, 'Chapters')
                              .replace(/\bActors?\b/g, 'Authors')
                              .replace(/\bDirectors?\b/g, 'Artists')
                              .replace(/\bStudios?\b/g, 'Publishers')
                              .replace(/\bRegions?\b/g, 'Origins');
            $(this).html(helpText);
        });
    }
});
</script>
@endpush

@push('after_styles')
<style>
/* Custom styles for manga admin interface */
.manga-admin-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 1rem;
    border-radius: 0.5rem;
    margin-bottom: 1rem;
}

.manga-stats-card {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 0.5rem;
    padding: 1rem;
    text-align: center;
    transition: all 0.3s ease;
}

.manga-stats-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.manga-stats-card .stats-icon {
    font-size: 2rem;
    margin-bottom: 0.5rem;
    opacity: 0.7;
}

.manga-stats-card .stats-number {
    font-size: 1.5rem;
    font-weight: bold;
    color: #495057;
}

.manga-stats-card .stats-label {
    font-size: 0.875rem;
    color: #6c757d;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Manga-specific badge colors */
.badge-manga-type {
    background-color: #17a2b8;
    color: white;
}

.badge-manga-status {
    background-color: #28a745;
    color: white;
}

.badge-manga-demographic {
    background-color: #6f42c1;
    color: white;
}

/* Chapter-specific styling */
.chapter-number-badge {
    background: linear-gradient(45deg, #007bff, #0056b3);
    color: white;
    padding: 0.25rem 0.5rem;
    border-radius: 1rem;
    font-size: 0.75rem;
    font-weight: bold;
}

.page-count-indicator {
    background-color: #ffc107;
    color: #212529;
    padding: 0.125rem 0.375rem;
    border-radius: 0.75rem;
    font-size: 0.7rem;
    font-weight: 500;
}

/* Reading progress indicators */
.reading-progress-bar {
    height: 4px;
    background-color: #e9ecef;
    border-radius: 2px;
    overflow: hidden;
}

.reading-progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #28a745, #20c997);
    transition: width 0.3s ease;
}

/* Manga cover image styling */
.manga-cover-preview {
    max-width: 100px;
    max-height: 150px;
    object-fit: cover;
    border-radius: 0.25rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

/* Chapter page thumbnails */
.chapter-page-thumbnail {
    width: 60px;
    height: 80px;
    object-fit: cover;
    border-radius: 0.25rem;
    border: 2px solid #dee2e6;
    transition: border-color 0.2s ease;
}

.chapter-page-thumbnail:hover {
    border-color: #007bff;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .manga-stats-card {
        margin-bottom: 1rem;
    }
    
    .manga-cover-preview {
        max-width: 80px;
        max-height: 120px;
    }
}
</style>
@endpush