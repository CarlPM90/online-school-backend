<?php
// Debug progress API for user carl.p.morris@gmail.com
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    // Bootstrap Laravel app
    require_once '/var/www/html/vendor/autoload.php';
    $app = require_once '/var/www/html/bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    
    // Get user by email
    $user = \App\Models\User::where('email', 'carl.p.morris@gmail.com')->first();
    
    if (!$user) {
        echo json_encode([
            'error' => 'User not found',
            'email' => 'carl.p.morris@gmail.com'
        ]);
        exit;
    }
    
    // Check user's enrolled courses
    $enrolledCourses = $user->courses()->get();
    
    // Check if course progress tracking tables exist
    $progressTables = [];
    try {
        $tables = \DB::select('SHOW TABLES');
        foreach ($tables as $table) {
            $tableName = array_values((array)$table)[0];
            if (strpos($tableName, 'progress') !== false || 
                strpos($tableName, 'topic') !== false ||
                strpos($tableName, 'tracker') !== false) {
                $progressTables[] = $tableName;
            }
        }
    } catch (Exception $e) {
        $progressTables = ['Error: ' . $e->getMessage()];
    }
    
    // Get course details
    $courseDetails = [];
    foreach ($enrolledCourses as $course) {
        $courseDetails[] = [
            'id' => $course->id,
            'title' => $course->title ?? 'No title',
            'slug' => $course->slug ?? 'No slug',
            'created_at' => $course->created_at,
            'pivot' => [
                'created_at' => $course->pivot->created_at ?? null,
                'finished' => $course->pivot->finished ?? null,
                'deadline' => $course->pivot->deadline ?? null,
            ]
        ];
    }
    
    // Try to get progress data if tracker exists
    $progressData = null;
    try {
        if (class_exists('\EscolaLms\Tracker\Models\TrackProgress')) {
            $progressData = \EscolaLms\Tracker\Models\TrackProgress::where('user_id', $user->id)->get();
        }
    } catch (Exception $e) {
        $progressData = 'Tracker not available: ' . $e->getMessage();
    }
    
    echo json_encode([
        'status' => 'success',
        'user' => [
            'id' => $user->id,
            'email' => $user->email,
            'name' => $user->name ?? 'No name'
        ],
        'enrolled_courses_count' => $enrolledCourses->count(),
        'course_details' => $courseDetails,
        'progress_tables' => $progressTables,
        'progress_data' => $progressData,
        'debug_info' => [
            'course_user_table_exists' => true,
            'user_has_courses_trait' => method_exists($user, 'courses'),
            'timestamp' => now()
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