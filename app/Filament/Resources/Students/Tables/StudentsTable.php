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
use App\Exports\StudentsExcelExport;
use App\Models\Student;
use App\Models\Payment;
use App\Services\StudentFeeService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Response;
use Maatwebsite\Excel\Facades\Excel;

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
                TextColumn::make('profile_code')
                    ->label('Mã hồ sơ')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('full_name')
                    ->label('Họ và tên')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
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
                    })
                    ->toggleable(),
                TextColumn::make('collaborator.full_name')
                    ->label('Người giới thiệu')
                    ->formatStateUsing(fn($state) => $state ?: '—')
                    ->searchable(query: function ($query, $search) {
                        return $query->whereHas('collaborator', function ($q) use ($search) {
                            $q->where('full_name', 'like', "%$search%")
                                ->orWhere('email', 'like', "%$search%")
                                ->orWhere('phone', 'like', "%$search%");
                        });
                    })
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('dob')
                    ->label('Ngày sinh')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('major')
                    ->label('Ngành học')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('address')
                    ->label('Địa chỉ')
                    ->limit(50)
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('intake.name')
                    ->label('Đợt tuyển')
                    ->formatStateUsing(fn($state, $record) => $state ?: ($record->intake_month ? "Tháng {$record->intake_month}" : '—'))
                    ->sortable()
                    ->toggleable(),
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
                    })
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('application_status')
                    ->label('Trạng thái hồ sơ')
                    ->sortable()
                    ->toggleable()
                    ->formatStateUsing(function ($state, Student $record) {
                        // Nếu có application_status trong database, dùng nó
                        if ($state) {
                            return match ($state) {
                                'draft' => 'Đang nhập',
                                'pending_documents' => 'Thiếu giấy tờ',
                                'submitted' => 'Đã nộp hồ sơ',
                                'verified' => 'Đã xác minh',
                                'eligible' => 'Đủ điều kiện',
                                'ineligible' => 'Không đủ điều kiện',
                                default => $state,
                            };
                        }

                        // Fallback: tính toán từ status và document_checklist
                        $checklist = $record->document_checklist ?? [];

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

                        // Ưu tiên trạng thái không đủ điều kiện
                        if ($record->status === Student::STATUS_REJECTED) {
                            return 'Không đủ điều kiện';
                        }

                        // Đang nhập: hồ sơ mới tạo, chưa nộp
                        if (in_array($record->status, [Student::STATUS_NEW, Student::STATUS_CONTACTED])) {
                            return 'Đang nhập';
                        }

                        // Đã nộp nhưng thiếu giấy tờ
                        if (in_array($record->status, [Student::STATUS_SUBMITTED, Student::STATUS_APPROVED, Student::STATUS_ENROLLED]) && !$hasAllDocs) {
                            return 'Thiếu giấy tờ';
                        }

                        // Đã nộp đủ giấy tờ nhưng chưa đủ điều kiện (chờ xác minh / thanh toán)
                        if (in_array($record->status, [Student::STATUS_SUBMITTED, Student::STATUS_APPROVED]) && $hasAllDocs) {
                            return 'Đã nộp hồ sơ';
                        }

                        // Đủ điều kiện khi đã nhập học và đủ hồ sơ
                        if ($record->status === Student::STATUS_ENROLLED && $hasAllDocs) {
                            return 'Đủ điều kiện';
                        }

                        return 'Đang nhập';
                    })
                    ->badge()
                    ->color(fn(string $state) => match ($state) {
                        'Đang nhập' => 'gray',
                        'Đã nộp hồ sơ' => 'warning',
                        'Thiếu giấy tờ' => 'danger',
                        'Đủ điều kiện' => 'success',
                        'Không đủ điều kiện' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('status')
                    ->label('Lệ Phí')
                    ->toggleable()
                    ->badge()
                    ->color(function (Student $record): string {
                        // Ưu tiên hiển thị trạng thái thanh toán nếu có payment
                        if ($record->payment) {
                            return match ($record->payment->status) {
                                Payment::STATUS_NOT_PAID => 'gray',
                                Payment::STATUS_SUBMITTED => 'warning',
                                Payment::STATUS_VERIFIED => 'success',
                                Payment::STATUS_REVERTED => 'danger',
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
                                Payment::STATUS_VERIFIED => '✅ Đã nộp tiền',
                                Payment::STATUS_REVERTED => '↩️ Đã hoàn trả',
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
                                Payment::STATUS_VERIFIED => 'Đã nộp tiền và tạo commission',
                                Payment::STATUS_REVERTED => 'Đã hoàn trả từ trạng thái đã xác nhận',
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
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('updated_at')
                    ->label('Ngày cập nhật')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
            ])
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('application_status')
                    ->label('Trạng thái hồ sơ')
                    ->options([
                        'draft' => 'Đang nhập',
                        'pending_documents' => 'Thiếu giấy tờ',
                        'submitted' => 'Đã nộp hồ sơ',
                        'verified' => 'Đã xác minh',
                        'eligible' => 'Đủ điều kiện',
                        'ineligible' => 'Không đủ điều kiện',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (!isset($data['value']) || $data['value'] === '') {
                            return $query;
                        }
                        return $query->where('application_status', $data['value']);
                    }),

                \Filament\Tables\Filters\SelectFilter::make('payment_status')
                    ->label('Trạng thái thanh toán')
                    ->options([
                        Payment::STATUS_NOT_PAID => 'Chưa nộp tiền',
                        Payment::STATUS_SUBMITTED => 'Chờ xác minh',
                        Payment::STATUS_VERIFIED => 'Đã xác nhận',
                        Payment::STATUS_REVERTED => 'Đã hoàn trả',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (!isset($data['value']) || $data['value'] === '') {
                            return $query;
                        }
                        return $query->whereHas('payment', function (Builder $paymentQuery) use ($data) {
                            $paymentQuery->where('status', $data['value']);
                        });
                    }),

                \Filament\Tables\Filters\SelectFilter::make('program_type')
                    ->label('Hệ đào tạo')
                    ->options([
                        'REGULAR' => 'Chính quy',
                        'PART_TIME' => 'Vừa học vừa làm',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (!isset($data['value']) || $data['value'] === '') {
                            return $query;
                        }
                        return $query->where('program_type', $data['value']);
                    }),

                \Filament\Tables\Filters\SelectFilter::make('major')
                    ->label('Ngành học')
                    ->options(function () {
                        return \App\Models\Quota::whereNotNull('major_name')
                            ->distinct()
                            ->orderBy('major_name')
                            ->pluck('major_name', 'major_name')
                            ->toArray();
                    })
                    ->searchable()
                    ->query(function (Builder $query, array $data): Builder {
                        if (!isset($data['value']) || $data['value'] === '') {
                            return $query;
                        }
                        return $query->where('major', $data['value']);
                    }),

                \Filament\Tables\Filters\SelectFilter::make('intake_id')
                    ->label('Đợt tuyển sinh')
                    ->relationship('intake', 'name')
                    ->searchable()
                    ->preload(),

                \Filament\Tables\Filters\SelectFilter::make('organization_id')
                    ->label('Tổ chức')
                    ->relationship('organization', 'name')
                    ->searchable()
                    ->preload()
                    ->visible(fn() => Auth::user()?->role === 'super_admin'),

                \Filament\Tables\Filters\SelectFilter::make('status')
                    ->label('Trạng thái')
                    ->options(Student::getStatusOptions())
                    ->query(function (Builder $query, array $data): Builder {
                        if (!isset($data['value']) || $data['value'] === '') {
                            return $query;
                        }
                        return $query->where('status', $data['value']);
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
                        ->action(function (array $data, Student $record): void {
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
                                \App\Models\Payment::create([
                                    'student_id' => $record->id,
                                    'primary_collaborator_id' => $record->collaborator_id,
                                    'organization_id' => $record->organization_id,
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

                    Action::make('mark_enrolled')
                        ->label('Xác nhận SV nhập học')
                        ->icon('heroicon-o-academic-cap')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Xác nhận sinh viên nhập học')
                        ->modalDescription('Bạn có chắc chắn muốn đánh dấu sinh viên này đã nhập học?')
                        ->modalSubmitActionLabel('Xác nhận')
                        ->modalCancelActionLabel('Hủy')
                        ->visible(
                            fn(Student $record): bool =>
                            $record->status !== Student::STATUS_ENROLLED &&
                                ($record->payment?->status === Payment::STATUS_VERIFIED) &&
                                in_array(Auth::user()->role, ['super_admin', 'organization_owner'])
                        )
                        ->action(function (Student $record) {
                            // Kiểm tra checklist hồ sơ
                            $requiredDocuments = [
                                'phieu_tuyen_sinh',
                                'phieu_xet_tuyen',
                                'bang_cao_dang',
                                'bang_thpt',
                                'bang_diem',
                                'giay_khai_sinh',
                                'cccd',
                                'giay_kham_suc_khoe',
                                'anh_4x6',
                            ];

                            $checklist = $record->document_checklist ?? [];
                            $missingDocs = array_diff($requiredDocuments, $checklist);

                            if (!empty($missingDocs)) {
                                $docLabels = [
                                    'phieu_tuyen_sinh' => 'Phiếu tuyển sinh hệ CQ hoặc VHVL',
                                    'phieu_xet_tuyen' => 'Phiếu xét tuyển hệ đào tạo từ xa',
                                    'bang_cao_dang' => 'Bản sao công chứng bằng Cao đẳng',
                                    'bang_thpt' => 'Bản sao công chứng bằng THPT',
                                    'bang_diem' => 'Bản công chứng bảng điểm',
                                    'giay_khai_sinh' => 'Bản sao công chứng giấy khai sinh',
                                    'cccd' => 'Bản sao công chứng CCCD',
                                    'giay_kham_suc_khoe' => 'Giấy khám sức khỏe',
                                    'anh_4x6' => '04 ảnh 4x6cm',
                                ];

                                $missingList = array_map(fn($doc) => '• ' . ($docLabels[$doc] ?? $doc), $missingDocs);

                                \Filament\Notifications\Notification::make()
                                    ->title('Chưa đủ hồ sơ')
                                    ->body('Sinh viên chưa nộp đủ hồ sơ. Còn thiếu:' . "\n" . implode("\n", $missingList))
                                    ->warning()
                                    ->duration(10000)
                                    ->send();

                                return;
                            }

                            $record->update(['status' => Student::STATUS_ENROLLED]);

                            \Filament\Notifications\Notification::make()
                                ->title('Đã xác nhận sinh viên nhập học')
                                ->body('Sinh viên đã hoàn thành đầy đủ hồ sơ và được xác nhận nhập học.')
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
                        ->form([
                            \Filament\Forms\Components\TextInput::make('amount')
                                ->label('Số tiền (VNĐ)')
                                ->helperText('Nếu đang để 0, hãy nhập số tiền trước khi xác nhận (ví dụ: 1.750.000)')
                                ->default(function (Student $record) {
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
                                ->formatStateUsing(function ($state, Student $record) {
                                    // Luôn ưu tiên lấy từ payment->amount để đảm bảo hiển thị đúng
                                    if ($record->payment) {
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

                                    // Nếu state không hợp lệ, trả về rỗng
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
                        ])
                        ->visible(
                            fn(Student $record): bool =>
                            // Kế toán, cán bộ hồ sơ, chủ đơn vị, super_admin được phép xác nhận thanh toán
                            (in_array(Auth::user()->role, ['accountant', 'document', 'organization_owner', 'super_admin']) || (Auth::user()->roles && Auth::user()->roles->contains('name', 'accountant'))) &&
                                // Sinh viên phải có payment record
                                $record->payment &&
                                // Payment phải ở trạng thái chờ xác minh hoặc đã hoàn trả (có thể xác nhận lại)
                                in_array($record->payment->status, [Payment::STATUS_SUBMITTED, Payment::STATUS_REVERTED])
                        )
                        ->action(function (array $data, Student $record) {
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
                                    $payment->update(['amount' => $amount]);
                                }

                                // Xác minh thanh toán
                                $payment->markAsVerified(Auth::id());

                                // Chuyển trạng thái học viên sang Đã duyệt (APPROVED)
                                $record->update(['status' => Student::STATUS_APPROVED]);

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

                            // Chuyển status Học viên sang SUBMITTED
                            $record->update(['status' => Student::STATUS_SUBMITTED]);

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
                Action::make('export_excel')
                    ->label('Xuất Excel')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->action(function (\Filament\Tables\Contracts\HasTable $livewire) {
                        // Lấy query đã áp dụng filter và search từ bảng hiện tại thay vì lấy full
                        $query = $livewire->getFilteredTableQuery();
                        $query->with(['payment', 'organization']);

                        $filename = 'danh_sach_hoc_vien_' . date('Y-m-d_His') . '.xlsx';
                        return Excel::download(new StudentsExcelExport($query), $filename);
                    }),
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('Xóa đã chọn')
                        ->modalHeading('Xóa học viên đã chọn')
                        ->modalDescription('Bạn có chắc chắn muốn xóa các học viên đã chọn? Hành động này không thể hoàn tác.')
                        ->modalSubmitActionLabel('Xóa')
                        ->modalCancelActionLabel('Hủy')
                        ->visible(fn() => in_array(Auth::user()->role, ['super_admin', 'organization_owner'])),
                ]),
            ])
            ->emptyStateHeading('Không có học viên');
    }
}
