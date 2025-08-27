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
use App\Services\QuotaService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class PublicStudentController extends Controller {
    protected $refTrackingService;
    protected $quotaService;

    public function __construct(RefTrackingService $refTrackingService, QuotaService $quotaService) {
        $this->refTrackingService = $refTrackingService;
        $this->quotaService = $quotaService;
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
        $majors = $majorConfigs->map(function ($major) use ($organization, $programConfigs) {
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

            // Fallback: nếu ngành chưa gán chương trình cụ thể, dùng toàn bộ chương trình của đơn vị
            if (empty($programs)) {
                $programs = $programConfigs->map(function ($p) {
                    return [
                        'id' => $p->id,
                        'name' => $p->name,
                    ];
                })->toArray();
            }

            // Lấy quota hiện tại (còn lại)
            $currentQuota = $this->quotaService->getCurrentQuota($organization->id, $major->id);

            // Sắp xếp intake months theo ngành
            $intakes = json_decode($major->intake_months, true) ?? [];
            if (is_array($intakes)) {
                sort($intakes, SORT_NUMERIC);
            }

            return [
                'id' => $major->id,
                'name' => $major->name,
                'quota' => $currentQuota,
                'intake_months' => $intakes,
                'programs' => $programs
            ];
        });

        // Fallback: nếu đơn vị chưa cấu hình ngành, hiển thị tất cả ngành đang kích hoạt
        if ($majors->isEmpty()) {
            $majors = Major::query()
                ->when(\Illuminate\Support\Facades\Schema::hasColumn('majors', 'is_active'), fn($q) => $q->where('is_active', true))
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(function ($m) use ($organization) {
                    return [
                        'id' => $m->id,
                        'name' => $m->name,
                        'quota' => $this->quotaService->getCurrentQuota($organization->id, $m->id),
                        'intake_months' => [],
                        'programs' => [],
                    ];
                });
        }

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
                'major_count' => $majors->count(),
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
            'email' => 'nullable|email|max:255|unique:students,email',

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
            'phone.unique' => 'Số điện thoại đã tồn tại',
            'email.unique' => 'Email đã tồn tại',
        ]);

        // Xác thực organization phải là của collaborator
        if ($validated['organization_id'] != $collaborator->organization_id) {
            return back()->withErrors(['organization_id' => 'Bạn không được chọn đơn vị này.'])->withInput();
        }

        $selectedOrg = Organization::find($validated['organization_id']);
        if (!empty($validated['major_id']) && !$selectedOrg?->majors()->where('majors.id', $validated['major_id'])->exists()) {
            return back()->withErrors(['major_id' => 'Ngành không thuộc đơn vị này'])->withInput();
        }
        if (!empty($validated['program_id'])) {
            $programValid = false;

            // 1) Hợp lệ nếu chương trình được gán cho ngành trong đơn vị (major_organization_program)
            if (!empty($validated['major_id'])) {
                $programValid = DB::table('major_organization_program')
                    ->join('major_organization', 'major_organization_program.major_organization_id', '=', 'major_organization.id')
                    ->where('major_organization.organization_id', $selectedOrg->id)
                    ->where('major_organization.major_id', $validated['major_id'])
                    ->where('major_organization_program.program_id', $validated['program_id'])
                    ->exists();
            }

            // 2) Fallback: hoặc chương trình thuộc đơn vị (organization_program)
            if (!$programValid) {
                $programValid = DB::table('organization_program')
                    ->where('organization_id', $selectedOrg->id)
                    ->where('program_id', $validated['program_id'])
                    ->exists();
            }

            if (!$programValid) {
                return back()->withErrors([
                    'program_id' => 'Hệ đào tạo không thuộc đơn vị này hoặc chưa được cấu hình cho ngành đã chọn',
                ])->withInput();
            }
        }

        // Kiểm tra quota của ngành
        if (!empty($validated['major_id'])) {
            $major = Major::find($validated['major_id']);
            if ($major && !$this->quotaService->hasQuota($selectedOrg->id, $major->id)) {
                return back()->withErrors(['major_id' => 'Ngành này đã hết chỉ tiêu!'])->withInput();
            }
        }

        $selectedMajorName = null;
        if (!empty($validated['major_id'])) {
            $selectedMajorName = Major::where('id', $validated['major_id'])->value('name');
        }
        $selectedProgramName = null;
        $selectedProgramCode = null;
        if (!empty($validated['program_id'])) {
            $selectedProgramName = Program::where('id', $validated['program_id'])->value('name');
            $selectedProgramCode = Program::where('id', $validated['program_id'])->value('code');
        }

        $notes = [];
        if (!empty($validated['notes'])) {
            $notes[] = $validated['notes'];
        }
        // Không thêm hệ/đợt vào ghi chú theo yêu cầu hiển thị riêng

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
            'program_type' => $selectedProgramCode,
            'intake_month' => $validated['intake_month'] ?? null,
            'source' => 'ref',
            'status' => 'new',
            'notes' => !empty($notes) ? implode("\n", $notes) : null,
        ]);

        // Xác định CTV cấp 1 (upline) và cấp 2 (nếu có)
        $primaryCollaboratorId = $collaborator->upline_id ? $collaborator->upline_id : $collaborator->id;
        $subCollaboratorId = $collaborator->upline_id ? $collaborator->id : null;

        \App\Models\Payment::firstOrCreate(
            [
                'student_id' => $student->id,
            ],
            [
                'organization_id' => $student->organization_id,
                'primary_collaborator_id' => $primaryCollaboratorId,
                'sub_collaborator_id' => $subCollaboratorId,
                'program_type' => $selectedProgramCode ?? 'REGULAR',
                'amount' => 0,
                'status' => \App\Models\Payment::STATUS_NOT_PAID,
            ]
        );

        // Xóa cookie sau khi đăng ký thành công
        $this->refTrackingService->clearRefCookie();

        return redirect()->route('public.success', ['type' => 'registration']);
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

        // Xác định CTV cấp 1 (upline) và cấp 2 (nếu có)
        $primaryId = $collaborator->upline_id ? $collaborator->upline_id : $collaborator->id;
        $subId = $collaborator->upline_id ? $collaborator->id : null;

        // Tạo payment SUBMITTED
        $payment = Payment::create([
            'organization_id' => $student->organization_id,
            'student_id' => $student->id,
            'primary_collaborator_id' => $primaryId,
            'sub_collaborator_id' => $subId,
            'program_type' => $validated['program_type'],
            'amount' => $validated['amount'],
            'bill_path' => $path,
            'status' => 'SUBMITTED',
        ]);

        // Giảm quota của ngành khi nộp tiền thành công
        $this->quotaService->decreaseQuotaOnPaymentSubmission($payment);

        return redirect()->route('public.success', ['type' => 'payment', 'ref_id' => $ref_id]);
    }
}
