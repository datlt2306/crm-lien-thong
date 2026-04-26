<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use App\Models\Payment;
use App\Models\Student;
use App\Models\Collaborator;

class FileController extends Controller {
    public function viewBill($paymentId) {
        $payment = Payment::findOrFail($paymentId);
        $user = Auth::user();

        if (!$user) abort(403);

        // Kiểm tra quyền qua hệ thống Permission hoặc Role cứng (Fallback)
        if ($user->can('payment_view_bill') || 
            in_array($user->role, ['super_admin', 'admin', 'accountant', 'document'])) {
            return $this->serveFile($payment->bill_path);
        }

        // CTV xem bill của học viên mình quản lý
        if ($user->hasRole('collaborator')) {
            $collaborator = Collaborator::where('email', $user->email)->first();
            if ($collaborator && $payment->primary_collaborator_id === $collaborator->id) {
                return $this->serveFile($payment->bill_path);
            }
        }

        abort(403, 'Bạn không có quyền xem minh chứng này');
    }

    public function publicViewBill(Request $request, $paymentUuid) {
        $payment = Payment::where('uuid', $paymentUuid)->firstOrFail();
        $token = $request->query('token');

        // Xác thực token bảo mật
        if (!$token || $token !== $payment->getPublicToken()) {
            abort(403, 'Liên kết đã hết hạn hoặc không hợp lệ.');
        }

        if ($request->query('type') === 'receipt') {
            return $this->serveFile($payment->receipt_path);
        }

        return $this->serveFile($payment->bill_path);
    }

    public function viewCommissionBill($commissionItemId) {
        $commissionItem = \App\Models\CommissionItem::findOrFail($commissionItemId);
        $user = Auth::user();

        if (!$user) abort(403);

        // Kiểm tra quyền qua hệ thống Permission hoặc Role cứng (Fallback)
        if ($user->can('commission_view') || 
            in_array($user->role, ['super_admin', 'admin', 'accountant'])) {
            return $this->serveFile($commissionItem->payment_bill_path);
        }

        // CTV xem bill của chính mình
        if ($user->hasRole('collaborator')) {
            $collaborator = Collaborator::where('email', $user->email)->first();
            if ($collaborator && $commissionItem->recipient_collaborator_id === $collaborator->id) {
                return $this->serveFile($commissionItem->payment_bill_path);
            }
        }

        abort(403, 'Bạn không có quyền xem minh chứng chi này');
    }

    public function viewReceipt($paymentId) {
        $payment = Payment::findOrFail($paymentId);
        $user = Auth::user();

        if (!$user) abort(403);

        // Kiểm tra quyền qua hệ thống Permission hoặc Role cứng (Fallback)
        if ($user->can('payment_view') || 
            in_array($user->role, ['super_admin', 'admin', 'accountant', 'document'])) {
            return $this->serveFile($payment->receipt_path);
        }

        // CTV xem phiếu thu của học viên mình
        if ($user->hasRole('collaborator')) {
            $collaborator = Collaborator::where('email', $user->email)->first();
            if ($collaborator && $payment->primary_collaborator_id === $collaborator->id) {
                return $this->serveFile($payment->receipt_path);
            }
        }

        abort(403, 'Bạn không có quyền xem phiếu thu này');
    }

    public function viewRefundProof($paymentId) {
        $payment = Payment::findOrFail($paymentId);
        $user = Auth::user();

        if (!$user) abort(403);

        // Kiểm tra quyền
        if ($user->can('payment_view') || 
            in_array($user->role, ['super_admin', 'admin', 'accountant', 'document'])) {
            return $this->serveFile($payment->refund_proof_path);
        }

        // CTV xem minh chứng hoàn tiền của học viên mình
        if ($user->hasRole('collaborator')) {
            $collaborator = Collaborator::where('email', $user->email)->first();
            if ($collaborator && $payment->primary_collaborator_id === $collaborator->id) {
                return $this->serveFile($payment->refund_proof_path);
            }
        }

        abort(403, 'Bạn không có quyền xem minh chứng hoàn tiền này');
    }

    private function serveFile($filePath) {
        if (!$filePath) {
            abort(404, 'File không tồn tại');
        }

        // 1. Thử kiểm tra ở Local trước (vì mày đang chạy 127.0.0.1)
        if (Storage::disk('public')->exists($filePath)) {
            return response()->file(storage_path('app/public/' . $filePath));
        }

        try {
            $disk = Storage::disk('google');
            
            // Nếu filePath đã là một URL đầy đủ
            if (filter_var($filePath, FILTER_VALIDATE_URL)) {
                return redirect($filePath);
            }

            // Lấy URL từ Disk (thường là link download uc?id=)
            $url = $disk->url($filePath);
            
            // Chuyển đổi link download sang link xem trực tiếp (View) của Google Drive
            $fileId = null;
            if (str_contains($url, 'uc?id=')) {
                parse_str(parse_url($url, PHP_URL_QUERY), $query);
                $fileId = $query['id'] ?? null;
            } elseif (preg_match('/[-\w]{25,}/', $filePath, $matches)) {
                $fileId = $matches[0];
            }

            if ($fileId) {
                return redirect("https://drive.google.com/file/d/{$fileId}/view");
            }

            return redirect($url);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('File View Error (Google): ' . $e->getMessage());
            
            // Nếu lỗi Google mà nãy chưa check Local thì check lại phát cuối (đề phòng)
            if (Storage::disk('public')->exists($filePath)) {
                return response()->file(storage_path('app/public/' . $filePath));
            }

            abort(404, 'Không thể mở file. Lỗi Drive: ' . $e->getMessage());
        }
    }
}
