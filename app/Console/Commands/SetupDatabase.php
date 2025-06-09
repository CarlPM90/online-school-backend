<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Exception;

class SetupDatabase extends Command
{
    protected $signature = 'db:setup {--force : Force fresh migration even if data exists}';
    protected $description = 'Set up the database with all package migrations and seeders';

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

            // Run fresh migrations with seeders
            $this->info('🗃️ Running fresh migrations with seeders...');
            Artisan::call('migrate:fresh', ['--seed' => true, '--force' => true]);

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
}