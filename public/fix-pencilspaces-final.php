<?php
// Final PencilSpaces API Configuration Fix - PostgreSQL compatible

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    // Get environment variables
    $envApiKey = $_ENV['PENCIL_SPACES_API_KEY'] ?? getenv('PENCIL_SPACES_API_KEY') ?? null;
    $envApiUrl = $_ENV['PENCIL_SPACES_API_URL'] ?? getenv('PENCIL_SPACES_API_URL') ?? 'https://api.pencilspaces.com';
    
    if (!$envApiKey) {
        echo json_encode(['status' => 'error', 'message' => 'API key not found in environment']);
        exit;
    }
    
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

    $results = [];

    // API Key setting
    $checkApiKey = $pdo->prepare("SELECT id FROM settings WHERE key = 'pencil_spaces_api_key'");
    $checkApiKey->execute();
    
    if ($checkApiKey->fetch()) {
        // Update existing
        $updateApiKey = $pdo->prepare("UPDATE settings SET value = ?, updated_at = NOW() WHERE key = 'pencil_spaces_api_key'");
        $updateApiKey->execute([json_encode($envApiKey)]);
        $results['api_key'] = '✅ Updated API key';
    } else {
        // Insert new - using PostgreSQL boolean literals
        $insertApiKey = $pdo->prepare("INSERT INTO settings (key, \"group\", value, type, public, enumerable, sort, created_at, updated_at) VALUES (?, ?, ?, ?, 'f', 'f', ?, NOW(), NOW())");
        $insertApiKey->execute([
            'pencil_spaces_api_key',
            'pencil_spaces', 
            json_encode($envApiKey),
            'text',
            0
        ]);
        $results['api_key'] = '✅ Added API key';
    }

    // API URL setting
    $checkApiUrl = $pdo->prepare("SELECT id FROM settings WHERE key = 'pencil_spaces_api_url'");
    $checkApiUrl->execute();
    
    if ($checkApiUrl->fetch()) {
        // Update existing
        $updateApiUrl = $pdo->prepare("UPDATE settings SET value = ?, updated_at = NOW() WHERE key = 'pencil_spaces_api_url'");
        $updateApiUrl->execute([json_encode($envApiUrl)]);
        $results['api_url'] = '✅ Updated API URL';
    } else {
        // Insert new
        $insertApiUrl = $pdo->prepare("INSERT INTO settings (key, \"group\", value, type, public, enumerable, sort, created_at, updated_at) VALUES (?, ?, ?, ?, 'f', 'f', ?, NOW(), NOW())");
        $insertApiUrl->execute([
            'pencil_spaces_api_url',
            'pencil_spaces',
            json_encode($envApiUrl), 
            'text',
            0
        ]);
        $results['api_url'] = '✅ Added API URL';
    }

    // Verify settings
    $verify = $pdo->prepare("SELECT key, value FROM settings WHERE key IN ('pencil_spaces_api_key', 'pencil_spaces_api_url')");
    $verify->execute();
    $settings = $verify->fetchAll();

    echo json_encode([
        'status' => 'success',
        'results' => $results,
        'verified_settings' => $settings,
        'env_key_length' => strlen($envApiKey),
        'message' => 'PencilSpaces API configuration complete'
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
?>