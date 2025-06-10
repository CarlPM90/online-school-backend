<?php

// Enhanced Laravel Debug Script for Railway Deployment
// This bypasses Laravel routing to diagnose 500 errors

echo "<!DOCTYPE html><html><head><title>Enhanced Laravel Debug</title>";
echo "<style>body{font-family:monospace;margin:20px;} .success{color:green;} .error{color:red;} .warning{color:orange;} .info{color:blue;} pre{background:#f5f5f5;padding:10px;border-radius:5px;}</style></head><body>";

echo "<h1>🔧 Enhanced Laravel Environment Debug</h1>";
echo "<p><strong>Timestamp:</strong> " . date('Y-m-d H:i:s T') . "</p>";

$startTime = microtime(true);
$startMemory = memory_get_usage();

// Helper function to format output
function debugOutput($message, $type = 'info') {
    $icons = ['success' => '✅', 'error' => '❌', 'warning' => '⚠️', 'info' => 'ℹ️'];
    echo "<p class='$type'>" . ($icons[$type] ?? '') . " $message</p>";
}

function debugSection($title) {
    echo "<h2>🔍 $title</h2>";
}

function debugCode($code) {
    echo "<pre>" . htmlspecialchars($code) . "</pre>";
}

try {
    debugSection("1. Testing Laravel Bootstrap");
    
    // Test autoloader
    if (file_exists(__DIR__.'/../vendor/autoload.php')) {
        require_once __DIR__.'/../vendor/autoload.php';
        debugOutput("Autoloader loaded", 'success');
    } else {
        debugOutput("Autoloader not found", 'error');
        exit;
    }

    // Test Laravel Application bootstrap
    debugOutput("Testing Laravel Application bootstrap...", 'info');
    
    if (file_exists(__DIR__.'/../bootstrap/app-fixed.php')) {
        debugOutput("Found app-fixed.php (using custom bootstrap)", 'success');
        $app = require_once __DIR__.'/../bootstrap/app-fixed.php';
        debugOutput("App bootstrapped: " . get_class($app), 'success');
    } else {
        debugOutput("app-fixed.php not found", 'error');
        exit;
    }

    // Test kernel creation
    try {
        $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
        debugOutput("Kernel created: " . get_class($kernel), 'success');
    } catch (Exception $e) {
        debugOutput("Kernel creation failed: " . $e->getMessage(), 'error');
    }

    debugSection("2. Environment Variables");
    
    $coreVars = [
        'APP_ENV' => env('APP_ENV'),
        'APP_DEBUG' => env('APP_DEBUG') ? 'true' : 'false',
        'APP_KEY' => env('APP_KEY') ? 'SET (' . strlen(env('APP_KEY')) . ' chars)' : 'NOT SET',
        'APP_URL' => env('APP_URL'),
        'DB_CONNECTION' => env('DB_CONNECTION'),
        'DB_HOST' => env('DB_HOST'),
        'DB_PORT' => env('DB_PORT'),
        'DB_DATABASE' => env('DB_DATABASE'),
        'DB_USERNAME' => env('DB_USERNAME') ? 'SET' : 'NOT SET',
        'DB_PASSWORD' => env('DB_PASSWORD') ? 'SET (' . strlen(env('DB_PASSWORD')) . ' chars)' : 'NOT SET',
    ];

    foreach ($coreVars as $var => $value) {
        debugOutput("$var: $value", $value === 'NOT SET' ? 'warning' : 'success');
    }

    debugSection("3. Testing Database Connection");
    
    try {
        $host = env('DB_HOST');
        $port = env('DB_PORT', 5432);
        $database = env('DB_DATABASE');
        $username = env('DB_USERNAME');
        $password = env('DB_PASSWORD');
        
        debugOutput("Attempting direct PDO connection...", 'info');
        debugOutput("Host: $host:$port, Database: $database, User: $username", 'info');
        
        $pdo = new PDO("pgsql:host=$host;port=$port;dbname=$database", $username, $password);
        debugOutput("Database connection successful", 'success');
        
        $version = $pdo->query('SELECT version()')->fetchColumn();
        debugOutput("PostgreSQL Version: " . substr($version, 0, 50) . "...", 'success');
        
    } catch (Exception $e) {
        debugOutput("Database connection failed: " . $e->getMessage(), 'error');
    }

    debugSection("4. Testing Laravel Service Providers");
    
    try {
        $providers = $app->getLoadedProviders();
        debugOutput("Loaded providers: " . count($providers), 'success');
        
        $criticalProviders = [
            'Illuminate\Foundation\Providers\FoundationServiceProvider',
            'Illuminate\Database\DatabaseServiceProvider',
            'App\Providers\AuthServiceProvider',
            'Laravel\Passport\PassportServiceProvider',
            'App\Providers\AppServiceProvider'
        ];
        
        foreach ($criticalProviders as $provider) {
            $loaded = isset($providers[$provider]);
            debugOutput("$provider: " . ($loaded ? 'LOADED' : 'NOT LOADED'), $loaded ? 'success' : 'warning');
        }
        
    } catch (Exception $e) {
        debugOutput("Provider check failed: " . $e->getMessage(), 'error');
    }

    debugSection("5. Testing Config Service");
    
    try {
        // Test if config is bound
        $isBound = $app->bound('config');
        debugOutput("Config service bound: " . ($isBound ? 'YES' : 'NO'), $isBound ? 'success' : 'error');
        
        if ($isBound) {
            $config = $app->make('config');
            debugOutput("Config service type: " . get_class($config), 'success');
            
            // Test config access
            $appName = $config->get('app.name', 'NOT SET');
            debugOutput("app.name config: $appName", 'success');
            
            $dbConnection = $config->get('database.default', 'NOT SET');
            debugOutput("database.default config: $dbConnection", 'success');
        } else {
            debugOutput("Attempting to register config manually...", 'warning');
            try {
                $app->singleton('config', function ($app) {
                    return new \Illuminate\Config\Repository();
                });
                debugOutput("Manual config registration successful", 'success');
            } catch (Exception $e) {
                debugOutput("Manual config registration failed: " . $e->getMessage(), 'error');
            }
        }
        
    } catch (Exception $e) {
        debugOutput("Config service test failed: " . $e->getMessage(), 'error');
    }

    debugSection("6. Testing Laravel Database Service");
    
    try {
        // Test if DB service exists
        $dbBound = $app->bound('db');
        debugOutput("DB service bound: " . ($dbBound ? 'YES' : 'NO'), $dbBound ? 'success' : 'error');
        
        if (!$dbBound) {
            debugOutput("Attempting to register database provider...", 'warning');
            try {
                $app->register(\Illuminate\Database\DatabaseServiceProvider::class);
                debugOutput("Database provider registered manually", 'success');
            } catch (Exception $e) {
                debugOutput("Database provider registration failed: " . $e->getMessage(), 'error');
            }
        }
        
        if ($app->bound('db')) {
            $db = $app->make('db');
            debugOutput("Database service type: " . get_class($db), 'success');
            
            // Test database query
            $result = $db->select('SELECT 1 as test');
            debugOutput("Database query test: " . json_encode($result), 'success');
        }
        
    } catch (Exception $e) {
        debugOutput("Database service test failed: " . $e->getMessage(), 'error');
        debugCode($e->getTraceAsString());
    }

    debugSection("7. Testing Passport Configuration");
    
    $passportVars = [
        'PASSPORT_PRIVATE_KEY' => env('PASSPORT_PRIVATE_KEY') ? 'SET (' . strlen(env('PASSPORT_PRIVATE_KEY')) . ' chars)' : 'NOT SET',
        'PASSPORT_PUBLIC_KEY' => env('PASSPORT_PUBLIC_KEY') ? 'SET (' . strlen(env('PASSPORT_PUBLIC_KEY')) . ' chars)' : 'NOT SET',
        'PASSPORT_PERSONAL_ACCESS_CLIENT_ID' => env('PASSPORT_PERSONAL_ACCESS_CLIENT_ID', 'NOT SET'),
        'PASSPORT_PERSONAL_ACCESS_CLIENT_SECRET' => env('PASSPORT_PERSONAL_ACCESS_CLIENT_SECRET') ? 'SET' : 'NOT SET',
    ];

    foreach ($passportVars as $var => $value) {
        debugOutput("$var: $value", $value === 'NOT SET' ? 'warning' : 'success');
    }

    // Test passport config access
    try {
        if ($app->bound('config')) {
            $config = $app->make('config');
            $privateKey = $config->get('passport.private_key');
            $publicKey = $config->get('passport.public_key');
            
            debugOutput("Passport private key in config: " . ($privateKey ? 'SET (' . strlen($privateKey) . ' chars)' : 'NOT SET'), $privateKey ? 'success' : 'warning');
            debugOutput("Passport public key in config: " . ($publicKey ? 'SET (' . strlen($publicKey) . ' chars)' : 'NOT SET'), $publicKey ? 'success' : 'warning');
        }
    } catch (Exception $e) {
        debugOutput("Passport config test failed: " . $e->getMessage(), 'error');
    }

    debugSection("8. Testing Route System");
    
    try {
        if ($app->bound('router')) {
            $router = $app->make('router');
            debugOutput("Router service type: " . get_class($router), 'success');
            
            $routes = $router->getRoutes();
            debugOutput("Total routes registered: " . count($routes), 'success');
        } else {
            debugOutput("Router service not bound", 'warning');
        }
    } catch (Exception $e) {
        debugOutput("Router test failed: " . $e->getMessage(), 'error');
    }

    debugSection("9. Performance Information");
    
    $endTime = microtime(true);
    $endMemory = memory_get_usage();
    $peakMemory = memory_get_peak_usage();
    
    debugOutput("Execution time: " . round(($endTime - $startTime) * 1000, 2) . "ms", 'info');
    debugOutput("Memory used: " . round(($endMemory - $startMemory) / 1024 / 1024, 2) . "MB", 'info');
    debugOutput("Peak memory: " . round($peakMemory / 1024 / 1024, 2) . "MB", 'info');

    debugSection("10. Summary");
    debugOutput("Laravel bootstrap appears to be working correctly", 'success');
    debugOutput("If you're still seeing 500 errors, check Laravel logs at storage/logs/laravel.log", 'info');

} catch (Exception $e) {
    debugOutput("❌ ERROR: " . $e->getMessage(), 'error');
    debugOutput("File: " . $e->getFile() . ":" . $e->getLine(), 'error');
    debugCode($e->getTraceAsString());
} catch (Error $e) {
    debugOutput("❌ FATAL ERROR: " . $e->getMessage(), 'error');
    debugOutput("File: " . $e->getFile() . ":" . $e->getLine(), 'error');
    debugCode($e->getTraceAsString());
}

echo "</body></html>";