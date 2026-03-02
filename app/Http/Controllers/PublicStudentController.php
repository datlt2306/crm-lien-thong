<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\Collaborator;
use App\Models\Organization;
use App\Models\Major;
use App\Models\Program;
use App\Models\Student;
use App\Models\Payment;
use App\Models\AnnualQuota;
use App\Models\Intake;
use App\Models\Quota;
use App\Services\RefTrackingService;
use App\Services\QuotaService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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
                ->map(fn($p) => ['id' => $p->id, 'name' => $p->name])
                ->toArray();

            // Sắp xếp intake months theo ngành (legacy từ major_organization)
            $intakeMonthsLegacy = json_decode($major->intake_months ?? '[]', true) ?? [];
            if (is_array($intakeMonthsLegacy)) {
                sort($intakeMonthsLegacy, SORT_NUMERIC);
            }

            $intakesFromQuotas = [];
            $year = (int) now()->format('Y');
            $today = now()->toDateString();

            // Ưu tiên: Chỉ tiêu năm (annual_quotas) cho programs; bảng intakes cho đợt tuyển
            if (Schema::hasTable('annual_quotas')) {
                $programIds = DB::table('annual_quotas')
                    ->where('organization_id', $organization->id)
                    ->where('major_id', $major->id)
                    ->where('year', $year)
                    ->where('status', AnnualQuota::STATUS_ACTIVE)
                    ->whereRaw('target_quota > current_quota')
                    ->pluck('program_id')
                    ->unique()
                    ->values();
                if ($programIds->isNotEmpty()) {
                    $programs = DB::table('programs')
                        ->whereIn('id', $programIds)
                        ->get(['id', 'name'])
                        ->map(fn($p) => ['id' => $p->id, 'name' => $p->name])
                        ->toArray();
                }
            }

            // Đợt tuyển: lấy trực tiếp từ bảng intakes theo organization, năm hiện tại và còn hạn
            if (Schema::hasTable('intakes')) {
                $intakesFromQuotas = DB::table('intakes')
                    ->where('organization_id', $organization->id)
                    ->whereYear('start_date', $year)
                    ->where('end_date', '>=', $today)
                    ->where('status', Intake::STATUS_ACTIVE)
                    ->orderBy('start_date')
                    ->get(['id', 'name', 'start_date', 'end_date'])
                    ->map(fn($i) => [
                        'id' => $i->id,
                        'name' => $i->name,
                        'start_date' => $i->start_date,
                        'end_date' => $i->end_date,
                    ])
                    ->toArray();
            }

            // Fallback: nếu chưa có annual, lấy programs từ quotas (chỉ tiêu theo đợt cũ)
            if (empty($programs) && Schema::hasTable('quotas') && Schema::hasTable('intakes')) {
                $rows = DB::table('quotas')
                    ->join('intakes', 'quotas.intake_id', '=', 'intakes.id')
                    ->where('quotas.organization_id', $organization->id)
                    ->where('quotas.major_id', $major->id)
                    ->where('intakes.end_date', '>=', $today)
                    ->where('quotas.status', Quota::STATUS_ACTIVE)
                    ->where('intakes.status', Intake::STATUS_ACTIVE)
                    ->select('quotas.program_id')
                    ->get();
                $programIds = $rows->pluck('program_id')->filter()->unique()->values();
                if ($programIds->isNotEmpty()) {
                    $programs = DB::table('programs')->whereIn('id', $programIds)->get(['id', 'name'])
                        ->map(fn($p) => ['id' => $p->id, 'name' => $p->name])->toArray();
                }
            }

            // Fallback programs: nếu chưa có từ annual hay quotas
            if (empty($programs)) {
                $programs = $programConfigs->map(fn($p) => ['id' => $p->id, 'name' => $p->name])->toArray();
            }

            $currentQuota = $this->quotaService->getCurrentQuota($organization->id, $major->id);

            return [
                'id' => $major->id,
                'name' => $major->name,
                'quota' => $currentQuota,
                'intake_months' => $intakeMonthsLegacy,
                'intakes' => $intakesFromQuotas,
                'programs' => $programs,
            ];
        });

        // Fallback: nếu đơn vị chưa cấu hình ngành, hiển thị ngành từ annual_quotas hoặc tất cả ngành đang kích hoạt
        if ($majors->isEmpty()) {
            $today = now()->toDateString();
            $year = (int) now()->format('Y');

            // Ưu tiên: Lấy majors từ annual_quotas
            $majorIdsFromAnnual = DB::table('annual_quotas')
                ->where('organization_id', $organization->id)
                ->where('year', $year)
                ->where('status', AnnualQuota::STATUS_ACTIVE)
                ->whereRaw('target_quota > current_quota')
                ->pluck('major_id')
                ->unique()
                ->values();

            if ($majorIdsFromAnnual->isNotEmpty()) {
                $majors = Major::whereIn('id', $majorIdsFromAnnual)
                    ->when(Schema::hasColumn('majors', 'is_active'), fn($q) => $q->where('is_active', true))
                    ->orderBy('name')
                    ->get(['id', 'name'])
                    ->map(function ($m) use ($organization, $today, $year, $programConfigs) {
                        // Lấy programs từ annual_quotas cho major này
                        $programIds = DB::table('annual_quotas')
                            ->where('organization_id', $organization->id)
                            ->where('major_id', $m->id)
                            ->where('year', $year)
                            ->where('status', AnnualQuota::STATUS_ACTIVE)
                            ->whereRaw('target_quota > current_quota')
                            ->pluck('program_id')
                            ->unique()
                            ->values();

                        $programs = DB::table('programs')
                            ->whereIn('id', $programIds)
                            ->get(['id', 'name'])
                            ->map(fn($p) => ['id' => $p->id, 'name' => $p->name])
                            ->toArray();

                        // Lấy intakes còn hạn của organization (không phụ thuộc vào quotas)
                        $intakesFromOrg = DB::table('intakes')
                            ->where('organization_id', $organization->id)
                            ->whereYear('start_date', $year)
                            ->where('end_date', '>=', $today)
                            ->where('status', Intake::STATUS_ACTIVE)
                            ->orderBy('start_date')
                            ->get(['id', 'name', 'start_date', 'end_date'])
                            ->map(fn($i) => [
                                'id' => $i->id,
                                'name' => $i->name,
                                'start_date' => $i->start_date,
                                'end_date' => $i->end_date,
                            ])
                            ->toArray();

                        return [
                            'id' => $m->id,
                            'name' => $m->name,
                            'quota' => $this->quotaService->getCurrentQuota($organization->id, $m->id),
                            'intake_months' => [],
                            'intakes' => $intakesFromOrg,
                            'programs' => $programs,
                        ];
                    });
            } else {
                // Fallback cuối cùng: lấy tất cả ngành active
                $majors = Major::query()
                    ->when(Schema::hasColumn('majors', 'is_active'), fn($q) => $q->where('is_active', true))
                    ->orderBy('name')
                    ->get(['id', 'name'])
                    ->map(fn($m) => [
                        'id' => $m->id,
                        'name' => $m->name,
                        'quota' => $this->quotaService->getCurrentQuota($organization->id, $m->id),
                        'intake_months' => [],
                        'intakes' => [],
                        'programs' => [],
                    ]);
            }
        }

        $programs = $programConfigs->map(function ($program) {
            return [
                'id' => $program->id,
                'name' => $program->name
            ];
        });

        // Lấy thông tin chi tiết về majors để hiển thị trong debug
        $majorDetails = $majorConfigs->map(function ($major) {
            return [
                'id' => $major->id,
                'name' => $major->name,
                'quota' => $major->quota,
            ];
        });

        return view('ref-form', [
            'ref_id' => $ref_id,
            'collaborator' => $collaborator,
            'majors' => $majors,
            'programs' => $programs,
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
            'intake_id' => [
                'required',
                Rule::exists('intakes', 'id')->where('organization_id', $collaborator->organization_id),
            ],
            'notes' => 'nullable|string',
        ], [
            'major_id.required' => 'Vui lòng chọn ngành muốn học',
            'major_id.exists' => 'Ngành đã chọn không hợp lệ',
            'program_id.required' => 'Vui lòng chọn hệ đào tạo',
            'program_id.exists' => 'Hệ đào tạo đã chọn không hợp lệ',
            'intake_id.required' => 'Vui lòng chọn đợt tuyển',
            'intake_id.exists' => 'Đợt tuyển không hợp lệ',
            'phone.unique' => 'Số điện thoại đã tồn tại',
            'email.unique' => 'Email đã tồn tại',
        ]);

        // Xác thực organization phải là của collaborator
        if ($validated['organization_id'] != $collaborator->organization_id) {
            return back()->withErrors(['organization_id' => 'Bạn không được chọn đơn vị này.'])->withInput();
        }

        $selectedOrg = Organization::find($validated['organization_id']);
        $year = (int) now()->format('Y');

        // Kiểm tra ngành có trong annual_quotas của đơn vị (hoặc fallback major_organization)
        if (!empty($validated['major_id'])) {
            $majorValid = DB::table('annual_quotas')
                ->where('organization_id', $selectedOrg->id)
                ->where('major_id', $validated['major_id'])
                ->where('year', $year)
                ->where('status', AnnualQuota::STATUS_ACTIVE)
                ->exists();

            // Fallback: kiểm tra major_organization
            if (!$majorValid) {
                $majorValid = $selectedOrg->majors()->where('majors.id', $validated['major_id'])->exists();
            }

            if (!$majorValid) {
                return back()->withErrors(['major_id' => 'Ngành không thuộc đơn vị này hoặc chưa được cấu hình chỉ tiêu'])->withInput();
            }
        }

        // Kiểm tra hệ đào tạo có trong annual_quotas
        if (!empty($validated['program_id'])) {
            $programValid = false;

            // 1) Ưu tiên: Kiểm tra từ annual_quotas
            if (!empty($validated['major_id'])) {
                $programValid = DB::table('annual_quotas')
                    ->where('organization_id', $selectedOrg->id)
                    ->where('major_id', $validated['major_id'])
                    ->where('program_id', $validated['program_id'])
                    ->where('year', $year)
                    ->where('status', AnnualQuota::STATUS_ACTIVE)
                    ->exists();
            }

            // 2) Fallback: major_organization_program
            if (!$programValid && !empty($validated['major_id'])) {
                $programValid = DB::table('major_organization_program')
                    ->join('major_organization', 'major_organization_program.major_organization_id', '=', 'major_organization.id')
                    ->where('major_organization.organization_id', $selectedOrg->id)
                    ->where('major_organization.major_id', $validated['major_id'])
                    ->where('major_organization_program.program_id', $validated['program_id'])
                    ->exists();
            }

            // 3) Fallback: organization_program
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

        // Kiểm tra quota (org, ngành, hệ) – ưu tiên chỉ tiêu năm
        if (!empty($validated['major_id']) && !empty($validated['program_id'])) {
            if (!$this->quotaService->hasQuota($selectedOrg->id, (int) $validated['major_id'], (int) $validated['program_id'])) {
                return back()->withErrors(['major_id' => 'Ngành này đã hết chỉ tiêu cho hệ đào tạo đã chọn!'])->withInput();
            }
        }

        $selectedMajorName = null;
        if (!empty($validated['major_id'])) {
            $selectedMajorName = Major::where('id', $validated['major_id'])->value('name');
        }
        $selectedProgramName = null;
        $selectedProgramType = null;
        if (!empty($validated['program_id'])) {
            $program = Program::find($validated['program_id']);
            if ($program) {
                $selectedProgramName = $program->name;
                // Map tên hệ đào tạo sang mã enum
                $selectedProgramType = match (strtolower($program->name)) {
                    'chính quy', 'hệ chính quy' => 'REGULAR',
                    'vừa học vừa làm', 'hệ vừa học vừa làm', 'bán thời gian' => 'PART_TIME',
                    default => 'REGULAR' // Default fallback
                };
            }
        }

        $notes = [];
        if (!empty($validated['notes'])) {
            $notes[] = $validated['notes'];
        }

        $intakeId = $validated['intake_id'];
        $intake = Intake::find($intakeId);
        $intakeMonth = $intake?->start_date?->format('n');

        try {
            DB::transaction(function () use ($validated, $selectedOrg, $collaborator, $selectedMajorName, $selectedProgramType, $intakeId, $intakeMonth, $notes) {
                $student = Student::create([
                    'full_name' => $validated['full_name'],
                    'dob' => $validated['dob'],
                    'address' => $validated['address'],
                    'phone' => $validated['phone'],
                    'email' => $validated['email'] ?? null,
                    'organization_id' => $selectedOrg?->id,
                    'collaborator_id' => $collaborator->id,
                    'instructor' => $collaborator->full_name ?? null,

                    'target_university' => $selectedOrg?->name,
                    'major_id' => $validated['major_id'],
                    'program_id' => $validated['program_id'] ?? null,
                    'major' => $selectedMajorName,
                    'program_type' => $selectedProgramType,
                    'intake_id' => $intakeId,
                    'intake_month' => $intakeMonth,
                    'source' => 'ref',
                    'status' => 'new',
                    'notes' => !empty($notes) ? implode("\n", $notes) : null,
                ]);

                \App\Models\Payment::firstOrCreate(
                    [
                        'student_id' => $student->id,
                    ],
                    [
                        'organization_id' => $student->organization_id,
                        'primary_collaborator_id' => $collaborator->id,
                        'program_type' => $selectedProgramType ?? 'REGULAR',
                        'amount' => 0,
                        'status' => \App\Models\Payment::STATUS_NOT_PAID,
                    ]
                );
            });
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['system_error' => 'Có lỗi xảy ra trong quá trình lưu hồ sơ, vui lòng thử lại!'])->withInput();
        }

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
            'program_type' => 'required|string',
            'bill' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        // Map program_type từ tên tiếng Việt sang mã enum
        $validated['program_type'] = match (strtolower($validated['program_type'])) {
            'chính quy', 'hệ chính quy' => 'REGULAR',
            'vừa học vừa làm', 'hệ vừa học vừa làm', 'bán thời gian' => 'PART_TIME',
            default => 'REGULAR' // Default fallback
        };

        // Tìm student theo số điện thoại và collaborator hiện tại
        $student = Student::where('phone', $validated['phone'])
            ->where('collaborator_id', $collaborator->id)
            ->first();

        if (!$student) {
            return back()->withErrors(['phone' => 'Không tìm thấy hồ sơ sinh viên. Vui lòng gửi form đăng ký trước.']);
        }

        // Lưu bill với extension hợp lệ và tên ngẫu nhiên UUID để chống RCE, Directory Traversal
        $extension = $request->file('bill')->extension();
        $safeFileName = \Illuminate\Support\Str::uuid() . '.' . $extension;
        $path = $request->file('bill')->storeAs('bills', $safeFileName, 'public');

        // Tạo payment SUBMITTED
        $payment = Payment::create([
            'organization_id' => $student->organization_id,
            'student_id' => $student->id,
            'primary_collaborator_id' => $collaborator->id,
            'program_type' => $validated['program_type'],
            'amount' => $validated['amount'],
            'bill_path' => $path,
            'status' => 'SUBMITTED',
        ]);

        // Quota sẽ được trừ khi payment được verify (trong PaymentObserver)

        return redirect()->back()->with('success', 'Tải lên hóa đơn thành công! Chờ chủ đơn vị xác nhận.');
    }
}
