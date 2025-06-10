<?php
// Emergency fix for PencilSpaces configuration using direct database connection

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin, Current-Timezone');

try {
    // Connect directly to database using environment variables
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
    
    $pencilSpacesUrl = $_ENV['PENCIL_SPACES_URL'] ?? getenv('PENCIL_SPACES_URL') ?? 'https://pencilspaces.com';
    $results = [];
    $currentTime = date('Y-m-d H:i:s');
    
    // Try to add to 'settings' table
    try {
        $checkStmt = $pdo->prepare("SELECT * FROM settings WHERE key = ?");
        $checkStmt->execute(['pencil_spaces_url']);
        $existingSetting = $checkStmt->fetch();

        if (!$existingSetting) {
            $insertStmt = $pdo->prepare("
                INSERT INTO settings (key, value, type, public, readonly, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $insertStmt->execute([
                'pencil_spaces_url',
                json_encode($pencilSpacesUrl),
                'string',
                true,
                false,
                $currentTime,
                $currentTime
            ]);
            $results['settings_table'] = "✅ Added PencilSpaces URL to settings table";
        } else {
            $results['settings_table'] = "✅ PencilSpaces URL already exists in settings table";
        }
    } catch (Exception $e) {
        $results['settings_table'] = "❌ Settings table: " . $e->getMessage();
    }

    // Try to add to 'escolalms_settings' table if it exists
    try {
        $tableCheckStmt = $pdo->prepare("
            SELECT table_name 
            FROM information_schema.tables 
            WHERE table_schema = 'public' AND table_name = 'escolalms_settings'
        ");
        $tableCheckStmt->execute();
        $tableExists = $tableCheckStmt->fetch();

        if ($tableExists) {
            $checkStmt = $pdo->prepare("SELECT * FROM escolalms_settings WHERE key = ?");
            $checkStmt->execute(['pencil_spaces_url']);
            $existingEscolaSetting = $checkStmt->fetch();

            if (!$existingEscolaSetting) {
                $insertStmt = $pdo->prepare("
                    INSERT INTO escolalms_settings (key, value, created_at, updated_at) 
                    VALUES (?, ?, ?, ?)
                ");
                $insertStmt->execute([
                    'pencil_spaces_url',
                    $pencilSpacesUrl,
                    $currentTime,
                    $currentTime
                ]);
                $results['escolalms_settings_table'] = "✅ Added to escolalms_settings table";
            } else {
                $results['escolalms_settings_table'] = "✅ Already exists in escolalms_settings table";
            }
        } else {
            $results['escolalms_settings_table'] = "ℹ️ escolalms_settings table doesn't exist";
        }
    } catch (Exception $e) {
        $results['escolalms_settings_table'] = "❌ escolalms_settings table: " . $e->getMessage();
    }

    // List all available tables for debugging
    $tablesStmt = $pdo->prepare("
        SELECT table_name 
        FROM information_schema.tables 
        WHERE table_schema = 'public' 
        ORDER BY table_name
    ");
    $tablesStmt->execute();
    $tableNames = array_column($tablesStmt->fetchAll(), 'table_name');

    echo json_encode([
        'status' => 'success',
        'pencil_spaces_url' => $pencilSpacesUrl,
        'results' => $results,
        'available_tables' => $tableNames,
        'instructions' => [
            'PencilSpaces URL has been configured',
            'The frontend should now be able to find the setting',
            'Try clicking the pencil icon again'
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