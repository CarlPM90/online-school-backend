<?php
// Test if routes are now available after adding service providers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    // Bootstrap Laravel app with proper error handling
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
    
    require_once '/var/www/html/vendor/autoload.php';
    $app = require_once '/var/www/html/bootstrap/app.php';
    
    // Clear any cached config
    $app->make('cache')->forget('config');
    $app->make('cache')->forget('routes');
    
    // Get routes
    $router = $app->make('router');
    $routes = $router->getRoutes();
    
    // Look for the essential API routes
    $foundRoutes = [];
    $testRoutes = [
        'api/courses',
        'api/auth/login', 
        'api/auth/me',
        'api/profile/me',
        'api/courses/progress'
    ];
    
    foreach ($routes as $route) {
        $uri = $route->uri();
        
        // Check if this is one of our test routes
        foreach ($testRoutes as $testRoute) {
            if ($uri === $testRoute || strpos($uri, $testRoute) !== false) {
                $foundRoutes[] = [
                    'uri' => $uri,
                    'methods' => implode('|', $route->methods()),
                    'name' => $route->getName(),
                    'middleware' => $route->gatherMiddleware()
                ];
            }
        }
    }
    
    // Get all API routes count
    $apiRoutesCount = 0;
    foreach ($routes as $route) {
        if (strpos($route->uri(), 'api/') === 0) {
            $apiRoutesCount++;
        }
    }
    
    echo json_encode([
        'status' => 'success',
        'timestamp' => date('Y-m-d H:i:s'),
        'total_api_routes' => $apiRoutesCount,
        'essential_routes_found' => count($foundRoutes),
        'found_routes' => $foundRoutes,
        'missing_routes' => array_filter($testRoutes, function($testRoute) use ($foundRoutes) {
            foreach ($foundRoutes as $found) {
                if (strpos($found['uri'], $testRoute) !== false) {
                    return false;
                }
            }
            return true;
        }),
        'fix_applied' => 'Added EscolaLMS service providers to config/app.php',
        'next_steps' => [
            'If routes are still missing, may need to run: php artisan config:clear',
            'May also need: php artisan route:clear',
            'Check if application boots properly'
        ]
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
        'suggestion' => 'The service providers might have incorrect class names or missing dependencies'
    ], JSON_PRETTY_PRINT);
}
?>