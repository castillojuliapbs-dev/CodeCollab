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

// Create writable dirs in /tmp for serverless
$storagePath = '/tmp/storage';
$bootstrapCachePath = '/tmp/bootstrap/cache';

$dirs = [
    $bootstrapCachePath,
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

// Copy bootstrap cache files to writable /tmp if they don't exist yet
$cacheFiles = ['packages.php', 'services.php'];
foreach ($cacheFiles as $file) {
    $src = __DIR__ . '/../bootstrap/cache/' . $file;
    $dst = $bootstrapCachePath . '/' . $file;
    if (file_exists($src) && !file_exists($dst)) {
        copy($src, $dst);
    }
}

putenv('VIEW_COMPILED_PATH=' . $storagePath . '/framework/views');

// Override APP_KEY
putenv('APP_KEY=base64:ZRDIgm3AJnVH/tJK2aZnPm5LsSSC8bVi3H43FPWA+wU=');
$_ENV['APP_KEY'] = 'base64:ZRDIgm3AJnVH/tJK2aZnPm5LsSSC8bVi3H43FPWA+wU=';
$_SERVER['APP_KEY'] = 'base64:ZRDIgm3AJnVH/tJK2aZnPm5LsSSC8bVi3H43FPWA+wU=';

// Load Composer autoloader
require __DIR__ . '/../vendor/autoload.php';

// Bootstrap Laravel
try {
    $app = require __DIR__ . '/../bootstrap/app.php';

    // Use writable /tmp paths for both bootstrap cache and storage
    $app->useBootstrapPath('/tmp/bootstrap');
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
