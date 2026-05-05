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
    protected static string|\BackedEnum|null $navigationIcon = null;

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
        $isCtv = $user->role === 'collaborator';

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
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('student.full_name')
                    ->label('Họ và tên')
                    ->searchable()
                    ->sortable()
                    ->description(function (Commission $record) {
                        $student = $record->student;
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

                \Filament\Tables\Columns\TextColumn::make('student.dob')
                    ->label('Ngày sinh')
                    ->date('d/m/Y')
                    ->sortable(),

                \Filament\Tables\Columns\TextColumn::make('student.major')
                    ->label('Ngành học')
                    ->searchable(),

                \Filament\Tables\Columns\TextColumn::make('student.address')
                    ->label('Địa chỉ')
                    ->limit(50)
                    ->searchable(),

                \Filament\Tables\Columns\TextColumn::make('student.intake.name')
                    ->label('Đợt tuyển')
                    ->formatStateUsing(fn($state, $record) => $state ?: ($record->student?->intake_month ? "Tháng {$record->student->intake_month}" : '—'))
                    ->sortable(),

                \Filament\Tables\Columns\TextColumn::make('student.program_type')
                    ->label('Hệ tuyển sinh')
                    ->state(function (Commission $record) {
                        return $record->rule['program_type'] ?? 
                                $record->student?->program_type;
                    })
                    ->formatStateUsing(fn($state) => match (strtoupper((string)$state)) {
                        'regular' => '🎓 Chính quy',
                        'part_time' => '⏰ Vừa học vừa làm',
                        'distance' => '💻 Đào tạo từ xa',
                        default => '—'
                    })
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        'regular' => 'success',
                        'part_time' => 'info',
                        'distance' => 'warning',
                        default => 'gray'
                    }),

                \Filament\Tables\Columns\TextColumn::make('payment.amount')
                    ->label('SV đóng phí')
                    ->money('VND')
                    ->sortable()
                    ->visible(fn(): bool => !$isCtv),
                
                \Filament\Tables\Columns\TextColumn::make('recipient_ctv')
                    ->label('Hoa hồng của tôi')
                    ->state(function (Commission $record) use ($user) {
                        $collab = \App\Models\Collaborator::where('email', $user->email)->first();
                        if (!$collab) return '—';
                        $item = $record->items->where('recipient_collaborator_id', $collab->id)->first();
                        if (!$item) return '—';
                        return number_format($item->amount, 0, ',', '.') . ' VND';
                    })
                    ->visible(fn(): bool => $isCtv),

                \Filament\Tables\Columns\TextColumn::make('total_amount')
                    ->label('Tổng & Chi trả')
                    ->state(function (Commission $record) {
                        $items = $record->items->where('status', '!=', \App\Models\CommissionItem::STATUS_CANCELLED);
                        return $items->sum('amount');
                    })
                    ->money('VND')
                    ->description(function (Commission $record) {
                        $items = $record->items->where('status', '!=', \App\Models\CommissionItem::STATUS_CANCELLED);
                        $total = $items->sum('amount');
                        $paid = $items->whereIn('status', [
                            \App\Models\CommissionItem::STATUS_PAID,
                            \App\Models\CommissionItem::STATUS_PAYMENT_CONFIRMED,
                            \App\Models\CommissionItem::STATUS_RECEIVED_CONFIRMED
                        ])->sum('amount');
                        $remaining = $total - $paid;

                        if ($paid == 0) return "Chưa chi trả";
                        
                        return "Đã chi: " . number_format($paid, 0, ',', '.') . " | Còn lại: " . number_format($remaining, 0, ',', '.');
                    })
                    ->sortable()
                    ->visible(fn(): bool => !$isCtv),

                \Filament\Tables\Columns\IconColumn::make('is_adjusted')
                    ->label('Điều chỉnh')
                    ->boolean()
                    ->trueIcon('heroicon-o-exclamation-triangle')
                    ->falseIcon('')
                    ->trueColor('warning')
                    ->tooltip('Hoa hồng này đã được điều chỉnh do SV chuyển hệ')
                    ->toggleable(),

                \Filament\Tables\Columns\TextColumn::make('payment.verifier.name')
                    ->label('Người thu')
                    ->sortable()
                    ->visible(fn(): bool => !$isCtv)
                    ->placeholder('Hệ thống'),

                \Filament\Tables\Columns\TextInputColumn::make('notes')
                    ->label('Ghi chú riêng')
                    ->placeholder('Nhập ghi chú...')
                    ->visible(fn() => Auth::user()->can('commission_update')),

                \Filament\Tables\Columns\BadgeColumn::make('status_summary')
                    ->label('Trạng thái chi trả')
                    ->state(function (Commission $record) {
                        $total = $record->items->count();
                        $paid = $record->items->whereIn('status', [CommissionItem::STATUS_PAID, CommissionItem::STATUS_PAYMENT_CONFIRMED, CommissionItem::STATUS_RECEIVED_CONFIRMED])->count();
                        
                        if ($paid === 0) return 'Chưa chi';
                        if ($paid < $total) return "Đang chi ({$paid}/{$total})";
                        return 'Hoàn tất';
                    })
                    ->color(fn($state) => match (true) {
                        $state === 'Hoàn tất' => 'success',
                        str_contains($state, 'Đang chi') => 'warning',
                        default => 'gray',
                    }),

                \Filament\Tables\Columns\TextColumn::make('payment_confirmed_at')
                    ->label('Đã thanh toán lúc')
                    ->state(fn (Commission $record) => $record->items->where('role', 'direct')->first()?->payment_confirmed_at)
                    ->dateTime('d/m/Y H:i')
                    ->sortable(query: function ($query, $direction) {
                        return $query->addSelect([
                            'direct_confirmed_at' => \App\Models\CommissionItem::select('payment_confirmed_at')
                                ->whereColumn('commission_id', 'commissions.id')
                                ->where('role', 'direct')
                                ->limit(1),
                        ])->orderBy('direct_confirmed_at', $direction);
                    })
                    ->visible(fn(): bool => Auth::user()->can('commission_view_any') && Auth::user()->role !== 'collaborator'),

            ])
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('status')
                    ->label('Trạng thái hoa hồng')
                    ->options(CommissionItem::getStatusOptions()),

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
                            $query->whereHas('student', function ($q) {
                                $q->where('status', \App\Models\Student::STATUS_ENROLLED);
                            });
                        } elseif ($value === 'NOT_ENROLLED') {
                            $query->whereHas('student', function ($q) {
                                $q->where('status', '!=', \App\Models\Student::STATUS_ENROLLED);
                            });
                        }
                    }),

                \Filament\Tables\Filters\SelectFilter::make('collector_id')
                    ->label('Người thu (Accountant)')
                    ->options(\App\Models\User::where('role', '!=', 'collaborator')->pluck('name', 'id'))
                    ->searchable()
                    ->query(function ($query, array $data) {
                        if (!empty($data['value'])) {
                            $query->whereHas('payment', function ($q) use ($data) {
                                $q->where('verified_by', $data['value']);
                            });
                        }
                    }),

                \Filament\Tables\Filters\Filter::make('created_at')
                    ->form([
                        \Filament\Forms\Components\Select::make('range')
                            ->label('Khoảng thời gian')
                            ->options([
                                'today' => 'Hôm nay',
                                '7_days' => '1 tuần',
                                '30_days' => '1 tháng',
                                'this_month' => 'Tháng này',
                                'custom' => 'Tùy chọn...',
                            ])
                            ->default('custom')
                            ->live(),
                        \Filament\Forms\Components\DatePicker::make('created_from')
                            ->label('Từ ngày')
                            ->visible(fn ($get) => $get('range') === 'custom' || !$get('range')),
                        \Filament\Forms\Components\DatePicker::make('created_until')
                            ->label('Đến ngày')
                            ->visible(fn ($get) => $get('range') === 'custom' || !$get('range')),
                    ])
                    ->query(function (\Illuminate\Database\Eloquent\Builder $query, array $data): \Illuminate\Database\Eloquent\Builder {
                        $from = $data['created_from'];
                        $until = $data['created_until'];

                        if ($data['range'] && $data['range'] !== 'custom') {
                            $until = now()->endOfDay();
                            $from = match ($data['range']) {
                                'today' => now()->startOfDay(),
                                '7_days' => now()->subDays(7)->startOfDay(),
                                '30_days' => now()->subDays(30)->startOfDay(),
                                'this_month' => now()->startOfMonth()->startOfDay(),
                                default => null,
                            };
                        }

                        return $query
                            ->when(
                                $from,
                                fn (\Illuminate\Database\Eloquent\Builder $query, $date): \Illuminate\Database\Eloquent\Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $until,
                                fn (\Illuminate\Database\Eloquent\Builder $query, $date): \Illuminate\Database\Eloquent\Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
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
                        ->visible(function (Commission $record) use ($user): bool {
                            return $record->items()->where('status', CommissionItem::STATUS_PENDING)->exists() && $user->can('commission_update');
                        })
                        ->action(function (Commission $record) {
                            $record->items()->where('status', CommissionItem::STATUS_PENDING)->each(fn($item) => $item->markAsPayable());

                            \Filament\Notifications\Notification::make()
                                ->title('Đã đánh dấu có thể thanh toán')
                                ->body('CTV có thể nhận hoa hồng cho các phần đã đến hạn.')
                                ->success()
                                ->send();
                        }),

                    Action::make('confirm_payment')
                        ->label('Xác nhận chi hoa hồng')
                        ->icon('heroicon-o-banknotes')
                        ->color('success')
                        ->form([
                            \Filament\Forms\Components\FileUpload::make('bill')
                                ->label('Bill thanh toán (Không bắt buộc)')
                                ->disk('local')
                                ->directory('commission-bills')
                                ->acceptedFileTypes(['image/*', 'application/pdf'])
                                ->maxSize(5120), // 5MB
                        ])
                        ->modalHeading('Xác nhận đã thanh toán')
                        ->modalDescription('Xác nhận đã thanh toán hoa hồng cho CTV. Bạn có thể tải bill lên nếu muốn lưu trữ.')
                        ->modalSubmitActionLabel('Xác nhận thanh toán')
                        ->modalCancelActionLabel('Hủy')
                        ->visible(function (Commission $record) use ($user): bool {
                            return $user->can('commission_payout')
                                && $record->items()->where('role', 'direct')->whereIn('status', [CommissionItem::STATUS_PAYABLE, CommissionItem::STATUS_PENDING])->exists();
                        })
                        ->action(function (Commission $record, array $data) {
                            $record->items()->where('role', 'direct')->whereIn('status', [CommissionItem::STATUS_PAYABLE, CommissionItem::STATUS_PENDING])
                                ->each(fn($item) => $item->markAsPaymentConfirmed($data['bill'] ?? null, \Illuminate\Support\Facades\Auth::user()->id));

                            \Filament\Notifications\Notification::make()
                                ->title('Đã xác nhận thanh toán')
                                ->body('Các khoản hoa hồng trực tiếp đã được chốt chi.')
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
                        ->visible(function (Commission $record) use ($user): bool {
                            if ($user->role !== 'collaborator') return false;
                            $collab = \App\Models\Collaborator::where('email', $user->email)->first();
                            if (!$collab) return false;
                            
                            return $record->items()
                                ->where('status', CommissionItem::STATUS_PAYMENT_CONFIRMED)
                                && $record->items()->where('role', 'direct')
                                && $record->items()->where('recipient_collaborator_id', $collab->id)
                                ->exists();
                        })
                        ->action(function (Commission $record) use ($user) {
                            $collab = \App\Models\Collaborator::where('email', $user->email)->first();
                            $item = $record->items()
                                ->where('status', CommissionItem::STATUS_PAYMENT_CONFIRMED)
                                ->where('role', 'direct')
                                ->where('recipient_collaborator_id', $collab->id)
                                ->first();

                            if ($item) {
                                $service = new \App\Services\CommissionService();
                                $service->confirmDirectReceived($item, $user->id);
                            }

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
                        ->url(fn (Commission $record) => $record->payment?->bill_url)
                        ->openUrlInNewTab()
                        ->visible(function (Commission $record) use ($user): bool {
                            $payment = $record->payment;
                            return $payment && !empty($payment->bill_path);
                        }),

                    Action::make('view_bill_transfer')
                        ->label('Minh chứng chi')
                        ->icon('heroicon-o-document-magnifying-glass')
                        ->url(fn (Commission $record) => $record->items()->whereNotNull('payment_bill_path')->first()?->bill_url)
                        ->openUrlInNewTab()
                        ->visible(fn (Commission $record): bool => $record->items()->whereNotNull('payment_bill_path')->exists()),

                    Action::make('view_bill_receipt')
                        ->label('Xem phiếu thu')
                        ->icon('heroicon-o-receipt-percent')
                        ->url(fn (Commission $record) => $record->payment?->receipt_url)
                        ->openUrlInNewTab()
                        ->visible(function (Commission $record) use ($user): bool {
                            $payment = $record->payment;
                            if (!$payment || !$payment->receipt_path) return false;
                            
                            return $user->can('payment_view');
                        }),

                    Action::make('manage_receipt')
                        ->label(function (Commission $record): string {
                            $payment = $record->payment;
                            return $payment && $payment->receipt_path ? 'Chỉnh sửa phiếu thu' : 'Upload phiếu thu';
                        })
                        ->icon(function (Commission $record): string {
                            $payment = $record->payment;
                            return $payment && $payment->receipt_path ? 'heroicon-o-pencil-square' : 'heroicon-o-document-plus';
                        })
                        ->color('primary')
                        ->form(function (Commission $record): array {
                            $payment = $record->payment;
                            $hasReceipt = $payment && $payment->receipt_path;

                            return [
                                \Filament\Forms\Components\TextInput::make('receipt_number')
                                    ->label('Mã số phiếu thu')
                                    ->maxLength(255)
                                    ->default($payment ? $payment->receipt_number : '')
                                    ->helperText('Nhập mã số phiếu thu'),

                                \Filament\Forms\Components\FileUpload::make('receipt')
                                    ->label($hasReceipt ? 'File phiếu thu mới (để trống nếu không thay đổi)' : 'File phiếu thu')
                                    ->acceptedFileTypes(['image/*', 'application/pdf'])
                                    ->maxSize(5120)
                                    ->disk('google')
                                    ->directory('/')
                                    ->getUploadedFileNameForStorageUsing(function ($file, Commission $record) {
                                        $payment = $record->payment;
                                        $student = $payment->student;
                                        $year = now()->format('Y');
                                        $profileCode = $student->profile_code;
                                        $fullName = $student->full_name;
                                        $major = $student->major;
                                        $ext = $file->getClientOriginalExtension();
                                        
                                        $systemCode = match (strtoupper((string)$student->program_type)) {
                                            'regular', 'CHÍNH QUY' => 'CQ',
                                            'part_time', 'VỪA HỌC VỪA LÀM' => 'VHVL',
                                            'distance', 'TỪ XA' => 'TX',
                                            default => $student->program_type
                                        };

                                        $fileName = "{$profileCode}_{$fullName}_{$major}_{$systemCode}.{$ext}";
                                        
                                        return "Phiếu thu/{$year}/{$fileName}";
                                    })
                                    ->required(!$hasReceipt)
                            ];
                        })
                        ->visible(function (Commission $record) use ($user): bool {
                            $payment = $record->payment;
                            if ($payment && $payment->receipt_path) {
                                if (!$user->can('payment_update_receipt')) {
                                    return false;
                                }
                            } else {
                                if (!$user->can('payment_upload_receipt')) {
                                    return false;
                                }
                            }
                            return $record->items()->where('role', 'direct')->whereIn('status', [
                                CommissionItem::STATUS_PAYABLE,
                                CommissionItem::STATUS_PAYMENT_CONFIRMED,
                            ])->exists();
                        })
                        ->action(function (array $data, Commission $record) {
                            $payment = $record->payment;
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
                        ->visible(fn($record) => $record->items()->where('status', CommissionItem::STATUS_PAYMENT_CONFIRMED)->exists() && Auth::user()->can('commission_payout'))
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
                            request()->merge(['audit_reason' => "Hoàn tác chốt sổ: " . $data['reason']]);
                            
                            $record->items()->where('status', CommissionItem::STATUS_PAYMENT_CONFIRMED)->each(function ($item) use ($data) {
                                $item->update([
                                    'status' => CommissionItem::STATUS_PAYABLE,
                                    'payment_bill_path' => null,
                                    'payment_confirmed_at' => null,
                                    'payment_confirmed_by' => null,
                                    'meta' => array_merge($item->meta ?? [], [
                                        'rollback_history' => array_merge($item->meta['rollback_history'] ?? [], [[
                                            'reason' => $data['reason'],
                                            'at' => now()->toDateTimeString(),
                                            'by' => Auth::user()->name,
                                        ]])
                                    ]),
                                ]);
                            });

                            \Filament\Notifications\Notification::make()
                                ->title('Đã hoàn tác chốt sổ thành công')
                                ->success()
                                ->send();
                        }),

                    Action::make('revert_payment')
                        ->label('Hoàn trả tiền')
                        ->icon('heroicon-o-arrow-uturn-left')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Xác nhận hoàn trả tiền & Hủy trạng thái')
                        ->modalDescription('Hành động này sẽ: 
                            1. Chuyển trạng thái thanh toán về "Hoàn trả". 
                            2. Xóa thông tin Phiếu thu cũ.
                            3. Đưa học viên về trạng thái "Mới" để CTV có thể nộp lại (nếu cần).')
                        ->form([
                            \Filament\Forms\Components\Textarea::make('reason')
                                ->label('Lý do hoàn trả')
                                ->required()
                                ->placeholder('Nhập lý do hoàn trả tiền...'),
                        ])
                        ->modalSubmitActionLabel('Xác nhận hoàn trả')
                        ->visible(function (Commission $record): bool {
                            $user = Auth::user();
                            $payment = $record->payment;
                            
                            if (!$payment || $payment->status !== \App\Models\Payment::STATUS_VERIFIED) {
                                return false;
                            }

                            // Chặn hoàn trả nếu đã xác nhận chi hoa hồng (kiểm tra toàn bộ commission liên quan đến payment này)
                            if ($payment->hasConfirmedCommission()) {
                                return false;
                            }

                            // Nếu đã có phiếu thu, yêu cầu quyền Hoàn trả (Refund)
                            if ($payment->receipt_path) {
                                return $user->can('payment_refund');
                            }

                            // Nếu chưa có phiếu thu, chỉ yêu cầu quyền Hủy xác nhận (Reverse)
                            return $user->can('payment_reverse');
                        })
                        ->action(function (array $data, Commission $record) {
                            $payment = $record->payment;
                            $student = $record->student;
                            
                            if (!$payment || !$student) return;

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
                            $student->update(['status' => \App\Models\Student::STATUS_NEW]);

                            // HUỶ toàn bộ hoa hồng liên quan đến đợt thanh toán này
                            $record->items()->update([
                                'status' => CommissionItem::STATUS_CANCELLED
                            ]);
                            
                            \Filament\Notifications\Notification::make()
                                ->success()
                                ->title('Đã hoàn trả trạng thái thành công')
                                ->body('Hồ sơ học viên đã được đưa về trạng thái Mới và các khoản hoa hồng liên quan đã được HUỶ.')
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
                        ->label('Xác nhận chi cho các mục ĐÃ CHỌN')
                        ->icon('heroicon-o-check-badge')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Chốt sổ & Xác nhận đã chi tiền')
                        ->modalDescription('Hệ thống sẽ đánh dấu các bản ghi này là "Đã chốt & Đã chi". Những sinh viên này sẽ không xuất hiện trong danh sách Chốt sổ lệ phí lần sau.')
                        ->modalSubmitActionLabel('Xác nhận hoàn tất chi trả')
                        ->modalCancelActionLabel('Hủy')
                        ->visible(fn() => Auth::user()->can('commission_payout'))
                        ->action(function ($records) {
                            $batchId = (string) \Illuminate\Support\Str::uuid();
                            $userId = Auth::user()->id;
                            
                            $batchData = [];
                            $totalAmount = 0;
                            $successCount = 0;

                            foreach ($records as $record) {
                                $studentName = $record->student?->full_name ?? 'N/A';
                                
                                $record->items()
                                    ->whereIn('status', [
                                        CommissionItem::STATUS_PAYABLE, 
                                        CommissionItem::STATUS_PENDING,
                                        CommissionItem::STATUS_PAID
                                    ])
                                    ->each(function ($item) use (&$batchData, &$totalAmount, &$successCount, $studentName, $userId) {
                                        $collaboratorName = $item->recipient?->full_name ?? 'N/A';
                                        $amount = (float)($item->amount ?? 0);

                                        $batchData[] = [
                                            'student' => $studentName,
                                            'collaborator' => $collaboratorName,
                                            'amount' => $amount,
                                        ];
                                        $totalAmount += $amount;

                                        $item->updateQuietly([
                                            'status' => CommissionItem::STATUS_PAYMENT_CONFIRMED,
                                            'payment_confirmed_at' => now(),
                                            'payment_confirmed_by' => $userId,
                                        ]);
                                        
                                        $successCount++;
                                    });
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

                if ($user->role === 'collaborator') {
                    // CTV chỉ thấy các commission mà mình có trong danh sách recipient
                    $collaborator = \App\Models\Collaborator::where('email', $user->email)->first();
                    if ($collaborator) {
                        $query->whereHas('items', function ($q) use ($collaborator) {
                            $q->where('recipient_collaborator_id', $collaborator->id);
                        });
                    } else {
                        $query->whereNull('id');
                    }
                    return;
                }

                if ($user->can('commission_view_any')) {
                    return;
                }
            });
    }

    public static function getPages(): array {
        return [
            'index' => ListCommissions::route('/'),
        ];
    }



    public static function getWidgets(): array {
        return [
            \App\Filament\Resources\Commissions\CommissionResource\Widgets\CommissionStatsWidget::class,
        ];
    }
}
