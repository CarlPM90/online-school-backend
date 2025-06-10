# PencilSpaces API Configuration

## Issue
The `/api/pencil-spaces/login` endpoint is failing with 500 error because **no PencilSpaces API key is configured**.

## Solution: Get Your PencilSpaces API Key

### Step 1: Get API Key from PencilSpaces
1. **Login to PencilSpaces** at https://my.pencilapp.com/
2. **Go to your profile/settings**
3. **Find API key section** (you need InstitutionAdmin access)
4. **Copy your API key**

### Step 2: Add to Railway Environment Variables
In your Railway dashboard, add these environment variables:

```bash
PENCIL_SPACES_API_KEY=your-actual-api-key-here
PENCIL_SPACES_API_URL=https://api.pencilspaces.com
```

### Step 3: Run Setup Command
After adding the environment variables, redeploy or run:
```bash
php artisan setup:database --skip-migrations
```

## What This Will Fix

✅ **Authentication Flow**: `/api/pencil-spaces/login` will work  
✅ **Direct Login URLs**: Users can access their PencilSpaces directly  
✅ **Frontend Integration**: Pencil icon will open PencilSpaces  

## Current Status

❌ **Missing**: `PENCIL_SPACES_API_KEY` environment variable  
✅ **Working**: PencilSpaces URL configuration  
✅ **Working**: Frontend settings API  

## Testing After Setup

1. **Check configuration**:
   ```
   https://web-production-82cf.up.railway.app/debug-pencilspaces.php
   ```

2. **Test authenticated request**:
   ```javascript
   fetch('/api/pencil-spaces/login', {
     headers: { 'Authorization': 'Bearer ' + token }
   })
   ```

## Package Documentation

The EscolaLMS PencilSpaces package (v0.0.2) requires:
- `pencil_spaces_api_key` setting
- `pencil_spaces_api_url` setting  
- User authentication for login endpoint