<?php
/**
 * @var $translation \Modules\Boat\Models\BoatTranslation
 * @var $row \Modules\Boat\Models\Boat
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

<div class="g-boat-feature">
    <div class="row">
        @if($row->max_guest)
            <div class="col-xs-6 col-lg-3 col-md-6">
                <div class="item">
                    <div class="icon">
                        <i class="icofont-ui-user-group"></i>
                    </div>
                    <div class="info">
                        <h4 class="name">{{__("Max Guests")}}</h4>
                        <p class="value">
                            {{$row->max_guest}}
                        </p>
                    </div>
                </div>
            </div>
        @endif
        @if($row->cabin)
            <div class="col-xs-6 col-lg-3 col-md-6">
                <div class="item">
                    <div class="icon">
                        <i class="icofont-sail-boat-alt-2"></i>
                    </div>
                    <div class="info">
                        <h4 class="name">{{__("Cabin")}}</h4>
                        <p class="value">
                            {{$row->cabin}}
                        </p>
                    </div>
                </div>
            </div>
        @endif
        @if($row->length)
            <div class="col-xs-6 col-lg-3 col-md-6">
                <div class="item">
                    <div class="icon">
                        <i class="icofont-yacht"></i>
                    </div>
                    <div class="info">
                        <h4 class="name">{{__("Length Boat")}}</h4>
                        <p class="value">
                            {{$row->length}}
                        </p>
                    </div>
                </div>
            </div>
        @endif
        @if(!empty($row->speed))
            <div class="col-xs-6 col-lg-3 col-md-6">
                <div class="item">
                    <div class="icon">
                        <i class="icofont-ship"></i>
                    </div>
                    <div class="info">
                        <h4 class="name">{{__("Speed")}}</h4>
                        <p class="value">
                            {{$row->speed}}
                        </p>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>


@include('Layout::global.details.gallery')
@if($translation->content)
    <div class="g-overview">
        <h3>{{__("Description")}}</h3>
        <div class="description">
            {!! clean($translation->content) !!}
        </div>
    </div>
@endif


@include('Boat::frontend.layouts.details.specs')
@include('Boat::frontend.layouts.details.attributes')
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
@if(!empty($translation->cancel_policy))
    <div class="g-overview">
        <h3>{{__("Cancellation Policy")}}</h3>
        <div class="description">
            <?php echo $translation->cancel_policy ?>
        </div>
    </div>
@endif
@if(!empty($translation->terms_information))
    <div class="g-overview">
        <h3>{{__("Additional Terms & Information")}}</h3>
        <div class="description">
            <?php echo $translation->terms_information ?>
        </div>
    </div>
@endif
@include('Boat::frontend.layouts.details.include-exclude')
@if($row->map_lat && $row->map_lng)
<div class="g-location">
    <h3>{{__("Location")}}</h3>
    <div class="location-map">
        <div id="map_content"></div>
    </div>
</div>
@endif
