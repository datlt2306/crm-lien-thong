<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$data = ['created_from' => '2026-04-05', 'created_until' => '2026-05-04'];
$q = \App\Models\Student::query();
$q->when(
    $data['created_from'] ?? null,
    fn ($query, $date) => $query->whereDate('created_at', '>=', $date),
)
->when(
    $data['created_until'] ?? null,
    fn ($query, $date) => $query->whereDate('created_at', '<=', $date),
);

echo "Query:\n" . $q->toSql() . "\n";
echo "Count:\n" . $q->count() . "\n";
