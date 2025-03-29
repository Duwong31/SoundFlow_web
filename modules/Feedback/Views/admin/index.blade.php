@extends('admin.layouts.app')

@section('content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between mb20">
            <h1 class="title-bar">{{ __('All Feedbacks') }}</h1>
        </div>
        @include('admin.message')
        <div class="filter-div d-flex justify-content-between ">
            <div class="col-left">
                <form method="post" action="{{ route('feedback.admin.bulkEdit') }}" class="filter-form filter-form-left d-flex justify-content-start">
                    {{ csrf_field() }}
                    <select name="action" class="form-control">
                        <option value="">{{ __("Bulk Actions") }}</option>
                        <option value="pending">{{ __("Pending") }}</option>
                        <option value="progress">{{ __("In Progress") }}</option>
                        <option value="resolved">{{ __("Resolved") }}</option>
                        <option value="rejected">{{ __("Rejected") }}</option>
                        <option value="delete">{{ __("Delete") }}</option>
                    </select>
                    <button data-confirm="{{__("Do you want to process this action?")}}" class="btn-info btn btn-icon dungdt-apply-form-btn" type="button">{{ __('Apply') }}</button>
                </form>
            </div>
            <div class="col-left">
                <form method="get" action="{{ route('feedback.admin.index') }}" class="filter-form filter-form-right d-flex justify-content-end flex-column flex-sm-row" role="search">
                    <input type="text" name="s" value="{{ Request::query('s') }}" placeholder="{{ __('Search by content or user...') }}" class="form-control">
                    <button class="btn-info btn btn-icon btn_search" type="submit">{{ __('Search') }}</button>
                </form>
            </div>
        </div>
        <div class="text-right">
            <p><i>{{ __('Found :total items', ['total' => $rows->total()]) }}</i></p>
        </div>
        <div class="panel">
            <div class="panel-body">
                <form action="{{ route('feedback.admin.bulkEdit') }}" class="bravo-form-item">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                            <tr>
                                <th width="60px"><input type="checkbox" class="check-all"></th>
                                <th>{{ __('Content') }}</th>
                                <th width="150px">{{ __('User') }}</th>
                                <th width="150px">{{ __('Media') }}</th>
                                <th width="100px">{{ __('Status') }}</th>
                                <th width="150px">{{ __('Date') }}</th>
                                <th width="100px">{{ __('Actions') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @if($rows->total() > 0)
                                @foreach($rows as $row)
                                    <tr>
                                        <td><input type="checkbox" name="ids[]" class="check-item" value="{{ $row->id }}"></td>
                                        <td class="title">
                                            <a href="{{ route('feedback.admin.view', ['id' => $row->id]) }}">{{ Str::limit(strip_tags($row->content), 60) }}</a>
                                        </td>
                                        <td>{{ $row->user->name ?? 'Unnamed' }}<br>{{ $row->user->email ?? '' }}</td>
                                        <td>
                                            @if(!empty($row->media_ids))
                                                @php
                                                    $mediaCount = count(json_decode($row->media_ids, true));
                                                @endphp
                                                <span class="badge badge-secondary">{{ $mediaCount }} {{ $mediaCount > 1 ? __('items') : __('item') }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge badge-{{ $row->status == 'pending' ? 'warning' : ($row->status == 'resolved' ? 'success' : ($row->status == 'rejected' ? 'danger' : 'info')) }}">{{ $statuses[$row->status] }}</span>
                                        </td>
                                        <td>{{ display_datetime($row->created_at) }}</td>
                                        <td>
                                            <a href="{{ route('feedback.admin.view', ['id' => $row->id]) }}" class="btn btn-primary btn-sm"><i class="fa fa-eye"></i> {{ __('View') }}</a>
                                        </td>
                                    </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="7">{{ __("No feedback found") }}</td>
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