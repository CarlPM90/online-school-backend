<?php

define('LARAVEL_START', microtime(true));

require __DIR__.'/../vendor/autoload.php';

// Use the fixed bootstrap
$app = require_once __DIR__.'/../bootstrap/app-fixed.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

$response->send();

$kernel->terminate($request, $response);
?>