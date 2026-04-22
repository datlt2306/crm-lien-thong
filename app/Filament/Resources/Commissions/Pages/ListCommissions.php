<?php

namespace App\Filament\Resources\Commissions\Pages;

use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\Commissions\CommissionResource;
use Illuminate\Support\Facades\Auth;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Grid;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\FeeClosingExport;

class ListCommissions extends ListRecords {
    protected static string $resource = CommissionResource::class;

    public static function canAccess(array $parameters = []): bool {
        $user = Auth::user();

        if (!$user) {
            return false;
        }

        // Kiểm tra qua quyền hạn động hoặc vai trò cứng (Fallback)
        return $user->can('commission_view_any') || 
            in_array($user->role, ['super_admin', 'admin', 'organization_owner', 'accountant', 'ctv', 'document']);
    }


    protected function getHeaderActions(): array {
        return [
            Action::make('export_fee_closing')
                ->label('Xuất bảng kê lệ phí')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->form([
                    Grid::make(2)
                        ->schema([
                            DatePicker::make('start_date')
                                ->label('Từ ngày')
                                ->default(now()->startOfMonth())
                                ->required()
                                ->live(),
                            DatePicker::make('end_date')
                                ->label('Đến ngày')
                                ->default(now())
                                ->required()
                                ->afterOrEqual('start_date'),
                        ]),
                    \Filament\Forms\Components\Select::make('collaborator_id')
                        ->label('Người hướng dẫn')
                        ->options(\App\Models\Collaborator::pluck('full_name', 'id'))
                        ->searchable()
                        ->required()
                        ->placeholder('Bắt buộc chọn một CTV')
                        ->helperText('Chọn CTV để lọc danh sách học viên'),
                    \Filament\Forms\Components\Select::make('collector_user_id')
                        ->label('Người thu')
                        ->options(\App\Models\User::where('is_active', true)
                            ->where('role', '!=', 'ctv')
                            ->pluck('name', 'id'))
                        ->searchable()
                        ->required()
                        ->default(fn() => Auth::id()),
                    TextInput::make('title')
                        ->label('Tiêu đề bảng kê')
                        ->placeholder('Ví dụ: DANH SÁCH LỆ PHÍ CÔ LY THU TỪ NGÀY 22/01-03/02/2026')
                        ->helperText('Để trống để tự động tạo theo mẫu: Danh sách lệ phí [Người thu] thu từ [Ngày] - [Ngày]'),
                    TextInput::make('note')
                        ->label('Ghi chú chung')
                        ->placeholder('Lệ phí hồ sơ')
                        ->default('Lệ phí hồ sơ'),
                ])
                ->action(function (array $data) {
                    $startDate = Carbon::parse($data['start_date']);
                    $endDate = Carbon::parse($data['end_date']);
                    
                    $filename = 'Chot-so-le-phi-' . $startDate->format('d-m') . '-to-' . $endDate->format('d-m-Y') . '.xlsx';
                    
                    return Excel::download(
                        new FeeClosingExport($data),
                        $filename
                    );
                }),

            Action::make('batch_confirm_payout')
                ->label('Xác nhận đã chi trả')
                ->icon('heroicon-o-check-badge')
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('Xác nhận chi hoa hồng hàng loạt')
                ->modalDescription('Hệ thống sẽ tìm tất cả các mục hoa hồng chưa chốt, khớp với tiêu chí bên dưới và đánh dấu là "Đã chốt & Đã chi". Bạn nên thực hiện hành động này sau khi đã gửi file cho CTV đối soát xong.')
                ->form([
                    Grid::make(2)
                        ->schema([
                            DatePicker::make('start_date')
                                ->label('Từ ngày (Ngày KT duyệt)')
                                ->default(now()->startOfMonth())
                                ->required(),
                            DatePicker::make('end_date')
                                ->label('Đến ngày (Ngày KT duyệt)')
                                ->default(now())
                                ->required()
                                ->afterOrEqual('start_date'),
                        ]),
                    \Filament\Forms\Components\Select::make('collaborator_id')
                        ->label('Người hướng dẫn (CTV)')
                        ->options(\App\Models\Collaborator::pluck('full_name', 'id'))
                        ->searchable()
                        ->required()
                        ->placeholder('Chọn CTV cần chốt chi'),
                    \Filament\Forms\Components\Textarea::make('reason')
                        ->label('Ghi chú chốt chi (nếu có)')
                        ->placeholder('Ví dụ: Chốt chi đợt 1 tháng 2...'),
                ])
                ->visible(fn() => Auth::user()->can('commission_payout'))
                ->action(function (array $data) {
                    $startDate = Carbon::parse($data['start_date'])->startOfDay();
                    $endDate = Carbon::parse($data['end_date'])->endOfDay();
                    $collaboratorId = $data['collaborator_id'];
                    $userId = Auth::id();

                    // Tìm các CommissionItem khớp tiêu chí
                    $items = \App\Models\CommissionItem::query()
                        ->where('recipient_collaborator_id', $collaboratorId)
                        ->whereIn('status', [
                            \App\Models\CommissionItem::STATUS_PAYABLE,
                            \App\Models\CommissionItem::STATUS_PENDING
                        ])
                        ->whereHas('commission.payment', function ($query) use ($startDate, $endDate) {
                            $query->where('status', \App\Models\Payment::STATUS_VERIFIED)
                                ->whereBetween('verified_at', [$startDate, $endDate]);
                        })
                        ->get();

                    if ($items->isEmpty()) {
                        \Filament\Notifications\Notification::make()
                            ->warning()
                            ->title('Không tìm thấy dữ liệu')
                            ->body('Không có mục hoa hồng nào khớp với tiêu chí đã chọn hoặc tất cả đã được chốt trước đó.')
                            ->send();
                        return;
                    }

                    $count = 0;
                    $totalAmount = 0;
                    $batchData = [];

                    foreach ($items as $item) {
                        $studentName = $item->commission?->student?->full_name ?? 'N/A';
                        $amount = (float)($item->amount ?? 0);

                        $batchData[] = [
                            'student' => $studentName,
                            'amount' => $amount,
                        ];
                        $totalAmount += $amount;

                        $item->updateQuietly([
                            'status' => \App\Models\CommissionItem::STATUS_PAYMENT_CONFIRMED,
                            'payment_confirmed_at' => now(),
                            'payment_confirmed_by' => $userId,
                        ]);
                        $count++;
                    }

                    // Tạo Audit Log cho cả lô
                    \App\Models\AuditLog::create([
                        'event_group' => \App\Models\AuditLog::GROUP_FINANCIAL,
                        'event_type' => \App\Models\AuditLog::TYPE_BATCH_CONFIRMED,
                        'user_id' => $userId,
                        'user_role' => Auth::user()->role,
                        'metadata' => [
                            'criteria' => $data,
                            'count' => $count,
                            'total_amount' => $totalAmount,
                            'items_summary' => $batchData,
                        ],
                        'reason' => 'Xác nhận chi theo tiêu chí: ' . ($data['reason'] ?? 'Không có ghi chú'),
                        'ip_address' => request()->ip(),
                        'user_agent' => request()->userAgent(),
                        'created_at' => now(),
                    ]);

                    \Filament\Notifications\Notification::make()
                        ->success()
                        ->title("Đã chốt chi thành công cho {$count} mục hoa hồng")
                        ->body("Tổng số tiền: " . number_format($totalAmount, 0, ',', '.') . " VNĐ")
                        ->send();
                }),
        ];
    }

    public function getMaxContentWidth(): string {
        return 'full';
    }

    public function getTitle(): string {
        return 'Báo cáo hoa hồng & Đối soát';
    }

    public function getBreadcrumb(): string {
        return 'Hoa hồng';
    }
}
