<?php

namespace App\Filament\Resources\Students\Pages;

use App\Filament\Resources\Students\StudentResource;
use App\Models\Payment;
use App\Models\Student;
use App\Models\StudentUpdateLog;
use App\Services\StudentFeeService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditStudent extends EditRecord {
    protected static string $resource = StudentResource::class;

    protected function getHeaderActions(): array {
        $record = $this->record;

        return [
            // Action xác nhận đã nộp tiền
            Action::make('confirm_payment')
                ->label('Xác nhận đã nộp tiền')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Xác nhận đã nộp tiền')
                ->modalDescription('Xác nhận học viên đã nộp tiền. Hệ thống sẽ chuyển trạng thái thanh toán sang "Đã nộp (chờ xác minh)".')
                ->modalSubmitActionLabel('Xác nhận')
                ->modalCancelActionLabel('Hủy')
                ->form([
                    \Filament\Forms\Components\TextInput::make('amount')
                        ->label('Số tiền (VNĐ)')
                        ->required()
                        ->helperText('Nhập số tiền học viên đã nộp (ví dụ: 1.750.000)')
                        ->formatStateUsing(function ($state) {
                            if (empty($state)) {
                                return '';
                            }
                            return number_format((float) $state, 0, '', '.');
                        })
                        ->dehydrateStateUsing(function ($state) {
                            if (empty($state)) {
                                return 0;
                            }
                            // Loại bỏ dấu chấm và chuyển thành số
                            return (int) str_replace('.', '', $state);
                        })
                        ->live()
                        ->afterStateUpdated(function ($state, callable $set) {
                            if (!empty($state)) {
                                // Format lại khi người dùng nhập
                                $numericValue = (int) str_replace('.', '', $state);
                                if ($numericValue > 0) {
                                    $formatted = number_format($numericValue, 0, '', '.');
                                    $set('amount', $formatted);
                                }
                            }
                        }),
                ])
                ->visible(function () use ($record): bool {
                    $user = Auth::user();

                    if ($record->status === Student::STATUS_ENROLLED || $record->status === Student::STATUS_SUBMITTED) {
                        return false;
                    }

                    if (!in_array($user->role, ['super_admin', 'ctv'])) {
                        return false;
                    }

                    // CTV không được phép confirm nếu payment đã verified
                    if ($user->role === 'ctv') {
                        $hasVerifiedPayment = Payment::where('student_id', $record->id)
                            ->where('status', Payment::STATUS_VERIFIED)
                            ->exists();

                        return !$hasVerifiedPayment;
                    }

                    return true;
                })
                ->action(function (array $data) use ($record): void {
                    $amount = (int) ($data['amount'] ?? 0);

                    // Cập nhật payment record nếu có
                    $payment = $record->payment;
                    if ($payment) {
                        $payment->update([
                            'amount' => $amount,
                            'status' => Payment::STATUS_SUBMITTED,
                            'receipt_uploaded_by' => Auth::id(),
                            'receipt_uploaded_at' => now(),
                        ]);
                    } else {
                        // Tạo payment record mới
                        Payment::create([
                            'student_id' => $record->id,
                            'primary_collaborator_id' => $record->collaborator_id,
                            'primary_collaborator_id' => $record->collaborator_id,
                            'program_type' => $record->program_type ?? 'REGULAR',
                            'amount' => $amount,
                            'status' => Payment::STATUS_SUBMITTED,
                            'receipt_uploaded_by' => Auth::id(),
                            'receipt_uploaded_at' => now(),
                        ]);
                    }

                    // Cập nhật trạng thái học viên sang "Đã nộp hồ sơ" (chờ xác minh)
                    $record->update([
                        'status' => Student::STATUS_SUBMITTED,
                    ]);

                    \Filament\Notifications\Notification::make()
                        ->title('Xác nhận thành công')
                        ->body('Học viên đã được xác nhận nộp tiền. Trạng thái: Đã nộp hồ sơ (chờ xác minh).')
                        ->success()
                        ->send();
                }),

            // Action upload bill (cho CTV)
            Action::make('upload_bill')
                ->label('Upload Bill')
                ->icon('heroicon-o-document-arrow-up')
                ->color('info')
                ->form([
                    \Filament\Forms\Components\FileUpload::make('bill')
                        ->label('Bill thanh toán')
                        ->acceptedFileTypes(['image/*', 'application/pdf'])
                        ->maxSize(5120) // 5MB
                        ->disk('google')
                        ->directory('bills')
                        ->required()
                        ->helperText('Upload bill thanh toán (JPG, PNG, PDF, tối đa 5MB)'),
                    \Filament\Forms\Components\TextInput::make('amount')
                        ->label('Số tiền')
                        ->required()
                        ->helperText('Nhập số tiền đã thanh toán (ví dụ: 1.750.000)')
                        ->formatStateUsing(function ($state) {
                            if (empty($state)) {
                                return '';
                            }
                            return number_format((float) $state, 0, '', '.');
                        })
                        ->dehydrateStateUsing(function ($state) {
                            if (empty($state)) {
                                return 0;
                            }
                            // Loại bỏ dấu chấm và chuyển thành số
                            return (int) str_replace('.', '', $state);
                        })
                        ->live()
                        ->afterStateUpdated(function ($state, callable $set) {
                            if (!empty($state)) {
                                // Format lại khi người dùng nhập
                                $numericValue = (int) str_replace('.', '', $state);
                                if ($numericValue > 0) {
                                    $formatted = number_format($numericValue, 0, '', '.');
                                    $set('amount', $formatted);
                                }
                            }
                        }),
                    \Filament\Forms\Components\Select::make('program_type')
                        ->label('Hệ liên thông')
                        ->options([
                            'REGULAR' => 'Chính quy',
                            'PART_TIME' => 'Vừa học vừa làm',
                        ])
                        ->required()
                        ->helperText('Chọn hệ liên thông của sinh viên'),
                ])
                ->visible(function () use ($record): bool {
                    $user = Auth::user();

                    // Chỉ CTV mới được upload bill
                    if ($user->role !== 'ctv') {
                        return false;
                    }

                    // Lấy collaborator của user
                    $collaborator = \App\Models\Collaborator::where('email', $user->email)->first();
                    if (!$collaborator) {
                        return false;
                    }

                    // Chỉ CTV có ref_id trùng với collaborator_id của sinh viên mới được upload bill
                    $canUpload = $record->collaborator_id === $collaborator->id;

                    // Kiểm tra payment status
                    if (!$record->payment) {
                        // Nếu chưa có payment, có thể upload (trạng thái NOT_PAID)
                        return $canUpload;
                    }

                    // Chỉ được upload khi payment ở trạng thái NOT_PAID
                    return $canUpload && $record->payment->status === Payment::STATUS_NOT_PAID;
                })
                ->action(function (array $data) use ($record) {
                    // Tìm collaborator của user hiện tại
                    $collaborator = \App\Models\Collaborator::where('email', Auth::user()->email)->first();

                    if (!$collaborator) {
                        \Filament\Notifications\Notification::make()
                            ->title('Lỗi')
                            ->body('Không tìm thấy thông tin cộng tác viên.')
                            ->danger()
                            ->send();
                        return;
                    }

                    // Tạo hoặc cập nhật payment record
                    $payment = $record->payment;
                    if (!$payment) {
                        $payment = Payment::create([
                            'student_id' => $record->id,
                            'primary_collaborator_id' => $record->collaborator_id,
                            'amount' => $data['amount'],
                            'program_type' => $data['program_type'],
                            'bill_path' => $data['bill'],
                            'status' => Payment::STATUS_SUBMITTED,
                        ]);
                    } else {
                        $payment->update([
                            'bill_path' => $data['bill'],
                            'amount' => $data['amount'],
                            'program_type' => $data['program_type'],
                            'status' => Payment::STATUS_SUBMITTED,
                        ]);
                    }

                    \Filament\Notifications\Notification::make()
                        ->title('Đã upload bill thành công')
                        ->body('Bill đã được gửi để xác minh.')
                        ->success()
                        ->send();
                }),
            Action::make('view_receipt')
                ->label('Xem Phiếu Thu')
                ->icon('heroicon-o-document-check')
                ->color('success')
                ->modalHeading('Phiếu thu từ Helen')
                ->modalContent(function () use ($record) {
                    if (!$record->payment || !$record->payment->receipt_path) {
                        return view('components.no-content', [
                            'message' => 'Không có phiếu thu để hiển thị.'
                        ]);
                    }

                    $fileUrl = route('files.receipt.view', $record->payment->id);
                    $fileName = basename($record->payment->receipt_path);

                    return view('components.bill-viewer', [ // Reuse bill-viewer components if it's general
                        'fileUrl' => $fileUrl,
                        'fileName' => $fileName,
                        'payment' => $record->payment
                    ]);
                })
                ->modalWidth('4xl')
                ->visible(fn(): bool => $record->payment && !empty($record->payment->receipt_path)),

            // Action xác nhận thanh toán (cho kế toán)
            Action::make('verify_payment')
                ->label('Xác nhận thanh toán')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Xác nhận')
                ->modalDescription('Xác nhận đã nhận tiền từ học viên. Hệ thống sẽ chuyển trạng thái thanh toán sang "Đã xác nhận" và tạo commission.')
                ->modalSubmitActionLabel('Xác nhận')
                ->modalCancelActionLabel('Hủy')
                ->tooltip('Xác nhận đã nhận tiền từ học viên và chuyển trạng thái thanh toán sang "Đã xác nhận"')
                ->form([
                    \Filament\Forms\Components\TextInput::make('amount')
                        ->label('Số tiền (VNĐ)')
                        ->helperText('Nếu đang để 0, hãy nhập số tiền trước khi xác nhận (ví dụ: 1.750.000)')
                        ->default(function () use ($record) {
                            $paymentAmount = (float) ($record->payment?->amount ?? 0);
                            if ($paymentAmount > 0) {
                                return number_format((int) round($paymentAmount), 0, '', '.');
                            }

                            $expectedFee = app(StudentFeeService::class)->getExpectedFeeForStudent($record);
                            if ($expectedFee !== null) {
                                return number_format((int) round($expectedFee), 0, '', '.');
                            }
                            return '';
                        })
                        ->formatStateUsing(function ($state) use ($record) {
                            // Luôn ưu tiên lấy từ payment->amount để đảm bảo hiển thị đúng
                            if ($record && $record->payment) {
                                $amount = (float) ($record->payment->amount ?? 0);
                                if ($amount > 0) {
                                    return number_format((int) round($amount), 0, '', '.');
                                }
                            }

                            // Nếu không có payment hoặc payment->amount = 0, format từ state (nhưng chỉ nếu state hợp lệ)
                            if (!empty($state) && $state != 0 && $state != '0') {
                                // Loại bỏ dấu chấm và dấu phẩy để lấy số
                                $numericValue = is_string($state)
                                    ? (int) str_replace(['.', ',', ' '], '', $state)
                                    : (int) round((float) $state);

                                // Chỉ format nếu số tiền hợp lệ (>= 100 VNĐ) - bỏ qua các giá trị nhỏ như 2
                                if ($numericValue >= 100) {
                                    return number_format($numericValue, 0, '', '.');
                                }
                            }
                            
                            // Nếu state không hợp lệ hoặc quá nhỏ, trả về rỗng
                            return '';
                        })
                        ->dehydrateStateUsing(function ($state) {
                            if (empty($state)) {
                                return 0;
                            }
                            // Loại bỏ dấu chấm và chuyển thành số
                            return (int) str_replace('.', '', $state);
                        })
                        ->live()
                        ->afterStateUpdated(function ($state, callable $set) {
                            if (!empty($state)) {
                                // Format lại khi người dùng nhập
                                $numericValue = (int) str_replace('.', '', $state);
                                if ($numericValue > 0) {
                                    $formatted = number_format($numericValue, 0, '', '.');
                                    $set('amount', $formatted);
                                }
                            }
                        })
                        ->required(),
                    \Filament\Forms\Components\TextInput::make('receipt_number')
                        ->label('Số phiếu thu')
                        ->required()
                        ->helperText('Nhập số phiếu thu từ Helen'),
                    \Filament\Forms\Components\FileUpload::make('receipt')
                        ->label('File phiếu thu')
                        ->acceptedFileTypes(['image/*', 'application/pdf'])
                        ->maxSize(5120) // 5MB
                        ->disk('google')
                        ->directory('receipts')
                        ->required()
                        ->helperText('Upload phiếu thu từ Helen (JPG, PNG, PDF, tối đa 5MB)'),
                ])
                ->visible(function () use ($record): bool {
                    // Kế toán & cán bộ hồ sơ được phép xác nhận thanh toán
                    return (in_array(Auth::user()->role, ['accountant', 'document']) || (Auth::user()->roles && Auth::user()->roles->contains('name', 'accountant'))) &&
                        // Sinh viên phải có payment record
                        $record->payment &&
                        // Payment phải ở trạng thái chờ xác minh hoặc đã hoàn trả (có thể xác nhận lại)
                        in_array($record->payment->status, [Payment::STATUS_SUBMITTED, Payment::STATUS_REVERTED]);
                })
                ->action(function (array $data) use ($record) {
                    $payment = $record->payment;
                    if ($payment) {
                        $amount = (int) ($data['amount'] ?? 0);

                        // Nếu kế toán để 0 thì tự tính theo cấu hình Quota (theo ngành/hệ/đợt).
                        if ($amount <= 0) {
                            $expectedFee = app(StudentFeeService::class)->getExpectedFeeForStudent($record);
                            if ($expectedFee !== null) {
                                $amount = (int) round($expectedFee);
                            }
                        }

                        // Chỉ cập nhật amount khi có giá trị hợp lệ (>0)
                        if ($amount > 0 && (float) ($payment->amount ?? 0) <= 0) {
                            $payment->amount = $amount;
                        }

                        // Cập nhật thông tin phiếu thu
                        $payment->receipt_number = $data['receipt_number'] ?? null;
                        $payment->receipt_path = $data['receipt'] ?? null;
                        $payment->receipt_uploaded_by = Auth::id();
                        $payment->receipt_uploaded_at = now();
                        $payment->save();

                        // Xác minh thanh toán
                        $payment->markAsVerified(Auth::id());

                        // Tạo commission
                        $commissionService = new \App\Services\CommissionService();
                        $commissionService->createCommissionFromPayment($payment);

                        \Filament\Notifications\Notification::make()
                            ->title('Xác minh thành công')
                            ->body('Thanh toán đã được xác minh và commission đã được tạo tự động.')
                            ->success()
                            ->send();
                    } else {
                        \Filament\Notifications\Notification::make()
                            ->title('Lỗi')
                            ->body('Không tìm thấy thông tin thanh toán.')
                            ->danger()
                            ->send();
                    }
                }),

            // Action cho cán bộ hồ sơ: Revert payment status từ VERIFIED về REVERTED
            Action::make('revert_payment_status')
                ->label('Hoàn trả trạng thái thanh toán')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Hoàn trả trạng thái thanh toán')
                ->modalDescription('Bạn có chắc chắn muốn hoàn trả trạng thái thanh toán từ "Đã xác nhận" về "Đã hoàn trả"? Hành động này sẽ được ghi lại trong lịch sử.')
                ->modalSubmitActionLabel('Xác nhận')
                ->modalCancelActionLabel('Hủy')
                ->form([
                    \Filament\Forms\Components\Textarea::make('reason')
                        ->label('Lý do hoàn trả')
                        ->required()
                        ->helperText('Nhập lý do hoàn trả trạng thái thanh toán (bắt buộc)')
                        ->rows(3),
                ])
                ->visible(function () use ($record): bool {
                    // Chỉ hiển thị cho cán bộ hồ sơ
                    $user = Auth::user();
                    if (!$user || $user->role !== 'document') {
                        return false;
                    }
                    
                    // Chỉ hiển thị khi payment status là VERIFIED
                    return $record->payment && 
                           $record->payment->status === Payment::STATUS_VERIFIED;
                })
                ->action(function (array $data) use ($record) {
                    $payment = $record->payment;
                    if (!$payment) {
                        \Filament\Notifications\Notification::make()
                            ->title('Lỗi')
                            ->body('Không tìm thấy thông tin thanh toán.')
                            ->danger()
                            ->send();
                        return;
                    }

                    $oldStatus = $payment->status;
                    $newStatus = Payment::STATUS_REVERTED;

                    // Cập nhật payment status
                    $payment->update([
                        'status' => $newStatus,
                        'verified_by' => null,
                        'verified_at' => null,
                        'edit_reason' => $data['reason'] ?? null,
                        'edited_at' => now(),
                        'edited_by' => Auth::id(),
                    ]);

                    // Log thay đổi vào StudentUpdateLog
                    if (\Illuminate\Support\Facades\Schema::hasTable('student_update_logs')) {
                        StudentUpdateLog::create([
                            'student_id' => $record->id,
                            'user_id' => Auth::id(),
                            'changes' => [
                                [
                                    'field' => 'payment_status',
                                    'from' => $oldStatus,
                                    'to' => $newStatus,
                                    'reason' => $data['reason'] ?? null,
                                ],
                            ],
                        ]);
                    }

                    \Filament\Notifications\Notification::make()
                        ->title('Hoàn trả thành công')
                        ->body('Trạng thái thanh toán đã được hoàn trả từ "Đã xác nhận" về "Đã hoàn trả". Thay đổi đã được ghi lại trong lịch sử.')
                        ->success()
                        ->send();
                }),

            Action::make('save')
                ->label('Lưu thay đổi')
                ->action('save'),
            Action::make('cancel')
                ->label('Hủy')
                ->color('gray')
                ->outlined()
                ->url(fn(): string => $this->getResource()::getUrl('index')),
            DeleteAction::make()
                ->label('Xóa học viên')
                ->modalHeading('Xóa học viên')
                ->modalDescription('Chỉ super admin được xóa. Hệ thống sẽ xóa mềm (có thể khôi phục).')
                ->modalSubmitActionLabel('Xóa')
                ->modalCancelActionLabel('Hủy')
                ->visible(fn(): bool => Auth::user()?->role === 'super_admin'),
        ];
    }

    protected function getFormActions(): array {
        return [];
    }

    public function getTitle(): string {
        return 'Chỉnh sửa học viên';
    }

    public function getBreadcrumb(): string {
        return 'Chỉnh sửa học viên';
    }

    protected function getValidationRules(): array {
        return Student::getValidationRules();
    }

    protected function mutateFormDataBeforeFill(array $data): array {
        // Tính toán application_status giống như logic trong StudentsTable để đảm bảo đồng nhất
        if (isset($data['id'])) {
            $student = Student::with('payment')->find($data['id']);
            if ($student) {
                $checklist = $student->document_checklist ?? [];

                $requiredDocuments = [
                    'phieu_tuyen_sinh',
                    'bang_cao_dang',
                    'bang_thpt',
                    'bang_diem',
                    'giay_khai_sinh',
                    'cccd',
                    'giay_kham_suc_khoe',
                    'anh_4x6',
                ];

                $missingDocs = array_diff($requiredDocuments, $checklist);
                $hasAllDocs = empty($missingDocs);

                // Tính toán application_status dựa trên logic trong table
                $calculatedStatus = null;

                // Ưu tiên trạng thái không đủ điều kiện
                if ($student->status === Student::STATUS_REJECTED) {
                    $calculatedStatus = 'ineligible';
                }
                // Đang nhập: hồ sơ mới tạo, chưa nộp
                elseif (in_array($student->status, [Student::STATUS_NEW, Student::STATUS_CONTACTED])) {
                    $calculatedStatus = 'draft';
                }
                // Đã nộp nhưng thiếu giấy tờ
                elseif (in_array($student->status, [Student::STATUS_SUBMITTED, Student::STATUS_APPROVED, Student::STATUS_ENROLLED]) && !$hasAllDocs) {
                    $calculatedStatus = 'pending_documents';
                }
                // Đã nộp đủ giấy tờ nhưng chưa đủ điều kiện (chờ xác minh / thanh toán)
                elseif (in_array($student->status, [Student::STATUS_SUBMITTED, Student::STATUS_APPROVED]) && $hasAllDocs) {
                    $calculatedStatus = 'submitted';
                }
                // Đủ điều kiện khi đã nhập học và đủ hồ sơ
                elseif ($student->status === Student::STATUS_ENROLLED && $hasAllDocs) {
                    $calculatedStatus = 'eligible';
                } else {
                    $calculatedStatus = 'draft';
                }

                // Nếu application_status trong DB chưa có hoặc không khớp với tính toán, dùng giá trị tính toán
                if (empty($data['application_status']) || $data['application_status'] !== $calculatedStatus) {
                    $data['application_status'] = $calculatedStatus;
                }

                // Tự động điền fee từ payment->amount nếu có
                if ($student->payment && !empty($student->payment->amount)) {
                    $amount = $student->payment->amount;
                    // Xử lý cả trường hợp decimal và integer
                    $amountValue = is_string($amount)
                        ? (float) str_replace([',', ' '], ['.', ''], $amount)
                        : (float) $amount;

                    // Nếu số tiền < 1000 và có phần thập phân, có thể là lỗi format (1.75 thay vì 1750000)
                    if ($amountValue > 0 && $amountValue < 1000 && fmod($amountValue, 1) != 0) {
                        // Tự động sửa: nhân với 1000000
                        $correctedAmount = $amountValue * 1000000;
                        if ($correctedAmount >= 100) {
                            $data['fee'] = (int) round($correctedAmount);
                            // Cập nhật lại payment->amount với giá trị đúng
                            $student->payment->update(['amount' => $correctedAmount]);
                        }
                    } elseif ($amountValue >= 100) {
                        $data['fee'] = (int) round($amountValue);
                    }
                }
            }
        }

        return $data;
    }

    public static function canAccess(array $parameters = []): bool {
        $user = Auth::user();
        if (!$user) {
            return false;
        }

        // Nếu là super_admin, admissions, document thì luôn được phép
        if (in_array($user->role, ['super_admin', 'admissions', 'document'])) {
            return true;
        }

        // Nếu là accountant thì luôn được phép
        if ($user->role === 'accountant') {
            return true;
        }

        // Nếu là CTV, cần kiểm tra payment đã được verified chưa
        if ($user->role === 'ctv') {
            // Lấy record ID từ parameters
            if (isset($parameters['record'])) {
                $recordId = $parameters['record'];
                // Đảm bảo recordId là giá trị đơn, không phải array hoặc collection
                if (is_array($recordId)) {
                    $recordId = $recordId['id'] ?? $recordId[0] ?? null;
                }
                if ($recordId && is_numeric($recordId)) {
                    $student = Student::where('id', $recordId)->first();
                    if ($student && $student instanceof Student) {
                    // Kiểm tra xem student có payment nào đã được verified không
                    $hasVerifiedPayment = \App\Models\Payment::where('student_id', $student->id)
                        ->where('status', \App\Models\Payment::STATUS_VERIFIED)
                        ->exists();

                    // Nếu có payment đã verified, CTV không được phép edit
                    if ($hasVerifiedPayment) {
                        return false;
                        }
                    }
                }
            }
            return true;
        }

        return false;
    }
}
