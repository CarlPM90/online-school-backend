#!/bin/bash

echo "🚀 Railway Build Script for Escola LMS..."

# Install dependencies
echo "📦 Installing Composer dependencies..."
composer install --no-dev --optimize-autoloader

# Generate application key if not exists
echo "🔑 Generating application key..."
php artisan key:generate --force

# Publish all package migrations
echo "📝 Publishing package migrations..."

# Core packages that need migrations published
php artisan vendor:publish --provider="EscolaLms\Auth\AuthServiceProvider" --tag="migrations" --force
php artisan vendor:publish --provider="EscolaLms\Core\CoreServiceProvider" --tag="migrations" --force
php artisan vendor:publish --provider="EscolaLms\Categories\CategoriesServiceProvider" --tag="migrations" --force
php artisan vendor:publish --provider="EscolaLms\Courses\CoursesServiceProvider" --tag="migrations" --force
php artisan vendor:publish --provider="EscolaLms\Settings\SettingsServiceProvider" --tag="migrations" --force
php artisan vendor:publish --provider="EscolaLms\Files\FilesServiceProvider" --tag="migrations" --force
php artisan vendor:publish --provider="EscolaLms\Tags\TagsServiceProvider" --tag="migrations" --force
php artisan vendor:publish --provider="EscolaLms\Permissions\PermissionsServiceProvider" --tag="migrations" --force
php artisan vendor:publish --provider="EscolaLms\Templates\TemplatesServiceProvider" --tag="migrations" --force
php artisan vendor:publish --provider="EscolaLms\Cart\CartServiceProvider" --tag="migrations" --force
php artisan vendor:publish --provider="EscolaLms\Payments\PaymentsServiceProvider" --tag="migrations" --force
php artisan vendor:publish --provider="EscolaLms\ModelFields\ModelFieldsServiceProvider" --tag="migrations" --force
php artisan vendor:publish --provider="EscolaLms\Consultations\ConsultationsServiceProvider" --tag="migrations" --force
php artisan vendor:publish --provider="EscolaLms\StationaryEvents\StationaryEventServiceProvider" --tag="migrations" --force
php artisan vendor:publish --provider="EscolaLms\Webinar\WebinarServiceProvider" --tag="migrations" --force
php artisan vendor:publish --provider="EscolaLms\Questionnaire\QuestionnaireServiceProvider" --tag="migrations" --force
php artisan vendor:publish --provider="EscolaLms\Reports\ReportsServiceProvider" --tag="migrations" --force
php artisan vendor:publish --provider="EscolaLms\Scorm\ScormServiceProvider" --tag="migrations" --force
php artisan vendor:publish --provider="EscolaLms\HeadlessH5P\HeadlessH5PServiceProvider" --tag="migrations" --force
php artisan vendor:publish --provider="EscolaLms\Notifications\NotificationsServiceProvider" --tag="migrations" --force
php artisan vendor:publish --provider="EscolaLms\Images\ImagesServiceProvider" --tag="migrations" --force
php artisan vendor:publish --provider="EscolaLms\Pages\PagesServiceProvider" --tag="migrations" --force
php artisan vendor:publish --provider="EscolaLms\Video\VideoServiceProvider" --tag="migrations" --force
php artisan vendor:publish --provider="EscolaLms\TopicTypes\TopicTypesServiceProvider" --tag="migrations" --force
php artisan vendor:publish --provider="EscolaLms\Tracker\TrackerServiceProvider" --tag="migrations" --force
php artisan vendor:publish --provider="EscolaLms\Vouchers\VouchersServiceProvider" --tag="migrations" --force
php artisan vendor:publish --provider="EscolaLms\BookmarksNotes\BookmarksNotesServiceProvider" --tag="migrations" --force
php artisan vendor:publish --provider="EscolaLms\Tasks\TasksServiceProvider" --tag="migrations" --force
php artisan vendor:publish --provider="EscolaLms\Invoices\InvoicesServiceProvider" --tag="migrations" --force
php artisan vendor:publish --provider="EscolaLms\TemplatesEmail\TemplatesEmailServiceProvider" --tag="migrations" --force
php artisan vendor:publish --provider="EscolaLms\TemplatesPdf\TemplatesPdfServiceProvider" --tag="migrations" --force
php artisan vendor:publish --provider="EscolaLms\TemplatesSms\TemplatesSmsServiceProvider" --tag="migrations" --force
php artisan vendor:publish --provider="EscolaLms\Translations\TranslationsServiceProvider" --tag="migrations" --force
php artisan vendor:publish --provider="EscolaLms\CsvUsers\CsvUsersServiceProvider" --tag="migrations" --force
php artisan vendor:publish --provider="EscolaLms\CoursesImportExport\CoursesImportExportServiceProvider" --tag="migrations" --force
php artisan vendor:publish --provider="EscolaLms\CourseAccess\CourseAccessServiceProvider" --tag="migrations" --force
php artisan vendor:publish --provider="EscolaLms\ConsultationAccess\ConsultationAccessServiceProvider" --tag="migrations" --force
php artisan vendor:publish --provider="EscolaLms\AssignWithoutAccount\AssignWithoutAccountServiceProvider" --tag="migrations" --force
php artisan vendor:publish --provider="EscolaLms\TopicTypeGift\TopicTypeGiftServiceProvider" --tag="migrations" --force
php artisan vendor:publish --provider="EscolaLms\TopicTypeProject\TopicTypeProjectServiceProvider" --tag="migrations" --force
php artisan vendor:publish --provider="EscolaLms\Cmi5\Cmi5ServiceProvider" --tag="migrations" --force
php artisan vendor:publish --provider="EscolaLms\Lrs\LrsServiceProvider" --tag="migrations" --force
php artisan vendor:publish --provider="EscolaLms\Mailerlite\MailerliteServiceProvider" --tag="migrations" --force
php artisan vendor:publish --provider="EscolaLms\Mattermost\MattermostServiceProvider" --tag="migrations" --force
php artisan vendor:publish --provider="EscolaLms\PencilSpaces\PencilSpacesServiceProvider" --tag="migrations" --force

# Also publish Laravel Passport migrations
php artisan vendor:publish --provider="Laravel\Passport\PassportServiceProvider" --tag="migrations" --force

# Publish seeders if needed
echo "🌱 Publishing seeders..."
php artisan vendor:publish --provider="EscolaLms\Auth\AuthServiceProvider" --tag="seeders" --force
php artisan vendor:publish --provider="EscolaLms\Permissions\PermissionsServiceProvider" --tag="seeders" --force
php artisan vendor:publish --provider="EscolaLms\HeadlessH5P\HeadlessH5PServiceProvider" --tag="seeders" --force

# Check if database is empty and run fresh migrations if needed
echo "🗃️ Setting up database..."
if [ "$FORCE_FRESH_MIGRATION" = "true" ] || ! php artisan migrate:status > /dev/null 2>&1; then
    echo "📦 Fresh database setup requested, running migrate:fresh with seeders..."
    php artisan migrate:fresh --seed --force
else
    echo "🔄 Existing database detected, running standard migrations..."
    php artisan migrate --force
fi

# Install Passport keys
echo "🔐 Installing Passport keys..."
php artisan passport:keys --force

# Create Passport client
echo "👤 Creating Passport client..."
php artisan passport:client --personal --no-interaction

# Ensure package discovery happens before caching
echo "📦 Discovering packages before caching..."
php artisan package:discover --ansi

# Cache configuration for production
echo "⚙️ Caching configuration for production..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Create storage link
echo "🔗 Creating storage link..."
php artisan storage:link

echo "✅ Railway build completed successfully!"