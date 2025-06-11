# Authentication & Route Registration Fix

## Root Cause
The authentication errors (401/403) are caused by **missing route registration** for EscolaLMS packages. Routes are being cached before packages are fully discovered.

## Changes Made

### 1. Updated `railway-startup.sh`
- Added `php artisan package:discover --ansi` before route caching
- This ensures all EscolaLMS package routes are registered before caching

### 2. Updated `railway-build.sh` 
- Added package discovery before production caching
- Ensures consistent route registration across build and runtime

### 3. Fixed PencilSpaces API URL
The missing environment variable needs to be added to Railway:

```bash
PENCIL_SPACES_API_URL=https://prod-api.pencilapp.com
```

## Missing Routes Should Now Be Available

After deployment restart, these routes should work:
- `GET/POST /api/login` 
- `POST /api/register`
- `POST /api/auth/refresh`
- `GET /api/profile/me`
- `GET /api/courses/progress`
- `GET /api/notifications`

## Deployment Steps

1. **Add Environment Variable** to Railway:
   ```
   PENCIL_SPACES_API_URL=https://prod-api.pencilapp.com
   ```

2. **Redeploy** - The updated startup script will:
   - Clear old caches
   - Rediscover packages
   - Register all EscolaLMS routes
   - Cache routes properly

3. **Test Authentication** - Frontend should now be able to:
   - Log in users
   - Refresh tokens  
   - Access protected routes

## Why This Fixes the Issues

- **401/403 Errors**: These were actually 404s (routes didn't exist) misinterpreted by frontend
- **Missing Routes**: Package discovery ensures EscolaLMS auth/course/notification routes are registered
- **PencilSpaces 500s**: Correct API URL allows backend to call PencilSpaces API

The authentication system was properly configured - just the routes weren't being registered due to caching happening too early in the deployment process.