@extends('layouts.app')
@push('css')
    <link href="{{ asset('module/booking/css/checkout.css?_ver='.config('app.asset_version')) }}" rel="stylesheet">
@endpush
@section('content')
    <div class="bravo-booking-page padding-content" >
        <div class="container">
            @include ('Booking::frontend/global/booking-detail-notice')
            <div class="row booking-success-detail">
                <div class="col-md-8">
                    @include ($service->booking_customer_info_file ?? 'Booking::frontend/booking/booking-customer-info')
                    <div class="text-center">
                        <a href="{{route('user.booking_history')}}" class="btn btn-primary">{{__('Booking History')}}</a>
                    </div>
                </div>
                <div class="col-md-4">
                    @include ($service->checkout_booking_detail_file ?? '')
                </div>
            </div>
        </div>
    </div>
@endsection
