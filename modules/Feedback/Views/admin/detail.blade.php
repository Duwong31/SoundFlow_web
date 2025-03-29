@extends('admin.layouts.app')

@section('content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between mb20">
            <h1 class="title-bar">{{ __('Feedback Details') }}</h1>
            <div class="title-actions">
                <a href="{{ route('feedback.admin.index') }}" class="btn btn-primary">{{ __('All Feedbacks') }}</a>
            </div>
        </div>
        @include('admin.message')
        <div class="row">
            <div class="col-md-8">
                <div class="panel">
                    <div class="panel-title"><strong>{{ __('Feedback Content') }}</strong></div>
                    <div class="panel-body">
                        <div class="form-group">
                            <label>{{ __('Content') }}</label>
                            <div class="border p-3 rounded bg-light">
                                {!! nl2br(e($row->content)) !!}
                            </div>
                        </div>

                        @if(count($media_items) > 0)
                            <div class="form-group">
                                <label>{{ __('Media Files') }}</label>
                                <div class="row">
                                    @foreach($media_items as $media)
                                        <div class="col-md-4 mb-3">
                                            @if($media['type'] == 'video')
                                                <video class="img-thumbnail" controls>
                                                    <source src="{{ $media['url'] }}" type="{{ $media['file_type'] }}">
                                                    Your browser does not support the video tag.
                                                </video>
                                            @else
                                                <a href="{{ $media['url'] }}" target="_blank">
                                                    <img src="{{ $media['url'] }}" class="img-thumbnail" alt="{{ $media['name'] }}">
                                                </a>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <div class="form-group">
                            <label>{{ __('Admin Response') }}</label>
                            <form action="{{ route('feedback.admin.response', ['id' => $row->id]) }}" method="post">
                                @csrf
                                <div class="form-group">
                                    <textarea name="admin_response" class="form-control" rows="8">{{ $row->admin_response }}</textarea>
                                </div>
                                
                                <input type="hidden" name="status" value="{{ $row->status ?: 'pending' }}">
                                
                                <div class="mt-4">
                                    <button type="submit" class="btn btn-primary btn-lg">{{ __('Save') }}</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="panel">
                    <div class="panel-title"><strong>{{ __('Feedback Information') }}</strong></div>
                    <div class="panel-body">
                        <ul class="list-unstyled">
                            <li class="mb-3">
                                <strong>{{ __('ID:') }}</strong> #{{ $row->id }}
                            </li>
                            <li class="mb-3">
                                <strong>{{ __('Status:') }}</strong>
                                <span class="badge badge-{{ $row->status == 'pending' ? 'warning' : ($row->status == 'resolved' ? 'success' : ($row->status == 'rejected' ? 'danger' : 'info')) }}">{{ $statuses[$row->status] }}</span>
                            </li>
                            <li class="mb-3">
                                <strong>{{ __('Date Submitted:') }}</strong>
                                {{ display_datetime($row->created_at) }}
                            </li>
                            <li class="mb-3">
                                <strong>{{ __('Submitted by:') }}</strong>
                                <div>
                                    {{ $row->user->name ?? __('Unnamed') }}
                                    <br>
                                    {{ $row->user->email ?? '' }}
                                </div>
                            </li>
                            @if(!empty($row->responded_at))
                                <li class="mb-3">
                                    <strong>{{ __('Response Date:') }}</strong>
                                    {{ display_datetime($row->responded_at) }}
                                </li>
                                <li class="mb-3">
                                    <strong>{{ __('Responded by:') }}</strong>
                                    {{ $row->responder->name ?? __('Unknown') }}
                                </li>
                            @endif
                        </ul>
                    </div>
                </div>
                
                <!-- @if(!empty($row->admin_response))
                    <div class="panel">
                        <div class="panel-title"><strong>{{ __('Previous Admin Response') }}</strong></div>
                        <div class="panel-body">
                            <div class="border p-3 rounded bg-white">
                                {!! nl2br(e($row->admin_response)) !!}
                            </div>
                            <div class="text-muted mt-2">
                                {{ __('Responded by: :name', ['name' => $row->responder->name ?? __('Unknown')]) }}
                                <br>
                                {{ __('Response date: :date', ['date' => display_datetime($row->responded_at)]) }}
                            </div>
                        </div>
                    </div>
                @endif -->
            </div>
        </div>
    </div>
@endsection 