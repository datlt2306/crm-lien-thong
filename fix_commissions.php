<?php

use App\Models\Payment;
use App\Models\Commission;
use App\Models\CommissionItem;
use App\Services\CommissionService;
use Illuminate\Support\Facades\DB;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$service = new CommissionService();

echo "Bắt đầu tính toán lại toàn bộ hoa hồng...\n";

DB::transaction(function() use ($service) {
    $payments = Payment::where('status', 'verified')->get();
    echo "Tìm thấy " . $payments->count() . " phiếu thu đã xác nhận.\n";

    foreach ($payments as $payment) {
        // 1. Tìm hoặc tạo Commission record
        $commission = Commission::where('payment_id', $payment->id)->first();
        
        if ($commission) {
            // Xoá các item chưa được chi trả để tạo lại theo policy mới
            // Chỉ xoá các item có trạng thái pending hoặc payable (chưa thực sự chi tiền)
            $commission->items()
                ->whereIn('status', [CommissionItem::STATUS_PENDING, CommissionItem::STATUS_PAYABLE])
                ->delete();
        }

        // 2. Chạy logic tạo hoa hồng (nó sẽ tự tìm policy khớp nhất)
        $service->createCommissionFromPayment($payment);
        
        echo " - Đã xử lý xong cho sinh viên: " . ($payment->student->full_name ?? 'N/A') . " (Hệ: " . $payment->program_type . ")\n";
    }
});

echo "Hoàn thành! Vui lòng kiểm tra lại màn hình Báo cáo hoa hồng.\n";
