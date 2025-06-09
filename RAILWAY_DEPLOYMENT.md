# Railway Deployment Guide for Escola LMS

This guide explains how to properly deploy the Escola LMS backend to Railway with all required database tables.

## The Problem

The Escola LMS application uses many packages that require their own database tables. When deploying to Railway, these package migrations are not automatically published, leading to errors like:

```
ERROR: relation "model_fields_metadata" does not exist
```

## Solution

Use the provided deployment script that publishes all package migrations before running them.

## Deployment Steps

### 1. Railway Configuration

In your Railway project settings:

1. **Build Command**: Leave empty (Railway will auto-detect)
2. **Start Command**: `./railway-deploy.sh && php artisan serve --host=0.0.0.0 --port=$PORT`
3. **Environment Variables**:
   ```
   APP_ENV=production
   APP_DEBUG=false
   APP_KEY=[generate with php artisan key:generate]
   DB_CONNECTION=pgsql
   DB_HOST=[your railway postgres host]
   DB_PORT=[your railway postgres port]
   DB_DATABASE=[your railway postgres database]
   DB_USERNAME=[your railway postgres username]
   DB_PASSWORD=[your railway postgres password]
   ```

### 2. Database Setup

1. Create a PostgreSQL database in Railway
2. Copy the connection details to your environment variables
3. The deployment script will handle all table creation

### 3. Deploy

1. Push your code to your connected repository
2. Railway will automatically trigger a deployment
3. The `railway-deploy.sh` script will:
   - Install dependencies
   - Publish all package migrations
   - Run migrations to create all tables
   - Set up Passport authentication
   - Create necessary storage links

## What the Deployment Script Does

The `railway-deploy.sh` script addresses the missing tables issue by:

1. **Installing Dependencies**: Runs `composer install` with production optimizations
2. **Publishing Migrations**: Publishes migrations from all 30+ Escola LMS packages
3. **Running Migrations**: Creates all required database tables
4. **Setting up Authentication**: Configures Laravel Passport
5. **Optimizing**: Caches configurations for better performance

## Package Migrations Published

The script publishes migrations for these key packages:
- `escolalms/model-fields` (fixes the model_fields_metadata error)
- `escolalms/auth` (user authentication)
- `escolalms/core` (core functionality)
- `escolalms/courses` (course management)
- `escolalms/consultations` (consultation booking)
- `escolalms/webinar` (webinar functionality)
- And 25+ other packages

## Troubleshooting

### If deployment fails:

1. **Check Railway logs** for specific error messages
2. **Verify environment variables** are correctly set
3. **Ensure PostgreSQL database** is properly connected
4. **Check script permissions**: The script should be executable

### If you get "table does not exist" errors:

1. The deployment script should have run successfully
2. Check if all migrations were published and run
3. You may need to run migrations manually:
   ```bash
   php artisan migrate --force
   ```

### Manual Migration Publishing

If you need to publish specific package migrations manually:

```bash
# For model-fields package specifically
php artisan vendor:publish --provider="EscolaLms\ModelFields\ModelFieldsServiceProvider" --tag="migrations" --force

# Then run migrations
php artisan migrate --force
```

## Post-Deployment

After successful deployment:

1. **Test the API endpoints** to ensure everything works
2. **Check the Swagger documentation** at `/api/documentation`
3. **Verify database tables** are created in your PostgreSQL instance
4. **Test authentication** endpoints

## Environment Variables Reference

Required environment variables for Railway:

```env
# Application
APP_NAME="Escola LMS"
APP_ENV=production
APP_KEY=base64:your-generated-key
APP_DEBUG=false
APP_URL=https://your-railway-domain.railway.app

# Database (PostgreSQL)
DB_CONNECTION=pgsql
DB_HOST=your-postgres-host
DB_PORT=5432
DB_DATABASE=railway
DB_USERNAME=postgres
DB_PASSWORD=your-postgres-password

# Cache & Queue
CACHE_DRIVER=file
QUEUE_CONNECTION=sync
SESSION_DRIVER=file

# Mail (configure as needed)
MAIL_MAILER=smtp
```

## Support

If you encounter issues:
1. Check Railway deployment logs
2. Verify all environment variables
3. Ensure the deployment script has proper permissions
4. Check that your PostgreSQL database is accessible