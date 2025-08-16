{{-- Manga Actions Dropdown Button --}}
@if ($crud->hasAccess('update'))
    <div class="btn-group" role="group">
        <button type="button" class="btn btn-sm btn-outline-primary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            <i class="fa fa-cog"></i> Actions
        </button>
        <div class="dropdown-menu">
            <a class="dropdown-item" href="{{ $entry->getUrl() }}" target="_blank">
                <i class="fa fa-external-link-alt"></i> View Manga
            </a>
            
            <a class="dropdown-item" href="{{ backpack_url('chapter?manga_id='.$entry->getKey()) }}">
                <i class="fa fa-list"></i> Manage Chapters
            </a>
            
            <div class="dropdown-divider"></div>
            
            <a class="dropdown-item" href="{{ url($crud->route.'/'.$entry->getKey().'/batch-upload-chapters') }}">
                <i class="fa fa-upload"></i> Batch Upload Chapters
            </a>
            
            <a class="dropdown-item" href="{{ url($crud->route.'/'.$entry->getKey().'/generate-sitemap') }}">
                <i class="fa fa-sitemap"></i> Generate Sitemap
            </a>
            
            <div class="dropdown-divider"></div>
            
            @if($entry->is_recommended)
                <a class="dropdown-item" href="{{ url($crud->route.'/'.$entry->getKey().'/unrecommend') }}">
                    <i class="fa fa-star-o"></i> Remove from Recommended
                </a>
            @else
                <a class="dropdown-item" href="{{ url($crud->route.'/'.$entry->getKey().'/recommend') }}">
                    <i class="fa fa-star"></i> Add to Recommended
                </a>
            @endif
            
            <div class="dropdown-divider"></div>
            
            <a class="dropdown-item" href="{{ url($crud->route.'/'.$entry->getKey().'/duplicate') }}">
                <i class="fa fa-copy"></i> Duplicate Manga
            </a>
            
            <a class="dropdown-item text-danger" href="{{ url($crud->route.'/'.$entry->getKey().'/reset-views') }}">
                <i class="fa fa-undo"></i> Reset View Counts
            </a>
        </div>
    </div>
@endif