<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Exception;

class SetupDatabase extends Command
{
    protected $signature = 'db:setup {--force : Force fresh migration (DESTROYS ALL DATA)}';
    protected $description = 'Set up the database with all package migrations and seeders (safe for existing data)';

    public function handle()
    {
        $this->info('🚀 Starting Escola LMS database setup...');

        try {
            // Test database connection
            $this->info('🔍 Testing database connection...');
            DB::connection()->getPdo();
            $this->info('✅ Database connection successful!');

            // Check if database has data
            $hasData = false;
            try {
                $userCount = DB::table('users')->count();
                $hasData = $userCount > 0;
            } catch (Exception $e) {
                // Users table doesn't exist yet
                $hasData = false;
            }

            if ($hasData && !$this->option('force')) {
                $this->warn('❗ Database already contains data. Skipping destructive operations.');
                $skipDestructiveOps = true;
            } else {
                $skipDestructiveOps = false;
            }

            // Skip key generation if key already exists to avoid breaking encrypted data
            if (!config('app.key')) {
                $this->info('🔑 Generating application key...');
                Artisan::call('key:generate', ['--force' => true]);
            } else {
                $this->info('🔑 Application key already set, skipping generation');
            }

            // Publish migrations for all packages
            $this->info('📝 Publishing package migrations...');
            
            $packages = [
                'EscolaLms\Auth\AuthServiceProvider',
                'EscolaLms\Core\CoreServiceProvider',
                'EscolaLms\Categories\CategoriesServiceProvider',
                'EscolaLms\Courses\CoursesServiceProvider',
                'EscolaLms\Settings\SettingsServiceProvider',
                'EscolaLms\Files\FilesServiceProvider',
                'EscolaLms\Tags\TagsServiceProvider',
                'EscolaLms\Permissions\PermissionsServiceProvider',
                'EscolaLms\Templates\TemplatesServiceProvider',
                'EscolaLms\Cart\CartServiceProvider',
                'EscolaLms\Payments\PaymentsServiceProvider',
                'EscolaLms\ModelFields\ModelFieldsServiceProvider',
                'EscolaLms\Consultations\ConsultationsServiceProvider',
                'EscolaLms\StationaryEvents\StationaryEventServiceProvider',
                'EscolaLms\Webinar\WebinarServiceProvider',
                'EscolaLms\Questionnaire\QuestionnaireServiceProvider',
                'EscolaLms\Reports\ReportsServiceProvider',
                'EscolaLms\Scorm\ScormServiceProvider',
                'EscolaLms\HeadlessH5P\HeadlessH5PServiceProvider',
                'EscolaLms\Notifications\NotificationsServiceProvider',
                'EscolaLms\Images\ImagesServiceProvider',
                'EscolaLms\Pages\PagesServiceProvider',
                'EscolaLms\Video\VideoServiceProvider',
                'EscolaLms\TopicTypes\TopicTypesServiceProvider',
                'EscolaLms\Tracker\TrackerServiceProvider',
                'EscolaLms\Vouchers\VouchersServiceProvider',
                'EscolaLms\BookmarksNotes\BookmarksNotesServiceProvider',
                'EscolaLms\Tasks\TasksServiceProvider',
                'EscolaLms\Invoices\InvoicesServiceProvider',
                'EscolaLms\TemplatesEmail\TemplatesEmailServiceProvider',
                'EscolaLms\TemplatesPdf\TemplatesPdfServiceProvider',
                'EscolaLms\TemplatesSms\TemplatesSmsServiceProvider',
                'EscolaLms\Translations\TranslationsServiceProvider',
                'EscolaLms\CsvUsers\CsvUsersServiceProvider',
                'EscolaLms\CoursesImportExport\CoursesImportExportServiceProvider',
                'EscolaLms\CourseAccess\CourseAccessServiceProvider',
                'EscolaLms\ConsultationAccess\ConsultationAccessServiceProvider',
                'EscolaLms\AssignWithoutAccount\AssignWithoutAccountServiceProvider',
                'EscolaLms\TopicTypeGift\TopicTypeGiftServiceProvider',
                'EscolaLms\TopicTypeProject\TopicTypeProjectServiceProvider',
                'EscolaLms\Cmi5\Cmi5ServiceProvider',
                'EscolaLms\Lrs\LrsServiceProvider',
                'EscolaLms\Mailerlite\MailerliteServiceProvider',
                'EscolaLms\Mattermost\MattermostServiceProvider',
                'EscolaLms\PencilSpaces\PencilSpacesServiceProvider',
                'Laravel\Passport\PassportServiceProvider'
            ];

            $bar = $this->output->createProgressBar(count($packages));
            $bar->start();

            foreach ($packages as $package) {
                try {
                    Artisan::call('vendor:publish', [
                        '--provider' => $package,
                        '--tag' => 'migrations',
                        '--force' => true
                    ]);
                    $bar->advance();
                } catch (Exception $e) {
                    $this->warn("Failed to publish migrations for $package: " . $e->getMessage());
                    $bar->advance();
                }
            }

            $bar->finish();
            $this->newLine();

            // Clean up any migration conflicts
            $this->info('🧹 Cleaning up migration conflicts...');
            $this->cleanupMigrationConflicts();

            // Check if this is first setup or update
            $this->info('🗃️ Setting up database migrations...');
            if ($skipDestructiveOps) {
                $this->info('🔄 Data exists - running safe migrations only...');
                try {
                    Artisan::call('migrate', ['--force' => true]);
                    $this->info('✅ Safe migrations completed');
                } catch (Exception $e) {
                    $this->warn('Migration warning: ' . $e->getMessage());
                }
            } else if ($this->option('force') || $this->isFirstTimeSetup()) {
                if ($this->option('force')) {
                    $this->warn('⚠️  FORCE FLAG DETECTED - This will destroy all existing data!');
                    $this->info('📦 Running fresh migrations with seeders...');
                } else {
                    $this->info('📦 First-time setup detected - running fresh migrations with seeders...');
                }
                Artisan::call('migrate:fresh', ['--seed' => true, '--force' => true]);
            } else {
                $this->info('🔄 Existing setup detected - running standard migrations...');
                Artisan::call('migrate', ['--force' => true]);
                
                // Only seed if no users exist
                try {
                    $userCount = DB::table('users')->count();
                    if ($userCount === 0) {
                        $this->info('🌱 No users found - running seeders...');
                        Artisan::call('db:seed', ['--force' => true]);
                    } else {
                        $this->info('👥 Users exist - skipping seeders to preserve data');
                    }
                } catch (Exception $e) {
                    $this->warn('Could not check user count: ' . $e->getMessage());
                }
            }

            // Setup Passport - ALWAYS ensure keys exist for API authentication
            $this->info('🔐 Setting up Laravel Passport keys...');
            try {
                $this->ensurePassportKeys();
                
                // Only create new client if no clients exist or if force flag is set
                if (!$skipDestructiveOps || $this->needsPassportClient()) {
                    Artisan::call('passport:client', ['--personal' => true, '--no-interaction' => true]);
                    $this->info('✅ Passport client created');
                } else {
                    $this->info('✅ Passport client already exists');
                }
            } catch (Exception $e) {
                $this->warn('Passport setup warning: ' . $e->getMessage());
            }

            // Setup PencilSpaces configuration
            $this->info('✏️ Setting up PencilSpaces configuration...');
            try {
                $this->setupPencilSpacesConfig();
                $this->setupPencilSpacesApiConfig();
            } catch (Exception $e) {
                $this->warn('PencilSpaces setup warning: ' . $e->getMessage());
            }

            // Fix failed_jobs table and clear encrypted job data
            $this->info('🔧 Fixing failed_jobs table and clearing problematic jobs...');
            try {
                if (!Schema::hasColumn('failed_jobs', 'uuid')) {
                    DB::statement('ALTER TABLE failed_jobs ADD COLUMN uuid VARCHAR(255) NULL');
                    $this->info('✅ Added uuid column to failed_jobs table');
                }
                
                // Clear all failed jobs (they may have encryption issues)
                DB::table('failed_jobs')->delete();
                $this->info('✅ Cleared failed jobs');
                
                // Clear jobs table if it exists (pending jobs with wrong key)
                if (Schema::hasTable('jobs')) {
                    DB::table('jobs')->delete();
                    $this->info('✅ Cleared pending jobs');
                }
                
                // Clear Redis queues to remove encrypted job data
                try {
                    $redis = app('redis')->connection();
                    $redis->flushdb();
                    $this->info('✅ Cleared Redis queues');
                } catch (Exception $e) {
                    $this->warn('Could not clear Redis queues: ' . $e->getMessage());
                }
                
            } catch (Exception $e) {
                $this->warn('Failed jobs fix failed: ' . $e->getMessage());
            }

            // Create storage link
            $this->info('🔗 Creating storage link...');
            try {
                Artisan::call('storage:link');
            } catch (Exception $e) {
                $this->warn('Storage link already exists or failed: ' . $e->getMessage());
            }

            // Clear configurations but don't cache for Railway
            $this->info('⚙️ Clearing configurations for Railway deployment...');
            Artisan::call('config:clear');
            Artisan::call('route:clear');
            Artisan::call('view:clear');
            Artisan::call('cache:clear');
            
            // Don't cache config/routes on Railway - causes container issues
            if (!$this->isRailwayEnvironment()) {
                Artisan::call('config:cache');
                Artisan::call('route:cache');
                $this->info('✅ Configurations cached');
            } else {
                $this->info('⚠️ Skipping config cache for Railway compatibility');
            }

            $this->info('✅ Database setup completed successfully!');
            $this->info('📚 API documentation available at: /api/documentation');
            $this->info('👤 Default admin: admin@escolalms.com / secret');

            return 0;

        } catch (Exception $e) {
            $this->error('❌ Setup failed: ' . $e->getMessage());
            $this->error('Stack trace: ' . $e->getTraceAsString());
            return 1;
        }
    }

    private function cleanupMigrationConflicts()
    {
        $migrationPath = database_path('migrations');
        $conflictingMigrations = [];

        // Find migrations with potentially conflicting class names
        $migrationFiles = glob($migrationPath . '/*.php');
        $classNames = [];

        // First pass: collect all class names
        foreach ($migrationFiles as $file) {
            $content = file_get_contents($file);
            if (preg_match('/class\s+(\w+)\s+extends\s+Migration/', $content, $matches)) {
                $className = $matches[1];
                if (!isset($classNames[$className])) {
                    $classNames[$className] = [];
                }
                $classNames[$className][] = $file;
            }
        }

        // Second pass: find duplicates
        foreach ($classNames as $className => $files) {
            if (count($files) > 1) {
                $this->warn("Found duplicate migration class: $className in " . count($files) . " files");
                // Keep the first one, remove the rest
                for ($i = 1; $i < count($files); $i++) {
                    $conflictingMigrations[] = $files[$i];
                    $this->warn("Will remove: " . basename($files[$i]));
                }
            }
        }

        // Remove conflicting migrations
        foreach ($conflictingMigrations as $conflictFile) {
            $this->warn("Removing conflicting migration: " . basename($conflictFile));
            unlink($conflictFile);
        }

        if (empty($conflictingMigrations)) {
            $this->info("No migration conflicts found.");
        } else {
            $this->info("Removed " . count($conflictingMigrations) . " conflicting migrations.");
        }
    }

    private function isFirstTimeSetup()
    {
        try {
            // Check if migrations table exists and has any records
            $migrationCount = DB::table('migrations')->count();
            
            // If no migrations have been run, it's first time setup
            if ($migrationCount === 0) {
                return true;
            }

            // Check if core tables exist - if not, it's essentially first time
            $coreTablesExist = Schema::hasTable('users') && 
                             Schema::hasTable('oauth_clients') && 
                             Schema::hasTable('model_fields_metadata');
            
            return !$coreTablesExist;
            
        } catch (Exception $e) {
            // If we can't query migrations table, it probably doesn't exist = first time
            return true;
        }
    }

    private function ensurePassportKeys()
    {
        // Check if keys exist from environment variables
        $envPrivateKey = env('PASSPORT_PRIVATE_KEY');
        $envPublicKey = env('PASSPORT_PUBLIC_KEY');
        
        if ($envPrivateKey && $envPublicKey) {
            $this->info('🔑 Using Passport keys from environment variables');
            
            // Validate the keys are valid
            $privateKeyContent = base64_decode($envPrivateKey, true) ?: $envPrivateKey;
            $publicKeyContent = base64_decode($envPublicKey, true) ?: $envPublicKey;
            
            if (strpos($privateKeyContent, '-----BEGIN PRIVATE KEY-----') !== false && 
                strpos($publicKeyContent, '-----BEGIN PUBLIC KEY-----') !== false) {
                $this->info('✅ Valid Passport keys found in environment variables');
                return;
            } else {
                $this->warn('⚠️ Environment variables contain invalid keys, regenerating...');
            }
        }
        
        // Generate new keys to temporary files
        $this->info('🔑 Generating new Passport keys...');
        $privateKeyPath = storage_path('oauth-private.key');
        $publicKeyPath = storage_path('oauth-public.key');
        
        Artisan::call('passport:keys', ['--force' => true]);
        
        // Read the generated keys and prepare for environment variables
        if (file_exists($privateKeyPath) && file_exists($publicKeyPath)) {
            $privateKey = file_get_contents($privateKeyPath);
            $publicKey = file_get_contents($publicKeyPath);
            
            $this->info('✅ New Passport keys generated');
            
            // Always show the keys for backup, then try to set automatically
            $privateKeyB64 = base64_encode($privateKey);
            $publicKeyB64 = base64_encode($publicKey);
            
            $this->warn('🔑 Generated Passport Keys (backup these):');
            $this->warn('PASSPORT_PRIVATE_KEY=' . $privateKeyB64);
            $this->warn('PASSPORT_PUBLIC_KEY=' . $publicKeyB64);
            
            // Try to set Railway environment variables automatically
            if ($this->isRailwayEnvironment()) {
                $this->info('🚂 Attempting to set Railway environment variables...');
                $this->setRailwayEnvVars($privateKey, $publicKey);
            } else {
                $this->warn('💡 Set these environment variables for persistent deployment:');
                $this->warn('PASSPORT_PRIVATE_KEY=' . $privateKeyB64);
                $this->warn('PASSPORT_PUBLIC_KEY=' . $publicKeyB64);
            }
            
            // Clean up temporary files - we use env vars, not files
            unlink($privateKeyPath);
            unlink($publicKeyPath);
            $this->info('🧹 Cleaned up temporary key files');
        }
    }

    private function needsPassportClient()
    {
        try {
            if (!Schema::hasTable('oauth_clients')) {
                return true;
            }
            
            $clientCount = DB::table('oauth_clients')->where('personal_access_client', true)->count();
            return $clientCount === 0;
        } catch (Exception $e) {
            return true;
        }
    }

    private function isRailwayEnvironment()
    {
        return !empty(env('RAILWAY_ENVIRONMENT')) || !empty(env('RAILWAY_PROJECT_ID'));
    }

    private function setRailwayEnvVars($privateKey, $publicKey)
    {
        $privateKeyB64 = base64_encode($privateKey);
        $publicKeyB64 = base64_encode($publicKey);
        
        // Try using Railway CLI if available
        $commands = [
            "railway variables set PASSPORT_PRIVATE_KEY=\"{$privateKeyB64}\"",
            "railway variables set PASSPORT_PUBLIC_KEY=\"{$publicKeyB64}\""
        ];
        
        foreach ($commands as $command) {
            $result = shell_exec($command . ' 2>&1');
            if (strpos($result, 'error') !== false || strpos($result, 'Error') !== false) {
                $this->warn("Failed to set env var via Railway CLI: {$result}");
                $this->warn('💡 Manually set these environment variables in Railway dashboard:');
                $this->warn("PASSPORT_PRIVATE_KEY={$privateKeyB64}");
                $this->warn("PASSPORT_PUBLIC_KEY={$publicKeyB64}");
                return false;
            }
        }
        
        $this->info('✅ Environment variables set successfully via Railway CLI');
        $this->warn('🔄 Redeploy required for changes to take effect');
        return true;
    }

    private function setupPencilSpacesConfig()
    {
        // Check if settings table exists (from escolalms/settings package)
        if (!Schema::hasTable('settings')) {
            $this->warn('Settings table not found - running migrations first...');
            return;
        }

        // Add PencilSpaces URL setting if it doesn't exist
        $pencilSpacesUrl = env('PENCIL_SPACES_URL', 'https://pencilspaces.com');
        
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
            $this->info("✅ Added PencilSpaces URL setting: {$pencilSpacesUrl}");
        } else {
            $this->info('✅ PencilSpaces URL setting already exists');
        }

        // Also add as backup in escolalms_settings table if it exists
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
            }
        }
    }

    private function setupPencilSpacesApiConfig()
    {
        // Check if settings table exists (from escolalms/settings package)
        if (!Schema::hasTable('settings')) {
            $this->warn('Settings table not found - skipping PencilSpaces API configuration');
            return;
        }

        // Set up required PencilSpaces API configuration
        $apiSettings = [
            'pencil_spaces_api_key' => env('PENCIL_SPACES_API_KEY', ''),
            'pencil_spaces_api_url' => env('PENCIL_SPACES_API_URL', 'https://api.pencilspaces.com'),
        ];

        foreach ($apiSettings as $key => $defaultValue) {
            $existingSetting = DB::table('settings')
                ->where('key', $key)
                ->first();

            if (!$existingSetting && $defaultValue !== '') {
                DB::table('settings')->insert([
                    'key' => $key,
                    'group' => 'pencil_spaces',
                    'value' => json_encode($defaultValue),
                    'type' => 'text',
                    'public' => false, // API keys should not be public
                    'enumerable' => false,
                    'readonly' => false,
                    'sort' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $this->info("✅ Added {$key} setting");
            } elseif ($defaultValue === '') {
                $this->warn("⚠️  {$key} environment variable not set - PencilSpaces API may not work");
                $this->warn("💡 Add {$key}=your-api-key to Railway environment variables");
            }
        }
    }
}