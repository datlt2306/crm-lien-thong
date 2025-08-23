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
use App\Filament\Resources\Commissions\Pages\ListCommissions;
use Illuminate\Support\Facades\Auth;

class CommissionResource extends Resource {
    protected static ?string $model = Commission::class;
    protected static string|\UnitEnum|null $navigationGroup = 'Thanh toán & Hoa hồng';
    protected static ?string $navigationLabel = 'Hoa hồng';
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
        // Kiểm tra xem có phải là chủ đơn vị không
        $isOwner = $user->role === 'chủ đơn vị';
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
                    ->sortable(),

                \Filament\Tables\Columns\TextColumn::make('recipient.full_name')
                    ->label('CTV nhận hoa hồng')
                    ->searchable()
                    ->sortable()
                    ->visible(fn(): bool => !$isCtv || !$isDirectRef) // Ẩn cho CTV trực tiếp giới thiệu, hiển thị cho CTV khác
                    ->formatStateUsing(function ($state, CommissionItem $record) use ($user) {
                        // Cho chủ đơn vị, hiển thị CTV cấp 1 thay vì CTV thực tế nhận
                        if ($user->role === 'chủ đơn vị') {
                            $student = $record->commission->student;
                            if ($student && $student->collaborator && $student->collaborator->upline) {
                                return $student->collaborator->upline->full_name; // CTV cấp 1
                            }
                        }
                        return $state; // CTV thực tế nhận
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
                        return CommissionItem::getStatusOptions()[$state] ?? $state;
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

                \Filament\Tables\Columns\TextColumn::make('payable_at')
                    ->label('Có thể thanh toán từ')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
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
                        return $record->status === CommissionItem::STATUS_PENDING && $user->role === 'chủ đơn vị';
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
                    ->label('Xác nhận đã thanh toán')
                    ->icon('heroicon-o-check-circle')
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
                        return in_array($record->status, [CommissionItem::STATUS_PAYABLE, CommissionItem::STATUS_PENDING]) && $user->role === 'chủ đơn vị';
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
                        return $record->status === CommissionItem::STATUS_PAYMENT_CONFIRMED && $user->role === 'ctv';
                    })
                    ->action(function (CommissionItem $record) {
                        $record->markAsReceivedConfirmed(\Illuminate\Support\Facades\Auth::user()->id);

                        \Filament\Notifications\Notification::make()
                            ->title('Đã xác nhận nhận tiền')
                            ->body('Quy trình thanh toán hoa hồng đã hoàn tất.')
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
                        return $record->payment_bill_path && in_array($user->role, ['chủ đơn vị', 'ctv']);
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
                        return in_array($record->status, [CommissionItem::STATUS_PENDING, CommissionItem::STATUS_PAYABLE]) && $user->role === 'chủ đơn vị';
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
                        ->visible(fn() => Auth::user()->role === 'chủ đơn vị')
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
            ->modifyQueryUsing(function ($query) {
                $user = Auth::user();

                if ($user->role === 'super_admin') {
                    return;
                }

                if ($user->role === 'ctv') {
                    // CTV thấy commission của mình và downline
                    $collaborator = Collaborator::where('email', $user->email)->first();
                    if ($collaborator) {
                        // Lấy tất cả downline IDs
                        $downlineIds = self::getDownlineIds($collaborator->id);
                        // Thêm chính mình vào danh sách
                        $allIds = array_merge([$collaborator->id], $downlineIds);
                        $query->whereIn('recipient_collaborator_id', $allIds);
                    } else {
                        $query->whereNull('id');
                    }
                }

                if ($user->role === 'chủ đơn vị') {
                    // Chủ đơn vị chỉ thấy commission của tổ chức mình
                    $org = Organization::where('owner_id', $user->id)->first();
                    if ($org) {
                        $query->whereHas('recipient', function ($q) use ($org) {
                            $q->where('organization_id', $org->id);
                        });
                    }
                }
            });
    }

    public static function getPages(): array {
        return [
            'index' => ListCommissions::route('/'),
        ];
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
