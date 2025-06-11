<?php
// Debug API endpoints that frontend is calling
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    // Bootstrap Laravel app
    require_once '/var/www/html/vendor/autoload.php';
    $app = require_once '/var/www/html/bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    
    // Get all routes and find progress/course related ones
    $router = $app->make('router');
    $routes = $router->getRoutes();
    
    $relevantRoutes = [];
    $keywords = ['progress', 'course', 'tracker', 'enrollment', 'user/me', 'profile'];
    
    foreach ($routes as $route) {
        $uri = $route->uri();
        $methods = $route->methods();
        
        // Skip non-API routes
        if (strpos($uri, 'api/') !== 0) continue;
        
        // Check if route contains relevant keywords
        foreach ($keywords as $keyword) {
            if (strpos(strtolower($uri), strtolower($keyword)) !== false) {
                $relevantRoutes[] = [
                    'uri' => $uri,
                    'methods' => implode('|', $methods),
                    'name' => $route->getName(),
                    'action' => $route->getActionName(),
                    'middleware' => $route->gatherMiddleware()
                ];
                break;
            }
        }
    }
    
    // Test specific endpoints that might be used by frontend
    $testEndpoints = [
        '/api/courses',
        '/api/courses/progress', 
        '/api/profile/me',
        '/api/user/me',
        '/api/progress',
        '/api/tracker/progress'
    ];
    
    $endpointTests = [];
    foreach ($testEndpoints as $endpoint) {
        $found = false;
        foreach ($relevantRoutes as $route) {
            if ($route['uri'] === ltrim($endpoint, '/')) {
                $endpointTests[$endpoint] = 'EXISTS - ' . $route['methods'];
                $found = true;
                break;
            }
        }
        if (!$found) {
            $endpointTests[$endpoint] = 'NOT FOUND';
        }
    }
    
    // Check what packages are installed
    $installedPackages = [];
    $composerLock = json_decode(file_get_contents('/var/www/html/composer.lock'), true);
    if ($composerLock && isset($composerLock['packages'])) {
        foreach ($composerLock['packages'] as $package) {
            if (strpos($package['name'], 'escolalms') !== false) {
                $installedPackages[] = $package['name'] . ' ' . $package['version'];
            }
        }
    }
    
    echo json_encode([
        'status' => 'success',
        'relevant_routes_count' => count($relevantRoutes), 
        'relevant_routes' => $relevantRoutes,
        'endpoint_tests' => $endpointTests,
        'installed_escolalms_packages' => $installedPackages,
        'recommendations' => [
            'The frontend is likely calling one of these endpoints for progress data',
            'Check which exact endpoint the frontend fetchProgress() function calls',
            'Verify authentication is properly configured for these routes',
            'If routes are missing, the EscolaLMS packages may not be properly registered'
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