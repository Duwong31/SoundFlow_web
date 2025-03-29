<div class="g-header">
    <div class="left">
        @if($row->star_rate)
            <div class="star-rate">
                @for ($star = 1 ;$star <= $row->star_rate ; $star++)
                    <i class="fa fa-star"></i>
                @endfor
            </div>
        @endif
        <h1>{{$translation->title}}</h1>
        @if($translation->address)
            <h2 class="address"><i class="fa fa-map-marker"></i>
                {{$translation->address}}
            </h2>
        @endif
    </div>
    <div class="right">
        @if($row->getReviewEnable())
            @if($review_score)
                <div class="review-score">
                    <div class="head">
                        <div class="left">
                            <span class="head-rating">{{$review_score['score_text']}}</span>
                            <span class="text-rating">{{__("from :number reviews",['number'=>$review_score['total_review']])}}</span>
                        </div>
                        <div class="score">
                            {{$review_score['score_total']}}<span>/5</span>
                        </div>
                    </div>
                    <div class="foot">
                        {{__(":number% of guests recommend",['number'=>$row->recommend_percent])}}
                    </div>
                </div>
            @endif
        @endif
    </div>
</div>
@include('Layout::global.details.gallery')
@if($translation->content)
    <div class="g-overview">
        <h3>{{__("Description")}}</h3>
        <div class="description">
            <?php echo $translation->content ?>
        </div>
    </div>
@endif
@include('Hotel::frontend.layouts.details.hotel-rooms')
<div class="g-all-attribute is_mobile">
    @include('Hotel::frontend.layouts.details.hotel-attributes')
</div>
<div class="g-rules">
    <h3>{{__("Rules")}}</h3>
    <div class="description">
        <div class="row">
            <div class="col-lg-4">
                <div class="key">{{__("Check In")}}</div>
            </div>
            <div class="col-lg-8">
                <div class="value">	{{$row->check_in_time}} </div>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-4">
                <div class="key">{{__("Check Out")}}</div>
            </div>
            <div class="col-lg-8">
                <div class="value">	{{$row->check_out_time}} </div>
            </div>
        </div>
        @if($translation->policy)
            <div class="row">
                <div class="col-lg-4">
                    <div class="key">{{__("Hotel Policies")}}</div>
                </div>
                <div class="col-lg-8">
                    @foreach($translation->policy as $key => $item)
                        <div class="item @if($key > 1) d-none @endif">
                            <div class="strong">{{$item['title']}}</div>
                            <div class="context">{!! $item['content'] !!}</div>
                        </div>
                    @endforeach
                    @if( count($translation->policy) > 2)
                        <div class="btn-show-all">
                            <span class="text">{{__("Show All")}}</span>
                            <i class="fa fa-caret-down"></i>
                        </div>
                    @endif
                </div>
            </div>
        @endif
    </div>
</div>
<div class="bravo-hr"></div>
@includeIf("Hotel::frontend.layouts.details.hotel-surrounding")

@if($row->map_lat && $row->map_lng)
    <div class="g-location">
        <div class="location-title">
            <h3>{{__("Location")}}</h3>
            @if($translation->address)
                <div class="address">
                    <i class="icofont-location-arrow"></i>
                    {{$translation->address}}
                </div>
            @endif
        </div>

        <div class="location-map">
            <div id="map_content"></div>
        </div>
    </div>
@endif
<div class="bravo-hr"></div>
