<?php

namespace App\Filament\Resources\Commissions;

use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Gate;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Models\Commission;
use App\Models\CommissionItem;
use App\Models\Organization;
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
        return true; // Cho phép tất cả user đã đăng nhập thấy menu, quyền truy cập sẽ được kiểm tra ở page level
    }

    public static function form(Schema $schema): Schema {
        return $schema;
    }

    public static function table(Table $table): Table {
        $user = Auth::user();
        $isCtv = $user->role === 'ctv';

        // Kiểm tra xem CTV có phải là người trực tiếp giới thiệu sinh viên không
        $isDirectRef = false;
        // Kiểm tra xem CTV có phải là CTV cấp 1 không (không có upline)
        $isPrimaryCtv = false;
        // Kiểm tra xem có phải là organization_owner không
        $isOwner = $user->role === 'organization_owner';
        if ($isCtv) {
            $collaborator = Collaborator::where('email', $user->email)->first();
            // CTV trực tiếp giới thiệu sinh viên sẽ có commission với role = 'direct'
            // và recipient_collaborator_id = collaborator.id
            $isDirectRef = $collaborator && CommissionItem::where('recipient_collaborator_id', $collaborator->id)
                ->where('role', 'direct')
                ->exists();
            // CTV cấp 1 là CTV không có upline
            $isPrimaryCtv = $collaborator && $collaborator->upline_id === null;
        }

        return $table
            ->query(CommissionItem::query())
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('commission.student.full_name')
                    ->label('Sinh viên')
                    ->searchable()
                    ->sortable()
                    ->action(
                        \Filament\Actions\Action::make('view_student')
                            ->label('Xem thông tin sinh viên')
                            ->icon('heroicon-o-eye')
                            ->modalContent(function (CommissionItem $record) {
                                $student = $record->commission->student;
                                if (!$student) {
                                    return view('components.student-info', [
                                        'student' => null,
                                        'error' => 'Không tìm thấy thông tin sinh viên'
                                    ]);
                                }

                                return view('components.student-info-modal', [
                                    'student' => $student,
                                ]);
                            })
                            ->modalWidth('4xl')
                    ),

                \Filament\Tables\Columns\TextColumn::make('recipient.full_name')
                    ->label('CTV nhận hoa hồng')
                    ->searchable()
                    ->sortable()
                    ->visible(function ($record) use ($user, $isCtv, $isDirectRef): bool {
                        if (!$isCtv) return true; // Super admin và organization_owner vẫn thấy
                        if ($isDirectRef) return false; // CTV trực tiếp giới thiệu không thấy

                        // CTV cấp 2 (downline) không thấy cột này
                        $collab = Collaborator::where('email', $user->email)->first();
                        if ($collab && $collab->upline_id !== null) {
                            return false; // CTV cấp 2 không thấy
                        }

                        return true; // CTV cấp 1 thấy
                    })
                    ->formatStateUsing(function ($state, CommissionItem $record) use ($user) {
                        if ($user->role === 'organization_owner') {
                            // Với organization_owner: hiển thị rõ vai trò để tránh hiểu nhầm
                            if (strtolower($record->role) === 'direct') {
                                return $state; // CTV cấp 1 nhận trực tiếp
                            }
                            if (strtolower($record->role) === 'downline') {
                                return $state . ' (CTV cấp 2)'; // Hiển thị đúng CTV cấp 2
                            }
                        }
                        return $state;
                    }),

                \Filament\Tables\Columns\TextColumn::make('role')
                    ->label('Vai trò')
                    ->badge()
                    ->color(fn(string $state): string => match (strtoupper($state)) {
                        'DIRECT' => 'success',
                        'DOWNLINE' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => match (strtoupper($state)) {
                        'DIRECT' => 'CTV cấp 1',
                        'DOWNLINE' => 'CTV cấp 2',
                        default => $state,
                    })
                    ->visible(fn(): bool => !$isCtv && !$isOwner), // Chỉ hiển thị cho Super Admin

                \Filament\Tables\Columns\TextColumn::make('amount')
                    ->label('Số tiền hoa hồng')
                    ->money('VND')
                    ->sortable(),

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
                        // Nếu là CTV và trạng thái là PAYABLE, hiển thị "Chưa nhận được hoa hồng"
                        if ($user->role === 'ctv' && $state === CommissionItem::STATUS_PAYABLE) {
                            return 'Chưa nhận được hoa hồng';
                        }
                        // Nếu là CTV và trạng thái là PAYMENT_CONFIRMED, hiển thị "Chờ xác nhận nhận tiền"
                        if ($user->role === 'ctv' && $state === CommissionItem::STATUS_PAYMENT_CONFIRMED) {
                            return 'Chờ xác nhận nhận tiền';
                        }
                        // Nếu là CTV và đã xác nhận nhận tiền (ưu tiên hiển thị thông điệp rõ ràng cho CTV1)
                        if ($user->role === 'ctv' && $state === CommissionItem::STATUS_RECEIVED_CONFIRMED) {
                            return 'Đã nhận tiền thành công';
                        }
                        // Với chủ đơn vị: khi CTV cấp 1 đã xác nhận đã nhận (RECEIVED_CONFIRMED)
                        // hiển thị "Đã chuyển thành công" để phản ánh trạng thái từ phía đơn vị
                        if (
                            $user->role === 'organization_owner'
                            && $record->role === 'direct'
                            && $state === CommissionItem::STATUS_RECEIVED_CONFIRMED
                        ) {
                            return 'Đã chuyển thành công';
                        }
                        return CommissionItem::getStatusOptions()[$state] ?? $state;
                    })
                    ->visible(function ($record) use ($isCtv, $user): bool {
                        if (!$isCtv) return true;
                        if (!$record) return false;

                        // CTV cấp 1: chỉ hiển thị cho item KHÔNG PHẢI direct (downline)
                        $collab = Collaborator::where('email', $user->email)->first();
                        if ($collab && $collab->upline_id === null) {
                            return strtolower($record->role) !== 'direct';
                        }

                        // CTV cấp 2: không hiển thị cột trạng thái gốc (sẽ dùng cột downline_status)
                        return false;
                    }),

                // Dành cho CTV cấp 1: Trạng thái từ đơn vị (khi CTV1 đã nhận tiền từ đơn vị)
                \Filament\Tables\Columns\BadgeColumn::make('status_from_org')
                    ->label('Trạng thái từ đơn vị')
                    ->state(function ($record) use ($user) {
                        if (!$record || $user->role !== 'ctv') return null;
                        if ($record->role !== 'direct') return null;
                        return $record->status === CommissionItem::STATUS_RECEIVED_CONFIRMED
                            ? 'Đã nhận tiền thành công'
                            : null;
                    })
                    ->color(function ($state) {
                        return $state ? 'success' : 'gray';
                    })
                    ->visible(function ($record) use ($user) {
                        if ($user->role !== 'ctv') return false;
                        $collab = Collaborator::where('email', $user->email)->first();
                        if (!$collab) return false;
                        // Chỉ hiển thị cho CTV cấp 1 (không có upline)
                        return $collab->upline_id === null;
                    }),

                // Dành cho CTV cấp 1: Trạng thái với CTV (khi đã chuyển cho CTV2 xong)
                \Filament\Tables\Columns\BadgeColumn::make('status_with_ctv')
                    ->label('Trạng thái với CTV')
                    ->state(function ($record) use ($user) {
                        if (!$record || $user->role !== 'ctv') return null;
                        if ($record->role !== 'direct') return null;
                        $downlineItem = CommissionItem::where('commission_id', $record->commission_id)
                            ->where('role', 'downline')
                            ->orderBy('id')
                            ->first();
                        if ($downlineItem && $downlineItem->status === CommissionItem::STATUS_RECEIVED_CONFIRMED) {
                            return 'Đã chuyển tiền thành công';
                        }
                        return null;
                    })
                    ->color(function ($state) {
                        return $state ? 'success' : 'gray';
                    })
                    ->visible(function ($record) use ($user) {
                        if ($user->role !== 'ctv') return false;
                        $collab = Collaborator::where('email', $user->email)->first();
                        if (!$collab) return false;
                        // Chỉ hiển thị cho CTV cấp 1 (không có upline)
                        return $collab->upline_id === null;
                    }),

                // Gợi ý cho CTV cấp 1 (VHVL): cần SV nhập học mới được chuyển cho CTV2
                \Filament\Tables\Columns\BadgeColumn::make('downline_enroll_hint')
                    ->label('CTV2 (VHVL)')
                    ->state(function ($record) use ($user) {
                        if (!$record || $user->role !== 'ctv') return null;
                        $collab = Collaborator::where('email', $user->email)->first();
                        if (!$collab) return null;
                        if (!($record->role === 'direct' && $record->recipient_collaborator_id === $collab->id)) {
                            return null;
                        }
                        $commission = $record->commission;
                        $payment = $commission?->payment;
                        $student = $commission?->student;
                        $programType = strtoupper($payment->program_type ?? $student?->program_type ?? '');
                        if (!in_array($programType, ['PART_TIME', 'VHVL', 'VHVLV'])) {
                            return null;
                        }
                        if (!$student || $student->status !== \App\Models\Student::STATUS_ENROLLED) {
                            return 'Cần SV nhập học để chuyển CTV2';
                        }
                        return null;
                    })
                    ->color('warning')
                    ->extraAttributes(function ($record) use ($user) {
                        if (!$record || $user->role !== 'ctv') return [];
                        $commission = $record->commission;
                        $student = $commission?->student;
                        if (!$student) return [];
                        return [
                            'title' => 'SV hiện chưa được chủ đơn vị xác nhận nhập học (VHVL). Sau khi SV nhập học, bạn có thể chuyển cho CTV2.',
                        ];
                    })
                    ->visible(function ($record) use ($user) {
                        if (!$record || $user->role !== 'ctv') return false;
                        $collab = Collaborator::where('email', $user->email)->first();
                        if (!$collab) return false;
                        if (!($record->role === 'direct' && $record->recipient_collaborator_id === $collab->id)) {
                            return false;
                        }
                        $commission = $record->commission;
                        $payment = $commission?->payment;
                        $student = $commission?->student;
                        $programType = strtoupper($payment->program_type ?? $student?->program_type ?? '');
                        if (!in_array($programType, ['PART_TIME', 'VHVL', 'VHVLV'])) {
                            return false;
                        }
                        return !$student || $student->status !== \App\Models\Student::STATUS_ENROLLED;
                    }),

                // Hiển thị cho CTV cấp 1: chỉ khi downline đã xác nhận nhận tiền
                \Filament\Tables\Columns\BadgeColumn::make('downline_received_status')
                    ->label('Chuyển CTV2')
                    ->state(function ($record) use ($user) {
                        if (!$record || $user->role !== 'ctv') return null;
                        $collab = Collaborator::where('email', $user->email)->first();
                        if (!$collab) return null;
                        // Chỉ áp dụng cho item DIRECT của CTV cấp 1
                        if (!($record->role === 'direct' && $record->recipient_collaborator_id === $collab->id)) {
                            return null;
                        }
                        $downlineItem = CommissionItem::where('commission_id', $record->commission_id)
                            ->where('role', 'downline')
                            ->orderBy('id')
                            ->first();
                        if ($downlineItem && $downlineItem->status === CommissionItem::STATUS_RECEIVED_CONFIRMED) {
                            return 'Đã hoàn tất chuyển CTV2';
                        }
                        return null;
                    })
                    ->color('success')
                    ->extraAttributes(function ($record) use ($user) {
                        if (!$record || $user->role !== 'ctv') return [];
                        $collab = Collaborator::where('email', $user->email)->first();
                        if (!$collab) return [];
                        if (!($record->role === 'direct' && $record->recipient_collaborator_id === $collab->id)) {
                            return [];
                        }
                        $downlineItem = CommissionItem::where('commission_id', $record->commission_id)
                            ->where('role', 'downline')
                            ->orderBy('id')
                            ->first();
                        if ($downlineItem && $downlineItem->status === CommissionItem::STATUS_RECEIVED_CONFIRMED) {
                            $time = optional($downlineItem->received_confirmed_at)->format('d/m/Y H:i');
                            return [
                                'title' => $time ? "CTV2 xác nhận lúc {$time}" : 'CTV2 đã xác nhận nhận tiền',
                            ];
                        }
                        return [];
                    })
                    ->visible(function ($record) use ($user) {
                        if (!$record || $user->role !== 'ctv') return false;
                        $collab = Collaborator::where('email', $user->email)->first();
                        if (!$collab) return false;
                        if (!($record->role === 'direct' && $record->recipient_collaborator_id === $collab->id)) {
                            return false;
                        }
                        $downlineItem = CommissionItem::where('commission_id', $record->commission_id)
                            ->where('role', 'downline')
                            ->orderBy('id')
                            ->first();
                        return $downlineItem && $downlineItem->status === CommissionItem::STATUS_RECEIVED_CONFIRMED;
                    }),

                // Cột trạng thái tổng quát cho CTV cấp 2
                \Filament\Tables\Columns\BadgeColumn::make('downline_status')
                    ->label('Trạng thái')
                    ->state(function ($record) use ($user) {
                        if (!$record || $user->role !== 'ctv') return null;
                        $collab = Collaborator::where('email', $user->email)->first();
                        if (!$collab || $collab->upline_id === null) return null; // Chỉ CTV cấp 2 (có upline)
                        if ($record->recipient_collaborator_id !== $collab->id) return null;

                        return match ($record->status) {
                            CommissionItem::STATUS_PENDING => 'Chờ xử lý',
                            CommissionItem::STATUS_PAYABLE => 'Chưa nhận được hoa hồng',
                            CommissionItem::STATUS_PAYMENT_CONFIRMED => 'Chờ xác nhận nhận tiền',
                            CommissionItem::STATUS_RECEIVED_CONFIRMED => 'Đã nhận thành công',
                            CommissionItem::STATUS_CANCELLED => 'Đã hủy',
                            default => 'Không xác định',
                        };
                    })
                    ->color(function ($record) use ($user) {
                        if (!$record || $user->role !== 'ctv') return 'gray';
                        $collab = Collaborator::where('email', $user->email)->first();
                        if (!$collab || $collab->upline_id === null) return 'gray';
                        if ($record->recipient_collaborator_id !== $collab->id) return 'gray';

                        return match ($record->status) {
                            CommissionItem::STATUS_PENDING => 'gray',
                            CommissionItem::STATUS_PAYABLE => 'warning',
                            CommissionItem::STATUS_PAYMENT_CONFIRMED => 'info',
                            CommissionItem::STATUS_RECEIVED_CONFIRMED => 'success',
                            CommissionItem::STATUS_CANCELLED => 'danger',
                            default => 'gray',
                        };
                    })
                    ->visible(function ($record) use ($user) {
                        if (!$record || $user->role !== 'ctv') return false;
                        $collab = Collaborator::where('email', $user->email)->first();
                        if (!$collab || $collab->upline_id === null) return false; // Chỉ CTV cấp 2 (có upline)
                        // Đơn giản hóa: chỉ cần kiểm tra CTV cấp 2 và record thuộc về họ
                        return $record->recipient_collaborator_id === $collab->id;
                    }),

                // Cột trạng thái đơn giản cho CTV cấp 2 (debug)
                \Filament\Tables\Columns\BadgeColumn::make('simple_status')
                    ->label('Trạng thái đơn giản')
                    ->state(function ($record) use ($user) {
                        if (!$record || $user->role !== 'ctv') return null;
                        $collab = Collaborator::where('email', $user->email)->first();
                        if (!$collab || $collab->upline_id === null) return null; // Chỉ CTV cấp 2 (có upline)
                        if ($record->recipient_collaborator_id !== $collab->id) return null;

                        return $record->status;
                    })
                    ->color('info')
                    ->visible(function ($record) use ($user) {
                        if (!$record || $user->role !== 'ctv') return false;
                        $collab = Collaborator::where('email', $user->email)->first();
                        if (!$collab || $collab->upline_id === null) return false; // Chỉ CTV cấp 2 (có upline)
                        return $record->recipient_collaborator_id === $collab->id;
                    }),

                // Cột debug để kiểm tra dữ liệu
                \Filament\Tables\Columns\TextColumn::make('debug_info')
                    ->label('Debug Info')
                    ->state(function ($record) use ($user) {
                        if (!$record || $user->role !== 'ctv') return null;
                        $collab = Collaborator::where('email', $user->email)->first();
                        if (!$collab) return 'No collab';
                        if ($collab->upline_id === null) return 'CTV cấp 1';

                        return "Role: {$record->role}, Recipient: {$record->recipient_collaborator_id}, Collab: {$collab->id}";
                    })
                    ->visible(function ($record) use ($user) {
                        if (!$record || $user->role !== 'ctv') return false;
                        $collab = Collaborator::where('email', $user->email)->first();
                        if (!$collab || $collab->upline_id === null) return false; // Chỉ CTV cấp 2 (có upline)
                        return true; // Hiển thị cho tất cả CTV cấp 2
                    }),

                // Hiển thị cho CTV cấp 2: trạng thái đã nhận tiền thành công
                \Filament\Tables\Columns\BadgeColumn::make('downline_received_confirmed')
                    ->label('Đã nhận tiền thành công')
                    ->state(function ($record) use ($user) {
                        if (!$record || $user->role !== 'ctv') return null;
                        $collab = Collaborator::where('email', $user->email)->first();
                        if (!$collab || $collab->upline_id === null) return null; // Chỉ CTV cấp 2 (có upline)
                        if ($record->role !== 'downline' || $record->recipient_collaborator_id !== $collab->id) return null;
                        if ($record->status === CommissionItem::STATUS_RECEIVED_CONFIRMED) {
                            return 'Đã nhận tiền thành công';
                        }
                        return null;
                    })
                    ->color('success')
                    ->extraAttributes(function ($record) use ($user) {
                        if (!$record || $user->role !== 'ctv') return [];
                        $collab = Collaborator::where('email', $user->email)->first();
                        if (!$collab || $collab->upline_id === null) return []; // Chỉ CTV cấp 2 (có upline)
                        if ($record->role !== 'downline' || $record->recipient_collaborator_id !== $collab->id) return [];
                        if ($record->status === CommissionItem::STATUS_RECEIVED_CONFIRMED) {
                            $time = optional($record->received_confirmed_at)->format('d/m/Y H:i');
                            return [
                                'title' => $time ? "Đã nhận tiền lúc {$time}" : 'Đã nhận tiền thành công',
                            ];
                        }
                        return [];
                    })
                    ->visible(function ($record) use ($user) {
                        if (!$record || $user->role !== 'ctv') return false;
                        $collab = Collaborator::where('email', $user->email)->first();
                        if (!$collab || $collab->upline_id === null) return false; // Chỉ CTV cấp 2 (có upline)
                        return $record->role === 'downline' && $record->recipient_collaborator_id === $collab->id;
                    }),

                \Filament\Tables\Columns\TextColumn::make('trigger')
                    ->label('Điều kiện kích hoạt')
                    ->badge()
                    ->color(fn(string $state): string => match (strtoupper($state)) {
                        'PAYMENT_VERIFIED' => 'blue',
                        'STUDENT_ENROLLED' => 'green',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => match (strtoupper($state)) {
                        'PAYMENT_VERIFIED' => 'Khi xác nhận thanh toán',
                        'STUDENT_ENROLLED' => 'Khi nhập học',
                        default => $state,
                    })
                    ->visible(fn(): bool => !$isCtv && !$isOwner), // Chỉ hiển thị cho Super Admin



                \Filament\Tables\Columns\TextColumn::make('payment_confirmed_at')
                    ->label('Đã thanh toán lúc')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->visible(fn(): bool => !$isCtv), // Chỉ hiển thị cho chủ đơn vị và super admin
            ])
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('status')
                    ->label('Trạng thái')
                    ->options(CommissionItem::getStatusOptions()),

                \Filament\Tables\Filters\SelectFilter::make('role')
                    ->label('Vai trò')
                    ->options([
                        'DIRECT' => 'CTV cấp 1',
                        'DOWNLINE' => 'CTV cấp 2',
                    ]),

                \Filament\Tables\Filters\SelectFilter::make('trigger')
                    ->label('Điều kiện kích hoạt')
                    ->options([
                        'PAYMENT_VERIFIED' => 'Khi xác nhận thanh toán',
                        'STUDENT_ENROLLED' => 'Khi nhập học',
                    ]),
            ])
            ->actions([
                Action::make('mark_payable')
                    ->label('Đánh dấu có thể thanh toán')
                    ->icon('heroicon-o-currency-dollar')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Đánh dấu có thể thanh toán')
                    ->modalDescription('Đánh dấu commission này đã đến hạn chi, CTV có thể nhận.')
                    ->modalSubmitActionLabel('Xác nhận')
                    ->modalCancelActionLabel('Hủy')
                    ->visible(function (CommissionItem $record) use ($user): bool {
                        return $record->status === CommissionItem::STATUS_PENDING && $user->role === 'organization_owner';
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
                    ->label('Xác nhận')
                    ->icon('heroicon-o-currency-dollar')
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
                        // Chủ đơn vị xác nhận khi item là DIRECT và đang PAYABLE/PENDING
                        return $user->role === 'organization_owner'
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
                    ->modalDescription('Xác nhận đã nhận được tiền hoa hồng từ chủ đơn vị.')
                    ->modalSubmitActionLabel('Xác nhận đã nhận')
                    ->modalCancelActionLabel('Hủy')
                    ->visible(function (CommissionItem $record) use ($user): bool {
                        if ($user->role !== 'ctv') return false;
                        // Chỉ hiện cho item DIRECT thuộc CTV hiện tại sau khi Chủ đơn vị xác nhận (PAYMENT_CONFIRMED)
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

                // Từ item DIRECT: CTV cấp 1 (người nhận DIRECT) chuyển tiền cho CTV cấp 2 (ref của sinh viên)
                Action::make('transfer_downline_from_direct')
                    ->label('Chuyển cho CTV cấp 2')
                    ->icon('heroicon-o-arrow-path-rounded-square')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->form([
                        \Filament\Forms\Components\FileUpload::make('bill')
                            ->label('Bill chuyển cho CTV cấp 2')
                            ->disk('local')
                            ->directory('commission-bills')
                            ->acceptedFileTypes(['image/*', 'application/pdf'])
                            ->maxSize(5120)
                            ->required(),
                    ])
                    ->modalHeading('Chuyển tiền cho CTV cấp 2')
                    ->modalDescription('Xác nhận chuyển tiền hoa hồng cho CTV ref của sinh viên.')
                    ->modalSubmitActionLabel('Chuyển tiền')
                    ->modalCancelActionLabel('Hủy')
                    ->visible(function (CommissionItem $record) use ($user): bool {
                        if ($user->role !== 'ctv') return false;
                        // Hiển thị nếu là item DIRECT thuộc CTV hiện tại và đã xác nhận nhận tiền
                        $collab = \App\Models\Collaborator::where('email', $user->email)->first();
                        if (!$collab) return false;
                        if (!($record->role === 'direct'
                            && $record->status === CommissionItem::STATUS_RECEIVED_CONFIRMED
                            && $record->recipient_collaborator_id === $collab->id)) {
                            return false;
                        }
                        // Ẩn nút nếu CTV cấp 2 đã xác nhận nhận tiền (downline item = RECEIVED_CONFIRMED)
                        $downlineItem = \App\Models\CommissionItem::where('commission_id', $record->commission_id)
                            ->where('role', 'downline')
                            ->orderBy('id')
                            ->first();
                        if ($downlineItem && $downlineItem->status === \App\Models\CommissionItem::STATUS_RECEIVED_CONFIRMED) {
                            return false;
                        }
                        // Với hệ VHVL/PART_TIME: chỉ hiển thị khi SV đã được chủ đơn vị xác nhận nhập học
                        $commission = $record->commission;
                        $payment = $commission?->payment;
                        $student = $commission?->student;
                        $programType = strtoupper($payment->program_type ?? $student?->program_type ?? '');
                        if (in_array($programType, ['PART_TIME', 'VHVL', 'VHVLV'])) {
                            if (!$student || $student->status !== Student::STATUS_ENROLLED) {
                                return false;
                            }
                        }
                        return true;
                    })
                    ->action(function (CommissionItem $record, array $data) {
                        $downlineItem = \App\Models\CommissionItem::where('commission_id', $record->commission_id)
                            ->where('role', 'downline')
                            ->orderBy('id')
                            ->first();
                        if (!$downlineItem) {
                            // Tạo item downline nếu chưa có
                            $commission = $record->commission;
                            $payment = $commission?->payment;
                            if (!$commission || !$payment) {
                                \Filament\Notifications\Notification::make()->title('Thiếu dữ liệu commission/payment')->warning()->send();
                                return;
                            }
                            $service = new \App\Services\CommissionService();
                            $downlineItem = $service->createDownlineCommission($commission, $payment);
                            if (!$downlineItem) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Không có cấu hình hoa hồng cho CTV cấp 2')
                                    ->warning()
                                    ->send();
                                return;
                            }
                        }
                        $service = new \App\Services\CommissionService();
                        $service->confirmDownlineTransfer($downlineItem, $data['bill'] ?? null, \Illuminate\Support\Facades\Auth::id());

                        \Filament\Notifications\Notification::make()
                            ->title('Đã xác nhận chuyển tiền cho CTV cấp 2')
                            ->success()
                            ->send();
                    }),

                Action::make('view_bill')
                    ->label('Xem Bill')
                    ->icon('heroicon-o-document-text')
                    ->color('gray')
                    ->modalContent(function (CommissionItem $record) {
                        return view('components.commission-bill-viewer', [
                            'commissionItem' => $record,
                        ]);
                    })
                    ->modalWidth('4xl')
                    ->visible(function (CommissionItem $record) use ($user): bool {
                        return $record->payment_bill_path && in_array($user->role, ['organization_owner', 'ctv']);
                    }),

                // CTV cấp 2 xác nhận đã nhận tiền (tiền chuyển từ ví CTV1 sang CTV2)
                Action::make('transfer_to_downline')
                    ->label('CTV cấp 2 xác nhận đã nhận tiền')
                    ->icon('heroicon-o-hand-thumb-up')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Xác nhận đã nhận tiền')
                    ->modalDescription('Xác nhận đã nhận tiền từ CTV cấp 1.')
                    ->modalSubmitActionLabel('Xác nhận')
                    ->modalCancelActionLabel('Hủy')
                    ->visible(function (CommissionItem $record) use ($user): bool {
                        if ($user->role !== 'ctv') return false;
                        $collab = \App\Models\Collaborator::where('email', $user->email)->first();
                        if (!$collab) return false;
                        return $record->role === 'downline'
                            && $record->recipient_collaborator_id === $collab->id
                            && $record->status === \App\Models\CommissionItem::STATUS_PAYMENT_CONFIRMED;
                    })
                    ->action(function (CommissionItem $record) {
                        $service = new \App\Services\CommissionService();
                        $service->confirmDownlineReceived($record, \Illuminate\Support\Facades\Auth::id());

                        \Filament\Notifications\Notification::make()
                            ->title('Đã xác nhận nhận tiền')
                            ->success()
                            ->send();

                        // Chuyển hướng sang trang Ví tiền để hiển thị ngay số dư mới
                        return redirect()->route('filament.admin.resources.wallets.index');
                    }),

                Action::make('mark_cancelled')
                    ->label('Đánh dấu đã huỷ')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Đánh dấu đã huỷ')
                    ->modalDescription('Đánh dấu huỷ hoa hồng này (VD: SV không nhập học).')
                    ->modalSubmitActionLabel('Xác nhận')
                    ->modalCancelActionLabel('Hủy')
                    ->visible(function (CommissionItem $record) use ($user): bool {
                        return in_array($record->status, [CommissionItem::STATUS_PENDING, CommissionItem::STATUS_PAYABLE]) && $user->role === 'organization_owner';
                    })
                    ->action(function (CommissionItem $record) {
                        $record->markAsCancelled();

                        \Filament\Notifications\Notification::make()
                            ->title('Đã đánh dấu huỷ')
                            ->body('Hoa hồng đã được huỷ.')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    \Filament\Actions\BulkAction::make('bulk_confirm_payment')
                        ->label('Xác nhận thanh toán hàng loạt')
                        ->icon('heroicon-o-currency-dollar')
                        ->color('success')
                        ->form([
                            \Filament\Forms\Components\FileUpload::make('bill')
                                ->label('Bill thanh toán chung')
                                ->required()
                                ->disk('local')
                                ->directory('commission-bills')
                                ->acceptedFileTypes(['image/*', 'application/pdf'])
                                ->maxSize(5120) // 5MB
                                ->helperText('Upload một bill thanh toán chung cho tất cả các hoa hồng đã chọn'),
                            \Filament\Forms\Components\Textarea::make('note')
                                ->label('Ghi chú')
                                ->rows(3)
                                ->placeholder('Ghi chú về việc thanh toán hàng loạt này...')
                                ->helperText('Ghi chú tùy chọn cho việc thanh toán hàng loạt'),
                        ])
                        ->modalHeading('Xác nhận thanh toán hàng loạt')
                        ->modalDescription('Xác nhận đã thanh toán hoa hồng cho tất cả các CTV đã chọn. Một bill chung sẽ được áp dụng cho tất cả.')
                        ->modalSubmitActionLabel('Xác nhận thanh toán tất cả')
                        ->modalCancelActionLabel('Hủy')
                        ->visible(fn() => Auth::user()->role === 'organization_owner')
                        ->action(function (array $data, $records) {
                            $userId = Auth::user()->id;
                            $billPath = $data['bill'];
                            $note = $data['note'] ?? null;

                            $successCount = 0;
                            $errorCount = 0;

                            foreach ($records as $record) {
                                try {
                                    // Chỉ xử lý các commission có thể thanh toán
                                    if (in_array($record->status, [CommissionItem::STATUS_PAYABLE, CommissionItem::STATUS_PENDING])) {
                                        $record->markAsPaymentConfirmed($billPath, $userId);
                                        $successCount++;
                                    }
                                } catch (\Exception $e) {
                                    $errorCount++;
                                }
                            }

                            // Hiển thị thông báo kết quả
                            if ($successCount > 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title("Đã xác nhận thanh toán thành công {$successCount} hoa hồng")
                                    ->body($errorCount > 0 ? "Có {$errorCount} hoa hồng không thể xử lý." : "Tất cả hoa hồng đã được xác nhận.")
                                    ->success()
                                    ->send();
                            } else {
                                \Filament\Notifications\Notification::make()
                                    ->title('Không có hoa hồng nào được xác nhận')
                                    ->body('Vui lòng kiểm tra lại trạng thái của các hoa hồng đã chọn.')
                                    ->warning()
                                    ->send();
                            }
                        })
                        ->deselectRecordsAfterCompletion(),

                    DeleteBulkAction::make()
                        ->label('Xóa đã chọn')
                        ->modalHeading('Xóa hoa hồng đã chọn')
                        ->modalDescription('Bạn có chắc chắn muốn xóa các hoa hồng đã chọn? Hành động này không thể hoàn tác.')
                        ->modalSubmitActionLabel('Xóa')
                        ->modalCancelActionLabel('Hủy'),
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

                if ($user->role === 'organization_owner') {
                    // Chủ đơn vị chỉ thấy commission của tổ chức mình
                    $org = Organization::where('organization_owner_id', $user->id)->first();
                    if ($org) {
                        $query->whereHas('recipient', function ($q) use ($org) {
                            $q->where('organization_id', $org->id);
                        });
                        // Và chỉ hiển thị khoản hoa hồng CTV cấp 1 (direct)
                        $query->where('role', 'direct');
                    }
                }

                if ($user->role === 'accountant') {
                    // Kế toán có thể xem tất cả commissions để đối soát
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
            // Đếm theo Commission để tránh nhân đôi (DIRECT + DOWNLINE)
            return (string) Commission::count();
        } catch (\Throwable) {
            return null;
        }
    }

    public static function getNavigationBadgeTooltip(): ?string {
        return 'Tổng số bộ hoa hồng';
    }

    /**
     * Lấy tất cả ID của downline collaborators
     */
    private static function getDownlineIds(int $collaboratorId): array {
        $downlineIds = [];

        $downlines = Collaborator::where('upline_id', $collaboratorId)->get();

        foreach ($downlines as $downline) {
            $downlineIds[] = $downline->id;
            // Đệ quy lấy downline của downline
            $subDownlineIds = self::getDownlineIds($downline->id);
            $downlineIds = array_merge($downlineIds, $subDownlineIds);
        }

        return $downlineIds;
    }
}
