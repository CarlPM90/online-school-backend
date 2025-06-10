<?php
// Fix PencilSpaces API Configuration - Updated for correct table structure

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

    // First, check what columns actually exist in the settings table
    $columnsStmt = $pdo->prepare("SELECT column_name FROM information_schema.columns WHERE table_name = 'settings' ORDER BY ordinal_position");
    $columnsStmt->execute();
    $columns = $columnsStmt->fetchAll(PDO::FETCH_COLUMN);

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
            // Build insert query based on available columns
            $baseColumns = ['key', 'value', 'type', 'created_at', 'updated_at'];
            $baseValues = [$key, json_encode($value), 'text', 'NOW()', 'NOW()'];
            
            // Add optional columns if they exist
            if (in_array('group', $columns)) {
                $baseColumns[] = '"group"';
                $baseValues[] = 'pencil_spaces';
            }
            if (in_array('public', $columns)) {
                $baseColumns[] = 'public';
                $baseValues[] = false; // API keys should not be public
            }
            if (in_array('enumerable', $columns)) {
                $baseColumns[] = 'enumerable';
                $baseValues[] = false;
            }
            if (in_array('sort', $columns)) {
                $baseColumns[] = 'sort';
                $baseValues[] = 0;
            }
            
            $placeholders = str_repeat('?,', count($baseValues) - 1) . '?';
            $sql = "INSERT INTO settings (" . implode(', ', $baseColumns) . ") VALUES ($placeholders)";
            
            // Replace NOW() placeholders
            $sql = str_replace("'NOW()'", "NOW()", $sql);
            
            $insertStmt = $pdo->prepare($sql);
            $insertStmt->execute(array_filter($baseValues, function($v) { return $v !== 'NOW()'; }));
            
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
        'table_columns' => $columns,
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