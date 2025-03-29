@if(is_default_lang())
<div class="panel">
    <div class="panel-title"><strong>{{__("Other Information")}}</strong></div>
    <div class="panel-body">
        <div class="form-group-item">
            <label class="control-label">{{__('Fee Items')}}</label>
            <div class="g-items-header">
                <div class="row">
                    <div class="col-md-2">{{__("Icon")}}</div>
                    <div class="col-md-2">{{__("Name")}}</div>
                    <div class="col-md-3">{{__('Amount')}}</div>
                    <div class="col-md-2">{{__('Unit')}}</div>
                    <div class="col-md-2">{{__('Description')}}</div>
                    <div class="col-md-1"></div>
                </div>
            </div>
            <div class="g-items">
                @if(!empty($row->additional_fees))
                    @php 
                        if(is_string($row->additional_fees)) {
                            $additional_fees = json_decode($row->additional_fees, true);
                        } else {
                            $additional_fees = $row->additional_fees;
                        }
                        
                        // Ensure it's an array
                        if(!is_array($additional_fees)) {
                            $additional_fees = [];
                        }
                    @endphp
                    @foreach($additional_fees as $key=>$fee)
                        <div class="item" data-number="{{$key}}">
                            <div class="row">
                                <div class="col-md-2">
                                    {!! \Modules\Media\Helpers\FileHelper::fieldUpload('additional_fees['.$key.'][icon_id]', $fee['icon_id'] ?? '') !!}
                                </div>
                                <div class="col-md-2">
                                    <input type="text" name="additional_fees[{{$key}}][name]" class="form-control" value="{{$fee['name'] ?? ''}}" placeholder="{{__('Fee name')}}">
                                </div>
                                <div class="col-md-3">
                                    <input type="number" name="additional_fees[{{$key}}][amount]" class="form-control" value="{{$fee['amount'] ?? ''}}" placeholder="{{__('Amount')}}">
                                </div>
                                <div class="col-md-2">
                                    <input type="text" name="additional_fees[{{$key}}][unit]" class="form-control" value="{{$fee['unit'] ?? ''}}" placeholder="{{__('đ/giờ, đ/km')}}">
                                </div>
                                <div class="col-md-2">
                                    <textarea name="additional_fees[{{$key}}][description]" class="form-control" placeholder="{{__('Fee description')}}">{{$fee['description'] ?? ''}}</textarea>
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
                <span class="btn btn-info btn-sm btn-add-item"><i class="icon ion-ios-add-circle-outline"></i> {{__('Add item')}}</span>
            </div>
            <div class="g-more hide">
                <div class="item" data-number="__number__">
                    <div class="row">
                        <div class="col-md-2">
                            {!! \Modules\Media\Helpers\FileHelper::fieldUpload('additional_fees[__number__][icon_id]', '') !!}
                        </div>
                        <div class="col-md-2">
                            <input type="text" __name__="additional_fees[__number__][name]" class="form-control" placeholder="{{__('Fee name')}}">
                        </div>
                        <div class="col-md-3">
                            <input type="number" __name__="additional_fees[__number__][amount]" class="form-control" placeholder="{{__('Amount')}}">
                        </div>
                        <div class="col-md-2">
                            <input type="text" __name__="additional_fees[__number__][unit]" class="form-control" placeholder="{{__('đ/giờ, đ/km')}}">
                        </div>
                        <div class="col-md-2">
                            <textarea __name__="additional_fees[__number__][description]" class="form-control" placeholder="{{__('Fee description')}}"></textarea>
                        </div>
                        <div class="col-md-1">
                            <span class="btn btn-danger btn-sm btn-remove-item"><i class="fa fa-trash"></i></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endif
