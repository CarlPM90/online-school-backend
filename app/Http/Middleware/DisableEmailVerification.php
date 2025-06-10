<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Temporary middleware to auto-verify users during development
 * when email system is disabled.
 * 
 * TODO: Remove this when proper email service is configured
 */
class DisableEmailVerification
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Only apply this in development when mail driver is 'log'
        if (config('mail.default') === 'log' && auth()->check()) {
            $user = auth()->user();
            
            // Auto-verify email if not already verified
            if (!$user->hasVerifiedEmail()) {
                $user->markEmailAsVerified();
            }
        }

        return $next($request);
    }
}