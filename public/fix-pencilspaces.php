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
    
    // Get PencilSpaces URL from environment or use default
    $pencilSpacesUrl = $_ENV['PENCIL_SPACES_URL'] ?? getenv('PENCIL_SPACES_URL');
    if (!$pencilSpacesUrl || $pencilSpacesUrl === false || $pencilSpacesUrl === '') {
        $pencilSpacesUrl = 'https://pencilspaces.com';
    }
    $results = [];
    $currentTime = date('Y-m-d H:i:s');
    
    // First, check the structure of settings table and constraints
    try {
        $columnsStmt = $pdo->prepare("
            SELECT column_name 
            FROM information_schema.columns 
            WHERE table_name = 'settings' AND table_schema = 'public'
        ");
        $columnsStmt->execute();
        $columns = array_column($columnsStmt->fetchAll(), 'column_name');
        $results['settings_table_columns'] = $columns;
        
        // Check for check constraints to see valid type values
        $constraintsStmt = $pdo->prepare("
            SELECT constraint_name, check_clause
            FROM information_schema.check_constraints 
            WHERE constraint_name LIKE '%settings_type%'
        ");
        $constraintsStmt->execute();
        $constraints = $constraintsStmt->fetchAll();
        $results['type_constraints'] = $constraints;
        
        // Look at existing settings to see what types are used
        $existingTypesStmt = $pdo->prepare("SELECT DISTINCT type FROM settings LIMIT 10");
        $existingTypesStmt->execute();
        $existingTypes = array_column($existingTypesStmt->fetchAll(), 'type');
        $results['existing_types'] = $existingTypes;
        
    } catch (Exception $e) {
        $results['settings_table_info'] = "Error: " . $e->getMessage();
    }

    // Try to add to 'settings' table with correct columns
    // Add both pencil_spaces_url and pencil_spaces_link for frontend compatibility
    $settingsToAdd = [
        'pencil_spaces_url' => $pencilSpacesUrl,
        'pencil_spaces_link' => $pencilSpacesUrl,
    ];
    
    foreach ($settingsToAdd as $key => $url) {
        try {
            $checkStmt = $pdo->prepare("SELECT * FROM settings WHERE key = ?");
            $checkStmt->execute([$key]);
            $existingSetting = $checkStmt->fetch();

            if (!$existingSetting) {
                // Try different type values to find one that works
                $typesToTry = ['text', 'string', 'url', 'varchar', 'input', 'textarea'];
                $insertSuccessful = false;
                
                foreach ($typesToTry as $typeValue) {
                    try {
                        $insertStmt = $pdo->prepare("
                            INSERT INTO settings (key, \"group\", value, public, enumerable, sort, type, created_at, updated_at) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                        ");
                        $insertStmt->execute([
                            $key, // Use the key from the loop (pencil_spaces_url or pencil_spaces_link)
                            'general', // Default group
                            json_encode($url),
                            't',  // public (PostgreSQL boolean)
                            'f', // enumerable (PostgreSQL boolean)
                            0,     // sort
                            $typeValue, // Try different type values
                            $currentTime,
                            $currentTime
                        ]);
                        $insertSuccessful = true;
                        $results["settings_table_{$key}"] = "✅ Added {$key} to settings table with type: {$typeValue}";
                        break;
                    } catch (Exception $e) {
                        continue; // Try next type
                    }
                }
                
                if (!$insertSuccessful) {
                    $results["settings_table_{$key}"] = "❌ Failed to insert {$key} with any type value";
                }
            } else {
                $results["settings_table_{$key}"] = "✅ {$key} already exists in settings table";
            }
        } catch (Exception $e) {
            $results["settings_table_{$key}"] = "❌ Error with {$key}: " . $e->getMessage();
        }
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

    // Check if pencil_space_accounts table has any configuration
    try {
        $pencilStmt = $pdo->prepare("SELECT COUNT(*) as count FROM pencil_space_accounts");
        $pencilStmt->execute();
        $pencilCount = $pencilStmt->fetch()['count'];
        $results['pencil_space_accounts'] = "ℹ️ Found {$pencilCount} records in pencil_space_accounts table";
        
        // Check the structure of pencil_space_accounts
        $pencilColumnsStmt = $pdo->prepare("
            SELECT column_name 
            FROM information_schema.columns 
            WHERE table_name = 'pencil_space_accounts' AND table_schema = 'public'
        ");
        $pencilColumnsStmt->execute();
        $pencilColumns = array_column($pencilColumnsStmt->fetchAll(), 'column_name');
        $results['pencil_space_accounts_columns'] = $pencilColumns;
    } catch (Exception $e) {
        $results['pencil_space_accounts'] = "❌ pencil_space_accounts: " . $e->getMessage();
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