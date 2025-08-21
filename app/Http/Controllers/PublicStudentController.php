<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Collaborator;
use App\Models\Organization;
use App\Models\Major;
use App\Models\Program;
use App\Models\Student;
use App\Models\Payment;
use Illuminate\Support\Facades\Storage;

class PublicStudentController extends Controller {
    public function showForm($ref_id) {
        $collaborator = Collaborator::where('ref_id', $ref_id)->first();
        if (!$collaborator) {
            abort(404, 'Liên kết không hợp lệ!');
        }

        // Tập tổ chức được phép: many-to-many nếu có, fallback 1 tổ chức
        $allowedOrgs = collect();
        if (method_exists($collaborator, 'organizations')) {
            try {
                $allowedOrgs = $collaborator->organizations()->select('id', 'name')->get();
            } catch (\Throwable $e) {
                $allowedOrgs = collect();
            }
        }
        if ($allowedOrgs->isEmpty() && $collaborator->organization) {
            $allowedOrgs = collect([['id' => $collaborator->organization->id, 'name' => $collaborator->organization->name]]);
        }

        // Majors/Programs cho từng tổ chức được phép
        $majorsByOrg = [];
        $programsByOrg = [];
        foreach ($allowedOrgs as $o) {
            $org = Organization::with(['majors:id,name', 'programs:id,name'])->find($o['id'] ?? $o->id);
            if ($org) {
                $majorsByOrg[$org->id] = $org->majors->map(fn($m) => ['id' => $m->id, 'name' => $m->name])->values();
                $programsByOrg[$org->id] = $org->programs->map(fn($p) => ['id' => $p->id, 'name' => $p->name])->values();
            }
        }

        $defaultOrganizationId = ($allowedOrgs->first()['id'] ?? $allowedOrgs->first()->id ?? null);

        return view('ref-form', [
            'ref_id' => $ref_id,
            'collaborator' => $collaborator,
            'organizations' => $allowedOrgs,
            'majorsByOrg' => $majorsByOrg,
            'programsByOrg' => $programsByOrg,
            'defaultOrganizationId' => $defaultOrganizationId,
        ]);
    }

    public function submitForm($ref_id, Request $request) {
        $collaborator = Collaborator::where('ref_id', $ref_id)->first();
        if (!$collaborator) {
            return back()->withErrors(['ref_id' => 'Liên kết không hợp lệ!']);
        }
        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'dob' => 'required|date',
            'address' => 'required|string|max:255',
            'phone' => 'required|string|max:20|unique:students,phone',
            'email' => 'nullable|email|max:255',
            'current_college' => 'nullable|string|max:255',
            'organization_id' => 'required|exists:organizations,id',
            'major_id' => 'nullable|exists:majors,id',
            'program_id' => 'nullable|exists:programs,id',
            'notes' => 'nullable|string',
        ]);

        // Xác thực organization thuộc quyền của CTV
        $allowedOrgIds = [];
        if (method_exists($collaborator, 'organizations')) {
            $allowedOrgIds = $collaborator->organizations()->pluck('organizations.id')->toArray();
        }
        if (empty($allowedOrgIds) && $collaborator->organization_id) {
            $allowedOrgIds = [$collaborator->organization_id];
        }
        if (!in_array($validated['organization_id'], $allowedOrgIds, true)) {
            return back()->withErrors(['organization_id' => 'Bạn không được chọn đơn vị này.'])->withInput();
        }

        $selectedOrg = Organization::find($validated['organization_id']);
        if (!empty($validated['major_id']) && !$selectedOrg?->majors()->where('majors.id', $validated['major_id'])->exists()) {
            return back()->withErrors(['major_id' => 'Ngành không thuộc đơn vị này'])->withInput();
        }
        if (!empty($validated['program_id']) && !$selectedOrg?->programs()->where('programs.id', $validated['program_id'])->exists()) {
            return back()->withErrors(['program_id' => 'Hệ đào tạo không thuộc đơn vị này'])->withInput();
        }

        $selectedMajorName = null;
        if (!empty($validated['major_id'])) {
            $selectedMajorName = Major::where('id', $validated['major_id'])->value('name');
        }
        $selectedProgramName = null;
        if (!empty($validated['program_id'])) {
            $selectedProgramName = Program::where('id', $validated['program_id'])->value('name');
        }

        $student = Student::create([
            'full_name' => $validated['full_name'],
            'dob' => $validated['dob'],
            'address' => $validated['address'],
            'phone' => $validated['phone'],
            'email' => $validated['email'] ?? null,
            'organization_id' => $selectedOrg?->id,
            'collaborator_id' => $collaborator->id,
            'current_college' => $validated['current_college'] ?? null,
            'target_university' => $selectedOrg?->name,
            'major' => $selectedMajorName,
            'source' => 'ref',
            'status' => 'new',
            'notes' => trim(($validated['notes'] ?? '') . ($selectedProgramName ? ("\nChọn hệ đào tạo: " . $selectedProgramName) : '')) ?: null,
        ]);
        return redirect()->back()->with('success', 'Đăng ký thành công! Chúng tôi sẽ liên hệ với bạn sớm nhất.');
    }

    /**
     * Hiển thị form upload bill thanh toán cho sinh viên đã đăng ký.
     */
    public function showPaymentForm(string $ref_id) {
        $collaborator = Collaborator::where('ref_id', $ref_id)->first();
        if (!$collaborator) {
            abort(404, 'Liên kết không hợp lệ!');
        }

        return view('ref-payment', [
            'ref_id' => $ref_id,
            'collaborator' => $collaborator,
        ]);
    }

    /**
     * Nhận bill thanh toán từ sinh viên, tạo Payment ở trạng thái SUBMITTED.
     */
    public function submitPayment(string $ref_id, Request $request) {
        $collaborator = Collaborator::where('ref_id', $ref_id)->first();
        if (!$collaborator) {
            return back()->withErrors(['ref_id' => 'Liên kết không hợp lệ!']);
        }

        $validated = $request->validate([
            'phone' => 'required|string|max:20',
            'amount' => 'required|numeric|min:1000',
            'program_type' => 'required|in:REGULAR,PART_TIME',
            'bill' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        // Tìm student theo số điện thoại và collaborator hiện tại
        $student = Student::where('phone', $validated['phone'])
            ->where('collaborator_id', $collaborator->id)
            ->first();

        if (!$student) {
            return back()->withErrors(['phone' => 'Không tìm thấy hồ sơ sinh viên. Vui lòng gửi form đăng ký trước.']);
        }

        // Lưu bill
        $path = $request->file('bill')->store('bills', 'public');

        // Tạo payment SUBMITTED
        Payment::create([
            'organization_id' => $student->organization_id,
            'student_id' => $student->id,
            'primary_collaborator_id' => $collaborator->id,
            'sub_collaborator_id' => null,
            'program_type' => $validated['program_type'],
            'amount' => $validated['amount'],
            'bill_path' => $path,
            'status' => 'SUBMITTED',
        ]);

        return redirect()->back()->with('success', 'Tải lên hóa đơn thành công! Chờ chủ đơn vị xác nhận.');
    }
}
