<?php

use App\Models\Collaborator;
use Illuminate\Support\Facades\DB;

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$collaborators = Collaborator::whereNull('ref_id')->orWhere('ref_id', '')->get();

echo "Found " . $collaborators->count() . " collaborators without ref_id.\n";

foreach ($collaborators as $collab) {
    do {
        $ref = strtoupper(substr(bin2hex(random_bytes(8)), 0, 8));
    } while (Collaborator::where('ref_id', $ref)->exists());
    
    $collab->update(['ref_id' => $ref]);
    echo "Generated ref_id {$ref} for collaborator: {$collab->full_name} ({$collab->email})\n";
}

echo "Done.\n";
