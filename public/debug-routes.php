<?php
// Debug available routes
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '/var/www/html/vendor/autoload.php';

try {
    // Bootstrap Laravel app
    $app = require_once '/var/www/html/bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    
    // Get route collection
    $router = $app->make('router');
    $routes = $router->getRoutes();
    
    $apiRoutes = [];
    $authRoutes = [];
    $allRoutes = [];
    
    foreach ($routes as $route) {
        $uri = $route->uri();
        $methods = implode('|', $route->methods());
        $name = $route->getName();
        $action = $route->getActionName();
        
        $routeInfo = [
            'uri' => $uri,
            'methods' => $methods,
            'name' => $name,
            'action' => $action
        ];
        
        $allRoutes[] = $routeInfo;
        
        // Categorize routes
        if (strpos($uri, 'api/') === 0) {
            $apiRoutes[] = $routeInfo;
            
            if (strpos($uri, 'auth') !== false || 
                strpos($uri, 'login') !== false || 
                strpos($uri, 'register') !== false ||
                strpos($uri, 'profile') !== false) {
                $authRoutes[] = $routeInfo;
            }
        }
    }
    
    // Check for specific missing routes
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
            if ($route['uri'] === $checkRoute) {
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
        'total_routes' => count($allRoutes),
        'api_routes_count' => count($apiRoutes),
        'auth_routes_count' => count($authRoutes),
        'missing_critical_routes' => $missingRoutes,
        'sample_api_routes' => array_slice($apiRoutes, 0, 10),
        'auth_routes' => $authRoutes,
        'debug_info' => [
            'app_loaded' => true,
            'router_available' => true,
            'vendor_autoload' => true
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