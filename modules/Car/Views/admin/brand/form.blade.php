<div class="form-group">
    <label>{{__("Name")}}</label>
    <input type="text" value="{{ $row->name ?? '' }}" placeholder="{{__("Brand name")}}" name="name" class="form-control">
</div>
<div class="form-group">
    <label>{{__("Status")}}</label>
    <select name="status" class="form-control">
        <option value="1" @if(isset($row->status) && $row->status == 1) selected @endif>{{__("Active")}}</option>
        <option value="0" @if(isset($row->status) && $row->status == 0) selected @endif>{{__("Inactive")}}</option>
    </select>
</div>
<div class="form-group">
    <label class="control-label">{{__("Logo")}}</label>
    <div class="form-group-image">
        {!! \Modules\Media\Helpers\FileHelper::fieldUpload('logo_id', $row->logo_id ?? '') !!}
    </div>
</div> 