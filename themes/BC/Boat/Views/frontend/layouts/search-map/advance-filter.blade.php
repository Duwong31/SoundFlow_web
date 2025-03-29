@php
    $boat_map_search_fields = setting_item_array('boat_map_search_fields');
    $usedAttrs = [];
    foreach ($boat_map_search_fields as $field){
        if($field['field'] == 'attr' and !empty($field['attr']))
        {
            $usedAttrs[] = $field['attr'];
        }
    }
    $selected = (array) request()->query('terms');
@endphp
<div id="advance_filters" class="d-none">
    <div class="ad-filter-b">
        @include('Layout::global.search.filters-map.attrs')
    </div>
    <div class="ad-filter-f text-right">
        <a href="#" onclick="return false" class="btn btn-primary btn-apply-advances">{{__("Apply Filters")}}</a>
    </div>
</div>
