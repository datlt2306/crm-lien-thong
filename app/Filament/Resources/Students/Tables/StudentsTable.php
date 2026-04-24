<?php

namespace App\Filament\Resources\Students\Tables;

use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Notifications\Notification;
use App\Models\Payment;
use App\Models\Commission;
use App\Models\Collaborator;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use App\Exports\StudentsExcelExport;
use App\Models\Student;
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

                // Kiểm tra xem user có quyền chỉnh sửa sinh viên không
                if ($user->can('student_update')) {
                    // Nếu là CTV, chỉ được phép nếu payment chưa verified
                    if ($user->hasRole('ctv')) {
                        $hasVerifiedPayment = $record->payment?->status === Payment::STATUS_VERIFIED;
                        if (!$hasVerifiedPayment) {
                            return \App\Filament\Resources\Students\StudentResource::getUrl('edit', ['record' => $record]);
                        }
                        return null;
                    }
                    
                    return \App\Filament\Resources\Students\StudentResource::getUrl('edit', ['record' => $record]);
                }

                return null;
            })
            ->columns([
                TextColumn::make('profile_code')
                    ->label('Mã hồ sơ')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Hoạt động')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->toggleable(),
                Tables\Columns\IconColumn::make('has_transferred')
                    ->label('Chuyển hệ')
                    ->boolean()
                    ->trueIcon('heroicon-o-arrows-right-left')
                    ->falseIcon('')
                    ->trueColor('warning')
                    ->tooltip(fn($record) => $record->has_transferred ? 'Học viên này đã từng thực hiện chuyển hệ đào tạo' : null)
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
                    ->formatStateUsing(fn($state) => $state ?: '—')
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
                        'DISTANCE' => '💻 Đào tạo từ xa',
                        default => '—'
                    })
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        'REGULAR' => 'success',      // Xanh lá rõ ràng
                        'PART_TIME' => 'info',       // Xanh dương rõ ràng
                        'DISTANCE' => 'warning',     // Cam/Vàng rõ ràng
                        default => 'gray'
                    })
                    ->tooltip(fn($state) => match ($state) {
                        'REGULAR' => '🎓 Hệ đào tạo chính quy, học tập toàn thời gian',
                        'PART_TIME' => '⏰ Hệ vừa học vừa làm, linh hoạt thời gian',
                        'DISTANCE' => '💻 Hệ đào tạo từ xa, học trực tuyến linh hoạt',
                        default => ''
                    })
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('application_status')
                    ->label('Trạng thái hồ sơ')
                    ->sortable()
                    ->toggleable()
                    ->state(function (Student $record) {
                        // Nếu có application_status trong database, dùng nó
                        if ($record->application_status) {
                            return match ($record->application_status) {
                                'draft' => 'Đang nhập',
                                'pending_documents' => 'Thiếu giấy tờ',
                                'submitted' => 'Đã nộp hồ sơ',
                                'verified' => 'Đã xác minh',
                                'eligible' => 'Đủ điều kiện',
                                'ineligible' => 'Không đủ điều kiện',
                                default => $record->application_status,
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
                    ->color(fn(?string $state) => match ($state) {
                        'Đang nhập' => 'gray',
                        'Đã nộp hồ sơ' => 'warning',
                        'Thiếu giấy tờ' => 'danger',
                        'Đủ điều kiện' => 'success',
                        'Không đủ điều kiện' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('payment.status')
                    ->label('Lệ Phí')
                    ->toggleable()
                    ->badge()
                    ->color(function (?string $state): string {
                        return match ($state) {
                            Payment::STATUS_SUBMITTED => 'warning',
                            Payment::STATUS_VERIFIED => 'success',
                            Payment::STATUS_REVERTED => 'danger',
                            default => 'gray',
                        };
                    })
                    ->formatStateUsing(function (?string $state): string {
                        return match ($state) {
                            Payment::STATUS_SUBMITTED => '⏳ Chờ xác minh',
                            Payment::STATUS_VERIFIED => '✅ Đã nộp tiền',
                            Payment::STATUS_REVERTED => '↩️ Đã hoàn tiền',
                            default => '💳 Chưa nộp tiền',
                        };
                    })
                    ->description(function (Student $record) {
                        if ($record->payment?->status === Payment::STATUS_REVERTED && $record->payment->edit_reason) {
                            return "Lý do: " . $record->payment->edit_reason;
                        }
                        return null;
                    })
                    ->tooltip(function (Student $record): string {
                        if ($record->payment) {
                            return match ($record->payment->status) {
                                Payment::STATUS_NOT_PAID => 'Học viên chưa nộp tiền',
                                Payment::STATUS_SUBMITTED => 'Đã nộp tiền, chờ kế toán xác minh',
                                Payment::STATUS_VERIFIED => 'Đã xác minh thanh toán',
                                Payment::STATUS_REVERTED => 'Đã hoàn trả thanh toán',
                                default => '',
                            };
                        }
                        return 'Chưa có thông tin thanh toán';
                    })
                    ->searchable(),

                Tables\Columns\TextColumn::make('payment_id_for_proof')
                    ->label('Hóa đơn')
                    ->state(fn(Student $record) => $record->id)
                    ->formatStateUsing(function ($state, Student $record) {
                        $payment = $record->payment;
                        if (!$payment) return '—';
                        
                        $links = [];
                        
                        // Minh chứng từ SV/CTV
                        if ($payment->bill_path) {
                            $url = $payment->bill_url;
                            $links[] = "<a href='{$url}' target='_blank' class='inline-flex items-center gap-1 text-primary-600 hover:underline' title='Xem hóa đơn sv nộp'>
                                <span>Xem bill nộp tiền</span>
                            </a>";
                        }
                        
                        // Phiếu thu từ Kế toán
                        if ($payment->receipt_path) {
                            $isVerified = $payment->status === Payment::STATUS_VERIFIED;
                            $url = $payment->receipt_url;
                            $colorClass = $isVerified ? 'text-success-600' : 'text-warning-600';
                            $label = $isVerified ? 'Xem Phiếu thu' : 'Phiếu thu (Chờ xác nhận)';
                            $title = $isVerified ? 'Xem phiếu thu chính thức' : 'Phiếu thu đã tải lên, chờ xác minh thanh toán';
                            
                            $links[] = "<a href='{$url}' target='_blank' class='inline-flex items-center gap-1 {$colorClass} hover:underline' title='{$title}'>
                                <span>{$label}</span>
                            </a>";
                        }

                        // Minh chứng hoàn tiền
                        if ($payment->refund_proof_path) {
                            $url = $payment->refund_proof_url;
                            $links[] = "<a href='{$url}' target='_blank' class='inline-flex items-center gap-1 text-danger-600 hover:underline' title='Xem minh chứng đã hoàn trả tiền thừa'>
                                <span>Xem minh chứng hoàn tiền</span>
                            </a>";
                        }
                        
                        return implode('<br>', $links) ?: '—';
                    })
                    ->html()
                    ->toggleable(isToggledHiddenByDefault: false),

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
                        
                        if ($data['value'] === Payment::STATUS_NOT_PAID) {
                            return $query->where(function (Builder $q) {
                                $q->whereDoesntHave('payment')
                                  ->orWhereHas('payment', fn (Builder $pq) => $pq->where('status', Payment::STATUS_NOT_PAID));
                            });
                        }

                        return $query->whereHas('payment', function (Builder $paymentQuery) use ($data) {
                            $paymentQuery->where('status', $data['value']);
                        });
                    }),

                \Filament\Tables\Filters\TernaryFilter::make('missing_receipt')
                    ->label('Thiếu phiếu thu')
                    ->placeholder('Tất cả')
                    ->trueLabel('Học viên thiếu phiếu thu')
                    ->falseLabel('Học viên đã đủ phiếu thu')
                    ->query(function (Builder $query, $state) {
                        if ($state === '1' || $state === true) {
                            return $query->whereHas('payment', function (Builder $q) {
                                $q->where('status', Payment::STATUS_VERIFIED)
                                  ->whereNull('receipt_path');
                            });
                        }

                        if ($state === '0' || $state === false) {
                            return $query->where(function (Builder $q) {
                                $q->whereDoesntHave('payment')
                                  ->orWhereHas('payment', fn (Builder $pq) => $pq->whereNotNull('receipt_path'));
                            });
                        }

                        return $query;
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
                        return \Illuminate\Support\Facades\Cache::remember('quota_major_names', now()->addHour(), function () {
                            return \App\Models\Quota::whereNotNull('major_name')
                                ->distinct()
                                ->orderBy('major_name')
                                ->pluck('major_name', 'major_name')
                                ->toArray();
                        });
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


                \Filament\Tables\Filters\SelectFilter::make('status')
                    ->label('Trạng thái')
                    ->options(Student::getStatusOptions())
                    ->query(function (Builder $query, array $data): Builder {
                        if (!isset($data['value']) || $data['value'] === '') {
                            return $query;
                        }
                        return $query->where('status', $data['value']);
                    }),

                \Filament\Tables\Filters\TernaryFilter::make('awaiting_refund')
                    ->label('Chờ hoàn tiền thừa')
                    ->placeholder('Tất cả')
                    ->trueLabel('Có tiền thừa chưa trả')
                    ->falseLabel('Đã trả hoặc không có')
                    ->query(function (Builder $query, $state) {
                        if ($state === '1' || $state === true) {
                            return $query->whereHas('payment', function (Builder $q) {
                                $q->where('excess_amount', '>', 0)
                                  ->where('refund_status', 'pending');
                            });
                        }
                        return $query;
                    }),


            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make()
                        ->label('Chỉnh sửa')
                        ->icon('heroicon-o-pencil')
                        ->visible(function (Student $record) {
                            $user = Auth::user();

                            if ($user->can('student_update')) {
                                if ($user->hasRole('ctv')) {
                                    return $record->payment?->status !== Payment::STATUS_VERIFIED;
                                }
                                return true;
                            }

                            return false;
                        }),

                    Action::make('confirm_payment')
                        ->label('Gửi Kế toán')
                        ->icon('heroicon-o-paper-airplane')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading(fn($record) => $record->status === Student::STATUS_SUBMITTED ? 'Cập nhật minh chứng nộp tiền' : 'Gửi Kế toán & Minh chứng')
                        ->modalDescription('Cập nhật số tiền và tải lên hóa đơn/bill chuyển khoản để Kế toán đối soát.')
                        ->modalSubmitActionLabel('Gửi xác nhận')
                        ->form([
                            \Filament\Forms\Components\Placeholder::make('amount_display')
                                ->label('Số tiền dự kiến (VNĐ)')
                                ->content(function (Student $record) {
                                    $expectedFee = app(\App\Services\StudentFeeService::class)->getExpectedFeeForStudent($record);
                                    return $expectedFee !== null ? number_format((int)$expectedFee, 0, '', '.') . ' VNĐ' : 'Chưa xác định';
                                }),
                            \Filament\Forms\Components\FileUpload::make('bill')
                                ->label('Minh chứng nộp tiền (Bill)')
                                ->acceptedFileTypes(['image/*', 'application/pdf'])
                                ->maxSize(5120)
                                ->disk('google')
                                ->directory('/')
                                ->getUploadedFileNameForStorageUsing(function ($file, Student $record) {
                                    // Sử dụng logic chuẩn hóa từ Model Payment
                                    $year = now()->format('Y');
                                    $profileCode = $record->profile_code;
                                    $fullName = $record->full_name;
                                    $major = $record->major;
                                    $ext = $file->getClientOriginalExtension();
                                    
                                    $systemCode = match (strtoupper((string)$record->program_type)) {
                                        'REGULAR', 'CHÍNH QUY' => 'CQ',
                                        'PART_TIME', 'VỪA HỌC VỪA LÀM' => 'VHVL',
                                        'DISTANCE', 'TỪ XA' => 'TX',
                                        default => $record->program_type
                                    };

                                    // Format: HS2026000194_Dat Le Trong_CNTT_CQ.png
                                    $fileName = "{$profileCode}_{$fullName}_{$major}_{$systemCode}.{$ext}";
                                    
                                    return "Hóa đơn đăng ký/{$year}/{$fileName}";
                                })
                                ->required()
                                ->helperText('Tải lên ảnh bill chuyển khoản hoặc phiếu thu để kế toán xác minh.'),
                        ])
                        ->visible(function (Student $record): bool {
                            $user = Auth::user();
                            
                            // Nếu đã được kế toán xác minh (VERIFIED) thì không cho sửa bill nữa
                            if ($record->payment?->status === Payment::STATUS_VERIFIED) {
                                return false;
                            }

                            if (!$user->can('payment_upload_bill')) return false;

                            // Ownership check for CTV: Only allow if they own the student
                            if ($user->hasRole('ctv')) {
                                $collaborator = Collaborator::where('email', $user->email)->first();
                                if (!$collaborator || $record->collaborator_id !== $collaborator->id) {
                                    return false;
                                }
                            }

                            return true;
                        })
                        ->action(function (array $data, Student $record): void {
                            // Tự động lấy số tiền từ Service thay vì input
                            $amount = (int) app(\App\Services\StudentFeeService::class)->getExpectedFeeForStudent($record);
                            $billPath = $data['bill'] ?? null;

                            $payment = $record->payment;
                            if ($payment) {
                                // Xóa file cũ nếu tải lên file mới
                                if ($billPath && $payment->bill_path && $billPath !== $payment->bill_path) {
                                    Storage::disk('google')->delete($payment->bill_path);
                                }

                                $payment->update([
                                    'amount' => $amount,
                                    'status' => Payment::STATUS_SUBMITTED,
                                    'bill_path' => $billPath ?: $payment->bill_path,
                                    'receipt_uploaded_by' => Auth::id(),
                                    'receipt_uploaded_at' => now(),
                                ]);
                            } else {
                                \App\Models\Payment::create([
                                    'student_id' => $record->id,
                                    'primary_collaborator_id' => $record->collaborator_id,
                                    'program_type' => $record->program_type ?? 'REGULAR',
                                    'amount' => $amount,
                                    'status' => Payment::STATUS_SUBMITTED,
                                    'bill_path' => $billPath,
                                    'receipt_uploaded_by' => Auth::id(),
                                    'receipt_uploaded_at' => now(),
                                ]);
                            }

                            $record->update(['status' => Student::STATUS_SUBMITTED]);

                            \Filament\Notifications\Notification::make()
                                ->title('Gửi hồ sơ thành công')
                                ->body('Học viên đã được gửi cho Kế toán đối soát theo quy trình.')
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
                        ->visible(function (Student $record): bool {
                            $user = Auth::user();

                            // Kiểm tra quyền thay đổi trạng thái nhập học
                            if (!$user->can('student_change_status')) {
                                return false;
                            }

                            // Chỉ hiện nút xác nhận nhập học NẾU lệ phí đã được xác minh xong
                            return $record->payment?->status === \App\Models\Payment::STATUS_VERIFIED;
                        })
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

                    // Action cho kế toán xác nhận
                    Action::make('verify_payment')
                        ->label('Xác nhận nộp tiền')
                        ->icon('heroicon-o-check-badge')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Xác nhận nộp tiền')
                        ->modalDescription('Xác nhận đã nhận tiền từ học viên. Hệ thống sẽ chuyển trạng thái thanh toán sang "Đã xác nhận" và tạo commission.')
                        ->modalSubmitActionLabel('Xác nhận')
                        ->modalCancelActionLabel('Hủy')
                        ->tooltip('Xác nhận đã nhận tiền từ học viên và chuyển trạng thái thanh toán sang "Đã xác nhận"')
                        ->form([
                            \Filament\Forms\Components\Placeholder::make('amount_info')
                                ->label('Số tiền cần xác nhận')
                                ->content(function (Student $record) {
                                    $amount = $record->payment?->amount ?? app(\App\Services\StudentFeeService::class)->getExpectedFeeForStudent($record);
                                    return number_format((int)$amount, 0, '', '.') . ' VNĐ';
                                })
                                ->helperText('Đối soát kỹ với minh chứng (Bill) và sao kê ngân hàng trước khi xác nhận.'),
                            \Filament\Forms\Components\FileUpload::make('receipt')
                                ->label('Phiếu thu chính thức (Không bắt buộc)')
                                ->acceptedFileTypes(['image/*', 'application/pdf'])
                                ->maxSize(5120)
                                ->disk('google')
                                ->directory('/')
                                ->getUploadedFileNameForStorageUsing(function ($file, Student $record) {
                                    $year = now()->format('Y');
                                    $profileCode = $record->profile_code;
                                    $fullName = $record->full_name;
                                    $major = $record->major;
                                    $ext = $file->getClientOriginalExtension();
                                    
                                    $systemCode = match (strtoupper((string)$record->program_type)) {
                                        'REGULAR', 'CHÍNH QUY' => 'CQ',
                                        'PART_TIME', 'VỪA HỌC VỪA LÀM' => 'VHVL',
                                        'DISTANCE', 'TỪ XA' => 'TX',
                                        default => $record->program_type
                                    };

                                    // Format: HS2026000194_Dat Le Trong_CNTT_CQ.png
                                    $fileName = "{$profileCode}_{$fullName}_{$major}_{$systemCode}.{$ext}";
                                    
                                    return "Phiếu thu/{$year}/{$fileName}";
                                })
                                ->helperText('Bạn có thể tải lên phiếu thu ngay bây giờ hoặc bổ sung sau bằng hành động "Tải lên Phiếu thu".'),
                        ])
                        ->visible(function (Student $record): bool {
                            $user = Auth::user();
                            if (!$user->can('payment_verify')) return false;
                            
                            // Cho phép xác nhận ngay cả khi chưa có record payment (tạo mới luôn) hoặc đang ở các trạng thái chờ
                            if ($user->can('payment_verify')) {
                                return !$record->payment || in_array($record->payment->status, [
                                    \App\Models\Payment::STATUS_NOT_PAID,
                                    \App\Models\Payment::STATUS_SUBMITTED,
                                    \App\Models\Payment::STATUS_REVERTED
                                ]);
                            }

                            return $record->payment && in_array($record->payment->status, [
                                \App\Models\Payment::STATUS_SUBMITTED,
                                \App\Models\Payment::STATUS_REVERTED
                            ]);
                        })
                        ->action(function (array $data, Student $record) {
                            $payment = $record->payment;
                            
                            // Nếu xác nhận ngay mà chưa có record payment (thanh toán trực tiếp)
                            if (!$payment) {
                                $amount = (int) app(\App\Services\StudentFeeService::class)->getExpectedFeeForStudent($record);
                                $payment = \App\Models\Payment::create([
                                    'student_id' => $record->id,
                                    'primary_collaborator_id' => $record->collaborator_id,
                                    'program_type' => $record->program_type ?? 'REGULAR',
                                    'amount' => $amount,
                                    'status' => Payment::STATUS_NOT_PAID, // Sẽ update ngay sau đây
                                ]);
                            }

                            $amount = (int) ($payment->amount ?: app(\App\Services\StudentFeeService::class)->getExpectedFeeForStudent($record));
                            
                            $updateData = [
                                'amount' => $amount,
                                'status' => Payment::STATUS_VERIFIED,
                            ];

                            // Nếu có upload phiếu thu trong lúc xác nhận
                            if (!empty($data['receipt'])) {
                                $updateData['receipt_path'] = $data['receipt'];
                                $updateData['receipt_uploaded_at'] = now();
                                $updateData['receipt_uploaded_by'] = Auth::id();
                            }

                            $payment->update($updateData);
                            $payment->markAsVerified(Auth::id());
                            
                            $record->update(['status' => Student::STATUS_APPROVED]);
                            (new \App\Services\CommissionService())->createCommissionFromPayment($payment);

                            \Filament\Notifications\Notification::make()->title('Xác minh thành công')->success()->send();
                        }),

                    // Nút riêng biệt cho Kế toán/Hồ sơ Upload Phiếu Thu
                    Action::make('upload_receipt')
                        ->label(fn(Student $record) => ($record->payment && $record->payment->receipt_path) ? 'Cập nhật Phiếu thu' : 'Tải lên Phiếu thu')
                        ->icon(fn(Student $record) => ($record->payment && $record->payment->receipt_path) ? 'heroicon-o-pencil-square' : 'heroicon-o-document-arrow-up')
                        ->color('info')
                        ->modalHeading('Tải lên Phiếu thu')
                        ->modalDescription('Tải lên bản scan phiếu thu từ nhà trường.')
                        ->form(function (Student $record): array {
                            $payment = $record->payment;
                            $hasReceipt = $payment && $payment->receipt_path;

                            return [
                                \Filament\Forms\Components\FileUpload::make('receipt')
                                    ->label($hasReceipt ? 'File phiếu thu mới (để trống nếu không thay đổi)' : 'Phiếu thu (Bản cứng scan)')
                                    ->acceptedFileTypes(['image/*', 'application/pdf'])
                                    ->maxSize(5120)
                                    ->disk('google')
                                    ->directory('/')
                                    ->getUploadedFileNameForStorageUsing(function ($file, Student $record) {
                                        $year = now()->format('Y');
                                        $profileCode = $record->profile_code;
                                        $fullName = $record->full_name;
                                        $major = $record->major;
                                        $ext = $file->getClientOriginalExtension();
                                        
                                        $systemCode = match (strtoupper((string)$record->program_type)) {
                                            'REGULAR', 'CHÍNH QUY' => 'CQ',
                                            'PART_TIME', 'VỪA HỌC VỪA LÀM' => 'VHVL',
                                            'DISTANCE', 'TỪ XA' => 'TX',
                                            default => $record->program_type
                                        };

                                        // Format: HS2026000194_Dat Le Trong_CNTT_CQ.png
                                        $fileName = "{$profileCode}_{$fullName}_{$major}_{$systemCode}.{$ext}";
                                        
                                        return "Phiếu thu/{$year}/{$fileName}";
                                    })
                                    ->required(!$hasReceipt)
                            ];
                        })
                        ->visible(function (Student $record): bool {
                            $user = Auth::user();
                            if ($record->payment && $record->payment->receipt_path) {
                                return $user->can('payment_update_receipt');
                            }
                            return $user->can('payment_upload_receipt');
                        })
                        ->action(function (array $data, Student $record) {
                            $payment = $record->payment;
                            if ($payment->receipt_path) {
                                Storage::disk('google')->delete($payment->receipt_path);
                            }
                            $payment->update([
                                'receipt_path' => $data['receipt'],
                                'receipt_uploaded_at' => now(),
                                'receipt_uploaded_by' => Auth::id(),
                            ]);
                            Notification::make()->title('Đã tải lên phiếu thu thành công')->success()->send();
                        }),

                    Action::make('revert_payment')
                        ->label('Hoàn trả')
                        ->icon('heroicon-o-arrow-path')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Hoàn trả trạng thái thanh toán')
                        ->modalDescription('Hành động này sẽ hủy xác nhận đã nộp tiền, đưa hồ sơ học viên quay lại trạng thái "Mới".')
                        ->form([
                            \Filament\Forms\Components\Textarea::make('reason')
                                ->label('Lý do hoàn trả')
                                ->placeholder('Vd: Học viên rút hồ sơ, sai thông tin giao dịch...')
                                ->required()
                                ->rows(3),
                        ])
                        ->modalSubmitActionLabel('Xác nhận hoàn trả')
                        ->visible(function (Student $record): bool {
                            $user = Auth::user();
                            if (!$record->payment || $record->payment->status !== \App\Models\Payment::STATUS_VERIFIED) {
                                return false;
                            }

                            // Chặn hoàn trả nếu đã xác nhận chi hoa hồng
                            if ($record->payment->hasConfirmedCommission()) {
                                return false;
                            }

                            return $user->can('payment_revert');
                        })
                        ->action(function (array $data, Student $record) {
                            $payment = $record->payment;
                            if (!$payment) return;

                            // Kiểm tra lại lần cuối trước khi thực hiện
                            if ($payment->hasConfirmedCommission()) {
                                \Filament\Notifications\Notification::make()
                                    ->danger()
                                    ->title('Không thể hoàn trả')
                                    ->body('Hoa hồng liên quan đã được xác nhận chi hoặc đã chi trả cho CTV. Vui lòng xử lý hoa hồng trước.')
                                    ->send();
                                return;
                            }

                            // Chuyển trạng thái thanh toán về Hoàn trả (Reverted)
                            $payment->update([
                                'status' => \App\Models\Payment::STATUS_REVERTED,
                                'edit_reason' => $data['reason'] ?? null,
                                'verified_at' => null,
                                'verified_by' => null,
                                'receipt_path' => null,
                                'receipt_number' => null,
                                'receipt_uploaded_by' => null,
                                'receipt_uploaded_at' => null,
                            ]);

                            // Đưa học viên về trạng thái Mới để CTV có thể nộp lại
                            $record->update(['status' => Student::STATUS_NEW]);
                            
                            \Filament\Notifications\Notification::make()
                                ->success()
                                ->title('Đã hoàn trả trạng thái thành công')
                                ->body('Hồ sơ học viên đã được đưa về trạng thái Mới.')
                                ->send();
                        }),

                    Action::make('transfer_program')
                        ->label('Chuyển hệ đào tạo')
                        ->icon('heroicon-o-arrows-right-left')
                        ->color('info')
                        ->modalHeading('Chuyển hệ đào tạo')
                        ->modalDescription('Hành động này sẽ tự động tìm chỉ tiêu phù hợp và tính toán lại lệ phí/hoa hồng.')
                        ->form([
                            \Filament\Forms\Components\Select::make('program_type')
                                ->label('Hệ đào tạo mới')
                                ->options(function (Student $record) {
                                    $major = $record->major;
                                    
                                    // Tìm tất cả các Quota đang hoạt động của ngành này
                                    $quotas = \App\Models\Quota::active()
                                        ->where('major_name', $major)
                                        ->with('intake')
                                        ->get();

                                    $options = [];
                                    foreach ($quotas as $q) {
                                        $label = match($q->program_name) {
                                            'REGULAR' => 'Chính quy',
                                            'PART_TIME' => 'Vừa học vừa làm',
                                            'DISTANCE' => 'Đào tạo từ xa',
                                            default => $q->program_name
                                        };

                                        // Thêm thông tin Chỉ tiêu và Đợt vào label
                                        $intakeLabel = $q->intake?->name ?: 'Không rõ đợt';
                                        $options[$q->program_name] = "{$label} - {$intakeLabel}";
                                    }

                                    return $options;
                                })
                                ->required()
                                ->placeholder('Chọn hệ đào tạo mới...')
                                ->default(fn($record) => $record->program_type),
                            \Filament\Forms\Components\Textarea::make('reason')
                                ->label('Lý do chuyển hệ')
                                ->required()
                                ->placeholder('Vd: Sinh viên xin đổi sang học từ xa để vừa học vừa làm...'),
                        ])
                        ->action(function (array $data, Student $record) {
                            $oldQuota = $record->quota;
                            $oldProgramType = $record->program_type;
                            $oldMajor = $record->major;

                            // Tự động tìm Quota mới khớp với Ngành hiện tại + Hệ đào tạo mới
                            // Ưu tiên đợt (Intake) hiện tại
                            $newQuota = \App\Models\Quota::query()
                                ->where('major_name', $oldMajor)
                                ->where('program_name', $data['program_type'])
                                ->where('intake_id', $record->intake_id)
                                ->where('status', \App\Models\Quota::STATUS_ACTIVE)
                                ->first();

                            // Nếu không có trong đợt hiện tại, tìm đợt khác có chỉ tiêu này
                            if (!$newQuota) {
                                $newQuota = \App\Models\Quota::query()
                                    ->where('major_name', $oldMajor)
                                    ->where('program_name', $data['program_type'])
                                    ->where('status', \App\Models\Quota::STATUS_ACTIVE)
                                    ->orderByDesc('intake_id') // Ưu tiên đợt mới nhất
                                    ->first();
                            }

                            if (!$newQuota) {
                                \Filament\Notifications\Notification::make()
                                    ->danger()
                                    ->title('Không tìm thấy chỉ tiêu')
                                    ->body("Không tìm thấy bất kỳ chỉ tiêu \"" . (\App\Models\Student::getProgramTypeOptions()[$data['program_type']] ?? $data['program_type']) . "\" nào cho ngành \"{$oldMajor}\" trên hệ thống.")
                                    ->send();
                                return;
                            }

                            // 1. Tính toán chênh lệch lệ phí
                            $feeService = app(\App\Services\StudentFeeService::class);
                            $oldExpectedFee = $feeService->getExpectedFeeForStudent($record);
                            
                            // Giả lập student mới để tính phí dự kiến
                            $tempStudent = clone $record;
                            $tempStudent->quota_id = $newQuota->id;
                            $tempStudent->program_type = $data['program_type'];
                            $newExpectedFee = $feeService->getExpectedFeeForStudent($tempStudent);

                            $feeDifference = (float)($oldExpectedFee ?? 0) - (float)($newExpectedFee ?? 0);

                            // 2. Cập nhật sinh viên
                            $record->update([
                                'quota_id' => $newQuota->id,
                                'program_type' => $data['program_type'],
                                'intake_id' => $newQuota->intake_id, // Cập nhật đợt mới nếu cần
                                'has_transferred' => true,
                            ]);

                            // 3. Xử lý Payment & Tiền thừa
                            if ($record->payment && $record->payment->status === Payment::STATUS_VERIFIED) {
                                // Cập nhật hệ đào tạo trong payment để CommissionService lấy đúng dữ liệu mới
                                $record->payment->update(['program_type' => $data['program_type']]);

                                if ($feeDifference > 0) {
                                    $record->payment->update([
                                        'excess_amount' => (float)$record->payment->excess_amount + $feeDifference,
                                        'refund_status' => 'pending',
                                        'refund_notes' => ($record->payment->refund_notes ? $record->payment->refund_notes . "\n" : "") . "Chênh lệch phí khi chuyển hệ: " . number_format($feeDifference, 0, ',', '.') . " VNĐ",
                                    ]);
                                }
                            }

                            // 4. Xử lý Hoa hồng (Commission)
                            $payment = $record->payment?->fresh();
                            if ($payment && $payment->commission) {
                                $commissionService = new \App\Services\CommissionService();
                                
                                // Gọi service tính toán lại số tiền cho các item
                                $commissionService->recalculateCommissionOnTransfer($payment);

                                // Refresh để lấy dữ liệu mới sau khi recalculate
                                $payment->load('commission.items');
                                
                                foreach ($payment->commission->items as $item) {
                                    $isPaid = in_array($item->status, [
                                        \App\Models\CommissionItem::STATUS_PAID,
                                        \App\Models\CommissionItem::STATUS_PAYMENT_CONFIRMED,
                                        \App\Models\CommissionItem::STATUS_RECEIVED_CONFIRMED
                                    ]);

                                    $noteMessage = $isPaid 
                                        ? "⚠️ SV chuyển hệ sau khi đã chi trả. Cần đối soát lại hệ mới (" . (\App\Models\Student::getProgramTypeOptions()[$data['program_type']] ?? $data['program_type']) . ")."
                                        : "ℹ️ Thông tin đã thay đổi do SV chuyển hệ. Kế toán kiểm tra lại trước khi chốt.";
                                    
                                    // Làm sạch và gom nhóm ghi chú để tránh trùng lặp
                                    $currentNotes = $item->notes ?? '';
                                    if (!str_contains($currentNotes, $noteMessage)) {
                                        $item->update([
                                            'notes' => trim($currentNotes . " " . $noteMessage),
                                        ]);
                                    }
                                }
                            }

                            // 5. Lưu lịch sử
                            \App\Models\StudentTransfer::create([
                                'student_id' => $record->id,
                                'old_quota_id' => $oldQuota?->id,
                                'new_quota_id' => $newQuota->id,
                                'old_program_type' => $oldProgramType,
                                'new_program_type' => $data['program_type'],
                                'old_major' => $oldMajor,
                                'new_major' => $record->major,
                                'fee_difference' => $feeDifference,
                                'reason' => $data['reason'],
                                'created_by' => Auth::id(),
                            ]);

                            $intakeName = $newQuota->intake?->name;
                            \Filament\Notifications\Notification::make()
                                ->success()
                                ->title('Chuyển hệ thành công')
                                ->body("Đã chuyển sang hệ " . (\App\Models\Student::getProgramTypeOptions()[$data['program_type']] ?? $data['program_type']) . " " . ($intakeName ? "({$intakeName})" : "") . ". " . ($feeDifference > 0 ? "Số tiền thừa: " . number_format($feeDifference, 0, ',', '.') . " VNĐ" : ""))
                                ->send();
                        })
                        ->visible(fn() => Auth::user()->can('student_update')),

                    Action::make('confirm_refund')
                        ->label('Xác nhận hoàn tiền thừa')
                        ->icon('heroicon-o-currency-dollar')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Xác nhận đã trả tiền thừa cho SV')
                        ->modalDescription(fn($record) => "Học viên này đang có số tiền thừa: " . number_format($record->payment->excess_amount, 0, ',', '.') . " VNĐ. Xác nhận bạn đã chuyển khoản trả lại sinh viên?")
                        ->form([
                            \Filament\Forms\Components\FileUpload::make('refund_proof')
                                ->label('Bằng chứng chuyển khoản')
                                ->disk('google')
                                ->directory('Minh chứng hoàn tiền')
                                ->required(),
                            \Filament\Forms\Components\Textarea::make('refund_notes')
                                ->label('Ghi chú hoàn tiền'),
                        ])
                        ->action(function (array $data, Student $record) {
                            $record->payment->update([
                                'refund_status' => 'completed',
                                'refund_proof_path' => $data['refund_proof'],
                                'refund_notes' => ($record->payment->refund_notes ? $record->payment->refund_notes . "\n" : "") . "Đã hoàn trả: " . $data['refund_notes'],
                            ]);

                            Notification::make()
                                ->title('Đã xác nhận hoàn tiền')
                                ->success()
                                ->send();
                        })
                        ->visible(function (Student $record) {
                            return Auth::user()->can('payment_verify') && 
                                   $record->payment && 
                                   $record->payment->excess_amount > 0 && 
                                   $record->payment->refund_status === 'pending';
                        }),
                    Action::make('toggle_active')
                        ->label(fn($record) => $record->is_active ? 'Ngưng hoạt động' : 'Kích hoạt lại')
                        ->icon(fn($record) => $record->is_active ? 'heroicon-m-no-symbol' : 'heroicon-m-check-circle')
                        ->color(fn($record) => $record->is_active ? 'danger' : 'success')
                        ->action(fn($record) => $record->update(['is_active' => !$record->is_active]))
                        ->requiresConfirmation()
                        ->visible(function (Student $record) {
                            $user = Auth::user();
                            if (!$user || !$user->can('student_change_status')) return false;

                            // Đối với CTV (hoặc người chỉ có quyền cơ bản): Chỉ được ngưng hoạt động khi CHƯA nộp tiền
                            if ($user->role === 'ctv') {
                                $canToggle = !$record->payment || $record->payment->status === \App\Models\Payment::STATUS_NOT_PAID;
                                return $canToggle;
                            }

                            return true;
                        }),

                    Action::make('force_delete_inactive')
                        ->label('Xóa vĩnh viễn')
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Xóa vĩnh viễn học viên')
                        ->modalDescription('Hành động này sẽ xóa hoàn toàn dữ liệu học viên khỏi hệ thống và không thể khôi phục. Bạn chắc chắn chứ?')
                        ->action(fn($record) => $record->forceDelete())
                        ->visible(function (Student $record) {
                            $user = Auth::user();
                            if ($record->is_active || !$user->can('student_force_delete')) return false;

                            return true;
                        }),

                    DeleteAction::make()
                        ->label('Xóa')
                        ->modalHeading('Xóa học viên')
                        ->modalDescription('Bạn có chắc chắn muốn xóa học viên này? Nếu học viên đã có dữ liệu tài chính, hệ thống sẽ tự động chuyển sang trạng thái Ngừng hoạt động.')
                        ->modalSubmitActionLabel('Xóa/Vô hiệu hóa')
                        ->visible(function (Student $record) {
                            $user = Auth::user();
                            return $user->can('student_delete');
                        })
                        ->action(function ($record) {
                            // Kiểm tra dữ liệu tài chính
                            $hasFinancialData = Payment::where('student_id', $record->id)->exists() || 
                                              Commission::where('student_id', $record->id)->exists();

                            if ($hasFinancialData) {
                                // Nếu có dữ liệu tài chính -> Chỉ vô hiệu hóa
                                $record->update(['is_active' => false]);
                                
                                Notification::make()
                                    ->title('Đã chuyển sang Ngừng hoạt động')
                                    ->body("Học viên {$record->full_name} đã có dữ liệu tài chính nên không thể xóa vĩnh viễn. Hệ thống đã tự động chuyển trạng thái.")
                                    ->warning()
                                    ->send();
                            } else {
                                // Nếu không có dữ liệu -> Xóa cứng
                                $record->delete();
                                
                                Notification::make()
                                    ->title('Đã xóa vĩnh viễn')
                                    ->success()
                                    ->send();
                            }
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
                    ->visible(fn() => Auth::user()->can('student_export'))
                    ->action(function (\Filament\Tables\Contracts\HasTable $livewire) {
                        // Lấy query đã áp dụng filter và search từ bảng hiện tại thay vì lấy full
                        $query = $livewire->getFilteredTableQuery();
                        $query->with(['payment']);

                        $filename = 'danh_sach_hoc_vien_' . date('Y-m-d_His') . '.xlsx';
                        return Excel::download(new StudentsExcelExport($query), $filename);
                    }),
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('Xóa đã chọn')
                        ->modalHeading('Xóa học viên đã chọn')
                        ->modalDescription('Các học viên đã có dữ liệu tài chính sẽ được tự động chuyển sang trạng thái Ngừng hoạt động thay vì xóa vĩnh viễn.')
                        ->modalSubmitActionLabel('Bắt đầu xử lý')
                        ->visible(fn() => Auth::user()->can('student_delete'))
                        ->action(function ($records) {
                            $deletedCount = 0;
                            $deactivatedCount = 0;

                            foreach ($records as $record) {
                                $hasFinancialData = Payment::where('student_id', $record->id)->exists() || 
                                                  Commission::where('student_id', $record->id)->exists();

                                if ($hasFinancialData) {
                                    $record->update(['is_active' => false]);
                                    $deactivatedCount++;
                                } else {
                                    $record->delete();
                                    $deletedCount++;
                                }
                            }

                            Notification::make()
                                ->title('Xử lý hoàn tất')
                                ->body("Đã xóa vĩnh viễn $deletedCount mục và chuyển Ngừng hoạt động $deactivatedCount mục có dữ liệu tài chính.")
                                ->success()
                                ->send();
                        }),
                ]),
            ])
            ->emptyStateHeading('Không có học viên')
            ->defaultSort('id', 'desc');
    }
}
