<?php
/**
 * @var $translation \Modules\Event\Models\EventTranslation
 * @var $row \Modules\Event\Models\Event
 */
?>
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

@if(!empty($row->duration)  or !empty($row->location->name))
    <div class="g-event-feature">
        <div class="row">
            <div class="col-xs-6 col-lg-3 col-md-6">
                <div class="item">
                    <div class="icon">
                        <i class="icofont-heart-beat"></i>
                    </div>
                    <div class="info">
                        <h4 class="name">{{__("Wishlist")}}</h4>
                        <p class="value">
                            {{ __("People interest: :number",['number'=>$row->getNumberWishlistInService()]) }}
                        </p>
                    </div>
                </div>
            </div>
            @if($row->start_time)
                <div class="col-xs-6 col-lg-3 col-md-6">
                    <div class="item">
                        <div class="icon">
                            <i class="icofont-wall-clock"></i>
                        </div>
                        <div class="info">
                            <h4 class="name">{{__("Start Time")}}</h4>
                            <p class="value">
                                {{ $row->start_time }}
                            </p>
                        </div>
                    </div>
                </div>
            @endif
            @if($row->duration)
                <div class="col-xs-6 col-lg-3 col-md-6">
                    <div class="item">
                        <div class="icon">
                            <i class="icofont-infinite"></i>
                        </div>
                        <div class="info">
                            <h4 class="name">{{__("Duration")}}</h4>
                            <p class="value">
                                {{duration_format($row->duration)}}
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
        <h3>{{__("Description")}}</h3>
        <div class="description">
            <?php echo $translation->content ?>
        </div>
    </div>
@endif
@include('Event::frontend.layouts.details.attributes')
@if($translation->faqs)
<div class="g-faq">
    <h3> {{__("FAQs")}} </h3>
    @foreach($translation->faqs as $item)
        <div class="item">
            <div class="header">
                <i class="field-icon icofont-support-faq"></i>
                <h5>{{$item['title']}}</h5>
                <span class="arrow"><i class="fa fa-angle-down"></i></span>
            </div>
            <div class="body">
                {{$item['content']}}
            </div>
        </div>
    @endforeach
</div>
@endif
<div class="bravo-hr"></div>
@includeIf("Hotel::frontend.layouts.details.hotel-surrounding")
<div class="bravo-hr"></div>

@if($row->map_lat && $row->map_lng)
<div class="g-location">
    <h3>{{__("Location")}}</h3>
    <div class="location-map">
        <div id="map_content"></div>
    </div>
</div>
@endif
