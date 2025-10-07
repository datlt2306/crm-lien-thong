<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use App\Models\Payment;
use App\Models\Student;
use App\Models\Organization;
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

        // Chủ đơn vị có thể xem payment của tổ chức mình
        if ($user->role === 'organization_owner') {
            $org = Organization::where('organization_organization_owner_id', $user->id)->first();
            if ($org && $payment->organization_id === $org->id) {
                return $this->serveFile($payment->bill_path);
            }
        }

        // CTV có thể xem payment của mình và của downline trong nhánh
        if ($user->role === 'ctv') {
            $collaborator = Collaborator::where('email', $user->email)->first();
            if ($collaborator) {
                // Kiểm tra xem payment có phải của mình không
                if ($payment->primary_collaborator_id === $collaborator->id) {
                    return $this->serveFile($payment->bill_path);
                }

                // Kiểm tra xem payment có phải của downline trong nhánh không
                $downlineIds = self::getDownlineIds($collaborator->id);
                if (in_array($payment->primary_collaborator_id, $downlineIds)) {
                    return $this->serveFile($payment->bill_path);
                }
            }
        }

        abort(403, 'Không có quyền truy cập file này');
    }

    public function viewReceipt($paymentId) {
        $payment = Payment::findOrFail($paymentId);

        $user = Auth::user();
        if (!$user) {
            abort(403, 'Không có quyền truy cập');
        }

        // Super admin và accountant (Spatie role) có thể xem tất cả phiếu thu
        if ($user->role === 'super_admin' || ($user->hasRole('accountant') ?? false)) {
            return $this->serveFile($payment->receipt_path);
        }

        // Chủ đơn vị có thể xem payment của tổ chức mình
        if ($user->role === 'organization_owner') {
            $org = Organization::where('organization_organization_owner_id', $user->id)->first();
            if ($org && $payment->organization_id === $org->id) {
                return $this->serveFile($payment->receipt_path);
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

        // Chủ đơn vị có thể xem commission bill của tổ chức mình
        if ($user->role === 'organization_owner') {
            $org = Organization::where('organization_organization_owner_id', $user->id)->first();
            if ($org && $commissionItem->recipient->organization_id === $org->id) {
                return $this->serveFile($commissionItem->payment_bill_path);
            }
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

    /**
     * Lấy danh sách ID của tất cả downline trong nhánh
     */
    private static function getDownlineIds(int $collaboratorId): array {
        $downlineIds = [];

        // Lấy tất cả downline trực tiếp
        $directDownlines = Collaborator::where('upline_id', $collaboratorId)->get();

        foreach ($directDownlines as $downline) {
            $downlineIds[] = $downline->id;

            // Đệ quy lấy downline của downline
            $subDownlineIds = self::getDownlineIds($downline->id);
            $downlineIds = array_merge($downlineIds, $subDownlineIds);
        }

        return $downlineIds;
    }
}
