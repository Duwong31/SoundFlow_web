@if(is_default_lang())
<div class="row">
    <div class="col-sm-4">
        <h3 class="form-group-title">{{__("General Options")}}</h3>
    </div>
    <div class="col-md-8">
        <div class="panel">
            <div class="panel-title"><strong>{{__("General Options")}}</strong></div>
            <div class="panel-body">
                <div class="form-group" >
                    <label class="" >{{__("Allow customer upload picture to review")}}</label>
                    <div class="form-controls">
                        <label><input type="checkbox" name="review_upload_picture" value="1"  @if(!empty(setting_item('review_upload_picture'))) checked @endif /> {{__("Yes please")}} </label>
                        <br>
                    </div>
                </div>
                <div class="form-group">
                    <label class="" >{{__("Enable reCapcha Review Form")}}</label>
                    <div class="form-controls">
                        <label><input type="checkbox" name="review_enable_recaptcha" value="1" @if(!empty($settings['review_enable_recaptcha'])) checked @endif /> {{__("On ReCapcha")}} </label>
                        <br>
                        <small class="form-text text-muted">{{__("Turn on the mode for review form")}}</small>
                    </div>
                </div>
                <div class="form-group" >
                    <label class="" >{{__("Allow customer edit review")}}</label>
                    <div class="form-controls">
                        <label><input type="checkbox" name="enable_edit_review" value="1"  @if(!empty(setting_item('enable_edit_review'))) checked @endif /> {{__("Yes please")}} </label>
                        <br>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endif
