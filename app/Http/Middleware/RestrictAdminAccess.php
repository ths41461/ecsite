<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RestrictAdminAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is authenticated
        if (!Auth::check()) {
            // Redirect guest users to the login page
            return redirect()->guest(route('filament.admin.auth.login'));
        }

        $user = Auth::user();

        // Check if the user has admin role
        if (!$user->hasRole('admin')) {
            // Redirect non-admin users to the home page
            return redirect()->route('home');
        }

        return $next($request);
    }
}