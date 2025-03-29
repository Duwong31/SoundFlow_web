@if(!empty($field['attr']) and !empty($attr = \Modules\Core\Models\Attributes::find($field['attr'])))
    @php
        $attr_translate = $attr->translate();
        $query_attrs = request()->query('attrs',[]);
        if(!empty($query_attrs[$attr->id][0]))
            $selected = \Modules\Core\Models\Terms::find($query_attrs[$attr->id][0]);
        else $selected = false;
        $list_cat_json = [];
    @endphp
    @if($attr)
        <div class="filter-item">
            <div class="form-group">
                <i class="field-icon icofont-beach"></i>
                <div class="form-content">
                    @foreach($attr->terms as $term)
                        @php $translate = $term->translate();
                        $list_cat_json[] = [
                            'id' => $term->id,
                            'title' => $translate->name,
                        ];
                        @endphp
                    @endforeach
                    <div class="smart-search">
                        <input type="text" class="smart-select parent_text form-control" readonly placeholder="{{__("All :name",['name'=>$attr_translate->name])}}" value="{{ $selected ? $selected->name ?? '' :'' }}" data-default="{{ json_encode($list_cat_json) }}">
                        <input type="hidden" class="child_id" name="attrs[{{$attr->id}}][]" value="{{$query_attrs[$attr->id][0] ?? ''}}">
                    </div>
                </div>
            </div>
        </div>
    @endif
@endif
