<?php

define('LARAVEL_START', microtime(true));

// NOTE: our app lives in /app, the web root is public_html.
// So index.php must load from ../app/…
require __DIR__.'/../app/vendor/autoload.php';
$app = require_once __DIR__.'/../app/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
)->send();

$kernel->terminate($request, $response);

