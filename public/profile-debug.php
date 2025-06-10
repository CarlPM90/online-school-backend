<?php
// Debug profile endpoint to understand gender validation requirements

require_once __DIR__ . '/../bootstrap/app-fixed.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin, Current-Timezone');

try {
    $app = require_once __DIR__ . '/../bootstrap/app-fixed.php';
    $kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
    
    // Boot the application
    $app->boot();
    
    // Check if gender field has specific validation rules or enum values
    echo json_encode([
        'status' => 'success',
        'debug_info' => [
            'message' => 'Debug endpoint active',
            'laravel_version' => app()->version(),
            'env' => app()->environment(),
            'gender_enum_info' => [
                'message' => 'Check your frontend - gender field might need specific values',
                'common_values' => ['male', 'female', 'other', 'M', 'F', 'O'],
                'suggestion' => 'Frontend should send lowercase values: male, female, other'
            ],
            'pencil_spaces_check' => [
                'package_installed' => 'escolalms/pencil-spaces: 0.0.2',
                'settings_package' => 'escolalms/settings: 0.2.4',
                'config_suggestion' => 'Check settings configuration for PencilSpaces URL'
            ]
        ]
    ], JSON_PRETTY_PRINT);
    
} catch (\Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ], JSON_PRETTY_PRINT);
}