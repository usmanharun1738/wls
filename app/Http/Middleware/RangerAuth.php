<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RangerAuth
{
    /**
     * Ensure a ranger is logged in via session.
     */
    public function handle(Request $request, Closure $next)
    {
        if (! session()->has('ranger_id')) {
            return redirect()->route('ranger.login');
        }

        return $next($request);
    }
}
