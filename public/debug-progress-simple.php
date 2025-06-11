<?php
// Simple debug for progress issue using direct database queries
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    // Get database connection details from environment or config
    $host = $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?? 'localhost';
    $database = $_ENV['DB_DATABASE'] ?? getenv('DB_DATABASE') ?? 'escolalms';
    $username = $_ENV['DB_USERNAME'] ?? getenv('DB_USERNAME') ?? 'root';
    $password = $_ENV['DB_PASSWORD'] ?? getenv('DB_PASSWORD') ?? '';
    
    // Try to read .env file if environment variables aren't available
    if (file_exists('/var/www/html/.env')) {
        $envContent = file_get_contents('/var/www/html/.env');
        $envLines = explode("\n", $envContent);
        foreach ($envLines as $line) {
            if (strpos($line, 'DB_HOST=') === 0) {
                $host = trim(substr($line, 8));
            } elseif (strpos($line, 'DB_DATABASE=') === 0) {
                $database = trim(substr($line, 12));
            } elseif (strpos($line, 'DB_USERNAME=') === 0) {
                $username = trim(substr($line, 12));
            } elseif (strpos($line, 'DB_PASSWORD=') === 0) {
                $password = trim(substr($line, 12));
            }
        }
    }
    
    // Connect to database
    $pdo = new PDO("mysql:host=$host;dbname=$database", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get user details
    $userStmt = $pdo->prepare("SELECT id, email, name FROM users WHERE email = ?");
    $userStmt->execute(['carl.p.morris@gmail.com']);
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo json_encode(['error' => 'User not found']);
        exit;
    }
    
    // Get user's course enrollments
    $courseStmt = $pdo->prepare("
        SELECT cu.*, c.title, c.slug 
        FROM course_user cu 
        LEFT JOIN courses c ON cu.course_id = c.id 
        WHERE cu.user_id = ?
    ");
    $courseStmt->execute([$user['id']]);
    $enrollments = $courseStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get all tables to see what progress tracking tables exist
    $tablesStmt = $pdo->query("SHOW TABLES");
    $allTables = $tablesStmt->fetchAll(PDO::FETCH_COLUMN);
    
    $progressTables = [];
    foreach ($allTables as $table) {
        if (strpos($table, 'progress') !== false || 
            strpos($table, 'topic') !== false ||
            strpos($table, 'tracker') !== false ||
            strpos($table, 'lesson') !== false) {
            $progressTables[] = $table;
        }
    }
    
    // Try to get progress data from common progress tables
    $progressData = [];
    foreach ($progressTables as $table) {
        try {
            $progressStmt = $pdo->prepare("SELECT * FROM `$table` WHERE user_id = ? LIMIT 5");
            $progressStmt->execute([$user['id']]);
            $data = $progressStmt->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($data)) {
                $progressData[$table] = $data;
            }
        } catch (Exception $e) {
            $progressData[$table] = 'Error: ' . $e->getMessage();
        }
    }
    
    // Get course structure for enrolled courses
    $courseStructure = [];
    foreach ($enrollments as $enrollment) {
        if ($enrollment['course_id']) {
            try {
                // Check if there are topics/lessons for this course
                $topicStmt = $pdo->prepare("SELECT COUNT(*) as topic_count FROM topics WHERE course_id = ?");
                $topicStmt->execute([$enrollment['course_id']]);
                $topicCount = $topicStmt->fetch(PDO::FETCH_ASSOC);
                
                $courseStructure[$enrollment['course_id']] = [
                    'title' => $enrollment['title'],
                    'slug' => $enrollment['slug'],
                    'topic_count' => $topicCount['topic_count'] ?? 0,
                    'enrollment_date' => $enrollment['created_at'],
                    'finished' => $enrollment['finished']
                ];
            } catch (Exception $e) {
                $courseStructure[$enrollment['course_id']] = 'Error getting structure: ' . $e->getMessage();
            }
        }
    }
    
    echo json_encode([
        'status' => 'success',
        'user' => $user,
        'enrollments_count' => count($enrollments),
        'enrollments' => $enrollments,
        'progress_tables' => $progressTables,
        'progress_data' => $progressData,
        'course_structure' => $courseStructure,
        'database_info' => [
            'host' => $host,
            'database' => $database,
            'total_tables' => count($allTables)
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