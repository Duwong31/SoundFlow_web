@extends('admin.layouts.app')
@section('content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between mb20">
            <h1 class="title-bar">{{ __('Feedback Settings') }}</h1>
            <div class="title-actions">
                <a href="{{ route('feedback.admin.index') }}" class="btn btn-primary">{{ __('All Feedbacks') }}</a>
            </div>
        </div>
        @include('admin.message')
        <div class="row">
            <div class="col-md-8">
                <div class="panel">
                    <div class="panel-title"><strong>{{ __('General Settings') }}</strong></div>
                    <div class="panel-body">
                        <form action="{{ route('feedback.admin.settings.store') }}" method="post">
                            @csrf
                            <div class="form-group">
                                <label>{{ __('Email Notifications') }}</label>
                                <div class="form-controls">
                                    <label>
                                        <input type="checkbox" name="enable_mail" value="1" @if(!empty($settings['enable_mail'])) checked @endif> {{ __('Enable email notification when new feedback is submitted') }}
                                    </label>
                                </div>
                            </div>

                            <div class="form-group" data-condition="enable_mail:is(1)">
                                <label>{{ __('Admin Email') }}</label>
                                <div class="form-controls">
                                    <input type="email" class="form-control" name="admin_email" value="{{ $settings['admin_email'] ?? '' }}" placeholder="{{ __('Enter email to receive notifications') }}">
                                </div>
                            </div>

                            <div class="form-group">
                                <label>{{ __('Auto Response') }}</label>
                                <div class="form-controls">
                                    <label>
                                        <input type="checkbox" name="enable_auto_response" value="1" @if(!empty($settings['enable_auto_response'])) checked @endif> {{ __('Enable auto response to user') }}
                                    </label>
                                </div>
                            </div>

                            <div class="form-group" data-condition="enable_auto_response:is(1)">
                                <label>{{ __('Auto Response Message') }}</label>
                                <div class="form-controls">
                                    <textarea name="auto_response_message" class="form-control" rows="5">{{ $settings['auto_response_message'] ?? 'Thank you for your feedback. We have received your message and will respond shortly.' }}</textarea>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>{{ __('Enable Attachments') }}</label>
                                <div class="form-controls">
                                    <label>
                                        <input type="checkbox" name="enable_attachments" value="1" @if(!empty($settings['enable_attachments'])) checked @endif> {{ __('Allow users to upload files with feedback') }}
                                    </label>
                                </div>
                            </div>

                            <div class="form-group" data-condition="enable_attachments:is(1)">
                                <label>{{ __('Max File Size (MB)') }}</label>
                                <div class="form-controls">
                                    <input type="number" class="form-control" name="max_file_size" value="{{ $settings['max_file_size'] ?? 5 }}" min="1" max="50">
                                </div>
                            </div>

                            <div class="form-group">
                                <button class="btn btn-primary" type="submit">{{ __('Save Settings') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script.body')
<script>
    $(document).ready(function () {
        function checkConditions() {
            $('[data-condition]').each(function () {
                var condition = $(this).data('condition');
                var parts = condition.split(':');
                var conditionName = parts[0];
                var conditionValue = parts[1].split('(')[1].replace(')', '');
                
                var inputElement = $('[name="' + conditionName + '"]');
                var currentValue = inputElement.is(':checkbox') 
                    ? (inputElement.is(':checked') ? '1' : '0')
                    : inputElement.val();
                
                if (currentValue == conditionValue) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        }
        
        checkConditions();
        
        $('input, select').on('change', function() {
            checkConditions();
        });
    });
</script>
@endsection 