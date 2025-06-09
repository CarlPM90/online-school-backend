<?php


return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
    'allowed_origins' => env('APP_ENV') === 'local' ? ['*'] : [
        'https://tos-front-end.vercel.app',
        'https://tos-front-end-git-main-educave.vercel.app', 
        'https://tos-front-gkh4kgsdc-educave.vercel.app',
        '*.vercel.app'  // Only for preview deployments
    ],
    'allowed_origins_patterns' => [
        '/^https:\/\/.*\.vercel\.app$/',  // Vercel preview URLs
    ],
    'allowed_headers' => ['Content-Type', 'Authorization', 'X-Requested-With', 'Accept', 'Origin'],
    'exposed_headers' => [],
    'max_age' => 86400,  // Cache preflight for 24 hours
    'supports_credentials' => true,
];
