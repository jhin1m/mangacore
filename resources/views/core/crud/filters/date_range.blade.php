{{-- Date range filter --}}
<li filter-name="{{ $filter->name }}" filter-type="{{ $filter->type }}" filter-key="{{ $filter->key }}" class="nav-item {{ Request::get($filter->name)?'active':'' }}">
    <a href="#" class="nav-link dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">{{ $filter->label }} <span class="caret"></span></a>
    <div class="dropdown-menu p-3" style="min-width: 300px;">
        <div class="form-group">
            <label>{{ trans('backpack::crud.from') }}</label>
            <input type="date" class="form-control" id="datepicker_from_{{ $filter->key }}" value="{{ Request::get($filter->name) ? json_decode(Request::get($filter->name))->from ?? '' : '' }}">
        </div>
        <div class="form-group">
            <label>{{ trans('backpack::crud.to') }}</label>
            <input type="date" class="form-control" id="datepicker_to_{{ $filter->key }}" value="{{ Request::get($filter->name) ? json_decode(Request::get($filter->name))->to ?? '' : '' }}">
        </div>
        <div class="form-group">
            <button type="button" class="btn btn-primary btn-sm" id="apply_date_range_{{ $filter->key }}">{{ trans('backpack::crud.apply') }}</button>
            <button type="button" class="btn btn-secondary btn-sm" id="clear_date_range_{{ $filter->key }}">{{ trans('backpack::crud.clear') }}</button>
        </div>
    </div>
</li>

{{-- Extra CSS and JS for this particular filter --}}

{{-- FILTERS EXTRA CSS --}}
{{-- push things in the after_styles section --}}

@push('crud_list_styles')
    <style>
        .dropdown-menu {
            padding: 15px;
        }
        .dropdown-menu .form-group:last-child {
            margin-bottom: 0;
        }
    </style>
@endpush


{{-- FILTERS EXTRA JS --}}
{{-- push things in the after_scripts section --}}

@push('crud_list_scripts')
<script>
    jQuery(document).ready(function($) {
        var filterKey = "{{ $filter->key }}";
        var filterName = "{{ $filter->name }}";
        
        // Prevent dropdown from closing when clicking inside
        $("li[filter-key=" + filterKey + "] .dropdown-menu").click(function(e) {
            e.stopPropagation();
        });

        // Apply date range filter
        $("#apply_date_range_" + filterKey).click(function(e) {
            e.preventDefault();
            
            var fromDate = $("#datepicker_from_" + filterKey).val();
            var toDate = $("#datepicker_to_" + filterKey).val();
            
            if (fromDate && toDate) {
                var dateRange = JSON.stringify({
                    from: fromDate,
                    to: toDate
                });
                
                var new_url = updateDatatablesOnFilterChange(filterName, dateRange, true);
                
                // mark this filter as active in the navbar-filters
                $("li[filter-key=" + filterKey + "]").addClass('active');
                $('#remove_filters_button').removeClass('invisible');
                
                // Close dropdown
                $("li[filter-key=" + filterKey + "] .dropdown-toggle").dropdown('toggle');
            } else {
                alert('{{ trans('backpack::crud.please_select_both_dates') }}');
            }
        });

        // Clear date range filter
        $("#clear_date_range_" + filterKey).click(function(e) {
            e.preventDefault();
            
            $("#datepicker_from_" + filterKey).val('');
            $("#datepicker_to_" + filterKey).val('');
            
            var new_url = updateDatatablesOnFilterChange(filterName, '', true);
            
            // mark this filter as inactive in the navbar-filters
            $("li[filter-key=" + filterKey + "]").removeClass('active');
            
            // Close dropdown
            $("li[filter-key=" + filterKey + "] .dropdown-toggle").dropdown('toggle');
        });

        // clear filter event (used here and by the Remove all filters button)
        $("li[filter-key=" + filterKey + "]").on('filter:clear', function(e) {
            $("#datepicker_from_" + filterKey).val('');
            $("#datepicker_to_" + filterKey).val('');
            $("li[filter-key=" + filterKey + "]").removeClass('active');
        });
    });
</script>
@endpush