@extends('themes::layout')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-12">
            <h1>{{ $taxonomyName }}</h1>
            <p class="text-muted">Danh sách manga theo {{ $taxonomyType }}</p>
            
            <!-- Manga Grid -->
            <div class="row">
                @foreach($manga as $item)
                <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                    <div class="manga-card">
                        <a href="{{ $item->getUrl() }}">
                            <img src="{{ $item->getCoverUrl() }}" alt="{{ $item->title }}" class="img-fluid">
                            <h5>{{ $item->title }}</h5>
                        </a>
                        <p class="text-muted">{{ $item->getStatus() }}</p>
                        <div class="categories">
                            @foreach($item->categories->take(3) as $category)
                                <span class="badge badge-secondary">{{ $category->name }}</span>
                            @endforeach
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            
            @if($manga->count() == 0)
                <div class="text-center py-5">
                    <p>Không có manga nào được tìm thấy.</p>
                </div>
            @endif
            
            <!-- Pagination -->
            <div class="d-flex justify-content-center">
                {{ $manga->appends(request()->query())->links() }}
            </div>
        </div>
    </div>
</div>
@endsection