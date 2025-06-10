<?php
// Force Laravel service providers to load properly
require_once __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';

// Manually register critical service providers that should be loaded automatically
$criticalProviders = [
    \Illuminate\Foundation\Providers\FoundationServiceProvider::class,
    \Illuminate\Database\DatabaseServiceProvider::class,
    \Illuminate\Filesystem\FilesystemServiceProvider::class,
    \Illuminate\View\ViewServiceProvider::class,
    \Illuminate\Routing\RoutingServiceProvider::class,
];

echo "Manually registering critical service providers...\n";
foreach ($criticalProviders as $provider) {
    try {
        $app->register($provider);
        echo "✅ Registered: $provider\n";
    } catch (Exception $e) {
        echo "❌ Failed to register $provider: " . $e->getMessage() . "\n";
    }
}

// Test if services work now
echo "\nTesting services after manual registration:\n";
try {
    $config = $app->make('config');
    echo "✅ Config service working\n";
    
    $db = $app->make('db');
    echo "✅ Database service working\n";
    
    // Test a simple query
    $users = $db->table('users')->count();
    echo "✅ Database query successful - Users: $users\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>