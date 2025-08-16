{{-- Chapter Actions Dropdown Button --}}
@if ($crud->hasAccess('update'))
    <div class="btn-group" role="group">
        <button type="button" class="btn btn-sm btn-outline-primary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            <i class="fa fa-cog"></i> Actions
        </button>
        <div class="dropdown-menu">
            <a class="dropdown-item" href="{{ url($crud->route.'/'.$entry->getKey().'/edit#pages') }}">
                <i class="fa fa-images"></i> Manage Pages
            </a>
            
            @if($entry->pages->count() > 0)
                <a class="dropdown-item" href="{{ $entry->getUrl() }}" target="_blank">
                    <i class="fa fa-book-open"></i> Preview Chapter
                </a>
            @endif
            
            <div class="dropdown-divider"></div>
            
            <a class="dropdown-item" href="{{ url($crud->route.'/'.$entry->getKey().'/optimize-images') }}">
                <i class="fa fa-compress-alt"></i> Optimize Images
            </a>
            
            @if($entry->pages->count() == 0)
                <a class="dropdown-item text-warning" href="{{ url($crud->route.'/'.$entry->getKey().'/upload-pages') }}">
                    <i class="fa fa-upload"></i> Upload Pages
                </a>
            @endif
            
            <div class="dropdown-divider"></div>
            
            <a class="dropdown-item" href="{{ url($crud->route.'/'.$entry->getKey().'/duplicate') }}">
                <i class="fa fa-copy"></i> Duplicate Chapter
            </a>
        </div>
    </div>
@endif