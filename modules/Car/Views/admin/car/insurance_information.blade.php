@if(is_default_lang())
<div class="panel">
    <div class="panel-title"><strong>{{__("Insurance Information")}}</strong></div>
    <div class="panel-body">
        <div class="form-group">
            <label class="control-label">{{__("Insurance Features")}}</label>
            <div class="form-group-item">
                <div class="g-items-header">
                    <div class="row">
                        <div class="col-md-1">{{__("ID")}}</div>
                        <div class="col-md-10">{{__("Feature Name")}}</div>
                        <div class="col-md-1"></div>
                    </div>
                </div>
                <div class="g-items">
                    @php
                        $insurance_info = [];
                        if(!empty($row->insurance_info)){
                            $insurance_info = is_array($row->insurance_info) ? $row->insurance_info : json_decode($row->insurance_info, true);
                        }
                        $features = $insurance_info['features'] ?? [];
                    @endphp
                    @if(!empty($features))
                        @foreach($features as $key => $feature)
                            <div class="item" data-number="{{$key}}">
                                <div class="row">
                                    <div class="col-md-1">
                                        <input type="text" name="insurance_info[features][{{$key}}][id]" class="form-control" value="{{$feature['id'] ?? ''}}">
                                    </div>
                                    <div class="col-md-10">
                                        <input type="text" name="insurance_info[features][{{$key}}][name]" class="form-control" value="{{$feature['name'] ?? ''}}">
                                    </div>
                                    <div class="col-md-1">
                                        <span class="btn btn-danger btn-sm btn-remove-item"><i class="fa fa-trash"></i></span>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>
                <div class="text-right">
                    <span class="btn btn-info btn-sm btn-add-item"><i class="icon ion-ios-add-circle-outline"></i> {{__('Add feature')}}</span>
                </div>
                <div class="g-more hide">
                    <div class="item" data-number="__number__">
                        <div class="row">
                            <div class="col-md-1">
                                <input type="text" __name__="insurance_info[features][__number__][id]" class="form-control" placeholder="{{__('ID')}}">
                            </div>
                            <div class="col-md-10">
                                <input type="text" __name__="insurance_info[features][__number__][name]" class="form-control" placeholder="{{__('Feature name')}}">
                            </div>
                            <div class="col-md-1">
                                <span class="btn btn-danger btn-sm btn-remove-item"><i class="fa fa-trash"></i></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <hr>
        <h4>{{__("Insurance Options")}}</h4>
        
        <div class="row">
            <div class="col-md-6">
                <div class="panel">
                    <div class="panel-title"><strong>{{__("Basic Option")}}</strong></div>
                    <div class="panel-body">
                        @php
                            $basic_option = $insurance_info['options']['basic'] ?? [];
                        @endphp
                        <div class="form-group">
                            <label>{{__("Name")}}</label>
                            <input type="text" name="insurance_info[options][basic][name]" class="form-control" value="{{$basic_option['name'] ?? ''}}">
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="panel">
                    <div class="panel-title"><strong>{{__("Premium Option")}}</strong></div>
                    <div class="panel-body">
                        @php
                            $premium_option = $insurance_info['options']['premium'] ?? [];
                        @endphp
                        <div class="form-group">
                            <label>{{__("Name")}}</label>
                            <input type="text" name="insurance_info[options][premium][name]" class="form-control" value="{{$premium_option['name'] ?? ''}}">
                        </div>
                        <div class="form-group">
                            <label>{{__("Price")}}</label>
                            <input type="number" name="insurance_info[options][premium][price]" class="form-control" value="{{$premium_option['price'] ?? ''}}">
                        </div>
                        <div class="form-group">
                            <label>{{__("Price Unit")}}</label>
                            <input type="text" name="insurance_info[options][premium][price_unit]" class="form-control" value="{{$premium_option['price_unit'] ?? ''}}">
                        </div>
                        <div class="form-group">
                            <label>{{__("Description")}}</label>
                            <textarea name="insurance_info[options][premium][description]" class="form-control" rows="3">{{$premium_option['description'] ?? ''}}</textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <hr>
        <h4>{{__("Passenger Insurance")}}</h4>
        <div class="panel">
            <div class="panel-body">
                @php
                    $passenger_insurance = $insurance_info['passenger_insurance'] ?? [];
                @endphp
                <div class="form-group">
                    <label>{{__("Name")}}</label>
                    <input type="text" name="insurance_info[passenger_insurance][name]" class="form-control" value="{{$passenger_insurance['name'] ?? ''}}">
                </div>
                <div class="form-group">
                    <label>{{__("Price")}}</label>
                    <input type="number" name="insurance_info[passenger_insurance][price]" class="form-control" value="{{$passenger_insurance['price'] ?? ''}}">
                </div>
                <div class="form-group">
                    <label>{{__("Price Unit")}}</label>
                    <input type="text" name="insurance_info[passenger_insurance][price_unit]" class="form-control" value="{{$passenger_insurance['price_unit'] ?? ''}}">
                </div>
                <div class="form-group">
                    <label>{{__("Description")}}</label>
                    <textarea name="insurance_info[passenger_insurance][description]" class="form-control" rows="5">{{$passenger_insurance['description'] ?? ''}}</textarea>
                </div>
            </div>
        </div>
    </div>
</div>
@endif
