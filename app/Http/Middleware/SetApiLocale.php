<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SetApiLocale
{
    public function handle(Request $request, Closure $next)
    {
        // Get language from header, default to Vietnamese
        $locale = $request->header('Language', 'vi');
        
        // Set application locale
        app()->setLocale($locale);
        
        return $next($request);
    }
} 