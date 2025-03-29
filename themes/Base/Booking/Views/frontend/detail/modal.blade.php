<ul class="nav nav-tabs mb-2">
    <li class="nav-item">
        <a class="nav-link active" data-toggle="tab" data-bs-toggle="tab" href="#booking-detail-{{$booking->id}}">{{__("Booking Detail")}}</a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-toggle="tab" data-bs-toggle="tab" href="#booking-customer-{{$booking->id}}">
            @if(!empty($informationRole))
                {{__("Customer Information")}}
            @else
                {{__('Personal Information')}}
            @endif
        </a>
    </li>
    @if(count($booking->passengers))
        <li class="nav-item">
            <a class="nav-link" data-toggle="tab" data-bs-toggle="tab" href="#booking-guests-{{$booking->id}}">
                {{__('Guests Information')}}
            </a>
        </li>
    @endif
    <li class="nav-item">
        <a class="nav-link" data-toggle="tab" data-bs-toggle="tab" href="#booking-note-{{$booking->id}}">
            {{__('Booking Note')}}
        </a>
    </li>
</ul>
<div class="tab-content">
    <div id="booking-detail-{{$booking->id}}" class="tab-pane active">
        <div class="booking-review">
            <div class="booking-review-content">
                <div class="review-section">
                    <div class="info-form">
                        <ul>
                            <li>
                                <div class="label">{{__('Booking Status')}}</div>
                                <div class="val">{{$booking->statusName}}</div>
                            </li>
                            <li>
                                <div class="label">{{__('Booking Date')}}</div>
                                <div class="val">{{display_date($booking->created_at)}}</div>
                            </li>
                            @if(!empty($booking->gateway))
                                <?php $gateway = get_payment_gateway_obj($booking->gateway);?>
                                @if($gateway)
                                    <li>
                                        <div class="label">{{__('Payment Method')}}</div>
                                        <div class="val">{{$gateway->name}}</div>
                                    </li>
                                @endif
                            @endif
                            @php $vendor = $service->author; @endphp
                            @if($vendor->hasPermission('dashboard_vendor_access') and !$vendor->hasPermission('dashboard_access'))
                                <li>
                                    <div class="label">{{ __("Vendor") }}</div>
                                    <div class="val"><a href="{{route('user.profile',['id'=>$vendor->id])}}" target="_blank" >{{$vendor->getDisplayName()}}</a></div>
                                </li>
                            @endif
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <div class="more-booking-review">
            @include ($service->checkout_booking_detail_file ?? '')
        </div>
    </div>
    <div id="booking-customer-{{$booking->id}}" class="tab-pane fade">
        @include ($service->booking_customer_info_file ?? 'Booking::frontend/booking/booking-customer-info')
    </div>
    <div id="booking-guests-{{$booking->id}}" class="tab-pane fade">
        @include ('Booking::frontend.detail.passengers')
    </div>
    <div id="booking-note-{{$booking->id}}" class="tab-pane fade">
        <div class="bc-note-form">
            <div class="form-group ">
                <label for="note">{{ __("Note") }}</label>
                <textarea class="form-control mb-2" id="note" name="note" rows="5">{{ $booking->getMeta("note_for_vendor") }}</textarea>
                <input type="hidden" name="id" value="{{$booking->id}}">
                <div class="message_box"></div>
            </div>
            <div class="form-group">
                <button type="button" class="btn btn-primary btn-submit-note-vendor"> {{ __("Save") }}
                    <i class="fa icon-loading fa-spinner fa-spin fa-fw d-none"></i>
                </button>
            </div>
        </div>
    </div>
</div>
