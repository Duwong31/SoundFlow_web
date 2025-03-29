<div class="g-header">
    <div class="left">
        <h1>{{$translation->title}}</h1>
        @if($translation->address)
            <p class="address"><i class="fa fa-map-marker"></i>
                {{$translation->address}}
            </p>
        @endif
    </div>
    <div class="right">
        @if(setting_item('tour_enable_review') and $review_score)
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
    </div>
</div>
@if(!empty($row->duration) or !empty($row->category_tour->name) or !empty($row->max_people) or !empty($row->location->name))
    <div class="g-tour-feature">
    <div class="row">
        @if($row->duration)
            <div class="col-xs-6 col-lg-3 col-md-6">
                <div class="item">
                    <div class="icon">
                        <i class="icofont-wall-clock"></i>
                    </div>
                    <div class="info">
                        <h4 class="name">{{__("Duration")}}</h4>
                        <p class="value">
                            {{duration_format($row->duration,true)}}
                        </p>
                    </div>
                </div>
            </div>
        @endif
        @if(!empty($row->category_tour->name))
            @php $cat =  $row->category_tour->translate() @endphp
            <div class="col-xs-6 col-lg-3 col-md-6">
                <div class="item">
                    <div class="icon">
                        <i class="icofont-beach"></i>
                    </div>
                    <div class="info">
                        <h4 class="name">{{__("Tour Type")}}</h4>
                        <p class="value">
                            {{$cat->name ?? ''}}
                        </p>
                    </div>
                </div>
            </div>
        @endif
        @if($row->max_people)
            <div class="col-xs-6 col-lg-3 col-md-6">
                <div class="item">
                    <div class="icon">
                        <i class="icofont-travelling"></i>
                    </div>
                    <div class="info">
                        <h4 class="name">{{__("Group Size")}}</h4>
                        <p class="value">
                            @if($row->max_people > 1)
                                {{ __(":number persons",array('number'=>$row->max_people)) }}
                            @else
                                {{ __(":number person",array('number'=>$row->max_people)) }}
                            @endif
                        </p>
                    </div>
                </div>
            </div>
        @endif
        @if(!empty($row->location->name))
                @php $location =  $row->location->translate() @endphp
            <div class="col-xs-6 col-lg-3 col-md-6">
                <div class="item">
                    <div class="icon">
                        <i class="icofont-island-alt"></i>
                    </div>
                    <div class="info">
                        <h4 class="name">{{__("Location")}}</h4>
                        <p class="value">
                            {{$location->name ?? ''}}
                        </p>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
@endif
@include('Layout::global.details.gallery')
@if($translation->content)
    <div class="g-overview">
        <h3>{{__("Overview")}}</h3>
        <div class="description">
            <?php echo $translation->content ?>
        </div>
    </div>
@endif
@include('Tour::frontend.layouts.details.tour-include-exclude')
@include('Tour::frontend.layouts.details.tour-itinerary')
@include('Tour::frontend.layouts.details.tour-attributes')
@include('Tour::frontend.layouts.details.tour-faqs')
@includeIf("Hotel::frontend.layouts.details.hotel-surrounding")

@if($row->map_lat && $row->map_lng)
<div class="g-location">
    <div class="location-title">
        <h3>{{__("Tour Location")}}</h3>
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
