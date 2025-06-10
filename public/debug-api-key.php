<?php
// Debug PencilSpaces API Key Configuration

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin, Current-Timezone');

try {
    // Check environment variables directly
    $envApiKey = $_ENV['PENCIL_SPACES_API_KEY'] ?? getenv('PENCIL_SPACES_API_KEY') ?? null;
    $envApiUrl = $_ENV['PENCIL_SPACES_API_URL'] ?? getenv('PENCIL_SPACES_API_URL') ?? null;
    
    // Connect to database
    $host = $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?? 'localhost';
    $port = $_ENV['DB_PORT'] ?? getenv('DB_PORT') ?? '5432';
    $database = $_ENV['DB_DATABASE'] ?? getenv('DB_DATABASE') ?? 'postgres';
    $username = $_ENV['DB_USERNAME'] ?? getenv('DB_USERNAME') ?? 'postgres';
    $password = $_ENV['DB_PASSWORD'] ?? getenv('DB_PASSWORD') ?? '';

    $dsn = "pgsql:host={$host};port={$port};dbname={$database}";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    // Check database for API key settings
    $apiKeyStmt = $pdo->prepare("SELECT * FROM settings WHERE key = 'pencil_spaces_api_key'");
    $apiKeyStmt->execute();
    $dbApiKey = $apiKeyStmt->fetch();
    
    $apiUrlStmt = $pdo->prepare("SELECT * FROM settings WHERE key = 'pencil_spaces_api_url'");
    $apiUrlStmt->execute();
    $dbApiUrl = $apiUrlStmt->fetch();

    // Test if setup command needs to run
    $needsSetup = !$dbApiKey || !$dbApiUrl;
    
    echo json_encode([
        'status' => 'debug',
        'environment_variables' => [
            'PENCIL_SPACES_API_KEY' => $envApiKey ? 'SET (length: ' . strlen($envApiKey) . ')' : 'NOT SET',
            'PENCIL_SPACES_API_URL' => $envApiUrl ?: 'NOT SET'
        ],
        'database_settings' => [
            'pencil_spaces_api_key' => $dbApiKey ? 'EXISTS (length: ' . strlen(json_decode($dbApiKey['value'], true)) . ')' : 'MISSING',
            'pencil_spaces_api_url' => $dbApiUrl ? json_decode($dbApiUrl['value'], true) : 'MISSING'
        ],
        'diagnosis' => [
            'env_api_key_exists' => !empty($envApiKey),
            'db_api_key_exists' => !empty($dbApiKey),
            'needs_setup_command' => $needsSetup,
            'expected_api_key_length' => 44, // Expected length for the provided key
            'actual_env_key_length' => $envApiKey ? strlen($envApiKey) : 0
        ],
        'next_steps' => $needsSetup ? [
            'The environment variable exists but database settings are missing',
            'Run: php artisan db:setup to sync environment variables to database',
            'Or manually run the setupPencilSpacesApiConfig() method'
        ] : [
            'Configuration appears complete',
            'Check /api/pencil-spaces/login endpoint logs for specific error'
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