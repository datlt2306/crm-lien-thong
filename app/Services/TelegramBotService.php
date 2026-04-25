<?php

namespace App\Services;

use App\Models\RefCode;
use App\Models\Student;
use App\Models\Collaborator;
use App\Models\CommissionItem;
use App\Models\Payment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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
        $photo = $message['photo'] ?? null;
        $document = $message['document'] ?? null;
        $replyTo = $message['reply_to_message'] ?? null;

        // Xử lý nộp Bill qua Reply ảnh hoặc File
        if (($photo || $document) && $replyTo) {
            $this->handleBillUploadViaReply($chatId, $photo, $document, $replyTo);
            return;
        }

        if (str_starts_with($text, '/start')) {
            $this->sendMessage($chatId, "👋 Chào mừng bạn đến với hệ thống đối soát CRM!\n\nHãy dùng lệnh /check để xem báo cáo của mình.\nID Telegram của bạn là: `{$chatId}`\n\n💡 *Mẹo:* Bạn có thể TRẢ LỜI (Reply) tin nhắn thông báo sinh viên mới bằng một ảnh chuyển khoản để nộp hóa đơn nhanh!");
            return;
        }

        if (str_starts_with($text, '/check')) {
            $this->handleCheckCommand($chatId);
            return;
        }
    }

    protected function handleBillUploadViaReply($chatId, $photo, $document, $replyTo)
    {
        $originalText = $replyTo['text'] ?? $replyTo['caption'] ?? '';
        Log::info("Telegram Reply Received from {$chatId}. Original Text: " . $originalText);
        
        // Regex linh hoạt hơn để bắt mã hồ sơ
        if (!preg_match('/HS\d{4}[A-Z0-9]{4}\d{3}/', $originalText, $matches)) {
            Log::warning("Telegram Bill Upload: Could not find Profile Code in original message.");
            return; // Im hơi lặng tiếng nếu không phải reply tin nhắn thông báo HS
        }

        $profileCode = $matches[0];
        $student = Student::where('profile_code', $profileCode)->first();

        if (!$student) {
            $this->sendMessage($chatId, "⚠️ Không tìm thấy hồ sơ `{$profileCode}` trên hệ thống.");
            return;
        }

        $payment = $student->payment;

        // Chặn upload nếu đã thanh toán thành công (tránh spam)
        if ($payment && $payment->status === Payment::STATUS_VERIFIED) {
            $this->sendMessage($chatId, "🚫 *Thông báo:* Hồ sơ `{$profileCode}` của sinh viên *{$student->full_name}* đã được kế toán xác nhận thanh toán thành công.\n\nBạn không cần gửi thêm minh chứng cho hồ sơ này nữa.");
            return;
        }

        // Lấy File ID (Ưu tiên Document, sau đó đến Photo chất lượng cao nhất)
        $fileId = null;
        $extension = 'jpg';

        if ($document) {
            // Kiểm tra xem có phải là ảnh không (mime_type)
            $mimeType = $document['mime_type'] ?? '';
            if (!str_starts_with($mimeType, 'image/')) {
                $this->sendMessage($chatId, "⚠️ File bạn gửi không phải là định dạng ảnh. Vui lòng gửi ảnh Bill chuyển khoản.");
                return;
            }
            $fileId = $document['file_id'];
            $extension = pathinfo($document['file_name'] ?? 'bill.jpg', PATHINFO_EXTENSION) ?: 'jpg';
        } elseif ($photo) {
            $fileId = end($photo)['file_id'];
        }

        if (!$fileId) return;
        
        try {
            $this->sendMessage($chatId, "⏳ Đang tải ảnh và cập nhật hồ sơ `{$profileCode}`...");

            // Lấy path từ Telegram
            $fileResponse = Http::get("https://api.telegram.org/bot{$this->token}/getFile", ['file_id' => $fileId]);
            $filePath = $fileResponse->json('result.file_path');

            if (!$filePath) throw new \Exception("Không lấy được đường dẫn file từ Telegram.");

            $fileUrl = "https://api.telegram.org/file/bot{$this->token}/{$filePath}";
            
            // Tải file về
            $fileContent = Http::get($fileUrl)->body();

            $payment = $student->payment ?: $student->payment()->create([
                'status' => Payment::STATUS_NOT_PAID,
                'amount' => 0,
                'program_type' => $student->program_type,
                'primary_collaborator_id' => $student->collaborator_id,
            ]);

            $targetPath = $payment->generateStandardBillPath($extension);
            
            // Lưu vào Storage (Thử dùng google trước, nếu lỗi thì dùng public)
            try {
                Storage::disk('google')->put($targetPath, $fileContent);
                Log::info("Telegram Bill: Uploaded to Google Drive successfully.");
            } catch (\Exception $e) {
                Log::warning("Telegram Bill: Google Drive upload failed, falling back to Local. Error: " . $e->getMessage());
                Storage::disk('public')->put($targetPath, $fileContent);
            }

            // Cập nhật Database
            $payment->update([
                'bill_path' => $targetPath,
                'status' => Payment::STATUS_SUBMITTED,
            ]);

            $this->sendMessage($chatId, "✅ *Thành công!* Đã nhận hóa đơn cho sinh viên: *{$student->full_name}* ({$profileCode}).\n\nKế toán đã nhận được thông báo và sẽ duyệt sớm nhất có thể.");
            
            Log::info("Telegram Bill Upload Success: {$profileCode} by ChatID {$chatId}");

        } catch (\Exception $e) {
            Log::error("Telegram Bill Upload Error: " . $e->getMessage());
            $this->sendMessage($chatId, "❌ Có lỗi xảy ra khi tải ảnh: " . $e->getMessage());
        }
    }

    protected function handleCheckCommand($chatId)
    {
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
