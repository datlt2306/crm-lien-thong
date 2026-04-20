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
        // Tìm payment
        $payment = Payment::findOrFail($paymentId);

        // Kiểm tra quyền truy cập
        $user = Auth::user();

        if (!$user) {
            abort(403, 'Không có quyền truy cập');
        }

        // Super admin có thể xem tất cả
        if ($user->role === 'super_admin') {
            return $this->serveFile($payment->bill_path);
        }


        // CTV có thể xem payment của mình
        if ($user->role === 'ctv') {
            $collaborator = Collaborator::where('email', $user->email)->first();
            if ($collaborator && $payment->primary_collaborator_id === $collaborator->id) {
                return $this->serveFile($payment->bill_path);
            }
        }

        abort(403, 'Không có quyền truy cập file này');
    }

    public function publicViewBill(Request $request, $paymentId) {
        // Tìm payment
        $payment = Payment::findOrFail($paymentId);

        // Nếu yêu cầu phiếu thu (receipt)
        if ($request->query('type') === 'receipt') {
            return $this->serveFile($payment->receipt_path);
        }

        // Mặc định xem minh chứng (bill)
        return $this->serveFile($payment->bill_path);
    }


    public function viewCommissionBill($commissionItemId) {
        // Tìm commission item
        $commissionItem = \App\Models\CommissionItem::findOrFail($commissionItemId);

        // Kiểm tra quyền truy cập
        $user = Auth::user();

        if (!$user) {
            abort(403, 'Không có quyền truy cập');
        }

        // Super admin có thể xem tất cả
        if ($user->role === 'super_admin') {
            return $this->serveFile($commissionItem->payment_bill_path);
        }


        // CTV có thể xem commission bill của mình
        if ($user->role === 'ctv') {
            $collaborator = Collaborator::where('email', $user->email)->first();
            if ($collaborator && $commissionItem->recipient_collaborator_id === $collaborator->id) {
                return $this->serveFile($commissionItem->payment_bill_path);
            }
        }

        abort(403, 'Không có quyền truy cập file này');
    }

    public function viewReceipt($paymentId) {
        // Tìm payment
        $payment = Payment::findOrFail($paymentId);

        // Kiểm tra quyền truy cập
        $user = Auth::user();

        if (!$user) {
            abort(403, 'Không có quyền truy cập');
        }
        // Super admin và accountant có thể xem receipt tự do
        if (
            in_array($user->role, ['super_admin', 'accountant']) ||
            ($user->roles && $user->roles->contains('name', 'accountant'))
        ) {
            return $this->serveFile($payment->receipt_path);
        }


        // CTV chỉ được xem receipt của học viên do chính CTV đó quản lý (Chống IDOR tải chênh lệch)
        if ($user->role === 'ctv') {
            $collaborator = Collaborator::where('email', $user->email)->first();
            if ($collaborator && $payment->primary_collaborator_id === $collaborator->id) {
                return $this->serveFile($payment->receipt_path);
            }
        }

        abort(403, 'Không có quyền truy cập file này');
    }

    private function serveFile($filePath) {
        if (!$filePath) {
            abort(404, 'File không tồn tại');
        }

        try {
            $disk = Storage::disk('google');
            
            // Nếu filePath đã là một URL (hiếm gặp nhưng dự phòng)
            if (filter_var($filePath, FILTER_VALIDATE_URL)) {
                return redirect($filePath);
            }

            // Lấy URL từ Disk
            $url = $disk->url($filePath);
            
            // Nếu là link Google Drive (dạng uc?id=), chuyển sang dạng view để xem được PDF/In ấn
            if (str_contains($url, 'uc?id=')) {
                $fileId = null;
                parse_str(parse_url($url, PHP_URL_QUERY), $query);
                $fileId = $query['id'] ?? null;

                if ($fileId) {
                    // Trả về link view chính thức của Google Drive
                    return redirect("https://docs.google.com/file/d/{$fileId}/view");
                }
            }

            return redirect($url);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('File View Error: ' . $e->getMessage());
            abort(404, 'Không thể sinh liên kết cho file này. Lỗi: ' . $e->getMessage());
        }
    }
}
