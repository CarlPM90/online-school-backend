#!/bin/bash

# Service Readiness Verification for Railway
echo "🔍 Verifying service readiness..."

# Function to check if PHP-FPM is ready
check_php_fpm() {
    if pgrep -f "php-fpm: master process" > /dev/null; then
        echo "✅ PHP-FPM master process is running"
        return 0
    else
        echo "❌ PHP-FPM master process not found"
        return 1
    fi
}

# Function to check if Caddy is ready  
check_caddy() {
    if pgrep -f "caddy run" > /dev/null; then
        echo "✅ Caddy process is running"
        return 0
    else
        echo "❌ Caddy process not found"
        return 1
    fi
}

# Function to check if Laravel app responds
check_laravel() {
    local response
    response=$(curl -s -o /dev/null -w "%{http_code}" http://127.0.0.1:80/up 2>/dev/null)
    if [ "$response" = "200" ]; then
        echo "✅ Laravel /up endpoint responding with 200"
        return 0
    else
        echo "❌ Laravel /up endpoint returned: $response"
        return 1
    fi
}

# Wait for services to be ready
echo "⏳ Waiting for PHP-FPM to be ready..."
for i in {1..30}; do
    if check_php_fpm; then
        break
    fi
    if [ $i -eq 30 ]; then
        echo "💥 PHP-FPM failed to start after 30 seconds"
        exit 1
    fi
    sleep 1
done

echo "⏳ Waiting for Caddy to be ready..."
for i in {1..30}; do
    if check_caddy; then
        break
    fi
    if [ $i -eq 30 ]; then
        echo "💥 Caddy failed to start after 30 seconds"
        exit 1
    fi
    sleep 1
done

echo "⏳ Waiting for Laravel application to respond..."
for i in {1..60}; do
    if check_laravel; then
        echo "🚀 All services are ready! Railway health checks should now succeed."
        exit 0
    fi
    if [ $i -eq 60 ]; then
        echo "💥 Laravel application failed to respond after 60 seconds"
        echo "🔍 Debug info:"
        echo "Supervisord processes:"
        supervisorctl status
        echo "Port 80 status:"
        netstat -tlnp | grep :80 || echo "Port 80 not bound"
        exit 1
    fi
    sleep 1
done