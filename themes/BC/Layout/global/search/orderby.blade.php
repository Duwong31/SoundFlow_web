@if(empty($hideMap))
<div class="item">
    <a href="{{ route($routeName,['_layout'=>'map']) }}">{{__("Show on the map")}}</a>
</div>
@endif
<div class="item orderby">
    @php
        $param = request()->input();
        $orderby =  request()->input("orderby");
    @endphp
    <div class="item-title">
        {{ __("Sort by:") }}
    </div>
    <input type="hidden" name="orderby" value="{{$orderby}}">
    <div class="dropdown ">
        <span class=" dropdown-toggle"  data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            @switch($orderby)
                @case("price_low_high")
                {{ __("Price (Low to high)") }}
                @break
                @case("price_high_low")
                {{ __("Price (High to low)") }}
                @break
                @case("rate_high_low")
                {{ __("Rating (High to low)") }}
                @break
                @default
                {{ __("Recommended") }}
            @endswitch
        </span>
        <div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenuButton">
            <a class="dropdown-item" href="#" data-value="">{{ __("Recommended") }}</a>
            <a class="dropdown-item" href="#" data-value="price_low_high">{{ __("Price (Low to high)") }}</a>
            <a class="dropdown-item" href="#" data-value="price_high_low">{{ __("Price (High to low)") }}</a>
            <a class="dropdown-item" href="#" data-value="rate_high_low">{{ __("Rating (High to low)") }}</a>
        </div>
    </div>
</div>
