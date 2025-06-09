<?php


return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
    'allowed_origins' => ['*', '*.etd24.pl', 'https://tos-front-end-git-main-educave.vercel.app', 'https://tos-front-gkh4kgsdc-educave.vercel.app', 'https://tos-front-end.vercel.app', '*.vercel.app'],
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,
];
