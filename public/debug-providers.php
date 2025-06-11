<?php
// Debug what service providers are actually available
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    // Try to get the actual available provider classes
    require_once '/var/www/html/vendor/autoload.php';
    
    // Check if key EscolaLMS classes exist
    $availableClasses = [];
    $testClasses = [
        'EscolaLms\Core\EscolaLmsServiceProvider',
        'EscolaLms\Auth\AuthServiceProvider', 
        'EscolaLms\Auth\EscolaLmsAuthServiceProvider',
        'EscolaLms\Courses\CourseServiceProvider',
        'EscolaLms\Courses\EscolaLmsCourseServiceProvider',
        'EscolaLms\Tracker\TrackerServiceProvider',
        'EscolaLms\Tracker\EscolaLmsTrackerServiceProvider'
    ];
    
    foreach ($testClasses as $class) {
        if (class_exists($class)) {
            $availableClasses[] = $class;
        }
    }
    
    // Check if we can load composer.lock to see what's actually installed
    $installedPackages = [];
    if (file_exists('/var/www/html/composer.lock')) {
        $composerLock = json_decode(file_get_contents('/var/www/html/composer.lock'), true);
        if ($composerLock && isset($composerLock['packages'])) {
            foreach ($composerLock['packages'] as $package) {
                if (strpos($package['name'], 'escolalms') !== false) {
                    // Try to find the service provider in the package
                    $packagePath = '/var/www/html/vendor/' . $package['name'];
                    if (is_dir($packagePath)) {
                        // Look for service provider files
                        $serviceProviders = [];
                        $srcPath = $packagePath . '/src';
                        if (is_dir($srcPath)) {
                            $files = scandir($srcPath);
                            foreach ($files as $file) {
                                if (strpos($file, 'ServiceProvider.php') !== false) {
                                    $serviceProviders[] = $file;
                                }
                            }
                        }
                        
                        $installedPackages[] = [
                            'name' => $package['name'],
                            'version' => $package['version'],
                            'service_providers' => $serviceProviders,
                            'path_exists' => is_dir($packagePath)
                        ];
                    }
                }
            }
        }
    }
    
    // Try a minimal Laravel bootstrap to see what providers are auto-discovered
    $autoDiscoveredProviders = [];
    try {
        $app = require_once '/var/www/html/bootstrap/app.php';
        $providers = $app->getLoadedProviders();
        foreach ($providers as $provider => $loaded) {
            if (strpos($provider, 'EscolaLms') !== false) {
                $autoDiscoveredProviders[] = $provider;
            }
        }
    } catch (Exception $e) {
        $autoDiscoveredProviders = ['Error: ' . $e->getMessage()];
    }
    
    echo json_encode([
        'status' => 'success',
        'available_classes' => $availableClasses,
        'installed_packages' => $installedPackages,
        'auto_discovered_providers' => $autoDiscoveredProviders,
        'recommendations' => [
            'If no classes are available, the vendor directory may not be properly installed',
            'If classes exist but providers are wrong, use the correct class names from the scan',
            'Laravel should auto-discover providers, so manual registration may not be needed'
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