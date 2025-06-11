#!/usr/bin/env bash

echo "🔧 Fixing Authentication System for Railway Deployment..."

# Clear ALL caches to ensure clean state
echo "🧹 Clearing all caches..."
php artisan config:clear 2>/dev/null || echo "Config clear failed (OK if Laravel broken)"
php artisan route:clear 2>/dev/null || echo "Route clear failed (OK if Laravel broken)"
php artisan view:clear 2>/dev/null || echo "View clear failed (OK if Laravel broken)" 
php artisan cache:clear 2>/dev/null || echo "Cache clear failed (OK if Laravel broken)"

# Remove bootstrap cache files
echo "🗑️ Removing bootstrap cache files..."
rm -f bootstrap/cache/config.php
rm -f bootstrap/cache/routes.php
rm -f bootstrap/cache/services.php
rm -f bootstrap/cache/packages.php
rm -f bootstrap/cache/compiled.php

# Force package discovery to ensure EscolaLMS packages are registered
echo "📦 Re-discovering packages..."
php artisan package:discover --ansi

# Check if Passport is properly installed
echo "🔐 Checking Passport installation..."
PASSPORT_CLIENTS=$(php artisan tinker --execute="echo DB::table('oauth_clients')->count();")
if [ "$PASSPORT_CLIENTS" = "0" ]; then
    echo "🔑 Installing Passport OAuth clients..."
    php artisan passport:install --force
else
    echo "✅ Passport clients already exist ($PASSPORT_CLIENTS clients)"
fi

# Ensure Passport keys exist
echo "🗝️ Ensuring Passport keys exist..."
php artisan passport:keys --force

# DON'T cache routes yet - let Laravel auto-discover them first
echo "⚠️ Skipping route cache to allow dynamic route discovery"

# Test critical routes
echo "🧪 Testing critical API routes..."
echo "Available routes:"
php artisan route:list --name=api 2>/dev/null | head -10 || echo "Route list failed"

echo "✅ Authentication system fix completed!"
echo "🔄 A deployment restart may be required for changes to take effect."