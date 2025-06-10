<?php
// Debug all settings to see what the frontend might be looking for

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

    // Get all public settings
    $stmt = $pdo->prepare("SELECT * FROM settings WHERE public = 't' ORDER BY key");
    $stmt->execute();
    $allSettings = $stmt->fetchAll();

    // Look specifically for pencil-related settings
    $pencilStmt = $pdo->prepare("SELECT * FROM settings WHERE key LIKE '%pencil%' OR key LIKE '%space%'");
    $pencilStmt->execute();
    $pencilSettings = $pencilStmt->fetchAll();

    // Check if there are any settings with 'url' in the key
    $urlStmt = $pdo->prepare("SELECT * FROM settings WHERE key LIKE '%url%'");
    $urlStmt->execute();
    $urlSettings = $urlStmt->fetchAll();

    echo json_encode([
        'debug_info' => [
            'all_public_settings' => $allSettings,
            'pencil_related_settings' => $pencilSettings,
            'url_related_settings' => $urlSettings,
            'total_settings' => count($allSettings),
            'instructions' => [
                'Check if pencil_spaces_url appears in the settings',
                'Look for any other pencil-related keys',
                'Frontend might be looking for different key names'
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