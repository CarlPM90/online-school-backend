<?php
// Emergency fix for PencilSpaces configuration

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin, Current-Timezone');

try {
    // Bootstrap Laravel
    require_once __DIR__ . '/../bootstrap/app-fixed.php';
    $app = require_once __DIR__ . '/../bootstrap/app-fixed.php';
    $kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
    $app->boot();

    // Check if we can connect to database
    $pdo = DB::connection()->getPdo();
    
    $pencilSpacesUrl = env('PENCIL_SPACES_URL', 'https://pencilspaces.com');
    $results = [];
    
    // Try to add to 'settings' table
    try {
        $existingSetting = DB::table('settings')
            ->where('key', 'pencil_spaces_url')
            ->first();

        if (!$existingSetting) {
            DB::table('settings')->insert([
                'key' => 'pencil_spaces_url',
                'value' => json_encode($pencilSpacesUrl),
                'type' => 'string',
                'public' => true,
                'readonly' => false,
                'created_at' => now(),
                'updated_at' => now(),
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
        if (Schema::hasTable('escolalms_settings')) {
            $existingEscolaSetting = DB::table('escolalms_settings')
                ->where('key', 'pencil_spaces_url')
                ->first();

            if (!$existingEscolaSetting) {
                DB::table('escolalms_settings')->insert([
                    'key' => 'pencil_spaces_url',
                    'value' => $pencilSpacesUrl,
                    'created_at' => now(),
                    'updated_at' => now(),
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
    $tables = DB::select('SHOW TABLES');
    $tableNames = array_map(function($table) {
        return array_values((array)$table)[0];
    }, $tables);

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