# Database Safety Guide

## ⚠️ IMPORTANT: Production Data Safety

The `php artisan db:setup` command is now **DATA-SAFE** by default but has different behaviors:

### Current Deployment (First Time Setup)
- **Command**: `php artisan db:setup --force`
- **Behavior**: Drops all tables, creates fresh database with seed data
- **Safe for**: Initial deployment (no user data exists)

### Future Deployments (With User Data)
- **Command**: `php artisan db:setup` (no --force flag)
- **Behavior**: 
  - Only runs new migrations (`migrate`)
  - Preserves all existing user data
  - Skips seeders if users exist
- **Safe for**: Production updates with real user data

## Railway Pre-Deploy Configuration

### For Initial Setup (Current)
```bash
php artisan db:setup --force
```

### For Production Updates (Future)
**CHANGE YOUR RAILWAY PRE-DEPLOY COMMAND TO:**
```bash
php artisan db:setup
```

## Command Behaviors

### `php artisan db:setup` (Safe Mode)
✅ **Safe for production**
- Detects if database has existing data
- Runs `migrate` (adds new tables/columns only)
- Preserves all user data, courses, consultations, etc.
- Only seeds if no users exist

### `php artisan db:setup --force` (Destructive Mode)
❌ **DESTROYS ALL DATA**
- Always runs `migrate:fresh` (drops everything)
- Recreates all tables from scratch
- Seeds fresh data
- **Only use for:**
  - Initial setup
  - Development/testing
  - Complete reset scenarios

## What Each Mode Does

| Feature | Safe Mode | Force Mode |
|---------|-----------|------------|
| Preserves user data | ✅ Yes | ❌ No |
| Preserves courses | ✅ Yes | ❌ No |
| Preserves orders | ✅ Yes | ❌ No |
| Adds new tables | ✅ Yes | ✅ Yes |
| Adds new columns | ✅ Yes | ✅ Yes |
| Seeds initial data | Only if empty | ✅ Always |

## Production Migration Strategy

When you're ready for production (after initial setup):

1. **Update Railway pre-deploy command** to remove `--force`:
   ```bash
   php artisan db:setup
   ```

2. **Deploy new features safely:**
   - New package migrations will be published
   - New tables/columns will be added
   - Existing data will be preserved

3. **For emergency reset only:**
   - Temporarily add `--force` flag
   - **This will destroy all user data**
   - Use with extreme caution

## Current Status

**Your current setup uses `--force` flag which is correct for initial deployment.**

**After successful initial setup, remember to remove `--force` from your Railway pre-deploy command to protect user data in future deployments.**