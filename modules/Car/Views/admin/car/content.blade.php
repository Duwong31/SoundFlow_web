<div class="panel">
    <div class="panel-title"><strong>{{__("Car Content")}}</strong></div>
    <div class="panel-body">
        <div class="form-group magic-field" data-id="title" data-type="title">
            <label class="control-label">{{__("Title")}}</label>
            <input type="text" value="{{$translation->title}}" placeholder="{{__("Title")}}" name="title" class="form-control">
        </div>
        @if(is_default_lang())
        <div class="form-group">
            <label class="control-label">{{__("Brand")}}</label>
            <select name="brand_id" class="form-control" id="brand_select">
                <option value="">{{__("-- Select Brand --")}}</option>
                @foreach(\Modules\Car\Models\CarBrand::where('status', 1)->get() as $brand)
                    <option value="{{$brand->id}}" @if($row->brand_id == $brand->id) selected @endif data-name="{{$brand->name}}">{{$brand->name}}</option>
                @endforeach
            </select>
            <input type="hidden" name="brand" id="brand_name" value="{{$row->brand}}" required>
        </div>
        <div class="form-group">
            <label class="control-label">{{__("Model")}}</label>
            <input type="text" value="{{$row->model}}" placeholder="{{__("Model")}}" name="model" class="form-control">
        </div>
        @endif
        <div class="form-group magic-field" data-id="content" data-type="content">
            <label class="control-label">{{__("Introduction car")}}</label>
            <div class="">
                <textarea name="content" class="d-none has-ckeditor" id="content" cols="30" rows="10">{{$translation->content}}</textarea>
            </div>
        </div>
        <!-- @if(is_default_lang())
            <div class="form-group">
                <label class="control-label">{{__("Youtube Video")}}</label>
                <input type="text" name="video" class="form-control" value="{{$row->video}}" placeholder="{{__("Youtube link video")}}">
            </div>
        @endif -->
        <!-- <div class="form-group-item">
            <label class="control-label">{{__('FAQs')}}</label>
            <div class="g-items-header">
                <div class="row">
                    <div class="col-md-5">{{__("Title")}}</div>
                    <div class="col-md-5">{{__('Content')}}</div>
                    <div class="col-md-1"></div>
                </div>
            </div>
            <div class="g-items">
                @if(!empty($translation->faqs))
                    @php if(!is_array($translation->faqs)) $translation->faqs = json_decode($translation->faqs); @endphp
                    @foreach($translation->faqs as $key=>$faq)
                        <div class="item" data-number="{{$key}}">
                            <div class="row">
                                <div class="col-md-5">
                                    <input type="text" name="faqs[{{$key}}][title]" class="form-control" value="{{$faq['title']}}" placeholder="{{__('Eg: When and where does the tour end?')}}">
                                </div>
                                <div class="col-md-6">
                                    <textarea name="faqs[{{$key}}][content]" class="form-control" placeholder="...">{{$faq['content']}}</textarea>
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
                        <div class="col-md-5">
                            <input type="text" __name__="faqs[__number__][title]" class="form-control" placeholder="{{__('Eg: Can I bring my pet?')}}">
                        </div>
                        <div class="col-md-6">
                            <textarea __name__="faqs[__number__][content]" class="form-control" placeholder=""></textarea>
                        </div>
                        <div class="col-md-1">
                            <span class="btn btn-danger btn-sm btn-remove-item"><i class="fa fa-trash"></i></span>
                        </div>
                    </div>
                </div>
            </div>
        </div> -->
        @if(is_default_lang())
            <div class="form-group">
                <label class="control-label">{{__("Banner Image")}}</label>
                <div class="form-group-image">
                    {!! \Modules\Media\Helpers\FileHelper::fieldUpload('banner_image_id',$row->banner_image_id) !!}
                </div>
            </div>
            <div class="form-group">
                <label class="control-label">{{__("Gallery")}}</label>
                {!! \Modules\Media\Helpers\FileHelper::fieldGalleryUpload('gallery',$row->gallery) !!}
            </div>
        @endif
    </div>
</div>

@if(is_default_lang())
    <div class="panel">
        <div class="panel-title"><strong>{{__("Extra Info")}}</strong></div>
        <div class="panel-body">
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label>{{__("Fuel Capacity")}}</label>
                        <input type="number" value="{{$row->fuel_capacity}}" placeholder="{{__("Example: 50")}}" name="fuel_capacity" class="form-control">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>{{__("Rental Duration")}}</label>
                        <input type="varchar" value="{{$row->rental_duration}}" placeholder="{{__("Example: 10 day 4hr")}}" name="rental_duration" class="form-control">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>{{__("Driver Type")}}</label>
                        <input type="text" value="{{$row->driver_type}}" placeholder="{{__("Example: Tài xịn")}}" name="driver_type" class="form-control">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>{{__("Passenger")}}</label>
                        <input type="number" value="{{$row->passenger}}" placeholder="{{__("Example: 3")}}" name="passenger" class="form-control">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>{{__("Fuel Type")}}</label>
                        <select name="fuel_type" class="form-control">
                            <option value="" @if(empty($row->fuel_type)) selected @endif>{{__("-- Select --")}}</option>
                            <option value="điện" @if($row->fuel_type == 'điện') selected @endif>{{__("Điện")}}</option>
                            <option value="xăng" @if($row->fuel_type == 'xăng') selected @endif>{{__("Xăng")}}</option>
                            <option value="dầu" @if($row->fuel_type == 'dầu') selected @endif>{{__("Dầu")}}</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>{{__("Transmission Type")}}</label>
                        <select name="transmission_type" class="form-control">
                            <option value="" @if(empty($row->transmission_type)) selected @endif>{{__("-- Select --")}}</option>
                            <option value="số tự động" @if($row->transmission_type == 'số tự động') selected @endif>{{__("Số tự động")}}</option>
                            <option value="số sàn" @if($row->transmission_type == 'số sàn') selected @endif>{{__("Số sàn")}}</option>
                        </select>
                    </div>
                </div>
                <!-- These fields are commented out because they don't exist in the database schema
                <div class="col-md-3">
                    <div class="form-group">
                        <label>{{__("Gear Shift")}}</label>
                        <input type="text" value="{{$row->gear}}" placeholder="{{__("Example: Auto")}}" name="gear" class="form-control">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>{{__("Baggage")}}</label>
                        <input type="number" value="{{$row->baggage}}" placeholder="{{__("Example: 5")}}" name="baggage" class="form-control">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>{{__("Door")}}</label>
                        <input type="number" value="{{$row->door}}" placeholder="{{__("Example: 4")}}" name="door" class="form-control">
                    </div>
                </div> -->
            </div>
        </div>
    </div>
@endif

<script>
    jQuery(document).ready(function($) {
        // Handle brand selection
        $('#brand_select').on('change', function() {
            var selectedOption = $(this).find('option:selected');
            var brandName = selectedOption.data('name') || '';
            console.log('Brand changed to:', brandName);
            $('#brand_name').val(brandName);
        });
        
        // Initialize brand name on page load
        var initialSelectedOption = $('#brand_select').find('option:selected');
        var initialBrandName = initialSelectedOption.data('name') || '';
        console.log('Initial brand name:', initialBrandName);
        $('#brand_name').val(initialBrandName);
        
        // Trigger change event on page load to ensure brand name is set
        $('#brand_select').trigger('change');
    });
</script>
