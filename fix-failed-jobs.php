<?php
// Quick script to fix failed_jobs table
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

try {
    // Check if uuid column exists
    if (!Schema::hasColumn('failed_jobs', 'uuid')) {
        echo "Adding uuid column to failed_jobs table...\n";
        DB::statement('ALTER TABLE failed_jobs ADD COLUMN uuid VARCHAR(255) NULL');
        echo "UUID column added.\n";
    }
    
    // Clear all failed jobs to prevent constraint issues
    echo "Clearing failed jobs...\n";
    DB::table('failed_jobs')->delete();
    echo "Failed jobs cleared.\n";
    
    // Clear Redis queues if Redis is available
    try {
        $redis = app('redis')->connection();
        $redis->flushdb();
        echo "Redis queues cleared.\n";
    } catch (Exception $e) {
        echo "Could not clear Redis (this is okay): " . $e->getMessage() . "\n";
    }
    
    echo "✅ Failed jobs table fixed!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>