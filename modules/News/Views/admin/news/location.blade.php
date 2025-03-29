<div class="panel">
    <div class="panel-title"><strong>{{__("Location")}}</strong></div>
    <div class="panel-body">
        @if(is_default_lang())
            <div class="form-group">
                <label class="control-label">{{__("Location")}}</label>
                @if(!empty($is_smart_search))
                    <div class="form-group-smart-search">
                        <div class="form-content">
                            <?php
                            $location_name = "";
                            $list_json = [];
                            foreach ($locations as $location) {
                                if ($row->location == $location->id) {
                                    $location_name = $location->name;
                                }
                                $list_json[] = [
                                    'id' => $location->id,
                                    'title' => $location->name,
                                ];
                            }
                            ?>
                            <div class="smart-search">
                                <input type="text" class="smart-search-location parent_text form-control" placeholder="{{__("-- Please Select --")}}" value="{{ $location_name }}" data-onLoad="{{__("Loading...")}}"
                                       data-default="{{ json_encode($list_json) }}">
                                <input type="hidden" class="child_id" name="location" value="{{$row->location}}">
                            </div>
                        </div>
                    </div>
                @else
                    <div class="">
                        <select name="location" class="form-control">
                            <option value="">{{__("-- Please Select --")}}</option>
                            @foreach($locations as $location)
                                <option value="{{ $location->id }}" @if($row->location == $location->id) selected @endif>
                                    {{ $location->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif
            </div>
        @endif
        <div class="form-group">
            <label class="control-label">{{__("Display on map")}}</label>
            <div class="control-map-group">
                <div id="map_content" style="height: 400px;"></div>
                <input type="text" placeholder="{{__("Search by name...")}}" class="bravo_searchbox form-control" autocomplete="off" onkeydown="return event.key !== 'Enter';">
                <div class="g-control">
                    <div class="form-group">
                        <label>{{__("Map Latitude")}}:</label>
                        <input type="text" name="map_lat" class="form-control" value="{{$row->map_lat ?? '21.028511'}}" onkeydown="return event.key !== 'Enter';">
                    </div>
                    <div class="form-group">
                        <label>{{__("Map Longitude")}}:</label>
                        <input type="text" name="map_lng" class="form-control" value="{{$row->map_lng ?? '105.804817'}}" onkeydown="return event.key !== 'Enter';">
                    </div>
                    <div class="form-group">
                        <label>{{__("Map Zoom")}}:</label>
                        <input type="text" name="map_zoom" class="form-control" value="{{$row->map_zoom ?? '12'}}" onkeydown="return event.key !== 'Enter';">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('js')
    {!! \App\Helpers\MapEngine::scripts() !!}
    <script>
        jQuery(function ($) {
            new BravoMapEngine('map_content', {
                fitBounds: true,
                center: [{{$row->map_lat ?? "21.028511"}}, {{$row->map_lng ?? "105.804817"}}],
                zoom: {{$row->map_zoom ?? "12"}},
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

                    engineMap.searchBox($('.bravo_searchbox'),function (dataLatLng) {
                        engineMap.clearMarkers();
                        engineMap.addMarker(dataLatLng, {
                            icon_options: {}
                        });
                        $("input[name=map_lat]").attr("value", dataLatLng[0]);
                        $("input[name=map_lng]").attr("value", dataLatLng[1]);
                    });
                }
            });
        })
    </script>
@endpush 