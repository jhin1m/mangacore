@extends('themes::layout')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-12">
            <h1>{{ $type ? "Danh sách " . $type : "Danh sách manga" }}</h1>
            
            <!-- Filters -->
            <div class="filters mb-4">
                <form method="GET" class="row">
                    <div class="col-md-3">
                        <select name="category" class="form-control">
                            <option value="">Tất cả thể loại</option>
                            <!-- Categories would be populated here -->
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select name="status" class="form-control">
                            <option value="">Tất cả trạng thái</option>
                            <option value="ongoing" {{ request('status') == 'ongoing' ? 'selected' : '' }}>Đang tiến hành</option>
                            <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Hoàn thành</option>
                            <option value="hiatus" {{ request('status') == 'hiatus' ? 'selected' : '' }}>Tạm ngưng</option>
                            <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Đã hủy</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select name="sort" class="form-control">
                            <option value="updated_at" {{ request('sort') == 'updated_at' ? 'selected' : '' }}>Mới cập nhật</option>
                            <option value="title" {{ request('sort') == 'title' ? 'selected' : '' }}>Tên A-Z</option>
                            <option value="rating" {{ request('sort') == 'rating' ? 'selected' : '' }}>Đánh giá</option>
                            <option value="view_count" {{ request('sort') == 'view_count' ? 'selected' : '' }}>Lượt xem</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary">Lọc</button>
                    </div>
                </form>
            </div>
            
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
            
            <!-- Pagination -->
            <div class="d-flex justify-content-center">
                {{ $manga->appends(request()->query())->links() }}
            </div>
        </div>
    </div>
</div>
@endsection