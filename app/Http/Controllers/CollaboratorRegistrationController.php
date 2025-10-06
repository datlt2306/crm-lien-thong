<?php

namespace App\Http\Controllers;

use App\Models\CollaboratorRegistration;
use App\Models\Collaborator;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class CollaboratorRegistrationController extends Controller {
    /**
     * Hiển thị form đăng ký cộng tác viên
     */
    public function showRegistrationForm() {
        $organizations = Organization::where('status', 'active')->get();
        $collaborators = Collaborator::where('status', 'active')->get();

        return view('collaborator-registration', compact('organizations', 'collaborators'));
    }

    /**
     * Xử lý đăng ký cộng tác viên
     */
    public function store(Request $request) {
        $validator = Validator::make($request->all(), [
            'full_name' => 'required|string|max:255',
            'phone' => 'required|string|unique:collaborator_registrations,phone|unique:collaborators,phone',
            'email' => 'nullable|email|unique:collaborator_registrations,email|unique:collaborators,email',
            'organization_id' => 'required|exists:organizations,id',
            'upline_id' => 'nullable|exists:collaborators,id',
            'note' => 'nullable|string',
        ], [
            'full_name.required' => 'Họ và tên là bắt buộc',
            'phone.required' => 'Số điện thoại là bắt buộc',
            'phone.unique' => 'Số điện thoại đã được đăng ký',
            'email.email' => 'Email không đúng định dạng',
            'email.unique' => 'Email đã được đăng ký',
            'organization_id.required' => 'Vui lòng chọn tổ chức',
            'organization_id.exists' => 'Tổ chức không tồn tại',
            'upline_id.exists' => 'Cộng tác viên giới thiệu không tồn tại',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            // Kiểm tra upline có thuộc cùng tổ chức không
            if ($request->upline_id) {
                $upline = Collaborator::find($request->upline_id);
                if ($upline && $upline->organization_id != $request->organization_id) {
                    return redirect()->back()
                        ->with('error', 'Cộng tác viên giới thiệu phải thuộc cùng tổ chức')
                        ->withInput();
                }
            }

            CollaboratorRegistration::create([
                'full_name' => $request->full_name,
                'phone' => $request->phone,
                'email' => $request->email,
                'organization_id' => $request->organization_id,
                'upline_id' => $request->upline_id,
                'note' => $request->note,
                'status' => 'pending',
            ]);

            return redirect()->back()
                ->with('success', 'Đăng ký cộng tác viên thành công! Chúng tôi sẽ xem xét và phản hồi trong thời gian sớm nhất.');
        } catch (\Exception $e) {
            \Log::error('Lỗi khi đăng ký cộng tác viên: ' . $e->getMessage());

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

        $registration = CollaboratorRegistration::where('phone', $phone)->first();

        if (!$registration) {
            return response()->json(['error' => 'Không tìm thấy đăng ký với số điện thoại này'], 404);
        }

        return response()->json([
            'status' => $registration->status,
            'message' => $this->getStatusMessage($registration->status),
            'rejection_reason' => $registration->rejection_reason,
            'reviewed_at' => $registration->reviewed_at,
        ]);
    }

    /**
     * Lấy thông báo theo trạng thái
     */
    private function getStatusMessage(string $status): string {
        switch ($status) {
            case 'pending':
                return 'Đăng ký của bạn đang được xem xét. Vui lòng chờ phản hồi từ quản trị viên.';
            case 'approved':
                return 'Đăng ký của bạn đã được duyệt! Bạn đã trở thành cộng tác viên chính thức.';
            case 'rejected':
                return 'Đăng ký của bạn đã bị từ chối. Vui lòng liên hệ quản trị viên để biết thêm chi tiết.';
            default:
                return 'Trạng thái không xác định.';
        }
    }
}
