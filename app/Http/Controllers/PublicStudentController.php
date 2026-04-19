<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\Collaborator;

use App\Models\Student;
use App\Models\Payment;
use App\Models\AnnualQuota;
use App\Models\Intake;
use App\Models\Quota;
use App\Services\RefTrackingService;
use App\Services\QuotaService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
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

        // Lấy danh sách đợt tuyển có thể đăng ký.
        $intakes = Intake::whereIn('status', [Intake::STATUS_ACTIVE, Intake::STATUS_UPCOMING])
            ->with(['quotas' => function ($q) {
                // Chỉ lấy các chỉ tiêu đang mở và CÒN CHỖ
                $q->where('status', 'active')
                  ->whereColumn('current_quota', '<', 'target_quota');
            }])
            ->orderBy('start_date')
            ->get();

        // Tạo danh sách các "Chương trình" duy nhất (Major + Program Name)
        $programs = [];
        
        // 1. Nhặt từ các Đợt tuyển sinh đang mở
        foreach ($intakes as $intake) {
            foreach ($intake->quotas as $quota) {
                $key = $quota->major_name . '|' . $quota->program_name;
                if (!isset($programs[$key])) {
                    $programs[$key] = [
                        'major_name' => $quota->major_name,
                        'program_name' => $quota->program_name,
                        'label' => $quota->major_name . ' - ' . $this->mapProgramTypeLabel($quota->program_name),
                    ];
                }
            }
        }

        // 2. Nhặt thêm từ AnnualQuota nếu có ngành hệ khác đang "Đang tuyển sinh" và CÒN CHỖ
        $activeAnnualQuotas = AnnualQuota::where('status', 'active')
            ->whereColumn('current_quota', '<', 'target_quota')
            ->get();

        foreach ($activeAnnualQuotas as $aq) {
            $key = $aq->major_name . '|' . $aq->program_name;
            if (!isset($programs[$key])) {
                $programs[$key] = [
                    'major_name' => $aq->major_name,
                    'program_name' => $aq->program_name,
                    'label' => $aq->major_name . ' - ' . $this->mapProgramTypeLabel($aq->program_name),
                ];
            }
        }

        return view('ref-form', [
            'ref_id' => $ref_id,
            'collaborator' => $collaborator,
            'intakes' => $intakes,
            'programs' => array_values($programs),
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
            'phone' => ['required', 'string', 'max:20', Rule::unique('students', 'phone')->whereNull('deleted_at')],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('students', 'email')->whereNull('deleted_at')],
            'intake_id' => 'required|exists:intakes,id',
            'quota_id' => 'required|exists:quotas,id',
            'g-recaptcha-response' => 'required',
            'notes' => 'nullable|string',
        ], [
            'intake_id.required' => 'Vui lòng chọn đợt tuyển',
            'intake_id.exists' => 'Đợt tuyển không hợp lệ',
            'quota_id.required' => 'Vui lòng chọn chương trình đào tạo',
            'quota_id.exists' => 'Chương trình đào tạo không hợp lệ hoặc đã đóng',
            'phone.unique' => 'Số điện thoại đã tồn tại',
            'email.unique' => 'Email đã tồn tại',
            'g-recaptcha-response.required' => 'Vui lòng xác minh Captcha',
        ]);

        // Xác thực reCAPTCHA với Google
        $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
            'secret' => config('services.recaptcha.secret_key'),
            'response' => $validated['g-recaptcha-response'],
            'remoteip' => $request->ip(),
        ]);

        if (!$response->json('success')) {
            return back()->withErrors(['g-recaptcha-response' => 'Xác minh Captcha thất bại!'])->withInput();
        }

        $quota = Quota::find($validated['quota_id']);
        $intake = Intake::find($validated['intake_id']);

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
            $student = DB::transaction(function () use ($validated, $collaborator, $quota, $notes, $request) {
                $student = Student::create([
                    'full_name' => $validated['full_name'],
                    'dob' => $validated['dob'],
                    'address' => $validated['address'],
                    'phone' => $validated['phone'],
                    'email' => $validated['email'] ?? null,
                    'collaborator_id' => $collaborator->id,
                    'instructor' => $collaborator->full_name ?? null,

                    'target_university' => $quota->intake?->name, 
                    'major' => $quota->major_name,
                    'program_type' => $quota->program_name,
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

                \App\Models\Payment::create([
                    'student_id' => $student->id,
                    'primary_collaborator_id' => $collaborator->id,
                    'program_type' => $programTypeMap,
                    'amount' => 0, 
                    'bill_path' => null, // Sẽ được CTV tải lên sau
                    'status' => \App\Models\Payment::STATUS_NOT_PAID,
                ]);
                
                return $student;
            });

            // Gửi qua Laravel Mailer chuẩn
            if ($student && !empty($student->email)) {
                try {
                    \Illuminate\Support\Facades\Mail::to($student->email)
                        ->queue(new \App\Mail\StudentRegistrationSuccessful($student));
                    
                    \Illuminate\Support\Facades\Log::info('Mail queued successfully for: ' . $student->email);
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('Mail Queue Error: ' . $e->getMessage());
                }
            }

        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['system_error' => 'Có lỗi xảy ra: ' . $e->getMessage()])->withInput();
        }

        // Xóa cookie sau khi đăng ký thành công
        $this->refTrackingService->clearRefCookie();

        return redirect()->back()->with([
            'success' => 'Đăng ký thành công! Chúng tôi sẽ liên hệ với bạn sớm nhất.',
            'registered_student' => [
                'full_name' => $student->full_name,
                'profile_code' => $student->profile_code,
                'major' => $student->major,
                'program_type' => $student->program_type_label,
                'intake_name' => $intake->name,
                'intake_year' => $intake->start_date?->format('Y') ?? now()->format('Y'),
                'intake_month' => $intake->start_date?->format('n'),
            ]
        ]);
    }

    /**
     * Hiển thị form upload bill thanh toán cho sinh viên đã đăng ký.
     */
    public function showPaymentForm(string $ref_id) {
        $collaborator = $this->refTrackingService->getCollaborator(request(), $ref_id);
        if (!$collaborator) abort(404);

        return view('ref-payment-disabled', [
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
        $path = $request->file('bill')->storeAs('bills', $safeFileName, [
            'disk' => 'google',
            'visibility' => 'public'
        ]);

        // Tạo hoặc cập nhật payment (tránh sinh bản ghi trùng lặp)
        $payment = Payment::updateOrCreate(
            ['student_id' => $student->id],
            [
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

    public function showProfileTracking(Request $request, ?string $profile_code = null) {
        $inputCode = trim((string) ($profile_code ?: $request->query('profile_code', '')));
        $normalizedCode = strtoupper($inputCode);
        $student = null;

        if ($normalizedCode !== '') {
            $student = Student::query()
                ->with(['intake', 'payment', 'quota', 'collaborator'])
                ->whereRaw('UPPER(profile_code) = ?', [$normalizedCode])
                ->first();
        }

        return view('profile-tracking', [
            'profileCode' => $normalizedCode,
            'student' => $student,
            'statusLabel' => $student ? Student::getStatusOptions()[$student->status] ?? $student->status : null,
            'applicationStatusLabel' => $student ? $this->mapApplicationStatusLabel($student->application_status) : null,
            'paymentStatusLabel' => $student?->payment ? $this->mapPaymentStatusLabel($student->payment->status) : null,
            'programTypeLabel' => $student?->program_type_label,
            'paymentAmountLabel' => $student?->payment ? number_format((float) $student->payment->amount, 0, ',', '.') . ' VNĐ' : 'Chưa cập nhật',
            'isPaymentVerified' => $student?->payment?->status === Payment::STATUS_VERIFIED,
            'billUrl' => $student?->payment?->bill_path ? route('public.files.bill.view', ['paymentId' => $student->payment->id]) : null,
            'receiptUrl' => $student?->payment?->receipt_path ? route('public.files.bill.view', ['paymentId' => $student->payment->id]) . '?type=receipt' : null,
        ]);
    }

    private function mapApplicationStatusLabel(?string $status): string {
        if (!$status) {
            return 'Chưa cập nhật';
        }

        return match ($status) {
            'draft' => 'Đang nhập',
            'pending_documents' => 'Thiếu giấy tờ',
            'submitted' => 'Đã nộp hồ sơ',
            'verified' => 'Đã xác minh',
            'eligible' => 'Đủ điều kiện',
            'ineligible' => 'Không đủ điều kiện',
            default => $status,
        };
    }

    private function mapPaymentStatusLabel(?string $status): string {
        if (!$status) {
            return 'Chưa cập nhật';
        }

        return match ($status) {
            Payment::STATUS_NOT_PAID => 'Chưa nộp tiền',
            Payment::STATUS_SUBMITTED => 'Chờ xác minh',
            Payment::STATUS_VERIFIED => 'Đã xác nhận',
            Payment::STATUS_REVERTED => 'Đã hoàn trả',
            default => $status,
        };
    }

    private function mapProgramTypeLabel(?string $program): string {
        if (!$program) {
            return 'Chưa cập nhật';
        }

        return match (strtoupper(trim($program))) {
            'REGULAR' => 'Chính quy',
            'PART_TIME' => 'Vừa học vừa làm',
            'DISTANCE' => 'Đào tạo từ xa',
            default => $program,
        };
    }
}
