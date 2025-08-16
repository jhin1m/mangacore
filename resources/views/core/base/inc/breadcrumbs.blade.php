{{-- Custom breadcrumbs for manga admin interface --}}
@if (isset($breadcrumbs))
    <nav aria-label="breadcrumb" class="d-none d-lg-block">
        <ol class="breadcrumb bg-transparent mb-0">
            @foreach ($breadcrumbs as $label => $url)
                @if ($url)
                    <li class="breadcrumb-item">
                        <a href="{{ $url }}">
                            @if ($loop->first)
                                <i class="la la-home"></i>
                            @endif
                            {{ $label }}
                        </a>
                    </li>
                @else
                    <li class="breadcrumb-item active" aria-current="page">
                        {{ $label }}
                    </li>
                @endif
            @endforeach
        </ol>
    </nav>
@endif

{{-- Custom breadcrumb translations for manga context --}}
@push('after_scripts')
<script>
$(document).ready(function() {
    // Update breadcrumb text for manga context
    $('.breadcrumb-item').each(function() {
        var text = $(this).text().trim();
        
        // Replace movie-related terms with manga terms
        var replacements = {
            'Movies': 'Manga',
            'Movie': 'Manga',
            'Episodes': 'Chapters', 
            'Episode': 'Chapter',
            'Actors': 'Authors',
            'Actor': 'Author',
            'Directors': 'Artists',
            'Director': 'Artist',
            'Studios': 'Publishers',
            'Studio': 'Publisher',
            'Regions': 'Origins',
            'Region': 'Origin'
        };
        
        for (var old_term in replacements) {
            if (text === old_term) {
                $(this).text(replacements[old_term]);
                break;
            }
        }
    });
});
</script>
@endpush