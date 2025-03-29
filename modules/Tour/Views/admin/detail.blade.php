@extends('admin.layouts.app',['body_class'=>'resource-edit-page'])

@section('content')
    <form
        class="overflow-y-auto d-flex"
        action="{{route('tour.admin.store',['id'=>($row->id) ? $row->id : '-1','lang'=>request()->query('lang')])}}"
        method="post"
    >
        @csrf
        <div class="container-fluid overflow-y-auto d-flex flex-column">
            <div class="d-flex justify-content-between mb20">
                <div class="">
                    <h1 class="title-bar">{{$row->id ? __('Edit: ').$row->title : __('Add new tour')}}</h1>
                    @if($row->slug)
                        <p class="item-url-demo">{{__("Permalink")}}: {{ url(config('tour.tour_route_prefix') ) }}/<a href="#" class="open-edit-input" data-name="slug">{{$row->slug}}</a>
                        </p>
                    @endif
                </div>
                <div class="">
                    @if($row->slug)
                        <a class="btn btn-primary btn-sm" href="{{$row->getDetailUrl(request()->query('lang'))}}" target="_blank">{{__("View Tour")}}</a>
                    @endif
                </div>
            </div>
            @include('admin.message')
            @if($row->id)
                @include('Language::admin.navigation')
            @endif
            <div class="lang-content-box overflow-y-auto h-100 pr-0 pb-0">
                <div class="d-flex h-100">
                    <div class="col-md-2 p-0">
                        <div class="card">
                            <div class="card-header d-flex align-items-center">
                                <strong>
                                    <i class="fa fa-cogs"></i>
                                    {{__("Tour Information")}}</strong>
                            </div>
                            <div class="list-group list-group-flush">
                                <a href="#tab_general" class="list-group-item list-group-item-action active" data-toggle="tab">{{__('General')}}</a>
                                <a
                                    href="#tab_location" class="list-group-item list-group-item-action" data-toggle="tab"
                                >
                                    {{__('Location')}}
                                </a>
                                <a
                                    href="#tab_pricing"
                                    class="list-group-item list-group-item-action d-flex justify-content-between align-items-center @if(!$row->id) disabled @endif"
                                    data-toggle="tab"
                                >
                                    {{__('Pricing')}}
                                    @if(!$row->id)
                                        <i class="fa fa-lock"></i>
                                    @endif
                                </a>
                                <a
                                    href="#tab_availability"
                                    class="list-group-item list-group-item-action d-flex justify-content-between align-items-center @if(!$row->id) disabled @endif"
                                    data-toggle="tab"
                                >
                                    {{__('Availability')}}
                                    @if(!$row->id)
                                        <i class="fa fa-lock"></i>
                                    @endif
                                </a>
                                <a href="#tab_status" class="list-group-item list-group-item-action " data-toggle="tab">{{__('Status')}}</a>
                                <a href="#tab_seo" class="list-group-item list-group-item-action " data-toggle="tab">{{__('SEO')}}</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-10 overflow-y-auto h-100">
                        <div class="d-flex flex-column overflow-y-auto h-100">
                            <div
                                class="tab-content flex-grow-1 overflow-y-auto mb-3 p-1"
                                data-action="{{route('tour.admin.store',['id'=>($row->id) ? $row->id : '-1','lang'=>request()->query('lang')])}}"
                            >
                                <div class="tab-pane active" id="tab_general">
                                    @include('Tour::admin/tour/tour-content')
                                    @include('Hotel::admin.hotel.surrounding')
                                </div>
                                <div class="tab-pane" id="tab_location">
                                    @include('Tour::admin/tour/tour-location')
                                </div>
                                @if($row->id)
                                    <div class="tab-pane" id="tab_pricing">
                                        @include('Tour::admin/tour/pricing')
                                    </div>
                                    <div class="tab-pane" id="tab_availability">
                                        @include('Tour::admin/tour/availability')
                                    </div>
                                @endif
                                <div class="tab-pane" id="tab_seo">
                                    @include('Core::admin/seo-meta/seo-meta')
                                </div>
                                <div class="tab-pane" id="tab_status">
                                    <div class="panel">
                                        <div class="panel-title"><strong>{{__('Publish')}}</strong></div>
                                        <div class="panel-body">
                                            @if(is_default_lang())
                                                <div>
                                                    <label>
                                                        <input
                                                            @if($row->status=='publish') checked @endif type="radio" name="status" value="publish"
                                                        > {{__("Publish")}}
                                                    </label>
                                                </div>
                                                <div>
                                                    <label>
                                                        <input
                                                            @if($row->status=='draft') checked @endif type="radio" name="status" value="draft"
                                                        > {{__("Draft")}}
                                                    </label>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                    @if(is_default_lang())
                                        <div class="panel">
                                            <div class="panel-title"><strong>{{__("Author Setting")}}</strong></div>
                                            <div class="panel-body">
                                                <div class="form-group">
                                                        <?php
                                                        $user = $row->author;
                                                        \App\Helpers\AdminForm::select2('author_id', [
                                                            'configs' => [
                                                                'ajax'        => [
                                                                    'url'      => route('user.admin.getForSelect2'),
                                                                    'dataType' => 'json'
                                                                ],
                                                                'allowClear'  => true,
                                                                'placeholder' => __('-- Select User --')
                                                            ]
                                                        ], !empty($user->id) ? [
                                                            $user->id,
                                                            $user->getDisplayName() . ' (#' . $user->id . ')'
                                                        ] : false)
                                                        ?>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                    @if(is_default_lang())
                                        <div class="panel">
                                            <div class="panel-title"><strong>{{__("Tour Featured")}}</strong></div>
                                            <div class="panel-body">
                                                <div class="form-group">
                                                    <input
                                                        type="checkbox" name="is_featured" @if($row->is_featured) checked @endif value="1"
                                                    > {{__("Enable featured")}}
                                                </div>
                                                <div class="form-group">
                                                    <label>{{__('Default State')}}</label>
                                                    <br>
                                                    <select name="default_state" class="custom-select">
                                                        <option
                                                            value="1"
                                                            @if(old('default_state',$row->default_state ?? -1) == 1) selected @endif>{{__("Always available")}}</option>
                                                        <option
                                                            value="0"
                                                            @if(old('default_state',$row->default_state ?? -1) == 0) selected @endif>{{__("Only available on specific dates")}}</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        @include('Tour::admin/tour/attributes')
                                        <div class="panel">
                                            <div class="panel-title"><strong>{{__('Feature Image')}}</strong></div>
                                            <div class="panel-body">
                                                <div class="form-group">
                                                    {!! \Modules\Media\Helpers\FileHelper::fieldUpload('image_id',$row->image_id) !!}
                                                </div>
                                            </div>
                                        </div>
                                        @include('Tour::admin/tour/ical')
                                    @endif
                                </div>
                            </div>
                            <div class="flex-shrink-0 d-flex justify-content-between pb-3">
                                <div></div>
                                <div>
                                    <div class="text-right">
                                        <button class="btn btn-success" type="submit">
                                            <i class="fa fa-save"></i> {{__('Save changes')}}</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection
@push('js')
    {!! App\Helpers\MapEngine::scripts() !!}
    <script>
        jQuery(function ($) {
            new BravoMapEngine('map_content', {
                disableScripts: true,
                fitBounds: true,
                center: [{{$row->map_lat ?? setting_item('map_lat_default',51.505 ) }}, {{$row->map_lng ?? setting_item('map_lng_default',-0.09 ) }}],
                zoom:{{$row->map_zoom ?? "8"}},
                ready: function (engineMap) {
                    @if($row->map_lat && $row->map_lng)
                    engineMap.addMarker([{{$row->map_lat}}, {{$row->map_lng}}], {
                        icon_options: {}
                    });
                    @endif
                    engineMap.on('click', function (dataLatLng) {
                        engineMap.clearMarkers();
                        engineMap.addMarker(dataLatLng, {
                            icon_options: {}
                        });
                        $("input[name=map_lat]").attr("value", dataLatLng[0]);
                        $("input[name=map_lng]").attr("value", dataLatLng[1]);
                    });
                    engineMap.on('zoom_changed', function (zoom) {
                        $("input[name=map_zoom]").attr("value", zoom);
                    });
                    if(bookingCore.map_provider === "gmap"){
                        engineMap.searchBox($('#customPlaceAddress'),function (dataLatLng) {
                            engineMap.clearMarkers();
                            engineMap.addMarker(dataLatLng, {
                                icon_options: {}
                            });
                            $("input[name=map_lat]").attr("value", dataLatLng[0]);
                            $("input[name=map_lng]").attr("value", dataLatLng[1]);
                        });
                    }
                    engineMap.searchBox($('.bravo_searchbox'),function (dataLatLng) {
                        engineMap.clearMarkers();
                        engineMap.addMarker(dataLatLng, {
                            icon_options: {}
                        });
                        $("input[name=map_lat]").attr("value", dataLatLng[0]);
                        $("input[name=map_lng]").attr("value", dataLatLng[1]);
                    });
                    if(bookingCore.map_provider === "osm"){
                        $(".list-group-item").click(function (){
                            window.dispatchEvent(new Event('resize'));
                        });
                    }
                }
            });
        })
    </script>
@endpush
