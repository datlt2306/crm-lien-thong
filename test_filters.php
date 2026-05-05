<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::create(
        '/admin/students?tableFilters[created_at][created_from]=2026-04-05&tableFilters[created_at][created_until]=2026-05-04&tableFilters[payment_status][value]=not_paid'
    )
);
echo $response->status() . "\n";
