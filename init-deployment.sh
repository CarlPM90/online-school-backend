#!/bin/bash

echo "🚀 Initializing Escola LMS deployment..."

# Wait for database to be ready
echo "⏳ Waiting for database connection..."
max_attempts=30
attempt=1

while [ $attempt -le $max_attempts ]; do
    if php artisan migrate:status > /dev/null 2>&1; then
        echo "✅ Database connection established!"
        break
    else
        echo "Database not ready, attempt $attempt/$max_attempts, waiting 2 seconds..."
        sleep 2
        attempt=$((attempt + 1))
    fi
done

if [ $attempt -gt $max_attempts ]; then
    echo "❌ Database connection failed after $max_attempts attempts"
    exit 1
fi

# Check if migrations table exists and has content
migration_status=$(php artisan migrate:status 2>/dev/null | grep -c "Y" || echo "0")

if [ "$FORCE_FRESH_MIGRATION" = "true" ] || [ "$migration_status" -eq "0" ]; then
    echo "📦 Fresh database detected or forced - setting up from scratch..."
    
    # Publish all package migrations
    echo "📝 Publishing package migrations..."
    
    # Core packages that need migrations published
    php artisan vendor:publish --provider="EscolaLms\Auth\AuthServiceProvider" --tag="migrations" --force 2>/dev/null || echo "Auth migrations already published"
    php artisan vendor:publish --provider="EscolaLms\Core\CoreServiceProvider" --tag="migrations" --force 2>/dev/null || echo "Core migrations already published"
    php artisan vendor:publish --provider="EscolaLms\Categories\CategoriesServiceProvider" --tag="migrations" --force 2>/dev/null || echo "Categories migrations already published"
    php artisan vendor:publish --provider="EscolaLms\Courses\CoursesServiceProvider" --tag="migrations" --force 2>/dev/null || echo "Courses migrations already published"
    php artisan vendor:publish --provider="EscolaLms\Settings\SettingsServiceProvider" --tag="migrations" --force 2>/dev/null || echo "Settings migrations already published"
    php artisan vendor:publish --provider="EscolaLms\Files\FilesServiceProvider" --tag="migrations" --force 2>/dev/null || echo "Files migrations already published"
    php artisan vendor:publish --provider="EscolaLms\Tags\TagsServiceProvider" --tag="migrations" --force 2>/dev/null || echo "Tags migrations already published"
    php artisan vendor:publish --provider="EscolaLms\Permissions\PermissionsServiceProvider" --tag="migrations" --force 2>/dev/null || echo "Permissions migrations already published"
    php artisan vendor:publish --provider="EscolaLms\Templates\TemplatesServiceProvider" --tag="migrations" --force 2>/dev/null || echo "Templates migrations already published"
    php artisan vendor:publish --provider="EscolaLms\Cart\CartServiceProvider" --tag="migrations" --force 2>/dev/null || echo "Cart migrations already published"
    php artisan vendor:publish --provider="EscolaLms\Payments\PaymentsServiceProvider" --tag="migrations" --force 2>/dev/null || echo "Payments migrations already published"
    php artisan vendor:publish --provider="EscolaLms\ModelFields\ModelFieldsServiceProvider" --tag="migrations" --force 2>/dev/null || echo "ModelFields migrations already published"
    php artisan vendor:publish --provider="EscolaLms\Consultations\ConsultationsServiceProvider" --tag="migrations" --force 2>/dev/null || echo "Consultations migrations already published"
    php artisan vendor:publish --provider="EscolaLms\StationaryEvents\StationaryEventServiceProvider" --tag="migrations" --force 2>/dev/null || echo "StationaryEvents migrations already published"
    php artisan vendor:publish --provider="EscolaLms\Webinar\WebinarServiceProvider" --tag="migrations" --force 2>/dev/null || echo "Webinar migrations already published"
    php artisan vendor:publish --provider="EscolaLms\Questionnaire\QuestionnaireServiceProvider" --tag="migrations" --force 2>/dev/null || echo "Questionnaire migrations already published"
    php artisan vendor:publish --provider="EscolaLms\Reports\ReportsServiceProvider" --tag="migrations" --force 2>/dev/null || echo "Reports migrations already published"
    php artisan vendor:publish --provider="EscolaLms\Scorm\ScormServiceProvider" --tag="migrations" --force 2>/dev/null || echo "Scorm migrations already published"
    php artisan vendor:publish --provider="EscolaLms\HeadlessH5P\HeadlessH5PServiceProvider" --tag="migrations" --force 2>/dev/null || echo "HeadlessH5P migrations already published"
    php artisan vendor:publish --provider="EscolaLms\Notifications\NotificationsServiceProvider" --tag="migrations" --force 2>/dev/null || echo "Notifications migrations already published"
    php artisan vendor:publish --provider="EscolaLms\Images\ImagesServiceProvider" --tag="migrations" --force 2>/dev/null || echo "Images migrations already published"
    php artisan vendor:publish --provider="EscolaLms\Pages\PagesServiceProvider" --tag="migrations" --force 2>/dev/null || echo "Pages migrations already published"
    php artisan vendor:publish --provider="EscolaLms\Video\VideoServiceProvider" --tag="migrations" --force 2>/dev/null || echo "Video migrations already published"
    php artisan vendor:publish --provider="EscolaLms\TopicTypes\TopicTypesServiceProvider" --tag="migrations" --force 2>/dev/null || echo "TopicTypes migrations already published"
    php artisan vendor:publish --provider="EscolaLms\Tracker\TrackerServiceProvider" --tag="migrations" --force 2>/dev/null || echo "Tracker migrations already published"
    php artisan vendor:publish --provider="EscolaLms\Vouchers\VouchersServiceProvider" --tag="migrations" --force 2>/dev/null || echo "Vouchers migrations already published"
    php artisan vendor:publish --provider="EscolaLms\BookmarksNotes\BookmarksNotesServiceProvider" --tag="migrations" --force 2>/dev/null || echo "BookmarksNotes migrations already published"
    php artisan vendor:publish --provider="EscolaLms\Tasks\TasksServiceProvider" --tag="migrations" --force 2>/dev/null || echo "Tasks migrations already published"
    php artisan vendor:publish --provider="EscolaLms\Invoices\InvoicesServiceProvider" --tag="migrations" --force 2>/dev/null || echo "Invoices migrations already published"
    php artisan vendor:publish --provider="EscolaLms\TemplatesEmail\TemplatesEmailServiceProvider" --tag="migrations" --force 2>/dev/null || echo "TemplatesEmail migrations already published"
    php artisan vendor:publish --provider="EscolaLms\TemplatesPdf\TemplatesPdfServiceProvider" --tag="migrations" --force 2>/dev/null || echo "TemplatesPdf migrations already published"
    php artisan vendor:publish --provider="EscolaLms\TemplatesSms\TemplatesSmsServiceProvider" --tag="migrations" --force 2>/dev/null || echo "TemplatesSms migrations already published"
    php artisan vendor:publish --provider="EscolaLms\Translations\TranslationsServiceProvider" --tag="migrations" --force 2>/dev/null || echo "Translations migrations already published"
    php artisan vendor:publish --provider="EscolaLms\CsvUsers\CsvUsersServiceProvider" --tag="migrations" --force 2>/dev/null || echo "CsvUsers migrations already published"
    php artisan vendor:publish --provider="EscolaLms\CoursesImportExport\CoursesImportExportServiceProvider" --tag="migrations" --force 2>/dev/null || echo "CoursesImportExport migrations already published"
    php artisan vendor:publish --provider="EscolaLms\CourseAccess\CourseAccessServiceProvider" --tag="migrations" --force 2>/dev/null || echo "CourseAccess migrations already published"
    php artisan vendor:publish --provider="EscolaLms\ConsultationAccess\ConsultationAccessServiceProvider" --tag="migrations" --force 2>/dev/null || echo "ConsultationAccess migrations already published"
    php artisan vendor:publish --provider="EscolaLms\AssignWithoutAccount\AssignWithoutAccountServiceProvider" --tag="migrations" --force 2>/dev/null || echo "AssignWithoutAccount migrations already published"
    php artisan vendor:publish --provider="EscolaLms\TopicTypeGift\TopicTypeGiftServiceProvider" --tag="migrations" --force 2>/dev/null || echo "TopicTypeGift migrations already published"
    php artisan vendor:publish --provider="EscolaLms\TopicTypeProject\TopicTypeProjectServiceProvider" --tag="migrations" --force 2>/dev/null || echo "TopicTypeProject migrations already published"
    php artisan vendor:publish --provider="EscolaLms\Cmi5\Cmi5ServiceProvider" --tag="migrations" --force 2>/dev/null || echo "Cmi5 migrations already published"
    php artisan vendor:publish --provider="EscolaLms\Lrs\LrsServiceProvider" --tag="migrations" --force 2>/dev/null || echo "Lrs migrations already published"
    php artisan vendor:publish --provider="EscolaLms\Mailerlite\MailerliteServiceProvider" --tag="migrations" --force 2>/dev/null || echo "Mailerlite migrations already published"
    php artisan vendor:publish --provider="EscolaLms\Mattermost\MattermostServiceProvider" --tag="migrations" --force 2>/dev/null || echo "Mattermost migrations already published"
    php artisan vendor:publish --provider="EscolaLms\PencilSpaces\PencilSpacesServiceProvider" --tag="migrations" --force 2>/dev/null || echo "PencilSpaces migrations already published"
    
    # Also publish Laravel Passport migrations
    php artisan vendor:publish --provider="Laravel\Passport\PassportServiceProvider" --tag="migrations" --force 2>/dev/null || echo "Passport migrations already published"
    
    # Run fresh migrations with seed data
    echo "🔄 Running fresh migrations with seeders..."
    php artisan migrate:fresh --seed --force
    
    # Install Passport keys
    echo "🔐 Installing Passport keys..."
    php artisan passport:keys --force
    
    # Create Passport client
    echo "👤 Creating Passport client..."
    php artisan passport:client --personal --no-interaction
    
else
    echo "🔄 Existing database detected, running standard migrations..."
    php artisan migrate --force
fi

# Create storage link
echo "🔗 Creating storage link..."
php artisan storage:link

# Cache configurations
echo "⚙️ Caching configurations..."
php artisan config:cache

echo "✅ Deployment initialization completed!"