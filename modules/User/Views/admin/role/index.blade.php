@extends('admin.layouts.app')
@section('content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between mb20">
            <h1 class="title-bar">{{ __('Role')}}</h1>
            <div class="title-actions">
                <a href="{{route('user.admin.role.verifyFields')}}" class="btn btn-warning"><i class="fa fa-check-circle-o"></i> {{ __('Verify Configs')}}</a>
                <a href="{{route('user.admin.role.permission_matrix')}}" class="btn btn-info">{{ __('Permission Matrix')}}</a>
                <a href="{{route('user.admin.role.create')}}" class="btn btn-primary">{{ __('Add new role')}}</a>
            </div>
        </div>
        <div class="filter-div d-flex justify-content-between ">
            <div class="col-left">
                @if(!empty($rows))
                    <form method="post" action="{{route('user.admin.role.bulkEdit')}}" class="filter-form filter-form-left d-flex justify-content-start">
                        {{csrf_field()}}
                        <select name="action" class="form-control">
                            <option value="">{{__(" Bulk Actions ")}}</option>
                            <option value="delete">{{__(" Delete ")}}</option>
                        </select>
                        <button data-confirm="{{__("Do you want to delete?")}}" class="btn-info btn btn-icon dungdt-apply-form-btn" type="button">{{__('Apply')}}</button>
                    </form>
                @endif
            </div>
            <div class="col-left">
            </div>
        </div>
        @include('admin.message')
        <div class="panel">
            <div class="panel-title">{{ __('All Roles')}}</div>
            <div class="panel-body">
                <form action="" class="bravo-form-item">
                    <table class="table table-hover">
                        <thead>
                        <tr>
                            <th width="60px"><input type="checkbox" class="check-all"></th>
                            <th>{{ __('ID')}}</th>
                            <th>{{ __('Name')}}</th>
                            <th>{{ __('Code')}}</th>
                            <th>{{ __('Date')}}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($rows as $row)
                            <tr>
                                <td><input type="checkbox" name="ids[]" value="{{$row->id}}" class="check-item"></td>
                                <td>#{{$row->id}}</td>
                                <td class="title">
                                    <a href="{{route('user.admin.role.detail',['id' => $row->id])}}">{{ucfirst($row->name)}}</a>
                                </td>
                                <td>{{$row->code}}</td>
                                <td>{{ display_date($row->updated_at)}}</td>
                                <td>
                                    <a href="{{route('user.admin.role.detail',['id' => $row->id])}}" class="btn btn-default btn-sm"><i class="fa fa-edit"></i> {{__("Edit")}}</a>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </form>
                {{$rows->withQueryString()->links()}}
            </div>
        </div>
    </div>
@endsection
