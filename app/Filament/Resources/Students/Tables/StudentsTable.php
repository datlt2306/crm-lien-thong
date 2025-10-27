<?php

namespace App\Filament\Resources\Students\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use App\Models\Student;
use App\Models\Payment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Builder;

class StudentsTable {
    public static function configure(Table $table): Table {
        return $table
            ->recordUrl(function (Student $record) {
                $user = Auth::user();

                // Super admin, organization_owner, admissions, document luôn được phép
                if (in_array($user->role, ['super_admin', 'organization_owner', 'admissions', 'document'])) {
                    return \App\Filament\Resources\Students\StudentResource::getUrl('edit', ['record' => $record]);
                }

                // CTV chỉ được phép nếu payment chưa verified
                if ($user->role === 'ctv') {
                    // Kiểm tra xem có payment nào đã verified không
                    $hasVerifiedPayment = Payment::where('student_id', $record->id)
                        ->where('status', Payment::STATUS_VERIFIED)
                        ->exists();

                    // Nếu có payment verified, CTV không được phép edit
                    if (!$hasVerifiedPayment) {
                        return \App\Filament\Resources\Students\StudentResource::getUrl('edit', ['record' => $record]);
                    }
                }

                return null;
            })
            ->columns([
                TextColumn::make('full_name')
                    ->label('Họ và tên')
                    ->searchable(),
                TextColumn::make('contact')
                    ->label('Liên hệ')
                    ->state(fn($record) => $record)
                    ->formatStateUsing(function ($record) {
                        $phone = $record->phone ?: '';
                        $email = $record->email ?: '';
                        $lines = [];
                        if ($phone) {
                            $lines[] = '📞 ' . e($phone);
                        }
                        if ($email) {
                            $lines[] = '✉️ ' . e($email);
                        }
                        return implode('<br>', $lines) ?: '—';
                    })
                    ->html()
                    ->searchable(query: function ($query, $search) {
                        return $query->where(function ($q) use ($search) {
                            $q->where('phone', 'like', "%$search%")
                                ->orWhere('email', 'like', "%$search%");
                        });
                    }),
                TextColumn::make('dob')
                    ->label('Ngày sinh')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('major')
                    ->label('Ngành học')
                    ->searchable(),
                TextColumn::make('address')
                    ->label('Địa chỉ')
                    ->limit(50)
                    ->searchable(),
                TextColumn::make('intake_month')
                    ->label('Đợt tuyển')
                    ->formatStateUsing(fn($state) => $state ? "Tháng {$state}" : '—')
                    ->sortable(),
                TextColumn::make('program_type')
                    ->label('Hệ tuyển sinh')
                    ->formatStateUsing(fn($state) => match ($state) {
                        'REGULAR' => '🎓 Chính quy',
                        'PART_TIME' => '⏰ Vừa học vừa làm',
                        default => '—'
                    })
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        'REGULAR' => 'success',      // Xanh lá rõ ràng
                        'PART_TIME' => 'info',       // Xanh dương rõ ràng
                        default => 'gray'
                    })
                    ->tooltip(fn($state) => match ($state) {
                        'REGULAR' => '🎓 Hệ đào tạo chính quy, học tập toàn thời gian',
                        'PART_TIME' => '⏰ Hệ vừa học vừa làm, linh hoạt thời gian',
                        default => ''
                    }),
                TextColumn::make('source')
                    ->label('Nguồn')
                    ->searchable(),
                TextColumn::make('status')
                    ->label('Tình trạng')
                    ->badge()
                    ->color(function (Student $record): string {
                        // Ưu tiên hiển thị trạng thái thanh toán nếu có payment
                        if ($record->payment) {
                            return match ($record->payment->status) {
                                Payment::STATUS_NOT_PAID => 'gray',
                                Payment::STATUS_SUBMITTED => 'warning',
                                Payment::STATUS_VERIFIED => 'success',
                                default => 'gray',
                            };
                        }

                        // Nếu không có payment, hiển thị trạng thái sinh viên
                        return match ($record->status) {
                            Student::STATUS_NEW => 'slate',
                            Student::STATUS_CONTACTED => 'info',
                            Student::STATUS_SUBMITTED => 'warning',
                            Student::STATUS_APPROVED => 'orange',
                            Student::STATUS_ENROLLED => 'success',
                            Student::STATUS_REJECTED => 'danger',
                            Student::STATUS_DROPPED => 'gray',
                            default => 'slate',
                        };
                    })
                    ->formatStateUsing(function (Student $record): string {
                        // Ưu tiên hiển thị trạng thái thanh toán nếu có payment
                        if ($record->payment) {
                            return match ($record->payment->status) {
                                Payment::STATUS_NOT_PAID => '💳 Chưa nộp tiền',
                                Payment::STATUS_SUBMITTED => '⏳ Chờ xác minh',
                                Payment::STATUS_VERIFIED => '✅ Đã xác nhận',
                                default => '—',
                            };
                        }

                        // Nếu không có payment, hiển thị trạng thái sinh viên
                        $statusOptions = Student::getStatusOptions();
                        $statusLabel = $statusOptions[$record->status] ?? $record->status;

                        // Thêm icon cho từng trạng thái
                        $icons = [
                            Student::STATUS_NEW => '🆕',
                            Student::STATUS_CONTACTED => '📞',
                            Student::STATUS_SUBMITTED => '⏳',
                            Student::STATUS_APPROVED => '✅',
                            Student::STATUS_ENROLLED => '🎓',
                            Student::STATUS_REJECTED => '❌',
                            Student::STATUS_DROPPED => '🚫',
                        ];

                        $icon = $icons[$record->status] ?? '';
                        return $icon ? "{$icon} {$statusLabel}" : $statusLabel;
                    })
                    ->tooltip(function (Student $record): string {
                        if ($record->payment) {
                            return match ($record->payment->status) {
                                Payment::STATUS_NOT_PAID => 'Học viên chưa nộp tiền',
                                Payment::STATUS_SUBMITTED => 'Đã nộp tiền, chờ kế toán xác minh',
                                Payment::STATUS_VERIFIED => 'Đã xác minh và tạo commission',
                                default => '',
                            };
                        }

                        return 'Trạng thái: ' . (Student::getStatusOptions()[$record->status] ?? $record->status);
                    })
                    ->searchable(),

                TextColumn::make('created_at')
                    ->label('Ngày tạo')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Ngày cập nhật')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('payment_status')
                    ->label('Trạng thái thanh toán')
                    ->options([
                        Payment::STATUS_NOT_PAID => 'Chưa nộp tiền',
                        Payment::STATUS_SUBMITTED => 'Chờ xác minh',
                        Payment::STATUS_VERIFIED => 'Đã xác nhận',
                    ])
                    ->visible(fn() => Auth::user()->role === 'accountant' || (Auth::user()->roles && Auth::user()->roles->contains('name', 'accountant')))
                    ->query(function (Builder $query, array $data): Builder {
                        if (!isset($data['value']) || $data['value'] === '') {
                            return $query;
                        }
                        return $query->whereHas('payment', function (Builder $paymentQuery) use ($data) {
                            $paymentQuery->where('status', $data['value']);
                        });
                    }),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make()
                        ->label('Chỉnh sửa')
                        ->icon('heroicon-o-pencil')
                        ->visible(function (Student $record) {
                            $user = Auth::user();

                            // Super admin, organization_owner, admissions, document luôn được phép
                            if (in_array($user->role, ['super_admin', 'organization_owner', 'admissions', 'document'])) {
                                return true;
                            }

                            // CTV chỉ được phép nếu payment chưa verified
                            if ($user->role === 'ctv') {
                                // Kiểm tra xem có payment nào đã verified không
                                $hasVerifiedPayment = Payment::where('student_id', $record->id)
                                    ->where('status', Payment::STATUS_VERIFIED)
                                    ->exists();

                                // Nếu có payment verified, CTV không được phép edit
                                return !$hasVerifiedPayment;
                            }

                            return false;
                        }),

                    Action::make('confirm_payment')
                        ->label('Xác nhận đã nộp tiền')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Xác nhận đã nộp tiền')
                        ->modalDescription('Xác nhận học viên đã nộp tiền. Hệ thống sẽ chuyển trạng thái thanh toán sang "Đã nộp (chờ xác minh)".')
                        ->modalSubmitActionLabel('Xác nhận')
                        ->modalCancelActionLabel('Hủy')
                        ->visible(function (Student $record): bool {
                            $user = Auth::user();

                            if ($record->status === Student::STATUS_ENROLLED || $record->status === Student::STATUS_SUBMITTED) {
                                return false;
                            }

                            if (!in_array($user->role, ['super_admin', 'organization_owner', 'ctv'])) {
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
                        ->action(function (Student $record): void {
                            // Cập nhật payment record nếu có
                            $payment = $record->payment;
                            if ($payment) {
                                $payment->update([
                                    'status' => Payment::STATUS_SUBMITTED,
                                    'receipt_uploaded_by' => Auth::id(),
                                    'receipt_uploaded_at' => now(),
                                ]);
                            } else {
                                // Tạo payment record mới
                                \App\Models\Payment::create([
                                    'student_id' => $record->id,
                                    'primary_collaborator_id' => $record->collaborator_id,
                                    'organization_id' => $record->organization_id,
                                    'program_type' => $record->program_type ?? 'REGULAR',
                                    'amount' => 0, // Sẽ cập nhật sau
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

                    Action::make('mark_enrolled')
                        ->label('Xác nhận SV nhập học')
                        ->icon('heroicon-o-academic-cap')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Xác nhận sinh viên nhập học')
                        ->modalDescription('Bạn có chắc chắn muốn đánh dấu sinh viên này đã nhập học? Hệ thống sẽ tự động cập nhật commission cho CTV cấp 2.')
                        ->modalSubmitActionLabel('Xác nhận')
                        ->modalCancelActionLabel('Hủy')
                        ->visible(
                            fn(Student $record): bool =>
                            $record->status !== Student::STATUS_ENROLLED &&
                                ($record->payment?->status === Payment::STATUS_VERIFIED) &&
                                in_array(Auth::user()->role, ['super_admin', 'organization_owner'])
                        )
                        ->action(function (Student $record) {
                            $record->update(['status' => Student::STATUS_ENROLLED]);

                            // Cập nhật commission khi student nhập học
                            $commissionService = new \App\Services\CommissionService();
                            $commissionService->updateCommissionsOnEnrollment($record);

                            \Filament\Notifications\Notification::make()
                                ->title('Đã xác nhận sinh viên nhập học')
                                ->body('Commission đã được cập nhật tự động.')
                                ->success()
                                ->send();
                        }),

                    Action::make('mark_left_unit')
                        ->label('Sinh viên hủy đăng ký')
                        ->icon('heroicon-o-user-minus')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Xác nhận sinh viên hủy đăng ký')
                        ->modalDescription('Xác nhận sinh viên này đã hủy đăng ký. Hệ thống sẽ cập nhật trạng thái và bỏ liên kết CTV giới thiệu.')
                        ->modalSubmitActionLabel('Xác nhận')
                        ->modalCancelActionLabel('Hủy')
                        ->visible(
                            fn(Student $record): bool =>
                            $record->status !== Student::STATUS_ENROLLED &&
                                ($record->payment?->status === Payment::STATUS_VERIFIED) &&
                                in_array(Auth::user()->role, ['super_admin', 'organization_owner'])
                        )
                        ->action(function (Student $record) {
                            $record->update([
                                'status' => Student::STATUS_REJECTED,
                                'collaborator_id' => null,
                            ]);

                            \Filament\Notifications\Notification::make()
                                ->title('Hủy đăng ký')
                                ->body('Sinh viên đã được cập nhật trạng thái hủy đăng ký và bỏ liên kết CTV.')
                                ->success()
                                ->send();
                        }),

                    // Action cho kế toán xác nhận
                    Action::make('verify_payment')
                        ->label('Xác nhận')
                        ->icon('heroicon-o-check-badge')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Xác nhận')
                        ->modalDescription('Xác nhận đã nhận tiền từ học viên. Hệ thống sẽ chuyển trạng thái thanh toán sang "Đã xác nhận" và tạo commission.')
                        ->modalSubmitActionLabel('Xác nhận')
                        ->modalCancelActionLabel('Hủy')
                        ->tooltip('Xác nhận đã nhận tiền từ học viên và chuyển trạng thái thanh toán sang "Đã xác nhận"')
                        ->visible(
                            fn(Student $record): bool =>
                            // Chỉ hiển thị cho kế toán
                            (Auth::user()->role === 'accountant' || (Auth::user()->roles && Auth::user()->roles->contains('name', 'accountant'))) &&
                                // Sinh viên phải có payment record
                                $record->payment &&
                                // Payment phải ở trạng thái chờ xác minh
                                $record->payment->status === Payment::STATUS_SUBMITTED
                        )
                        ->action(function (Student $record) {
                            $payment = $record->payment;
                            if ($payment) {
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

                    // Action cho CTV upload bill
                    Action::make('upload_bill')
                        ->label('Upload Bill')
                        ->icon('heroicon-o-document-arrow-up')
                        ->color('info')
                        ->form([
                            \Filament\Forms\Components\FileUpload::make('bill')
                                ->label('Bill thanh toán')
                                ->acceptedFileTypes(['image/*', 'application/pdf'])
                                ->maxSize(5120) // 5MB
                                ->disk('local')
                                ->directory('bills')
                                ->required()
                                ->helperText('Upload bill thanh toán (JPG, PNG, PDF, tối đa 5MB)'),
                            \Filament\Forms\Components\TextInput::make('amount')
                                ->label('Số tiền')
                                ->numeric()
                                ->required()
                                ->helperText('Nhập số tiền đã thanh toán'),
                            \Filament\Forms\Components\Select::make('program_type')
                                ->label('Hệ liên thông')
                                ->options([
                                    'REGULAR' => 'Chính quy',
                                    'PART_TIME' => 'Vừa học vừa làm',
                                ])
                                ->required()
                                ->helperText('Chọn hệ liên thông của sinh viên'),
                        ])
                        ->visible(function (Student $record): bool {
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
                        ->action(function (array $data, Student $record) {
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
                                $payment = \App\Models\Payment::create([
                                    'student_id' => $record->id,
                                    'primary_collaborator_id' => $record->collaborator_id,
                                    'organization_id' => $record->organization_id,
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
                ])
                    ->label('Hành động')
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->color('gray')
                    ->button()
                    ->size('sm')
                    ->tooltip('Các hành động khả dụng')
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('Xóa đã chọn')
                        ->modalHeading('Xóa học viên đã chọn')
                        ->modalDescription('Bạn có chắc chắn muốn xóa các học viên đã chọn? Hành động này không thể hoàn tác.')
                        ->modalSubmitActionLabel('Xóa')
                        ->modalCancelActionLabel('Hủy')
                        ->visible(fn() => in_array(Auth::user()->role, ['super_admin', 'organization_owner'])),
                ]),
            ]);
    }
}
