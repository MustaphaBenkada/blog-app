<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    protected function redirectTo($request)
    {
       // If the request expects JSON, return null so it returns 401 JSON response instead of redirect
        if ($request->expectsJson()) {
            return null;
        }

        // Otherwise, fallback to login route redirect
        return route('login');
    }
}
