<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Commission;
use App\Models\CommissionItem;
use App\Models\Payment;
use App\Services\CommissionService;

CommissionItem::truncate();
Commission::truncate();

$payments = Payment::where('status', 'verified')->get();
$service = app(CommissionService::class);

foreach ($payments as $payment) {
    try {
        $service->createCommissionFromPayment($payment);
    } catch (\Exception $e) {
        echo "❌ Lỗi cho Payment #{$payment->id}: " . $e->getMessage() . "\n";
    }
}

echo "✅ Đã tính toán lại hoa hồng cho " . $payments->count() . " phiếu thu (đã duyệt).\n";
