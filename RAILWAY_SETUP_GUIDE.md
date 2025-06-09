# Railway Database Setup Guide

Since Railway doesn't provide shell access, here are two methods to set up your database:

## Method 1: Web Endpoint (Recommended)

### Step 1: Deploy the Application
1. Push these changes to your repository
2. Wait for Railway deployment to complete
3. The app will start (with database errors - this is expected)

### Step 2: Run Database Setup via Web
1. **Open your Railway app URL** in a browser
2. **Add `/setup` to the URL**  
   Example: `https://your-app-name.railway.app/setup`
3. **Click "Start Database Setup"** button
4. **Wait for completion** - you'll see real-time progress

### Step 3: Verify Success
- The setup page will show "Setup Complete!" when done
- Visit `/api/documentation` to see the API docs
- Test login with: `admin@escolalms.com` / `secret`

---

## Method 2: Local Setup via Public Network

If the web endpoint doesn't work, use your PostgreSQL public connection:

### Step 1: Enable PostgreSQL Public Networking
In Railway dashboard:
1. Go to your **PostgreSQL service**
2. Enable **Public Networking** 
3. Note the connection details: `switchback.proxy.rlwy.net:38938`

### Step 2: Setup Local Environment
Create a temporary `.env` file locally with public database credentials:

```env
APP_NAME="Escola LMS"
APP_ENV=production
APP_KEY=base64:your-app-key
APP_DEBUG=false

# Use Railway's public PostgreSQL connection
DB_CONNECTION=pgsql
DB_HOST=switchback.proxy.rlwy.net
DB_PORT=38938
DB_DATABASE=railway
DB_USERNAME=postgres
DB_PASSWORD=your-postgres-password

CACHE_DRIVER=file
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
```

### Step 3: Run Local Setup
```bash
# Install dependencies (if not already done)
composer install

# Run the database setup script
./setup-database.sh
```

### Step 4: Disable Public Access
After setup is complete:
1. **Disable Public Networking** on PostgreSQL service
2. **Delete the temporary local `.env`** file

---

## What the Setup Does

Both methods will:

1. ✅ **Test database connection**
2. 📝 **Publish migrations** from 40+ Escola LMS packages including:
   - `escolalms/model-fields` (fixes model_fields_metadata error)
   - `escolalms/auth` (user authentication)
   - `escolalms/courses` (course management)
   - `escolalms/consultations` (booking system)
   - And many more...
3. 🗃️ **Create all database tables** (migrate:fresh --seed)
4. 🔐 **Setup Laravel Passport** (API authentication)
5. 🌱 **Seed initial data** (admin user, permissions, etc.)
6. 🔗 **Create storage links**
7. ⚙️ **Cache configurations**

## Post-Setup

After successful setup:

- **API Documentation**: `/api/documentation`
- **Admin Login**: `admin@escolalms.com` / `secret`
- **Student Login**: `student@escolalms.com` / `secret`
- **Tutor Login**: `tutor@escolalms.com` / `secret`

## Troubleshooting

### If Web Setup Fails:
1. Check Railway logs for specific errors
2. Try Method 2 (local setup)
3. Ensure all environment variables are set correctly

### If Local Setup Fails:
1. Verify public network connection details
2. Check that you can connect to PostgreSQL from your machine
3. Ensure local PHP and Composer are working

### Database Already Setup Error:
If you see "Database already set up", the setup has already been completed successfully. The setup endpoint only runs on empty databases for security.

## Security Notes

- The setup endpoint only works on empty databases
- After setup, consider adding authentication to these routes
- The local setup method requires temporary public database access
- Always disable public networking after setup is complete

## Need Help?

If you encounter issues:
1. Check Railway deployment logs
2. Verify environment variables are correctly set
3. Ensure PostgreSQL service is running and accessible
4. Test basic database connectivity first