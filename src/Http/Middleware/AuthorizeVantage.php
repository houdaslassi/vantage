<?php

namespace houdaslassi\Vantage\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class AuthorizeVantage
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // If auth is disabled, allow access
        if (!config('vantage.auth.enabled', true)) {
            return $next($request);
        }

        // Check if user is authenticated (try default guard first, then web guard)
        $user = Auth::user() ?? Auth::guard('web')->user();
        
        if (!$user) {
            abort(401, 'Unauthenticated. Please log in to access Vantage dashboard.');
        }

        // Check authorization via gate (like Horizon)
        if (!Gate::forUser($user)->allows('viewVantage')) {
            abort(403, 'Unauthorized access to Vantage dashboard.');
        }

        return $next($request);
    }
}

