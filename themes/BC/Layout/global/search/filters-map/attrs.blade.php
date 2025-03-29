@php
    $selected = (array) Request::query('attrs',[]);
@endphp
@foreach ($attributes as $item)
    @if(empty($item['hide_in_filter_search']))
        @php
            if(in_array($item->id,$usedAttrs)) continue;
            $translate = $item->translate();
        @endphp
        <div class="filter-item">
            <div class="filter-title"><strong>{{$translate->name}}</strong></div>
            <ul class="filter-items row">
                @foreach($item->terms as $term)
                    @php $translate = $term->translate(); @endphp
                    <li class="filter-term-item col-xs-6 col-md-4">
                        <label>
                            <input
                                @if(in_array($term->slug,$selected[$item->id] ?? [])) checked
                                @endif type="checkbox"
                                name="attrs[{{$item->id}}][]"
                                value="{{$term->slug}}"
                            > {{$translate->name}}
                        </label>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
@endforeach
