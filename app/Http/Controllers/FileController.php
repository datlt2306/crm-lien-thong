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
        if (!$filePath || !Storage::disk('local')->exists($filePath)) {
            abort(404, 'File không tồn tại');
        }

        $file = Storage::disk('local')->get($filePath);
        $mimeType = mime_content_type(Storage::disk('local')->path($filePath));

        return response($file, 200, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="' . basename($filePath) . '"'
        ]);
    }
}
