<?php

error_reporting(E_ALL);
ini_set('display_errors', '1');

register_shutdown_function(function () {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        http_response_code(500);
        echo '<h1>Fatal Error</h1><pre>' . htmlspecialchars(print_r($error, true)) . '</pre>';
    }
});

// Set the working directory to the Laravel root
chdir(__DIR__ . '/..');

// Create writable bootstrap cache and storage dirs in /tmp BEFORE bootstrapping
$storagePath = '/tmp/storage';
$bootstrapPath = '/tmp/bootstrap';

$dirs = [
    $bootstrapPath . '/cache',
    $storagePath . '/app/public',
    $storagePath . '/framework/cache/data',
    $storagePath . '/framework/sessions',
    $storagePath . '/framework/views',
    $storagePath . '/framework/testing',
    $storagePath . '/logs',
];
foreach ($dirs as $dir) {
    if (!is_dir($dir)) mkdir($dir, 0755, true);
}

putenv('VIEW_COMPILED_PATH=' . $storagePath . '/framework/views');

// Load Composer autoloader
require __DIR__ . '/../vendor/autoload.php';

// Bootstrap Laravel
try {
    $app = require_once __DIR__ . '/../bootstrap/app.php';

    // Redirect bootstrap cache and storage to writable /tmp paths
    $app->useBootstrapPath($bootstrapPath);
    $app->useStoragePath($storagePath);

    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    $response = $kernel->handle(
        $request = Illuminate\Http\Request::capture()
    );
    $response->send();
    $kernel->terminate($request, $response);
} catch (\Throwable $e) {
    http_response_code(500);
    echo '<h1>Laravel Boot Error</h1>';
    echo '<pre>' . htmlspecialchars($e->getMessage()) . "\n\n" . htmlspecialchars($e->getTraceAsString()) . '</pre>';
}

// Load Composer autoloader
require __DIR__ . '/../vendor/autoload.php';

// Create storage directories in /tmp for serverless BEFORE bootstrapping
$storagePath = '/tmp/storage';
$directories = [
    $storagePath,
    $storagePath . '/app',
    $storagePath . '/app/public',
    $storagePath . '/framework',
    $storagePath . '/framework/cache',
    $storagePath . '/framework/cache/data',
    $storagePath . '/framework/sessions',
    $storagePath . '/framework/views',
    $storagePath . '/framework/testing',
    $storagePath . '/logs',
];

foreach ($directories as $directory) {
    if (!is_dir($directory)) {
        mkdir($directory, 0755, true);
    }
}

// Set environment variable so Laravel uses correct view compile path
putenv('VIEW_COMPILED_PATH=' . $storagePath . '/framework/views');

// Bootstrap Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';

// Set storage path for serverless environment
$app->useStoragePath($storagePath);

// Handle the request
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

$response->send();

$kernel->terminate($request, $response);
