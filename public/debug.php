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
    echo "\n4. Testing Laravel DB...\n";
    $app->boot();
    $users = DB::table('users')->count();
    echo "✅ Laravel DB working - Users count: $users\n";
    
    echo "\n🚀 All tests passed! Laravel should be working.\n";
    
} catch (Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?>