<?php
// Debug Authentication System
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    // Connect to database
    $host = $_ENV['DB_HOST'] ?? getenv('DB_HOST');
    $port = $_ENV['DB_PORT'] ?? getenv('DB_PORT') ?? '5432';
    $database = $_ENV['DB_DATABASE'] ?? getenv('DB_DATABASE');
    $username = $_ENV['DB_USERNAME'] ?? getenv('DB_USERNAME');
    $password = $_ENV['DB_PASSWORD'] ?? getenv('DB_PASSWORD');

    $dsn = "pgsql:host={$host};port={$port};dbname={$database}";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $debug = [];

    // Check OAuth tables
    $tables = ['oauth_clients', 'oauth_access_tokens', 'oauth_refresh_tokens'];
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM $table");
            $stmt->execute();
            $debug['oauth_tables'][$table] = $stmt->fetch()['count'];
        } catch (Exception $e) {
            $debug['oauth_tables'][$table] = 'Table missing: ' . $e->getMessage();
        }
    }

    // Check for active users
    try {
        $userStmt = $pdo->prepare("SELECT COUNT(*) as count FROM users");
        $userStmt->execute();
        $debug['users_count'] = $userStmt->fetch()['count'];
    } catch (Exception $e) {
        $debug['users_count'] = 'Error: ' . $e->getMessage();
    }

    // Check app key
    $debug['app_key'] = $_ENV['APP_KEY'] ?? getenv('APP_KEY') ? 'SET' : 'NOT SET';
    
    // Check passport keys
    $debug['passport_keys'] = [
        'private_key_env' => !empty($_ENV['PASSPORT_PRIVATE_KEY'] ?? getenv('PASSPORT_PRIVATE_KEY')) ? 'SET' : 'NOT SET',
        'public_key_env' => !empty($_ENV['PASSPORT_PUBLIC_KEY'] ?? getenv('PASSPORT_PUBLIC_KEY')) ? 'SET' : 'NOT SET'
    ];

    // Try to check if composer vendor exists
    $debug['composer_autoload'] = file_exists('/var/www/html/vendor/autoload.php') ? 'EXISTS' : 'MISSING';

    // Check CORS origins
    $debug['allowed_origins'] = [
        'frontend' => 'https://tos-front-end.vercel.app',
        'admin' => 'https://tos-admin-panel.vercel.app',
        'localhost_3000' => 'http://localhost:3000',
        'localhost_3001' => 'http://localhost:3001'
    ];

    echo json_encode([
        'status' => 'debug',
        'authentication_system' => $debug,
        'likely_issues' => [
            'missing_oauth_clients' => $debug['oauth_tables']['oauth_clients'] == 0,
            'missing_access_tokens' => $debug['oauth_tables']['oauth_access_tokens'] == 0,
            'missing_passport_setup' => $debug['passport_keys']['private_key_env'] === 'NOT SET',
            'missing_composer_deps' => $debug['composer_autoload'] === 'MISSING'
        ],
        'next_steps' => [
            'If oauth_clients is 0: Need to run php artisan passport:install',
            'If passport keys missing: Need to generate or set PASSPORT_PRIVATE_KEY/PASSPORT_PUBLIC_KEY',
            'If composer autoload missing: Need to run composer install',
            'Check if EscolaLMS packages are properly installed and routes registered'
        ]
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
?>