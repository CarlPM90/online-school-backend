<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Exception;

class SetupController extends Controller
{
    public function setupDatabase(Request $request)
    {
        // Security check - only allow setup if no users exist
        try {
            $userCount = DB::table('users')->count();
            if ($userCount > 0) {
                return response()->json([
                    'error' => 'Database already set up. Users table has data.',
                    'message' => 'Setup can only be run on empty database'
                ], 400);
            }
        } catch (Exception $e) {
            // Users table doesn't exist yet, which is what we expect
        }

        $output = [];
        $errors = [];

        try {
            $output[] = "🚀 Starting database setup...";

            // Test database connection
            $output[] = "🔍 Testing database connection...";
            DB::connection()->getPdo();
            $output[] = "✅ Database connection successful!";

            // Generate application key
            $output[] = "🔑 Generating application key...";
            Artisan::call('key:generate', ['--force' => true]);
            $output[] = "✅ Application key generated";

            // Publish migrations for core packages
            $output[] = "📝 Publishing package migrations...";
            
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

            foreach ($packages as $package) {
                try {
                    Artisan::call('vendor:publish', [
                        '--provider' => $package,
                        '--tag' => 'migrations',
                        '--force' => true
                    ]);
                    $output[] = "✅ Published migrations for " . class_basename($package);
                } catch (Exception $e) {
                    $errors[] = "❌ Failed to publish migrations for $package: " . $e->getMessage();
                }
            }

            // Run fresh migrations with seeders
            $output[] = "🗃️ Running fresh migrations with seeders...";
            Artisan::call('migrate:fresh', ['--seed' => true, '--force' => true]);
            $output[] = "✅ Database migrations completed";

            // Setup Passport
            $output[] = "🔐 Setting up Laravel Passport...";
            Artisan::call('passport:keys', ['--force' => true]);
            Artisan::call('passport:client', ['--personal' => true, '--no-interaction' => true]);
            $output[] = "✅ Passport setup completed";

            // Create storage link
            $output[] = "🔗 Creating storage link...";
            Artisan::call('storage:link');
            $output[] = "✅ Storage link created";

            // Cache configurations
            $output[] = "⚙️ Caching configurations...";
            Artisan::call('config:cache');
            $output[] = "✅ Configuration cached";

            $output[] = "🎉 Database setup completed successfully!";
            $output[] = "📚 API documentation available at: /api/documentation";
            $output[] = "👤 Default admin: admin@escolalms.com / secret";

            return response()->json([
                'success' => true,
                'message' => 'Database setup completed successfully!',
                'output' => $output,
                'errors' => $errors
            ]);

        } catch (Exception $e) {
            $errors[] = "❌ Setup failed: " . $e->getMessage();
            
            return response()->json([
                'success' => false,
                'message' => 'Database setup failed',
                'output' => $output,
                'errors' => $errors,
                'exception' => $e->getMessage()
            ], 500);
        }
    }
}