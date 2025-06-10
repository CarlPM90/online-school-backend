<?php
// Fix PencilSpaces API Configuration by syncing environment variables to database

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin, Current-Timezone');

try {
    // Get environment variables
    $envApiKey = $_ENV['PENCIL_SPACES_API_KEY'] ?? getenv('PENCIL_SPACES_API_KEY') ?? null;
    $envApiUrl = $_ENV['PENCIL_SPACES_API_URL'] ?? getenv('PENCIL_SPACES_API_URL') ?? 'https://api.pencilspaces.com';
    
    if (!$envApiKey) {
        echo json_encode([
            'status' => 'error',
            'message' => 'PENCIL_SPACES_API_KEY environment variable not found'
        ], JSON_PRETTY_PRINT);
        exit;
    }
    
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

    $results = [];
    
    // Set up required PencilSpaces API configuration
    $apiSettings = [
        'pencil_spaces_api_key' => $envApiKey,
        'pencil_spaces_api_url' => $envApiUrl,
    ];

    foreach ($apiSettings as $key => $value) {
        // Check if setting already exists
        $existingStmt = $pdo->prepare("SELECT * FROM settings WHERE key = ?");
        $existingStmt->execute([$key]);
        $existingSetting = $existingStmt->fetch();

        if (!$existingSetting && $value !== '') {
            // Insert new setting
            $insertStmt = $pdo->prepare("INSERT INTO settings (key, \"group\", value, type, public, enumerable, readonly, sort, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
            $insertStmt->execute([
                $key,
                'pencil_spaces',
                json_encode($value),
                'text',
                false, // API keys should not be public
                false,
                false,
                0
            ]);
            $results[$key] = "✅ Added $key setting";
        } elseif ($existingSetting) {
            // Update existing setting
            $updateStmt = $pdo->prepare("UPDATE settings SET value = ?, updated_at = NOW() WHERE key = ?");
            $updateStmt->execute([json_encode($value), $key]);
            $results[$key] = "✅ Updated $key setting";
        } else {
            $results[$key] = "⚠️ $key environment variable is empty";
        }
    }

    // Verify the settings were created
    $verifyStmt = $pdo->prepare("SELECT key, value FROM settings WHERE key IN ('pencil_spaces_api_key', 'pencil_spaces_api_url')");
    $verifyStmt->execute();
    $verifiedSettings = $verifyStmt->fetchAll();

    echo json_encode([
        'status' => 'success',
        'message' => 'PencilSpaces API configuration synchronized',
        'results' => $results,
        'verified_settings' => $verifiedSettings,
        'next_steps' => [
            'API configuration is now synced to database',
            'Test /api/pencil-spaces/login endpoint',
            'PencilSpaces integration should now work'
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