@extends('admin.layouts.app')
@section('content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between mb20">
            <h1 class="title-bar">{{__("Car Brands")}}</h1>
            <div class="title-actions">
                <a href="{{ route('car.admin.brand.create') }}" class="btn btn-primary">{{__("Add new")}}</a>
            </div>
        </div>
        @include('admin.message')
        <div class="filter-div d-flex justify-content-between ">
            <div class="col-left">
                @if(!empty($rows))
                    <form method="post" action="{{ route('car.admin.brand.bulkEdit') }}"
                          class="filter-form filter-form-left d-flex justify-content-start">
                        {{csrf_field()}}
                        <select name="action" class="form-control">
                            <option value="">{{__(" Bulk Actions ")}}</option>
                            <option value="1">{{__(" Publish ")}}</option>
                            <option value="0">{{__(" Draft ")}}</option>
                            <option value="delete">{{__(" Delete ")}}</option>
                        </select>
                        <button data-confirm="{{__("Do you want to perform this action?")}}"
                                class="btn-info btn btn-icon dungdt-apply-form-btn"
                                type="button">{{__('Apply')}}
                        </button>
                    </form>
                @endif
            </div>
            <div class="col-left">
                <form method="get" action="{{ route('car.admin.brand.index') }}"
                      class="filter-form filter-form-right d-flex justify-content-end flex-column flex-sm-row" role="search">
                    <input type="text" name="s" value="{{ Request()->s }}"
                           placeholder="{{__('Search by name')}}"
                           class="form-control">
                    <button class="btn-info btn btn-icon btn_search" type="submit">{{__('Search')}}</button>
                </form>
            </div>
        </div>
        <div class="panel">
            <div class="panel-body">
                <form action="" class="bravo-form-item">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                            <tr>
                                <th width="60px"><input type="checkbox" class="check-all"></th>
                                <th>{{__("Name")}}</th>
                                <!-- <th width="130px">{{__("Logo")}}</th> -->
                                <th width="100px">{{__("Status")}}</th>
                                <th width="100px">{{__("Date")}}</th>
                                <th width="100px"></th>
                            </tr>
                            </thead>
                            <tbody>
                            @if($rows->total() > 0)
                                @foreach($rows as $row)
                                    <tr>
                                        <td><input type="checkbox" name="ids[]" class="check-item" value="{{$row->id}}">
                                        </td>
                                        <td class="title">
                                            <a href="{{route('car.admin.brand.edit',['id'=>$row->id])}}">{{$row->name}}</a>
                                        </td>
                                        <!-- <td>
                                            @if($row->logo_id)
                                                <div class="item-list-img">
                                                    <img src="{{get_file_url($row->logo_id, 'thumb')}}" alt="{{$row->name}}" class="img-responsive">
                                                </div>
                                            @endif
                                        </td> -->
                                        <td>
                                            <span class="badge badge-{{ $row->status ? 'success' : 'secondary' }}">{{ $row->status ? __('Active') : __('Inactive') }}</span>
                                        </td>
                                        <td>{{ display_date($row->updated_at)}}</td>
                                        <td>
                                            <a href="{{route('car.admin.brand.edit',['id'=>$row->id])}}" class="btn btn-primary btn-sm"><i class="fa fa-edit"></i> {{__('Edit')}}
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="5">{{__("No brands found")}}</td>
                                </tr>
                            @endif
                            </tbody>
                        </table>
                    </div>
                </form>
                {{$rows->appends(request()->query())->links()}}
            </div>
        </div>
    </div>
@endsection 