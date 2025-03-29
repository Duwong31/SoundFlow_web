<hr>
<div class="row">
    <div class="col-sm-4">
        <h3 class="form-group-title">{{__("Header Settings")}}</h3>
    </div>
    <div class="col-sm-8">
        <div class="panel">
            <div class="panel-title"><strong>{{__('Header Style')}}</strong></div>
            <div class="panel-body">@if(is_default_lang())
                    <div class="form-group">
                        <label>{{__("Enable Header Sticky")}}</label>
                        <div class="form-controls">
                            <label>
                                <input
                                        type="checkbox"
                                        @if(setting_item('enable_header_sticky') ?? '' == 1) checked
                                        @endif name="enable_header_sticky"
                                        value="1"
                                >{{__('Enable')}}</label>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
