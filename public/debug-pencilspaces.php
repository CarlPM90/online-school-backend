<?php
// Debug PencilSpaces API endpoint issues

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin, Current-Timezone');

try {
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

    // Check if there are any PencilSpaces configuration settings
    $configStmt = $pdo->prepare("SELECT * FROM settings WHERE key LIKE '%pencil%' ORDER BY key");
    $configStmt->execute();
    $pencilSettings = $configStmt->fetchAll();

    // Check pencil_space_accounts table
    $accountsStmt = $pdo->prepare("SELECT COUNT(*) as count FROM pencil_space_accounts");
    $accountsStmt->execute();
    $accountsCount = $accountsStmt->fetch()['count'];

    // Check if PencilSpaces package config exists
    $packageConfigStmt = $pdo->prepare("SELECT * FROM settings WHERE \"group\" = 'pencil_spaces' OR key LIKE '%api_key%' OR key LIKE '%api_url%'");
    $packageConfigStmt->execute();
    $packageConfig = $packageConfigStmt->fetchAll();

    // Test manual direct URL access
    $pencilSpacesUrl = null;
    foreach ($pencilSettings as $setting) {
        if ($setting['key'] === 'pencil_spaces_link' || $setting['key'] === 'pencil_spaces_url') {
            $pencilSpacesUrl = json_decode($setting['value'], true);
            break;
        }
    }

    echo json_encode([
        'debug_info' => [
            'pencil_settings' => $pencilSettings,
            'accounts_count' => $accountsCount,
            'package_config' => $packageConfig,
            'pencil_spaces_url' => $pencilSpacesUrl,
            'api_endpoint_test' => [
                'expected_endpoint' => '/api/pencil-spaces/login',
                'purpose' => 'Generate direct login URL for authenticated user',
                'requires' => 'User authentication + PencilSpaces API key configuration'
            ],
            'frontend_flow' => [
                '1' => 'Frontend gets pencil_spaces_link from /api/settings',
                '2' => 'Frontend calls /api/pencil-spaces/login to get direct login URL',
                '3' => 'Backend should return direct login URL for current user',
                '4' => 'Frontend redirects user to that URL'
            ],
            'possible_issues' => [
                'authentication' => 'User not logged in when calling /api/pencil-spaces/login',
                'api_key_missing' => 'PencilSpaces API key not configured in settings',
                'package_not_configured' => 'EscolaLMS PencilSpaces package not properly set up'
            ]
        ]
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
?>