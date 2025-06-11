<?php
// Final test to verify API routes are working
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    require_once '/var/www/html/vendor/autoload.php';
    $app = require_once '/var/www/html/bootstrap/app.php';
    
    // Get routes
    $router = $app->make('router');
    $routes = $router->getRoutes();
    
    // Count API routes
    $apiRoutes = [];
    $essentialRoutes = [];
    
    foreach ($routes as $route) {
        $uri = $route->uri();
        if (strpos($uri, 'api/') === 0) {
            $apiRoutes[] = [
                'uri' => $uri,
                'methods' => implode('|', $route->methods())
            ];
            
            // Check for essential routes
            if (strpos($uri, 'courses') !== false || 
                strpos($uri, 'auth') !== false || 
                strpos($uri, 'profile') !== false ||
                strpos($uri, 'progress') !== false) {
                $essentialRoutes[] = $uri;
            }
        }
    }
    
    // Test progress data for the user
    $progressTestResult = null;
    try {
        // Quick test if User model works
        $user = \App\Models\User::where('email', 'carl.p.morris@gmail.com')->first();
        if ($user) {
            $courses = $user->courses()->get();
            $progressTestResult = [
                'user_found' => true,
                'enrolled_courses' => $courses->count(),
                'course_ids' => $courses->pluck('id')->toArray()
            ];
        } else {
            $progressTestResult = ['user_found' => false];
        }
    } catch (Exception $e) {
        $progressTestResult = ['error' => $e->getMessage()];
    }
    
    echo json_encode([
        'status' => 'success',
        'timestamp' => date('Y-m-d H:i:s'),
        'total_api_routes' => count($apiRoutes),
        'essential_routes_found' => count($essentialRoutes),
        'essential_routes' => $essentialRoutes,
        'sample_api_routes' => array_slice($apiRoutes, 0, 10),
        'progress_test' => $progressTestResult,
        'next_steps' => [
            'If routes are now available, test the frontend again',
            'The user should now see course data and progress',
            'If still empty, check authentication tokens'
        ]
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'suggestion' => 'Check if the service providers registered correctly'
    ], JSON_PRETTY_PRINT);
}
?>