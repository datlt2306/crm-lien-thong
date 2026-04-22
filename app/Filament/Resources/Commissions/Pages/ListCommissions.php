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

        // Các vai trò được quyền xem báo cáo hoa hồng & đối soát
        if (in_array($user->role, ['super_admin', 'admin', 'organization_owner', 'accountant', 'ctv'])) {
            return true;
        }

        return false;
    }


    protected function getHeaderActions(): array {
        return [
            Action::make('export_fee_closing')
                ->label('Chốt sổ lệ phí')
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
