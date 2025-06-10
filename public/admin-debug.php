<?php
// Debug admin routes and authentication

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin, Current-Timezone');

try {
    echo json_encode([
        'admin_help' => [
            'credentials' => [
                'email' => 'admin@escolalms.com',
                'password' => 'secret'
            ],
            'login_url' => 'https://web-production-82cf.up.railway.app/api/auth/login',
            'admin_endpoints' => [
                'users' => '/api/admin/users',
                'settings' => '/api/admin/settings', 
                'courses' => '/api/admin/courses',
                'documentation' => '/api/documentation'
            ],
            'instructions' => [
                '1. Login via POST to /api/auth/login with credentials above',
                '2. Use returned access_token in Authorization: Bearer TOKEN header',
                '3. Access admin endpoints with the token',
                '4. Check /api/documentation for full Swagger interface'
            ]
        ]
    ], JSON_PRETTY_PRINT);
    
} catch (\Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
?>