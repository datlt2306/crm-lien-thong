<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Collaborator;
use App\Models\Organization;
use App\Services\RefTrackingService;

class PublicCollaboratorController extends Controller {
    protected $refTrackingService;

    public function __construct(RefTrackingService $refTrackingService) {
        $this->refTrackingService = $refTrackingService;
    }

    public function showRegisterForm() {
        $organizations = Organization::orderBy('name')->pluck('name', 'id')->toArray();
        return view('ctv-register', [
            'organizations' => $organizations,
        ]);
    }

    public function submitRegister(Request $request) {
        $data = $request->validate([
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:collaborators,email',
            'phone' => 'required|string|max:20|unique:collaborators,phone',
            'organization_id' => 'nullable|exists:organizations,id',
            'password' => 'required|string|min:6|confirmed',
            'upline_ref' => 'nullable|string|max:32',
            'note' => 'nullable|string',
        ]);

        // Lấy collaborator từ cookie hoặc upline_ref
        $uplineId = null;
        $uplineRef = $data['upline_ref'] ?: $this->refTrackingService->getRefFromCookie($request);

        if (!empty($uplineRef)) {
            $uplineId = Collaborator::where('ref_id', $uplineRef)->value('id');
        }

        Collaborator::create([
            'full_name' => $data['full_name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'organization_id' => $data['organization_id'] ?? null,
            'upline_id' => $uplineId,
            'note' => $data['note'] ?? null,
            'status' => 'active',
        ]);

        // Xóa cookie sau khi đăng ký thành công
        $this->refTrackingService->clearRefCookie();

        return redirect()->back()->with('success', 'Đăng ký CTV thành công! Hệ thống sẽ liên hệ xác minh.');
    }

    public function showRefRegister(string $ref_id) {
        // Lưu ref_id vào cookie
        $this->refTrackingService->setRefCookie(request(), $ref_id);

        $upline = Collaborator::where('ref_id', $ref_id)->firstOrFail();
        $org = $upline->organization;
        return view('ctv-register-ref', [
            'upline' => $upline,
            'organization' => $org,
        ]);
    }

    public function submitRefRegister(string $ref_id, Request $request) {
        // Lấy collaborator từ ref_id hoặc cookie
        $upline = $this->refTrackingService->getCollaborator($request, $ref_id);

        if (!$upline) {
            return redirect()->back()->withErrors(['ref_id' => 'Liên kết không hợp lệ!'])->withInput();
        }

        $org = $upline->organization;
        $data = $request->validate([
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:collaborators,email',
            'phone' => 'required|string|max:20|unique:collaborators,phone',
            'password' => 'required|string|min:6|confirmed',
            'note' => 'nullable|string',
        ]);

        Collaborator::create([
            'full_name' => $data['full_name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'organization_id' => $org?->id,
            'upline_id' => $upline->id,
            'note' => $data['note'] ?? null,
            'status' => 'active',
        ]);

        // Xóa cookie sau khi đăng ký thành công
        $this->refTrackingService->clearRefCookie();

        return redirect()->back()->with('success', 'Đăng ký CTV thành công! Hệ thống sẽ liên hệ xác minh.');
    }
}
