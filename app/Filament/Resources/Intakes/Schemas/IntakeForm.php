<?php

namespace App\Filament\Resources\Intakes\Schemas;

use App\Models\Organization;
use App\Models\Program;
use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;

class IntakeForm {
    public static function configure(Schema $schema): Schema {
        return $schema
            ->schema([
                Section::make('📅 Thông tin đợt tuyển sinh')
                    ->description('Thiết lập thông tin cơ bản cho đợt tuyển sinh')
                    ->icon('heroicon-o-calendar-days')
                    ->schema([
                        TextInput::make('name')
                            ->label('🎯 Tên đợt tuyển sinh')
                            ->required()
                            ->placeholder('VD: Đợt 1 - Học kỳ I 2025')
                            ->maxLength(255)
                            ->helperText('Đặt tên đợt tuyển sinh (hiển thị trong danh sách, báo cáo và khi chọn đợt)'),

                        Textarea::make('description')
                            ->label('📝 Mô tả chi tiết')
                            ->placeholder('Mô tả chi tiết về đợt tuyển sinh...')
                            ->rows(3)
                            ->helperText('Mô tả về mục tiêu, đối tượng tuyển sinh hoặc yêu cầu đặc biệt')
                            ->columnSpanFull(),

                        Select::make('organization_id')
                            ->label('🏢 Tổ chức')
                            ->relationship('organization', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->placeholder('Chọn tổ chức...')
                            ->helperText('Tổ chức sẽ quản lý đợt tuyển sinh này'),

                        Select::make('program_id')
                            ->label('🎓 Chương trình đào tạo')
                            ->options(Program::where('is_active', true)->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->placeholder('Chọn chương trình đào tạo (tùy chọn)')
                            ->helperText('Chương trình đào tạo cho đợt tuyển sinh này'),

                        Select::make('status')
                            ->label('📋 Trạng thái')
                            ->options(\App\Models\Intake::getStatusOptions())
                            ->required()
                            ->default(\App\Models\Intake::STATUS_UPCOMING)
                            ->placeholder('Chọn trạng thái...')
                            ->helperText('Trạng thái hiện tại của đợt tuyển sinh'),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Section::make('⏰ Khoảng thời gian tuyển sinh')
                    ->description('Chọn khoảng thời gian nhận hồ sơ (từ ngày — đến ngày)')
                    ->icon('heroicon-o-clock')
                    ->schema([
                        DatePicker::make('start_date')
                            ->label('Từ ngày')
                            ->required()
                            ->displayFormat('d/m/Y')
                            ->native(false)
                            ->placeholder('Chọn ngày bắt đầu...')
                            ->helperText('Ngày bắt đầu nhận hồ sơ'),

                        DatePicker::make('end_date')
                            ->label('Đến ngày')
                            ->required()
                            ->displayFormat('d/m/Y')
                            ->native(false)
                            ->after('start_date')
                            ->placeholder('Chọn ngày kết thúc...')
                            ->helperText('Ngày cuối cùng nhận hồ sơ'),

                        DatePicker::make('enrollment_deadline')
                            ->label('📅 Hạn chót nhập học')
                            ->displayFormat('d/m/Y')
                            ->native(false)
                            ->after('end_date')
                            ->placeholder('Chọn hạn chót nhập học...')
                            ->helperText('Hạn chót để học viên hoàn tất nhập học'),
                    ])
                    ->columns(3)
                    ->collapsible(),

                Section::make('📊 Chỉ tiêu tuyển sinh năm')
                    ->description('Thông tin chỉ tiêu các ngành cho năm của đợt tuyển này (cấu hình tại Chỉ tiêu năm)')
                    ->icon('heroicon-o-chart-bar')
                    ->schema([
                        \Filament\Forms\Components\Placeholder::make('annual_quotas_info')
                            ->label('')
                            ->content(function ($record) {
                                if (!$record || !$record->organization_id || !$record->start_date) {
                                    return new \Illuminate\Support\HtmlString('<p class="text-gray-500">Vui lòng chọn tổ chức và ngày bắt đầu trước.</p>');
                                }

                                $year = $record->start_date->format('Y');
                                $quotas = \Illuminate\Support\Facades\DB::table('annual_quotas')
                                    ->join('majors', 'annual_quotas.major_id', '=', 'majors.id')
                                    ->join('programs', 'annual_quotas.program_id', '=', 'programs.id')
                                    ->where('annual_quotas.organization_id', $record->organization_id)
                                    ->where('annual_quotas.year', $year)
                                    ->select(
                                        'majors.name as major_name',
                                        'programs.name as program_name',
                                        'annual_quotas.target_quota',
                                        'annual_quotas.current_quota',
                                        'annual_quotas.status'
                                    )
                                    ->get();

                                if ($quotas->isEmpty()) {
                                    $url = route('filament.admin.resources.annual-quotas.create');
                                    return new \Illuminate\Support\HtmlString("
                                        <div class='p-4 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg border border-yellow-200 dark:border-yellow-800'>
                                            <p class='text-yellow-700 dark:text-yellow-300 font-medium'>⚠️ Chưa có chỉ tiêu năm {$year} nào được cấu hình cho tổ chức này.</p>
                                            <a href='{$url}' class='mt-2 inline-block text-blue-600 hover:underline'>
                                                → Tạo chỉ tiêu năm mới
                                            </a>
                                        </div>
                                    ");
                                }

                                $html = "<div class='space-y-2'>";
                                $totalTarget = 0;
                                $totalCurrent = 0;

                                foreach ($quotas as $q) {
                                    $remaining = $q->target_quota - $q->current_quota;
                                    $percent = $q->target_quota > 0 ? round(($q->current_quota / $q->target_quota) * 100, 1) : 0;
                                    $statusColor = $q->status === 'active' ? 'green' : ($q->status === 'full' ? 'red' : 'gray');
                                    $progressColor = $percent > 90 ? 'red' : ($percent > 70 ? 'yellow' : 'green');
                                    
                                    $totalTarget += $q->target_quota;
                                    $totalCurrent += $q->current_quota;

                                    $html .= "
                                        <div class='p-3 bg-gray-50 dark:bg-gray-800 rounded-lg border'>
                                            <div class='flex justify-between items-center'>
                                                <div>
                                                    <span class='font-semibold text-gray-900 dark:text-white'>{$q->major_name}</span>
                                                    <span class='text-sm text-gray-500 dark:text-gray-400'>({$q->program_name})</span>
                                                </div>
                                                <span class='px-2 py-1 text-xs rounded-full bg-{$statusColor}-100 text-{$statusColor}-700 dark:bg-{$statusColor}-900/30 dark:text-{$statusColor}-400'>
                                                    " . ($q->status === 'active' ? 'Đang tuyển' : ($q->status === 'full' ? 'Đã đủ' : 'Tạm dừng')) . "
                                                </span>
                                            </div>
                                            <div class='mt-2'>
                                                <div class='flex justify-between text-sm text-gray-600 dark:text-gray-300 mb-1'>
                                                    <span>Đã tuyển: {$q->current_quota}/{$q->target_quota}</span>
                                                    <span>Còn lại: <strong class='text-{$progressColor}-600'>{$remaining}</strong></span>
                                                </div>
                                                <div class='w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2'>
                                                    <div class='bg-{$progressColor}-500 h-2 rounded-full' style='width: {$percent}%'></div>
                                                </div>
                                            </div>
                                        </div>
                                    ";
                                }

                                $totalRemaining = $totalTarget - $totalCurrent;
                                $totalPercent = $totalTarget > 0 ? round(($totalCurrent / $totalTarget) * 100, 1) : 0;
                                $url = route('filament.admin.resources.annual-quotas.index') . "?tableFilters[organization_id][value]={$record->organization_id}&tableFilters[year][value]={$year}";

                                $html .= "
                                    <div class='mt-4 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800'>
                                        <div class='flex justify-between items-center'>
                                            <span class='font-semibold text-blue-700 dark:text-blue-300'>📊 Tổng cộng năm {$year}</span>
                                            <span class='text-blue-600 dark:text-blue-400'>{$totalCurrent}/{$totalTarget} ({$totalPercent}%)</span>
                                        </div>
                                        <div class='mt-2 text-sm'>
                                            <a href='{$url}' class='text-blue-600 hover:underline'>→ Quản lý chỉ tiêu năm {$year}</a>
                                        </div>
                                    </div>
                                </div>";

                                return new \Illuminate\Support\HtmlString($html);
                            })
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(false),
            ]);
    }
}
