<?php
// Direct SQL fix for API URL
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

    // Direct SQL update to fix the API URL
    $correctApiUrl = 'https://api.pencilspaces.com';
    
    $updateStmt = $pdo->prepare("UPDATE settings SET value = ? WHERE key = 'pencil_spaces_api_url'");
    $updateStmt->execute([json_encode($correctApiUrl)]);
    
    // Verify the change
    $verifyStmt = $pdo->prepare("SELECT key, value FROM settings WHERE key = 'pencil_spaces_api_url'");
    $verifyStmt->execute();
    $result = $verifyStmt->fetch();
    
    echo json_encode([
        'status' => 'success',
        'message' => 'API URL updated directly',
        'updated_setting' => $result,
        'decoded_value' => json_decode($result['value'])
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
?>