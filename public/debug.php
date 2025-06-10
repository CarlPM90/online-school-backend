<?php
// Laravel Debug Endpoint - bypasses Laravel routing
header('Content-Type: text/plain');
echo "=== Laravel Environment Debug ===\n\n";

// Test if we can include Laravel bootstrap
try {
    echo "1. Testing Laravel Bootstrap...\n";
    require_once __DIR__ . '/../vendor/autoload.php';
    echo "✅ Autoloader loaded\n";
    
    $app = require_once __DIR__ . '/../bootstrap/app.php';
    echo "✅ App bootstrapped\n";
    
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    echo "✅ Kernel created\n";
    
    // Test basic environment
    echo "\n2. Environment Variables:\n";
    echo "APP_ENV: " . env('APP_ENV', 'not set') . "\n";
    echo "APP_DEBUG: " . env('APP_DEBUG', 'not set') . "\n";
    echo "APP_KEY: " . (env('APP_KEY') ? 'SET' : 'NOT SET') . "\n";
    echo "DB_CONNECTION: " . env('DB_CONNECTION', 'not set') . "\n";
    
    // Test database connection
    echo "\n3. Testing Database Connection...\n";
    $pdo = new PDO(
        'pgsql:host=' . env('LARAVEL_DB_HOST', env('DB_HOST')) . ';port=' . env('LARAVEL_DB_PORT', env('DB_PORT')) . ';dbname=' . env('LARAVEL_DB_DATABASE', env('DB_DATABASE')),
        env('LARAVEL_DB_USERNAME', env('DB_USERNAME')),
        env('LARAVEL_DB_PASSWORD', env('DB_PASSWORD'))
    );
    echo "✅ Database connection successful\n";
    
    // Test Laravel DB facade
    echo "\n4. Testing Laravel Service Providers...\n";
    
    // Check if services are registered
    $services = $app->getLoadedProviders();
    echo "Loaded providers: " . count($services) . "\n";
    
    // Check specific services
    $dbService = $app->bound('db') ? 'BOUND' : 'NOT BOUND';
    echo "DB service: $dbService\n";
    
    if ($app->bound('db')) {
        $db = $app->make('db');
        $users = $db->table('users')->count();
        echo "✅ Laravel DB working - Users count: $users\n";
    } else {
        echo "❌ DB service not registered\n";
        
        // Try to manually register database service
        echo "Attempting to register database provider...\n";
        $app->register(\Illuminate\Database\DatabaseServiceProvider::class);
        
        if ($app->bound('db')) {
            echo "✅ DB service registered manually\n";
            $db = $app->make('db');
            $users = $db->table('users')->count();
            echo "✅ Laravel DB working - Users count: $users\n";
        }
    }
    
    // Test if routes are loaded
    echo "\n5. Testing Routes...\n";
    $router = $app->make('router');
    $routes = $router->getRoutes();
    echo "✅ Routes loaded - Count: " . count($routes) . "\n";
    
    // Check if our /up route exists
    foreach ($routes as $route) {
        if ($route->uri() === 'up') {
            echo "✅ Found /up route\n";
            break;
        }
    }
    
    echo "\n🚀 All tests passed! Laravel should be working.\n";
    
} catch (Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?>