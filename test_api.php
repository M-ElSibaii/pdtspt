<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);

try {
    $request = \Illuminate\Http\Request::create(
        '/api/4',
        'GET',
        [],
        [],
        [],
        ['HTTP_ACCEPT' => 'application/json']
    );
    
    $response = $kernel->handle($request);
    echo "Status: " . $response->status() . "\n";
    echo "Content:\n";
    echo $response->getContent();
    
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Trace:\n";
    echo $e->getTraceAsString();
}
?>
