@php
    $selected = (array) Request::query('attrs',[]);
@endphp
@foreach ($attributes as $item)
    @if(empty($item['hide_in_filter_search']))
        @php
            $translate = $item->translate();
        @endphp
        <div class="g-filter-item">
            <div class="item-title">
                <h3> {{$translate->name}} </h3>
                <i class="fa fa-angle-up" aria-hidden="true"></i>
            </div>
            <div class="item-content">
                <ul>
                    @foreach($item->terms as $key => $term)
                        @php $translate = $term->translate(); @endphp
                        <li @if($key > 2 and empty($selected)) class="hide" @endif>
                            <div class="bravo-checkbox">
                                <label>
                                    <input @if(in_array($term->slug,$selected[$item->id] ?? [])) checked @endif type="checkbox" name="attrs[{{$item->id}}][]" value="{{$term->slug}}"> {!! $translate->name !!}
                                    <span class="checkmark"></span>
                                </label>
                            </div>
                        </li>
                    @endforeach
                </ul>
                @if(count($item->terms) > 3 and empty($selected))
                    <button type="button" class="btn btn-link btn-more-item">{{__("More")}} <i class="fa fa-caret-down"></i></button>
                @endif
            </div>
        </div>
    @endif
@endforeach
