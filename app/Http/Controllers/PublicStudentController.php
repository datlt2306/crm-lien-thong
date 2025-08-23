<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Collaborator;
use App\Models\Organization;
use App\Models\Major;
use App\Models\Program;
use App\Models\Student;
use App\Models\Payment;
use App\Services\RefTrackingService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class PublicStudentController extends Controller {
    protected $refTrackingService;

    public function __construct(RefTrackingService $refTrackingService) {
        $this->refTrackingService = $refTrackingService;
    }

    public function showForm($ref_id) {
        // Lưu ref_id vào cookie
        $this->refTrackingService->setRefCookie(request(), $ref_id);
        $collaborator = Collaborator::where('ref_id', $ref_id)->first();
        if (!$collaborator) {
            abort(404, 'Liên kết không hợp lệ!');
        }

        // Lấy organization của collaborator
        $organization = $collaborator->organization;
        if (!$organization) {
            abort(404, 'Cộng tác viên chưa được gán vào tổ chức!');
        }

        // Lấy majors và programs theo cấu hình major_organization
        $majorConfigs = DB::table('major_organization')
            ->join('majors', 'major_organization.major_id', '=', 'majors.id')
            ->where('major_organization.organization_id', $organization->id)
            ->select('majors.id', 'majors.name', 'major_organization.quota', 'major_organization.intake_months')
            ->get();

        // Lấy programs theo cấu hình organization_program (fallback cho backward compatibility)
        $programConfigs = DB::table('organization_program')
            ->join('programs', 'organization_program.program_id', '=', 'programs.id')
            ->where('organization_program.organization_id', $organization->id)
            ->select('programs.id', 'programs.name')
            ->get();

        // Chuẩn bị dữ liệu cho view
        $majors = $majorConfigs->map(function ($major) use ($organization) {
            // Lấy programs cho major này từ bảng pivot mới
            $programs = DB::table('major_organization_program')
                ->join('major_organization', 'major_organization_program.major_organization_id', '=', 'major_organization.id')
                ->join('programs', 'major_organization_program.program_id', '=', 'programs.id')
                ->where('major_organization.organization_id', $organization->id)
                ->where('major_organization.major_id', $major->id)
                ->select('programs.id', 'programs.name')
                ->get()
                ->map(function ($program) {
                    return [
                        'id' => $program->id,
                        'name' => $program->name
                    ];
                })
                ->toArray();

            return [
                'id' => $major->id,
                'name' => $major->name,
                'quota' => $major->quota,
                'intake_months' => json_decode($major->intake_months, true) ?? [],
                'programs' => $programs
            ];
        });

        $programs = $programConfigs->map(function ($program) {
            return [
                'id' => $program->id,
                'name' => $program->name
            ];
        });

        // Lấy thông tin chi tiết về majors để hiển thị trong debug
        $majorDetails = $majorConfigs->map(function ($major) {
            $months = json_decode($major->intake_months, true) ?? [];
            sort($months, SORT_NUMERIC);
            return [
                'id' => $major->id,
                'name' => $major->name,
                'quota' => $major->quota,
                'intake_months' => $months
            ];
        });

        // Lấy tất cả đợt tuyển từ cấu hình majors
        $intakeMonths = [];
        foreach ($majorConfigs as $major) {
            $months = json_decode($major->intake_months, true);
            if (is_array($months)) {
                $intakeMonths = array_merge($intakeMonths, $months);
            }
        }
        $intakeMonths = array_unique($intakeMonths);
        sort($intakeMonths, SORT_NUMERIC);

        return view('ref-form', [
            'ref_id' => $ref_id,
            'collaborator' => $collaborator,
            'majors' => $majors,
            'programs' => $programs,
            'intakeMonths' => $intakeMonths,
            // Debug info - có thể bỏ sau
            'debug' => [
                'organization_id' => $organization->id,
                'major_count' => $majorConfigs->count(),
                'program_count' => $programConfigs->count(),
                'major_details' => $majorDetails,
            ]
        ]);
    }

    public function submitForm($ref_id, Request $request) {
        // Lấy collaborator từ ref_id hoặc cookie
        $collaborator = $this->refTrackingService->getCollaborator($request, $ref_id);

        if (!$collaborator) {
            return back()->withErrors(['ref_id' => 'Liên kết không hợp lệ!']);
        }
        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'dob' => 'required|date',
            'address' => 'required|string|max:255',
            'phone' => 'required|string|max:20|unique:students,phone',
            'email' => 'nullable|email|max:255',

            'organization_id' => 'required|exists:organizations,id',
            'major_id' => 'required|exists:majors,id',
            'program_id' => 'required|exists:programs,id',
            'intake_month' => 'required|integer|between:1,12',
            'notes' => 'nullable|string',
        ], [
            'major_id.required' => 'Vui lòng chọn ngành muốn học',
            'major_id.exists' => 'Ngành đã chọn không hợp lệ',
            'program_id.required' => 'Vui lòng chọn hệ đào tạo',
            'program_id.exists' => 'Hệ đào tạo đã chọn không hợp lệ',
            'intake_month.required' => 'Vui lòng chọn đợt tuyển',
            'intake_month.integer' => 'Đợt tuyển phải là số tháng hợp lệ',
            'intake_month.between' => 'Đợt tuyển phải từ tháng 1 đến tháng 12',
        ]);

        // Xác thực organization phải là của collaborator
        if ($validated['organization_id'] != $collaborator->organization_id) {
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

        $notes = [];
        if (!empty($validated['notes'])) {
            $notes[] = $validated['notes'];
        }
        if (!empty($selectedProgramName)) {
            $notes[] = "Chọn hệ đào tạo: " . $selectedProgramName;
        }
        if (!empty($validated['intake_month'])) {
            $notes[] = "Đợt tuyển: Tháng " . $validated['intake_month'];
        }

        $student = Student::create([
            'full_name' => $validated['full_name'],
            'dob' => $validated['dob'],
            'address' => $validated['address'],
            'phone' => $validated['phone'],
            'email' => $validated['email'] ?? null,
            'organization_id' => $selectedOrg?->id,
            'collaborator_id' => $collaborator->id,

            'target_university' => $selectedOrg?->name,
            'major' => $selectedMajorName,
            'source' => 'ref',
            'status' => 'new',
            'notes' => !empty($notes) ? implode("\n", $notes) : null,
        ]);

        // Xóa cookie sau khi đăng ký thành công
        $this->refTrackingService->clearRefCookie();

        return redirect()->back()->with('success', 'Đăng ký thành công! Chúng tôi sẽ liên hệ với bạn sớm nhất.');
    }

    /**
     * Hiển thị form upload bill thanh toán cho sinh viên đã đăng ký.
     */
    public function showPaymentForm(string $ref_id) {
        // Lấy collaborator từ ref_id hoặc cookie
        $collaborator = $this->refTrackingService->getCollaborator(request(), $ref_id);

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
        // Lấy collaborator từ ref_id hoặc cookie
        $collaborator = $this->refTrackingService->getCollaborator($request, $ref_id);

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
