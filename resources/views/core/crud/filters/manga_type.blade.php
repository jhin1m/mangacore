{{-- Manga Type Filter --}}
@php
    $filter_name = $filter->name;
    $filter_key = $filter->key;
    $filter_options = [
        '' => 'All Types',
        'manga' => 'Manga',
        'manhwa' => 'Manhwa',
        'manhua' => 'Manhua',
        'webtoon' => 'Webtoon'
    ];
@endphp

<li filter-name="{{ $filter_name }}" filter-type="{{ $filter->type }}" class="nav-item dropdown {{ Request::get($filter_key)?'active':'' }}">
    <a href="#" class="nav-link dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
        {{ $filter->label }} 
        @if(Request::get($filter_key))
            <span class="badge badge-info">{{ $filter_options[Request::get($filter_key)] ?? Request::get($filter_key) }}</span>
        @endif
        <span class="caret"></span>
    </a>
    <div class="dropdown-menu">
        @foreach($filter_options as $key => $label)
            <a class="dropdown-item {{ Request::get($filter_key) == $key ? 'active' : '' }}" 
               href="{{ Request::fullUrlWithQuery([$filter_key => $key]) }}">
                {{ $label }}
            </a>
        @endforeach
    </div>
</li>