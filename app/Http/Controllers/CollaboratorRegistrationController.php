<?php

namespace App\Http\Controllers;

use App\Models\CollaboratorRegistration;
use App\Models\Collaborator;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class CollaboratorRegistrationController extends Controller {
    /**
     * Hiển thị form đăng ký cộng tác viên
     */
    public function showRegistrationForm() {
        $organizations = Organization::where('status', 'active')->get();

        return view('collaborator-registration', compact('organizations'));
    }

    /**
     * Xử lý đăng ký cộng tác viên
     */
    public function store(Request $request) {
        $validator = Validator::make($request->all(), [
            'full_name' => 'required|string|max:255',
            'phone' => 'required|string|unique:collaborators,phone',
            'email' => 'nullable|email|unique:collaborators,email',
            'organization_id' => 'required|exists:organizations,id',
            'note' => 'nullable|string',
        ], [
            'full_name.required' => 'Họ và tên là bắt buộc',
            'phone.required' => 'Số điện thoại là bắt buộc',
            'phone.unique' => 'Số điện thoại đã được đăng ký',
            'email.email' => 'Email không đúng định dạng',
            'email.unique' => 'Email đã được đăng ký',
            'organization_id.required' => 'Vui lòng chọn tổ chức',
            'organization_id.exists' => 'Tổ chức không tồn tại',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            // Tạo collaborator với status pending để admin/owner có thể duyệt
            $collaborator = Collaborator::create([
                'full_name' => $request->full_name,
                'phone' => $request->phone,
                'email' => $request->email,
                'organization_id' => $request->organization_id,
                'upline_id' => null, // Không có người giới thiệu
                'ref_id' => 'REG-' . time() . '-' . rand(1000, 9999),
                'status' => 'pending', // Chờ duyệt
                'note' => $request->note,
            ]);

            return redirect()->back()
                ->with('success', 'Đăng ký cộng tác viên thành công! Chúng tôi sẽ xem xét và phản hồi trong thời gian sớm nhất.');
        } catch (\Exception $e) {
            Log::error('Lỗi khi đăng ký cộng tác viên: ' . $e->getMessage());

            return redirect()->back()
                ->with('error', 'Có lỗi xảy ra khi đăng ký. Vui lòng thử lại sau.')
                ->withInput();
        }
    }

    /**
     * Kiểm tra trạng thái đăng ký
     */
    public function checkStatus(Request $request) {
        $phone = $request->input('phone');

        if (!$phone) {
            return response()->json(['error' => 'Vui lòng nhập số điện thoại'], 400);
        }

        $collaborator = Collaborator::where('phone', $phone)->first();

        if (!$collaborator) {
            return response()->json(['error' => 'Không tìm thấy đăng ký với số điện thoại này'], 404);
        }

        return response()->json([
            'status' => $collaborator->status,
            'message' => $this->getStatusMessage($collaborator->status),
            'rejection_reason' => $collaborator->rejection_reason,
            'reviewed_at' => $collaborator->updated_at,
        ]);
    }

    /**
     * Lấy thông báo theo trạng thái
     */
    private function getStatusMessage(string $status): string {
        switch ($status) {
            case 'pending':
                return 'Đăng ký của bạn đang được xem xét. Vui lòng chờ phản hồi từ quản trị viên.';
            case 'active':
                return 'Đăng ký của bạn đã được duyệt! Bạn đã trở thành cộng tác viên chính thức.';
            case 'inactive':
                return 'Tài khoản cộng tác viên của bạn đã bị tạm dừng. Vui lòng liên hệ quản trị viên để biết thêm chi tiết.';
            default:
                return 'Trạng thái không xác định.';
        }
    }
}
