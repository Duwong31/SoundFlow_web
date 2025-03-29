@extends('admin.layouts.app')

@section('content')
    <form action="{{route('car.admin.brand.store',['id'=>($row->id) ? $row->id : '-1'])}}" method="post">
        @csrf
        <div class="container-fluid">
            <div class="d-flex justify-content-between mb20">
                <div class="">
                    <h1 class="title-bar">{{$row->id ? __('Edit: ').$row->name : __('Add new car brand')}}</h1>
                </div>
            </div>
            @include('admin.message')
            <div class="row">
                <div class="col-md-9">
                    <div class="panel">
                        <div class="panel-body">
                            <h3 class="panel-body-title">{{__("Brand Content")}}</h3>
                            @include('Car::admin/brand/form')
                            
                            <div class="form-group">
                                <label>{{ __("Content") }}</label>
                                <textarea name="content" class="form-control" rows="5">{{ $row->content }}</textarea>
                                <small class="form-text text-muted">{{ __("Description about the car brand") }}</small>
                            </div>
                            
                            <div class="mt-3">
                                <button class="btn btn-primary" type="submit">{{__('Save')}}</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection

@push('js')
    <script>
        $(document).ready(function () {
            $('.has-datetimepicker').daterangepicker({
                singleDatePicker: true,
                timePicker: true,
                showCalendar: false,
                autoUpdateInput: false, //disable default date
                sameDate: true,
                autoApply: true,
                disabledPast: true,
                enableLoading: true,
                showEventTooltip: true,
                classNotAvailable: ['disabled', 'off'],
                disableHightLight: true,
                locale: {
                    format: 'YYYY-MM-DD HH:mm:ss'
                }
            }).on('apply.daterangepicker', function (ev, picker) {
                $(this).val(picker.startDate.format('YYYY-MM-DD HH:mm:ss'));
            });
        })
    </script>
@endpush 