<?php
if (!function_exists('get_feedback_unread_count')) {
    function get_feedback_unread_count() {
        if (!auth()->check() || !auth()->user()->hasPermission('feedback_view')) {
            return 0;
        }
        
        return \Modules\Feedback\Models\Feedback::where('status', 'pending')->count();
    }
} 