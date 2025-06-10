<?php

/*
|--------------------------------------------------------------------------
| Create The Application
|--------------------------------------------------------------------------
*/

$app = new Illuminate\Foundation\Application(
    $_ENV['APP_BASE_PATH'] ?? dirname(__DIR__)
);

/*
|--------------------------------------------------------------------------
| Bind Important Interfaces
|--------------------------------------------------------------------------
*/

$app->singleton(
    Illuminate\Contracts\Http\Kernel::class,
    App\Http\Kernel::class
);

$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    App\Console\Kernel::class
);

$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    App\Exceptions\Handler::class
);

/*
|--------------------------------------------------------------------------
| Force Register Critical Service Providers
|--------------------------------------------------------------------------
*/

// CRITICAL: Manually bind config service before anything else
$app->singleton('config', function ($app) {
    return new \Illuminate\Config\Repository();
});

// Force register core Laravel service providers that may not be loading
$criticalProviders = [
    \Illuminate\Config\ConfigServiceProvider::class, // CRITICAL: Must be first
    \Illuminate\Foundation\Providers\FoundationServiceProvider::class,
    \Illuminate\Database\DatabaseServiceProvider::class,
    \Illuminate\Filesystem\FilesystemServiceProvider::class,
    \Illuminate\View\ViewServiceProvider::class,
    \Illuminate\Routing\RoutingServiceProvider::class,
    \Illuminate\Validation\ValidationServiceProvider::class,
    \Illuminate\Auth\AuthServiceProvider::class,
    \Illuminate\Broadcasting\BroadcastServiceProvider::class,
    \Illuminate\Bus\BusServiceProvider::class,
    \Illuminate\Cache\CacheServiceProvider::class,
    \Illuminate\Cookie\CookieServiceProvider::class,
    \Illuminate\Encryption\EncryptionServiceProvider::class,
    \Illuminate\Queue\QueueServiceProvider::class,
    \Illuminate\Redis\RedisServiceProvider::class,
    \Illuminate\Auth\Passwords\PasswordResetServiceProvider::class,
    \Illuminate\Session\SessionServiceProvider::class,
    \Illuminate\Translation\TranslationServiceProvider::class,
];

foreach ($criticalProviders as $provider) {
    try {
        $app->register($provider);
    } catch (Exception $e) {
        // Silently continue if provider fails to register
        error_log("Failed to register provider: $provider - " . $e->getMessage());
    }
}

/*
|--------------------------------------------------------------------------
| Return The Application
|--------------------------------------------------------------------------
*/

return $app;