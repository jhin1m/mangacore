{{-- Manage Pages Button for Chapter CRUD --}}
@if ($crud->hasAccess('update'))
    <a href="{{ url($crud->route.'/'.$entry->getKey().'/edit#pages') }}" 
       class="btn btn-sm btn-link" 
       data-toggle="tooltip" 
       title="Manage chapter pages">
        <i class="fa fa-images"></i> Manage Pages
    </a>
@endif