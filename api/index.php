<?php

// TEMPORARY DEBUG - test if PHP runtime works at all
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error) {
        http_response_code(500);
        echo '<h1>Fatal Error</h1><pre>' . htmlspecialchars(print_r($error, true)) . '</pre>';
    }
});

error_reporting(E_ALL);
ini_set('display_errors', '1');

echo '<h2>PHP is working! Version: ' . phpversion() . '</h2>';
echo '<h3>Checking autoloader...</h3>';

if (!file_exists(__DIR__ . '/../vendor/autoload.php')) {
    die('<p style="color:red">ERROR: vendor/autoload.php not found!</p>');
}
echo '<p style="color:green">vendor/autoload.php EXISTS</p>';

echo '<h3>Checking APP_KEY env...</h3>';
$key = getenv('APP_KEY');
echo '<p>' . ($key ? 'APP_KEY is SET (length: ' . strlen($key) . ')' : '<span style="color:red">APP_KEY is NOT SET</span>') . '</p>';

echo '<h3>Checking DB_HOST env...</h3>';
$db = getenv('DB_HOST');
echo '<p>' . ($db ? 'DB_HOST is SET: ' . htmlspecialchars($db) : '<span style="color:red">DB_HOST is NOT SET</span>') . '</p>';

echo '<h3>Done!</h3>';

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
