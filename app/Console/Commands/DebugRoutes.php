<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;

class DebugRoutes extends Command
{
    protected $signature = 'debug:routes';
    protected $description = 'Debug available routes';

    public function handle()
    {
        $routes = Route::getRoutes();
        
        $apiRoutes = [];
        $authRoutes = [];
        $missingRoutes = [];
        
        // Check for specific missing routes
        $checkRoutes = [
            'api/login',
            'api/register', 
            'api/auth/refresh',
            'api/profile/me',
            'api/courses/progress',
            'api/notifications'
        ];
        
        foreach ($routes as $route) {
            $uri = $route->uri();
            if (strpos($uri, 'api/') === 0) {
                $apiRoutes[] = [
                    'uri' => $uri,
                    'methods' => implode('|', $route->methods()),
                    'name' => $route->getName(),
                    'action' => $route->getActionName()
                ];
                
                if (strpos($uri, 'auth') !== false || 
                    strpos($uri, 'login') !== false || 
                    strpos($uri, 'register') !== false ||
                    strpos($uri, 'profile') !== false) {
                    $authRoutes[] = $uri;
                }
            }
        }
        
        foreach ($checkRoutes as $checkRoute) {
            $found = false;
            foreach ($apiRoutes as $route) {
                if ($route['uri'] === $checkRoute || 
                    strpos($route['uri'], $checkRoute) !== false) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $missingRoutes[] = $checkRoute;
            }
        }
        
        $this->info('=== API ROUTES DEBUG ===');
        $this->info('Total API routes: ' . count($apiRoutes));
        $this->info('Auth-related routes: ' . count($authRoutes));
        $this->info('Missing critical routes: ' . count($missingRoutes));
        
        $this->line('');
        $this->info('=== MISSING ROUTES ===');
        foreach ($missingRoutes as $missing) {
            $this->error('Missing: ' . $missing);
        }
        
        $this->line('');
        $this->info('=== AUTH ROUTES FOUND ===');
        foreach ($authRoutes as $authRoute) {
            $this->line($authRoute);
        }
        
        $this->line('');
        $this->info('=== SAMPLE API ROUTES ===');
        foreach (array_slice($apiRoutes, 0, 10) as $route) {
            $this->line($route['methods'] . ' ' . $route['uri'] . ' -> ' . $route['action']);
        }
        
        return 0;
    }
}