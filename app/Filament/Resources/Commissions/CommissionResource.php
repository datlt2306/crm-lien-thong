<?php

namespace App\Filament\Resources\Commissions;

use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Gate;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Models\Commission;
use App\Models\CommissionItem;
use App\Models\Collaborator;
use App\Models\Student;
use App\Filament\Resources\Commissions\Pages\ListCommissions;
use Illuminate\Support\Facades\Auth;

class CommissionResource extends Resource {
    protected static ?string $model = Commission::class;
    protected static string|\UnitEnum|null $navigationGroup = 'Tài chính';
    protected static ?string $navigationLabel = 'Hoa hồng & Đối soát';
    protected static ?int $navigationSort = 2;
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedCurrencyDollar;

    public static function shouldRegisterNavigation(): bool {
        $user = Auth::user();
        if (!$user) return false;

        // Cho phép hiển thị nếu có quyền xem danh sách hoa hồng
        return $user->can('commission_view_any');
    }

    public static function form(Schema $schema): Schema {
        return $schema;
    }

    public static function table(Table $table): Table {
        $user = Auth::user();
        $isCtv = $user->role === 'ctv';

        // Kiểm tra xem CTV có phải là người trực tiếp giới thiệu sinh viên không
        $isDirectRef = false;
        if ($isCtv) {
            $collaborator = Collaborator::where('email', $user->email)->first();
            // CTV trực tiếp giới thiệu sinh viên sẽ có commission với role = 'direct'
            // và recipient_collaborator_id = collaborator.id
            $isDirectRef = $collaborator && CommissionItem::where('recipient_collaborator_id', $collaborator->id)
                ->where('role', 'direct')
                ->exists();
        }

        return $table
            ->query(CommissionItem::query())
            // Hiển thị filter như segmented control phía trên bảng
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('commission.student.full_name')
                    ->label('Họ và tên')
                    ->searchable()
                    ->sortable()
                    ->description(function (CommissionItem $record) {
                        $student = $record->commission->student;
                        if (!$student) return '';

                        $lines = [];
                        if ($student->phone) {
                            $lines[] = '📞 ' . e($student->phone);
                        }
                        if ($student->email) {
                            $lines[] = '✉️ ' . e($student->email);
                        }
                        return implode(' | ', $lines);
                    }),

                \Filament\Tables\Columns\TextColumn::make('commission.student.dob')
                    ->label('Ngày sinh')
                    ->date('d/m/Y')
                    ->sortable(),

                \Filament\Tables\Columns\TextColumn::make('commission.student.major')
                    ->label('Ngành học')
                    ->searchable(),

                \Filament\Tables\Columns\TextColumn::make('commission.student.address')
                    ->label('Địa chỉ')
                    ->limit(50)
                    ->searchable(),

                \Filament\Tables\Columns\TextColumn::make('commission.student.intake.name')
                    ->label('Đợt tuyển')
                    ->formatStateUsing(fn($state, $record) => $state ?: ($record->commission?->student?->intake_month ? "Tháng {$record->commission->student->intake_month}" : '—'))
                    ->sortable(),

                \Filament\Tables\Columns\TextColumn::make('commission.student.program_type')
                    ->label('Hệ tuyển sinh')
                    ->formatStateUsing(fn($state) => match ($state) {
                        'REGULAR' => '🎓 Chính quy',
                        'PART_TIME' => '⏰ Vừa học vừa làm',
                        default => '—'
                    })
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        'REGULAR' => 'success',
                        'PART_TIME' => 'info',
                        default => 'gray'
                    }),

                // Trạng thái sinh viên: hiển thị "Đã nhập học" nếu đã ENROLLED
                \Filament\Tables\Columns\BadgeColumn::make('student_status')
                    ->label('Trạng thái SV')
                    ->state(function ($record) {
                        /** @var CommissionItem|null $record */
                        if (!$record) return null;
                        $student = $record->commission?->student;
                        if (!$student) return null;
                        return $student->status === \App\Models\Student::STATUS_ENROLLED ? 'Đã nhập học' : null;
                    })
                    ->color(function ($state) {
                        return $state ? 'success' : 'gray';
                    }),

                \Filament\Tables\Columns\TextColumn::make('recipient.full_name')
                    ->label('CTV nhận hoa hồng')
                    ->searchable()
                    ->sortable()
                    ->visible(fn(): bool => !$isCtv),

                \Filament\Tables\Columns\TextColumn::make('meta.description')
                    ->label('Nội dung')
                    ->wrap()
                    ->formatStateUsing(function ($state, $record) {
                        if ($state) return $state;
                        return match ($record->role) {
                            'direct' => 'Hoa hồng trực tiếp',
                            'override' => 'Hoa hồng quản lý/thưởng',
                            default => 'Phân bổ hoa hồng'
                        };
                    })
                    ->searchable(),

                \Filament\Tables\Columns\TextColumn::make('amount')
                    ->label('Số tiền hoa hồng')
                    ->money('VND')
                    ->sortable(),

                // Cột mã số phiếu thu
                \Filament\Tables\Columns\TextColumn::make('commission.payment.receipt_number')
                    ->label('Số phiếu thu')
                    ->sortable()
                    ->formatStateUsing(function (?CommissionItem $record) {
                        if (!$record) return '—';
                        $payment = $record->commission->payment;
                        if (!$payment || !$payment->receipt_number) {
                            return '—';
                        }
                        return $payment->receipt_number;
                    })
                    ->visible(fn(): bool => in_array(Auth::user()->role, ['accountant', 'super_admin']) || (Auth::user()->roles && Auth::user()->roles->contains('name', 'accountant'))),

                \Filament\Tables\Columns\BadgeColumn::make('status')
                    ->label('Trạng thái')
                    ->color(function (string $state): string {
                        return match ($state) {
                            CommissionItem::STATUS_PENDING => 'gray',
                            CommissionItem::STATUS_PAYABLE => 'warning',
                            CommissionItem::STATUS_PAID => 'success',
                            CommissionItem::STATUS_CANCELLED => 'danger',
                            CommissionItem::STATUS_PAYMENT_CONFIRMED => 'info',
                            CommissionItem::STATUS_RECEIVED_CONFIRMED => 'success',
                            default => 'gray',
                        };
                    })
                    ->formatStateUsing(function (string $state, CommissionItem $record) use ($user) {
                        if ($user->role === 'ctv' && $state === CommissionItem::STATUS_PAYABLE) {
                            return 'Chưa nhận được hoa hồng';
                        }
                        if ($state === CommissionItem::STATUS_PAYMENT_CONFIRMED) {
                            return 'Đã chốt & Đã chi';
                        }
                        if ($state === CommissionItem::STATUS_RECEIVED_CONFIRMED) {
                            return 'CTV đã nhận tiền';
                        }
                        return CommissionItem::getStatusOptions()[$state] ?? $state;
                    }),

                \Filament\Tables\Columns\TextColumn::make('payment_confirmed_at')
                    ->label('Đã thanh toán lúc')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->visible(fn(): bool => Auth::user()->role === 'super_admin'), // Chỉ hiển thị cho super admin

            ])
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('status')
                    ->label('Trạng thái hoa hồng')
                    ->options(CommissionItem::getStatusOptions()),

                // Đã loại bỏ filter Cấp 1/Cấp 2 - hệ thống chỉ còn 1 cấp

                // Filter Trạng thái SV: Tất cả / Đã nhập học / Chưa nhập học
                \Filament\Tables\Filters\SelectFilter::make('student_status')
                    ->label('Trạng thái SV')
                    ->options([
                        'ALL' => 'Tất cả',
                        'ENROLLED' => 'Đã nhập học',
                        'NOT_ENROLLED' => 'Chưa nhập học',
                    ])
                    ->default('ALL')
                    ->native(false)
                    ->query(function ($query, array $data) {
                        $value = strtoupper((string) ($data['value'] ?? 'ALL'));
                        if ($value === 'ENROLLED') {
                            $query->whereHas('commission.student', function ($q) {
                                $q->where('status', \App\Models\Student::STATUS_ENROLLED);
                            });
                        } elseif ($value === 'NOT_ENROLLED') {
                            $query->whereHas('commission.student', function ($q) {
                                $q->where('status', '!=', \App\Models\Student::STATUS_ENROLLED);
                            });
                        }
                    }),

                // Bỏ filter "Điều kiện kích hoạt"
            ])
            ->actions([
                ActionGroup::make([
                    Action::make('mark_payable')
                        ->label('Đánh dấu có thể thanh toán')
                        ->icon('heroicon-o-check-circle')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading('Đánh dấu có thể thanh toán')
                        ->modalDescription('Đánh dấu commission này đã đến hạn chi, CTV có thể nhận.')
                        ->modalSubmitActionLabel('Xác nhận')
                        ->modalCancelActionLabel('Hủy')
                        ->visible(function (CommissionItem $record) use ($user): bool {
                            return $record->status === CommissionItem::STATUS_PENDING && $user->role === 'super_admin';
                        })
                        ->action(function (CommissionItem $record) {
                            $record->markAsPayable();

                            \Filament\Notifications\Notification::make()
                                ->title('Đã đánh dấu có thể thanh toán')
                                ->body('CTV có thể nhận hoa hồng này.')
                                ->success()
                                ->send();
                        }),

                    Action::make('confirm_payment')
                        ->label('Xác nhận chi hoa hồng')
                        ->icon('heroicon-o-banknotes')
                        ->color('success')
                        ->form([
                            \Filament\Forms\Components\FileUpload::make('bill')
                                ->label('Bill thanh toán')
                                ->required()
                                ->disk('local')
                                ->directory('commission-bills')
                                ->acceptedFileTypes(['image/*', 'application/pdf'])
                                ->maxSize(5120), // 5MB
                        ])
                        ->modalHeading('Xác nhận đã thanh toán')
                        ->modalDescription('Xác nhận đã thanh toán hoa hồng cho CTV và upload bill.')
                        ->modalSubmitActionLabel('Xác nhận thanh toán')
                        ->modalCancelActionLabel('Hủy')
                        ->visible(function (CommissionItem $record) use ($user): bool {
                            return $user->role === 'super_admin'
                                && $record->role === 'direct'
                                && in_array($record->status, [CommissionItem::STATUS_PAYABLE, CommissionItem::STATUS_PENDING]);
                        })
                        ->action(function (CommissionItem $record, array $data) {
                            $record->markAsPaymentConfirmed($data['bill'], \Illuminate\Support\Facades\Auth::user()->id);

                            \Filament\Notifications\Notification::make()
                                ->title('Đã xác nhận thanh toán')
                                ->body('Bill đã được upload và CTV sẽ được thông báo.')
                                ->success()
                                ->send();
                        }),

                    Action::make('confirm_received')
                        ->label('Xác nhận đã nhận tiền')
                        ->icon('heroicon-o-hand-thumb-up')
                        ->color('info')
                        ->requiresConfirmation()
                        ->modalHeading('Xác nhận đã nhận tiền')
                        ->modalDescription('Xác nhận đã nhận được tiền hoa hồng.')
                        ->modalSubmitActionLabel('Xác nhận đã nhận')
                        ->modalCancelActionLabel('Hủy')
                        ->visible(function (CommissionItem $record) use ($user): bool {
                            if ($user->role !== 'ctv') return false;
                            $collab = \App\Models\Collaborator::where('email', $user->email)->first();
                            if (!$collab) return false;
                            return $record->status === CommissionItem::STATUS_PAYMENT_CONFIRMED
                                && $record->role === 'direct'
                                && $record->recipient_collaborator_id === $collab->id;
                        })
                        ->action(function (CommissionItem $record) {
                            $service = new \App\Services\CommissionService();
                            $service->confirmDirectReceived($record, \Illuminate\Support\Facades\Auth::user()->id);

                            \Filament\Notifications\Notification::make()
                                ->title('Đã xác nhận nhận tiền')
                                ->body('Hoa hồng đã được chuyển vào ví của bạn.')
                                ->success()
                                ->send();
                        }),

                    Action::make('view_bill_student')
                        ->label('Xem bill nộp tiền')
                        ->icon('heroicon-o-eye')
                        ->color('info')
                        ->url(fn (CommissionItem $record) => $record->commission->payment?->bill_url)
                        ->openUrlInNewTab()
                        ->visible(function (CommissionItem $record) use ($user): bool {
                            $payment = $record->commission->payment;
                            return $payment && !empty($payment->bill_path);
                        }),

                    Action::make('view_bill_transfer')
                        ->label('Minh chứng chi')
                        ->icon('heroicon-o-document-magnifying-glass')
                        ->url(fn (CommissionItem $record) => $record->bill_url)
                        ->openUrlInNewTab()
                        ->visible(fn (CommissionItem $record): bool => !empty($record->payment_bill_path)),

                    Action::make('view_bill_receipt')
                        ->label('Xem phiếu thu')
                        ->icon('heroicon-o-receipt-percent')
                        ->url(fn (CommissionItem $record) => $record->commission->payment?->receipt_url)
                        ->openUrlInNewTab()
                        ->visible(function (CommissionItem $record) use ($user): bool {
                            $payment = $record->commission->payment;
                            if (!$payment || !$payment->receipt_path) return false;
                            
                            // Cho phép Admin, Kế toán và CTV đều xem được phiếu thu
                            return in_array($user->role, ['super_admin', 'accountant', 'ctv']) || 
                                   ($user->roles && $user->roles->contains('name', 'accountant'));
                        }),

                    Action::make('manage_receipt')
                        ->label(function (CommissionItem $record): string {
                            $payment = $record->commission->payment;
                            return $payment && $payment->receipt_path ? 'Chỉnh sửa phiếu thu' : 'Upload phiếu thu';
                        })
                        ->icon(function (CommissionItem $record): string {
                            $payment = $record->commission->payment;
                            return $payment && $payment->receipt_path ? 'heroicon-o-pencil-square' : 'heroicon-o-document-plus';
                        })
                        ->color('primary')
                        ->form(function (CommissionItem $record): array {
                            $payment = $record->commission->payment;
                            $hasReceipt = $payment && $payment->receipt_path;

                            $form = [
                                \Filament\Forms\Components\TextInput::make('receipt_number')
                                    ->label('Mã số phiếu thu')
                                    ->maxLength(255)
                                    ->default($payment ? $payment->receipt_number : '')
                                    ->helperText('Nhập mã số phiếu thu từ Helen'),
                            ];

                            if ($hasReceipt) {
                                $form[] = \Filament\Forms\Components\ViewField::make('current_receipt')
                                    ->label('Phiếu thu hiện tại')
                                    ->view('components.bill-viewer')
                                    ->viewData([
                                        'payment' => $payment,
                                        'fileUrl' => route('files.receipt.view', $payment->id),
                                        'fileName' => basename($payment->receipt_path),
                                    ]);
                            }

                            $form[] = \Filament\Forms\Components\FileUpload::make('receipt')
                                ->label($hasReceipt ? 'File phiếu thu mới (để trống nếu không thay đổi)' : 'File phiếu thu')
                                ->acceptedFileTypes(['image/*', 'application/pdf'])
                                ->maxSize(5120)
                                ->disk('google')
                                ->directory('/')
                                ->getUploadedFileNameForStorageUsing(function ($file, CommissionItem $record) {
                                    $payment = $record->commission->payment;
                                    $student = $payment->student;
                                    $year = now()->format('Y');
                                    $profileCode = $student->profile_code;
                                    $fullName = $student->full_name;
                                    $major = $student->major;
                                    $ext = $file->getClientOriginalExtension();
                                    
                                    $systemCode = match (strtoupper((string)$student->program_type)) {
                                        'REGULAR', 'CHÍNH QUY' => 'CQ',
                                        'PART_TIME', 'VỪA HỌC VỪA LÀM' => 'VHVL',
                                        'DISTANCE', 'TỪ XA' => 'TX',
                                        default => $student->program_type
                                    };

                                    // Format: HS2026000194_Dat Le Trong_CNTT_CQ.png
                                    $fileName = "{$profileCode}_{$fullName}_{$major}_{$systemCode}.{$ext}";
                                    
                                    return "Phiếu thu/{$year}/{$fileName}";
                                })
                                ->required(!$hasReceipt);

                            return $form;
                        })
                        ->visible(function (CommissionItem $record) use ($user): bool {
                            if ($user->role !== 'accountant' && !($user->roles && $user->roles->contains('name', 'accountant'))) {
                                return false;
                            }
                            $payment = $record->commission?->payment;
                            if ($payment && $payment->receipt_path) {
                                return false;
                            }
                            return in_array($record->status, [
                                CommissionItem::STATUS_PAYABLE,
                                CommissionItem::STATUS_PAYMENT_CONFIRMED,
                            ]) && $record->role === 'direct';
                        })
                        ->action(function (array $data, CommissionItem $record) {
                            $payment = $record->commission->payment;
                            if ($payment) {
                                $updateData = [
                                    'receipt_uploaded_by' => Auth::id(),
                                    'receipt_uploaded_at' => now(),
                                ];
                                if (isset($data['receipt_number'])) {
                                    $updateData['receipt_number'] = $data['receipt_number'];
                                }
                                if (!empty($data['receipt'])) {
                                    $updateData['receipt_path'] = $data['receipt'];
                                }
                                $payment->update($updateData);
                                \Filament\Notifications\Notification::make()->title("Đã xử lý phiếu thu thành công")->success()->send();
                            }
                        }),
                    Action::make('rollback_payment')
                        ->label('Hoàn tác Chốt sổ')
                        ->icon('heroicon-o-arrow-path')
                        ->color('danger')
                        ->visible(fn($record) => $record->status === CommissionItem::STATUS_PAYMENT_CONFIRMED && in_array(Auth::user()->role, ['super_admin', 'admin', 'accountant']))
                        ->requiresConfirmation()
                        ->modalHeading('Hoàn tác trạng thái Chốt sổ')
                        ->modalDescription('Học viên này sẽ quay trở lại trạng thái "Có thể thanh toán" và xuất hiện trong danh sách Chốt sổ lần sau.')
                        ->form([
                            \Filament\Forms\Components\Textarea::make('reason')
                                ->label('Lý do hoàn tác')
                                ->required()
                                ->placeholder('Nhập lý do tại sao bạn muốn hoàn tác việc chốt sổ này...'),
                        ])
                        ->action(function ($record, array $data) {
                            // Merge reason into request so HasAuditLog trait captures it
                            request()->merge(['audit_reason' => "Hoàn tác chốt sổ: " . $data['reason']]);
                            
                            $record->update([
                                'status' => CommissionItem::STATUS_PAYABLE,
                                'payment_bill_path' => null,
                                'payment_confirmed_at' => null,
                                'payment_confirmed_by' => null,
                                'meta' => array_merge($record->meta ?? [], [
                                    'rollback_history' => array_merge($record->meta['rollback_history'] ?? [], [[
                                        'reason' => $data['reason'],
                                        'at' => now()->toDateTimeString(),
                                        'by' => Auth::user()->name,
                                    ]])
                                ]),
                            ]);

                            \Filament\Notifications\Notification::make()
                                ->title('Đã hoàn tác chốt sổ thành công')
                                ->success()
                                ->send();
                        }),
                ])
                ->label('Thao tác')
                ->icon('heroicon-m-ellipsis-vertical')
                ->color('gray')
                ->button()
                ->size('sm'),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    \Filament\Actions\BulkAction::make('bulk_confirm_payment')
                        ->label('Chốt sổ & Xác nhận chi hàng loạt')
                        ->icon('heroicon-o-check-badge')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Chốt sổ & Xác nhận đã chi tiền')
                        ->modalDescription('Hệ thống sẽ đánh dấu các bản ghi này là "Đã chốt & Đã chi". Những sinh viên này sẽ không xuất hiện trong danh sách Chốt sổ lệ phí lần sau.')
                        ->modalSubmitActionLabel('Xác nhận hoàn tất chi trả')
                        ->modalCancelActionLabel('Hủy')
                        ->visible(fn() => in_array(Auth::user()->role, ['super_admin', 'admin', 'accountant']))
                        ->action(function ($records) {
                            $batchId = (string) \Illuminate\Support\Str::uuid();
                            $userId = Auth::user()->id;
                            
                            $batchData = [];
                            $totalAmount = 0;
                            $successCount = 0;

                            foreach ($records as $record) {
                                // Cho phép chốt những ai đang có thể thanh toán, chờ nhập học, hoặc đã thanh toán cũ
                                if (in_array($record->status, [
                                    CommissionItem::STATUS_PAYABLE, 
                                    CommissionItem::STATUS_PENDING,
                                    CommissionItem::STATUS_PAID
                                ])) {
                                    $studentName = $record->commission?->student?->full_name ?? 'N/A';
                                    $collaboratorName = $record->recipient?->full_name ?? 'N/A';
                                    $amount = (float)($record->amount ?? 0);

                                    $batchData[] = [
                                        'student' => $studentName,
                                        'collaborator' => $collaboratorName,
                                        'amount' => $amount,
                                    ];
                                    $totalAmount += $amount;

                                    // Cập nhật âm thầm (không trigger trait HasAuditLog) để tránh rác 100 log lẻ
                                    $record->updateQuietly([
                                        'status' => CommissionItem::STATUS_PAYMENT_CONFIRMED,
                                        'payment_confirmed_at' => now(),
                                        'payment_confirmed_by' => $userId,
                                    ]);
                                    
                                    $successCount++;
                                }
                            }

                            if ($successCount > 0) {
                                // Tạo MỘT log duy nhất đại diện cho cả lô
                                \App\Models\AuditLog::create([
                                    'event_group' => \App\Models\AuditLog::GROUP_FINANCIAL,
                                    'event_type' => \App\Models\AuditLog::TYPE_BATCH_CONFIRMED,
                                    'user_id' => $userId,
                                    'user_role' => Auth::user()->role,
                                    'metadata' => [
                                        'batch_id' => $batchId,
                                        'batch_data' => $batchData,
                                        'total_amount' => $totalAmount,
                                    ],
                                    'reason' => 'Chốt sổ & Xác nhận chi hàng loạt (' . $successCount . ' học viên)',
                                    'ip_address' => request()->ip(),
                                    'user_agent' => request()->userAgent(),
                                    'created_at' => now(),
                                ]);

                                \Filament\Notifications\Notification::make()
                                    ->title('Đã chốt sổ thành công cho ' . $successCount . ' học viên')
                                    ->success()
                                    ->send();
                            } else {
                                \Filament\Notifications\Notification::make()
                                    ->title('Không có học viên nào được chốt sổ')
                                    ->warning()
                                    ->send();
                            }
                        })
                        ->deselectRecordsAfterCompletion(),

                ]),
            ])
            ->headerActions([
                // Nút chuyển cho CTV cấp 2: hiển thị khi chọn một item downline ở trạng thái phù hợp
            ])
            ->modifyQueryUsing(function ($query) {
                $user = Auth::user();

                if ($user->role === 'super_admin') {
                    return;
                }

                if ($user->role === 'ctv') {
                    // CTV chỉ thấy commission của chính mình (role nào xem role đó)
                    $collaborator = Collaborator::where('email', $user->email)->first();
                    if ($collaborator) {
                        $query->where('recipient_collaborator_id', $collaborator->id);
                    } else {
                        $query->whereNull('id');
                    }
                }


                if (in_array($user->role, ['accountant', 'document'])) {
                    // Kế toán và Cán bộ hồ sơ có thể xem tất cả commissions để đối soát
                    return;
                }
            });
    }

    public static function getPages(): array {
        return [
            'index' => ListCommissions::route('/'),
        ];
    }


    public static function getNavigationBadge(): ?string {
        try {
            $user = Auth::user();
            if (!$user) return null;

            if (in_array($user->role, ['super_admin', 'accountant', 'document'])) {
                return (string) CommissionItem::count();
            }

            if ($user->role === 'ctv') {
                $collaborator = Collaborator::where('email', $user->email)->first();
                if (!$collaborator) return '0';
                
                return (string) CommissionItem::where('recipient_collaborator_id', $collaborator->id)->count();
            }

            return null;
        } catch (\Throwable) {
            return null;
        }
    }

    public static function getNavigationBadgeTooltip(): ?string {
        return 'Tổng số bộ hoa hồng';
    }

}
