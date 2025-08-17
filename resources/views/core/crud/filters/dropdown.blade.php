{{-- Dropdown filter --}}
<li filter-name="{{ $filter->name }}" filter-type="{{ $filter->type }}" filter-key="{{ $filter->key }}" class="nav-item dropdown {{ Request::get($filter->name)?'active':'' }}">
    <a href="#" class="nav-link dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">{{ $filter->label }} <span class="caret"></span></a>
    <div class="dropdown-menu">
        <a class="dropdown-item" parameter="{{ $filter->name }}" href="">- {{ trans('backpack::crud.all') }} -</a>
        <div class="dropdown-divider"></div>
        @if (is_array($filter->values))
            @foreach($filter->values as $key => $value)
                <a  class="dropdown-item" 
                    parameter="{{ $filter->name }}" 
                    href="{{ Request::fullUrlWithQuery([$filter->name => $key]) }}"
                    >{{ $value }}</a>
            @endforeach
        @endif
    </div>
</li>

{{-- Extra CSS and JS for this particular filter --}}

{{-- FILTERS EXTRA CSS --}}
{{-- push things in the after_styles section --}}

@push('crud_list_styles')
    <!-- no css -->
@endpush


{{-- FILTERS EXTRA JS --}}
{{-- push things in the after_scripts section --}}

@push('crud_list_scripts')
<script>
    jQuery(document).ready(function($) {
        // trigger the dropdown filter functionality
        $("li[filter-key={{ $filter->key }}] a.dropdown-item").click(function(e) {
            e.preventDefault();

            var parameter = $(this).attr('parameter');
            var value = $(this).attr('href').split('?')[1] || '';
            
            // Get the parameter value from URL
            if (value) {
                var urlParams = new URLSearchParams(value);
                value = urlParams.get(parameter) || '';
            }

            var filterName = "{{ $filter->name }}";
            var filterKey = "{{ $filter->key }}";
            
            var new_url = updateDatatablesOnFilterChange(filterName, value, true);

            // mark this filter as active in the navbar-filters
            if (URI(new_url).hasQuery(filterName, true)) {
                $("li[filter-key="+filterKey+"]").addClass('active');
                $('#remove_filters_button').removeClass('invisible');
            } else {
                $("li[filter-key="+filterKey+"]").removeClass('active');
            }
        });

        // clear filter event (used here and by the Remove all filters button)
        $("li[filter-key={{ $filter->key }}]").on('filter:clear', function(e) {
            $("li[filter-key={{ $filter->key }}]").removeClass('active');
        });
    });
</script>
@endpush