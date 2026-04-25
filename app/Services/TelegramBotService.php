<?php

namespace App\Services;

use App\Models\RefCode;
use App\Models\Student;
use App\Models\Collaborator;
use App\Models\CommissionItem;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramBotService
{
    protected $token;

    public function __construct()
    {
        $this->token = config('services.telegram-bot-api.token');
    }

    public function handleWebhook($data)
    {
        $message = $data['message'] ?? null;
        if (!$message) return;

        $chatId = $message['chat']['id'];
        $text = $message['text'] ?? '';

        if (str_starts_with($text, '/start')) {
            $this->sendMessage($chatId, "👋 Chào mừng bạn đến với hệ thống đối soát CRM!\n\nHãy dùng lệnh /check để xem báo cáo của mình.\nID Telegram của bạn là: `{$chatId}`");
            return;
        }

        if (str_starts_with($text, '/check')) {
            $this->handleCheckCommand($chatId);
            return;
        }
    }

    protected function handleCheckCommand($chatId)
    {
        // Ưu tiên check Proxy trước
        $refCode = RefCode::where('telegram_chat_id', $chatId)->first();
        if ($refCode) {
            $this->sendProxyReport($refCode, $chatId);
            return;
        }

        $master = Collaborator::where('telegram_chat_id', $chatId)->first();
        if ($master) {
            $this->sendMasterReport($master, $chatId);
            return;
        }

        $this->sendMessage($chatId, "⚠️ Bạn chưa được cấp quyền đối soát. Hãy gửi ID `{$chatId}` cho Admin nhé.");
    }

    protected function sendMasterReport($master, $chatId)
    {
        $proxyRefs = RefCode::where('collaborator_id', $master->id)->get();
        
        $report = "📊 *BÁO CÁO TỔNG HỢP (MASTER)*\n";
        $report .= "Chào anh *{$master->full_name}*,\n";
        $report .= "----------------------------\n";

        $totalAll = 0;
        $proxyBreakdown = "";

        foreach ($proxyRefs as $ref) {
            $studentIds = Student::where('source_ref', $ref->code)->pluck('id');
            $amount = CommissionItem::whereHas('commission', function($q) use ($studentIds) {
                $q->whereIn('student_id', $studentIds);
            })->where('role', 'direct')->sum('amount');

            $totalAll += $amount;
            if ($amount > 0) {
                $proxyBreakdown .= "📍 Nguồn {$ref->name}: *" . number_format($amount) . "đ*\n";
            }
        }

        // Tính phần trực tiếp của Đạt
        $masterRefIds = [$master->ref_id, null, ''];
        $directStudentIds = Student::where('collaborator_id', $master->id)
            ->where(function($q) use ($masterRefIds) {
                $q->whereIn('source_ref', $masterRefIds)->orWhereNull('source_ref');
            })->pluck('id');
            
        $directAmount = CommissionItem::whereHas('commission', function($q) use ($directStudentIds) {
            $q->whereIn('student_id', $directStudentIds);
        })->where('role', 'direct')->sum('amount');
        
        $totalAll += $directAmount;

        $report .= "💰 TỔNG DOANH THU: *" . number_format($totalAll) . "đ*\n";
        $report .= "----------------------------\n";
        $report .= "💎 Trực tiếp (Đạt): *" . number_format($directAmount) . "đ*\n";
        $report .= $proxyBreakdown;
        $report .= "----------------------------\n";
        $report .= "📝 _Anh dùng số liệu này để báo kế toán chi tổng nhé._";

        $this->sendMessage($chatId, $report);
    }

    protected function sendProxyReport($refCode, $chatId)
    {
        $students = Student::where('source_ref', $refCode->code)->orderBy('created_at', 'desc')->get();
        
        $report = "📋 *BÁO CÁO HỒ SƠ* [{$refCode->name}]\n";
        $report .= "----------------------------\n";

        $counts = ['REGULAR' => 0, 'PART_TIME' => 0, 'DISTANCE' => 0];

        foreach ($students as $student) {
            $type = strtoupper($student->program_type);
            if (isset($counts[$type])) {
                $counts[$type]++;
            }
        }

        $report .= "🔵 *HỆ CHÍNH QUY:*\n";
        $report .= "   + Số lượng: *{$counts['REGULAR']} hồ sơ*\n\n";

        $report .= "🟠 *HỆ VỪA HỌC VỪA LÀM:*\n";
        $report .= "   + Số lượng: *{$counts['PART_TIME']} hồ sơ*\n\n";

        $report .= "🟢 *HỆ ĐÀO TẠO TỪ XA:*\n";
        $report .= "   + Số lượng: *{$counts['DISTANCE']} hồ sơ*\n";
        $report .= "----------------------------\n";
        
        $total = array_sum($counts);
        $report .= "👥 *TỔNG HỌC VIÊN:* *{$total} hồ sơ*\n";
        $report .= "----------------------------\n";
        $report .= "💡 _Hồ sơ hệ Chính quy sẽ được quyết toán vào mùng 5 hàng tháng._\n";
        $report .= "💡 _Hồ sơ hệ VHVL & ĐTTX sẽ được quyết toán sau khi sinh viên hoàn tất nhập học._";

        $this->sendMessage($chatId, $report);
    }

    public function sendMessage($chatId, $text)
    {
        if (!$this->token) return;
        Http::post("https://api.telegram.org/bot{$this->token}/sendMessage", [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'Markdown',
        ]);
    }
}
