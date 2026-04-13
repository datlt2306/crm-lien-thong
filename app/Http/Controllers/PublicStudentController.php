<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\Collaborator;
use App\Models\Organization;

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

        $today = now()->toDateString();
        
        // Lấy danh sách đợt tuyển đang active của đơn vị
        $intakes = Intake::where('organization_id', $organization->id)
            ->where('end_date', '>=', $today)
            ->where('status', Intake::STATUS_ACTIVE)
            ->with(['quotas' => function ($q) {
                // List toàn bộ quota thuộc đợt để user nhìn đầy đủ ngành/hệ đang có.
                // Việc "có đăng ký được hay không" sẽ được thể hiện ở UI (disabled) + validate backend.
                $q->orderBy('major_name')->orderBy('program_name')->orderBy('name');
            }])
            ->orderBy('start_date')
            ->get();

        return view('ref-form', [
            'ref_id' => $ref_id,
            'collaborator' => $collaborator,
            'intakes' => $intakes,
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
            'intake_id' => [
                'required',
                Rule::exists('intakes', 'id')->where('organization_id', $collaborator->organization_id),
            ],
            'quota_id' => [
                'required',
                Rule::exists('quotas', 'id')
                    ->where('organization_id', $collaborator->organization_id)
                    ->where('status', Quota::STATUS_ACTIVE),
            ],
            'notes' => 'nullable|string',
        ], [
            'intake_id.required' => 'Vui lòng chọn đợt tuyển',
            'intake_id.exists' => 'Đợt tuyển không hợp lệ',
            'quota_id.required' => 'Vui lòng chọn chương trình đào tạo',
            'quota_id.exists' => 'Chương trình đào tạo không hợp lệ hoặc đã đóng',
            'phone.unique' => 'Số điện thoại đã tồn tại',
            'email.unique' => 'Email đã tồn tại',
        ]);

        // Xác thực organization phải là của collaborator
        if ($validated['organization_id'] != $collaborator->organization_id) {
            return back()->withErrors(['organization_id' => 'Bạn không được chọn đơn vị này.'])->withInput();
        }

        $selectedOrg = Organization::find($validated['organization_id']);
        $quota = Quota::find($validated['quota_id']);

        // Check if the quota belongs to the given intake 
        if ($quota->intake_id != $validated['intake_id']) {
             return back()->withErrors(['quota_id' => 'Chương trình đào tạo không thuộc đợt tuyển này.'])->withInput();
        }

        // Kiểm tra available slots (Nếu logic QuotaService cần, hoặc check trực tiếp)
        if ($quota->available_slots <= 0) {
            return back()->withErrors(['quota_id' => 'Chương trình đào tạo này đã hết chỉ tiêu!'])->withInput();
        }

        $notes = [];
        if (!empty($validated['notes'])) {
            $notes[] = $validated['notes'];
        }

        try {
            DB::transaction(function () use ($validated, $selectedOrg, $collaborator, $quota, $notes) {
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
                    'quota_id' => $quota->id,
                    'intake_id' => $validated['intake_id'],
                    'source' => 'ref',
                    'status' => 'new',
                    'notes' => !empty($notes) ? implode("\n", $notes) : null,
                ]);

                // Map program_type for Payment
                $programTypeMap = match (strtolower($quota->program_name ?? '')) {
                    'chính quy', 'hệ chính quy' => 'REGULAR',
                    'vừa học vừa làm', 'hệ vừa học vừa làm', 'bán thời gian' => 'PART_TIME',
                    default => 'REGULAR' 
                };

                \App\Models\Payment::firstOrCreate(
                    [
                        'student_id' => $student->id,
                    ],
                    [
                        'organization_id' => $student->organization_id,
                        'primary_collaborator_id' => $collaborator->id,
                        'program_type' => $programTypeMap,
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

        // Tạo hoặc cập nhật payment (tránh sinh bản ghi trùng lặp)
        $payment = Payment::updateOrCreate(
            ['student_id' => $student->id],
            [
                'organization_id' => $student->organization_id,
                'primary_collaborator_id' => $collaborator->id,
                'program_type' => $validated['program_type'],
                'amount' => $validated['amount'],
                'bill_path' => $path,
                'status' => Payment::STATUS_SUBMITTED,
            ]
        );

        // Chuyển status Học viên sang SUBMITTED (Đã nộp hồ sơ/Đang chờ duyệt)
        $student->update(['status' => Student::STATUS_SUBMITTED]);

        // Quota sẽ được trừ khi payment được verify (trong PaymentObserver)

        return redirect()->back()->with('success', 'Tải lên hóa đơn thành công! Chờ chủ đơn vị xác nhận.');
    }
}
