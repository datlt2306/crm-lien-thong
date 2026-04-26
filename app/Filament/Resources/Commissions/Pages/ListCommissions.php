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
use App\Filament\Resources\Commissions\CommissionResource\Widgets\CommissionStatsWidget;

class ListCommissions extends ListRecords {
    protected static string $resource = CommissionResource::class;

    protected function getHeaderWidgets(): array {
        return [
            CommissionStatsWidget::class,
        ];
    }

    public static function canAccess(array $parameters = []): bool {
        $user = Auth::user();

        if (!$user) {
            return false;
        }

        // Kiểm tra qua quyền hạn động hoặc vai trò cứng (Fallback)
        return $user->can('commission_view_any') || 
            in_array($user->role, ['super_admin', 'admin', 'organization_owner', 'accountant', 'collaborator', 'document']);
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
                    \Filament\Forms\Components\Select::make('status')
                        ->label('Trạng thái hoa hồng')
                        ->options([
                            \App\Models\CommissionItem::STATUS_PAYABLE => 'Chưa chi (Đang chờ đối soát)',
                            \App\Models\CommissionItem::STATUS_PAYMENT_CONFIRMED => 'Đã chi (Đã xác nhận thanh toán)',
                        ])
                        ->default(\App\Models\CommissionItem::STATUS_PAYABLE)
                        ->required(),
                    \Filament\Forms\Components\Select::make('collaborator_id')
                        ->label('Người hướng dẫn')
                        ->options(\App\Models\Collaborator::pluck('full_name', 'id'))
                        ->searchable()
                        ->required()
                        ->hidden(fn() => Auth::user()->hasRole('collaborator'))
                        ->dehydrated(true)
                        ->default(function() {
                            $user = Auth::user();
                            if ($user->hasRole('collaborator')) {
                                return \App\Models\Collaborator::where('email', $user->email)->first()?->id;
                            }
                            return null;
                        })
                        ->placeholder('Bắt buộc chọn một CTV')
                        ->helperText('Chọn CTV để lọc danh sách học viên'),
                    TextInput::make('title')
                        ->label('Tiêu đề bảng kê')
                        ->hidden(fn() => Auth::user()->hasRole('collaborator'))
                        ->placeholder('Ví dụ: BẢNG KÊ HOA HỒNG CTV NGUYỄN VĂN A')
                        ->helperText('Để trống để tự động tạo theo mẫu: Danh sách lệ phí [Tên CTV] từ [Ngày] - [Ngày]'),
                    TextInput::make('note')
                        ->label('Ghi chú chung')
                        ->hidden(fn() => Auth::user()->hasRole('collaborator'))
                        ->placeholder('Nhập ghi chú cho bảng kê (ví dụ: Lệ phí hồ sơ, Học phí...)')
                        ->default(''),
                ])
                ->action(function (array $data) {
                    $user = Auth::user();
                    if ($user->hasRole('collaborator')) {
                        $data['collaborator_id'] = \App\Models\Collaborator::where('email', $user->email)->first()?->id;
                    }
                    
                    $startDate = Carbon::parse($data['start_date'])->startOfDay();
                    $endDate = Carbon::parse($data['end_date'])->endOfDay();
                    $collaboratorId = $data['collaborator_id'];

                    $count = \App\Models\CommissionItem::query()
                        ->where('recipient_collaborator_id', $collaboratorId)
                        ->where('status', \App\Models\CommissionItem::STATUS_PAYABLE)
                        ->whereBetween('payable_at', [$startDate, $endDate])
                        ->count();

                    \Filament\Notifications\Notification::make()
                        ->info()
                        ->title('Thông tin đối soát')
                        ->body("Tìm thấy {$count} mục hoa hồng phù hợp với tiêu chí.")
                        ->send();

                    if ($count === 0) {
                        return;
                    }

                    $filename = 'Chot-so-le-phi-' . $startDate->format('d-m') . '-to-' . $endDate->format('d-m-Y') . '.xlsx';
                    
                    return Excel::download(
                        new FeeClosingExport($data),
                        $filename
                    );
                }),

            Action::make('import_reconciliation')
                ->label('Import đối soát (Từ Excel)')
                ->icon('heroicon-o-document-arrow-up')
                ->color('warning')
                ->form([
                    \Filament\Forms\Components\FileUpload::make('file')
                        ->label('Chọn file Excel đã đối soát')
                        ->disk('local')
                        ->directory('temp-imports')
                        ->required()
                        ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel']),
                    \Filament\Forms\Components\Placeholder::make('help')
                        ->label('Hướng dẫn')
                        ->content(new \Illuminate\Support\HtmlString('
                            <ul class="list-disc ml-5 text-sm">
                                <li>Bước 1: Nhấn "Xuất bảng kê lệ phí" để tải file danh sách về.</li>
                                <li>Bước 2: Mở file Excel, điền chữ <b>"X"</b> vào cột <b>"Xác nhận đã chi (Điền X)"</b> cho những người đã trả tiền.</li>
                                <li>Bước 3: Lưu file và tải lên tại đây.</li>
                                <li class="text-danger-600 font-bold">Lưu ý: KHÔNG ĐƯỢC thay đổi cột "ID" trong file Excel.</li>
                            </ul>
                        ')),
                ])
                ->visible(fn() => Auth::user()->can('commission_payout'))
                ->action(function (array $data) {
                    $filePath = \Illuminate\Support\Facades\Storage::disk('local')->path($data['file']);
                    
                    try {
                        $spreadsheet = \Maatwebsite\Excel\Facades\Excel::toArray([], $filePath);
                        $totalUpdated = 0;
                        $userId = Auth::id();

                        foreach ($spreadsheet as $sheet) {
                            // Bỏ qua 2 dòng đầu (Tiêu đề và Header)
                            $rows = array_slice($sheet, 2);
                            
                            foreach ($rows as $row) {
                                // Cột I là Xác nhận (index 8), Cột J là ID (index 9)
                                $confirmValue = trim((string)($row[8] ?? ''));
                                $itemId = $row[9] ?? null;

                                if (!empty($confirmValue) && $itemId) {
                                    $item = \App\Models\CommissionItem::find($itemId);
                                    
                                    if ($item && $item->status === \App\Models\CommissionItem::STATUS_PAYABLE) {
                                        $item->updateQuietly([
                                            'status' => \App\Models\CommissionItem::STATUS_PAYMENT_CONFIRMED,
                                            'payment_confirmed_at' => now(),
                                            'payment_confirmed_by' => $userId,
                                        ]);
                                        $totalUpdated++;
                                    }
                                }
                            }
                        }

                        // Xoá file sau khi xong
                        \Illuminate\Support\Facades\Storage::disk('local')->delete($data['file']);

                        if ($totalUpdated > 0) {
                            \Filament\Notifications\Notification::make()
                                ->success()
                                ->title("Đối soát thành công")
                                ->body("Đã cập nhật trạng thái 'Đã chi' cho {$totalUpdated} mục hoa hồng.")
                                ->send();
                        } else {
                            \Filament\Notifications\Notification::make()
                                ->warning()
                                ->title("Không có dữ liệu thay đổi")
                                ->body("Không tìm thấy mục hoa hồng nào có đánh dấu 'X' hoặc các mục đã được chốt trước đó.")
                                ->send();
                        }

                    } catch (\Exception $e) {
                        \Filament\Notifications\Notification::make()
                            ->danger()
                            ->title("Lỗi xử lý file")
                            ->body($e->getMessage())
                            ->send();
                    }
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
