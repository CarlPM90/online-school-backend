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
                $this->warn('❗ Database already contains data. Use --force to reset.');
                return 0;
            }

            // Generate application key
            $this->info('🔑 Generating application key...');
            Artisan::call('key:generate', ['--force' => true]);

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
            if ($this->option('force') || $this->isFirstTimeSetup()) {
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

            // Setup Passport
            $this->info('🔐 Setting up Laravel Passport...');
            Artisan::call('passport:keys', ['--force' => true]);
            Artisan::call('passport:client', ['--personal' => true, '--no-interaction' => true]);

            // Create storage link
            $this->info('🔗 Creating storage link...');
            try {
                Artisan::call('storage:link');
            } catch (Exception $e) {
                $this->warn('Storage link already exists or failed: ' . $e->getMessage());
            }

            // Cache configurations
            $this->info('⚙️ Caching configurations...');
            Artisan::call('config:cache');

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
}