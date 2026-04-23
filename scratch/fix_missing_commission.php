<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Payment;
use App\Models\Commission;
use App\Services\CommissionService;

$payments = Payment::where('status', Payment::STATUS_VERIFIED)->get();

echo "Found " . $payments->count() . " verified payments.\n";

foreach ($payments as $payment) {
    echo "Checking Payment ID: {$payment->id}...\n";
    $commission = Commission::where('payment_id', $payment->id)->first();
    if (!$commission) {
        echo " - Commission record missing. Attempting to create...\n";
        try {
            (new CommissionService())->createCommissionFromPayment($payment);
            echo " - Successfully created commission.\n";
        } catch (\Exception $e) {
            echo " - Failed to create commission: " . $e->getMessage() . "\n";
        }
    } else {
        $itemsCount = $commission->items()->count();
        if ($itemsCount === 0) {
            echo " - No items found. Attempting to re-generate...\n";
            try {
                (new CommissionService())->createCommissionFromPayment($payment);
                echo " - Successfully recreated items.\n";
            } catch (\Exception $e) {
                echo " - Failed: " . $e->getMessage() . "\n";
            }
        } else {
            echo " - OK ($itemsCount items).\n";
        }
    }
}
