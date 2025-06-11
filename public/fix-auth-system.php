<?php
// Fix Authentication System
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '/var/www/html/vendor/autoload.php';

try {
    // Bootstrap Laravel app
    $app = require_once '/var/www/html/bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    
    $results = [];
    
    // 1. Clear caches that might be causing route issues
    try {
        Artisan::call('route:clear');
        $results['route_clear'] = '✅ Route cache cleared';
    } catch (Exception $e) {
        $results['route_clear'] = '❌ Failed: ' . $e->getMessage();
    }
    
    try {
        Artisan::call('config:clear');
        $results['config_clear'] = '✅ Config cache cleared';
    } catch (Exception $e) {
        $results['config_clear'] = '❌ Failed: ' . $e->getMessage();
    }
    
    try {
        Artisan::call('cache:clear');
        $results['cache_clear'] = '✅ Application cache cleared';
    } catch (Exception $e) {
        $results['cache_clear'] = '❌ Failed: ' . $e->getMessage();
    }
    
    // 2. Re-discover packages
    try {
        Artisan::call('package:discover');
        $results['package_discover'] = '✅ Packages re-discovered';
    } catch (Exception $e) {
        $results['package_discover'] = '❌ Failed: ' . $e->getMessage();
    }
    
    // 3. Check OAuth clients
    try {
        $clientCount = DB::table('oauth_clients')->count();
        if ($clientCount == 0) {
            Artisan::call('passport:install', ['--force' => true]);
            $results['passport_install'] = '✅ Passport OAuth clients created';
        } else {
            $results['passport_install'] = '✅ OAuth clients already exist (' . $clientCount . ')';
        }
    } catch (Exception $e) {
        $results['passport_install'] = '❌ Failed: ' . $e->getMessage();
    }
    
    // 4. Check available routes after clearing cache
    $router = $app->make('router');
    $routes = $router->getRoutes();
    
    $apiRoutes = [];
    $authRoutes = [];
    
    foreach ($routes as $route) {
        $uri = $route->uri();
        if (strpos($uri, 'api/') === 0) {
            $apiRoutes[] = $uri;
            
            if (strpos($uri, 'auth') !== false || 
                strpos($uri, 'login') !== false || 
                strpos($uri, 'register') !== false ||
                strpos($uri, 'profile') !== false) {
                $authRoutes[] = $uri;
            }
        }
    }
    
    // Check for missing critical routes
    $missingRoutes = [];
    $checkRoutes = [
        'api/login',
        'api/register', 
        'api/auth/refresh',
        'api/profile/me',
        'api/courses/progress',
        'api/notifications'
    ];
    
    foreach ($checkRoutes as $checkRoute) {
        $found = false;
        foreach ($apiRoutes as $route) {
            if ($route === $checkRoute || strpos($route, $checkRoute) !== false) {
                $found = true;
                break;
            }
        }
        if (!$found) {
            $missingRoutes[] = $checkRoute;
        }
    }
    
    echo json_encode([
        'status' => 'success',
        'fixes_applied' => $results,
        'route_analysis' => [
            'total_api_routes' => count($apiRoutes),
            'auth_routes_found' => count($authRoutes),
            'missing_critical_routes' => $missingRoutes,
            'sample_auth_routes' => array_slice($authRoutes, 0, 5),
            'sample_api_routes' => array_slice($apiRoutes, 0, 10)
        ],
        'next_steps' => count($missingRoutes) > 0 ? [
            'Some routes are still missing after cache clear',
            'This indicates EscolaLMS packages may not be properly registered',
            'Check service provider registration in bootstrap/cache/packages.php'
        ] : [
            'All critical routes should now be available',
            'Test authentication endpoints'
        ]
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ], JSON_PRETTY_PRINT);
}
?>