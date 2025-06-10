<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class FixCorsHeaders
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
        // Handle preflight OPTIONS requests
        if ($request->getMethod() === 'OPTIONS') {
            return response('', 200)
                ->header('Access-Control-Allow-Origin', 'https://tos-front-end.vercel.app')
                ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
                ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Accept, Origin')
                ->header('Access-Control-Allow-Credentials', 'true')
                ->header('Access-Control-Max-Age', '86400');
        }

        $response = $next($request);

        // Remove ALL CORS headers more aggressively
        $corsHeaders = [
            'Access-Control-Allow-Origin',
            'Access-Control-Allow-Methods', 
            'Access-Control-Allow-Headers',
            'Access-Control-Allow-Credentials',
            'Access-Control-Max-Age',
            'Access-Control-Expose-Headers',
            'Vary'
        ];
        
        foreach ($corsHeaders as $header) {
            $response->headers->remove($header);
            // Also remove with different casing
            $response->headers->remove(strtolower($header));
            $response->headers->remove(strtoupper($header));
        }
        
        // Clear any header bags that might be duplicating
        if (method_exists($response->headers, 'replace')) {
            foreach ($corsHeaders as $header) {
                $response->headers->replace([$header => null]);
            }
        }

        // Add clean CORS headers
        $origin = $request->header('Origin');
        $allowedOrigins = [
            'https://tos-front-end.vercel.app',
            'http://localhost:3000',
            'http://localhost:3001',
            'http://127.0.0.1:3000',
        ];

        if (in_array($origin, $allowedOrigins) || preg_match('/^https:\/\/.*\.vercel\.app$/', $origin)) {
            $response->headers->set('Access-Control-Allow-Origin', $origin);
        }

        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Accept, Origin');
        $response->headers->set('Access-Control-Allow-Credentials', 'true');

        return $response;
    }
}