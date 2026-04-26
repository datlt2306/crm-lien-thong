<?php

namespace App\Filament\Resources\Intakes\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Hidden;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;

class IntakeForm {
    private static function getProgramLabel(?string $programCode): string {
        return match (strtoupper((string) $programCode)) {
            'regular' => 'Chính quy',
            'part_time' => 'Vừa học vừa làm',
            'distance' => 'Đào tạo từ xa',
            default => $programCode ?: 'Chưa xác định',
        };
    }

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
                            ->placeholder('VD: Đợt 1')
                            ->maxLength(255)
                            ->helperText('Chỉ cần nhập tên đợt: Đợt 1, Đợt 2, Đợt 3...'),

                        Textarea::make('description')
                            ->label('📝 Mô tả chi tiết')
                            ->placeholder('Mô tả chi tiết về đợt tuyển sinh...')
                            ->rows(3)
                            ->helperText('Mô tả về mục tiêu, đối tượng tuyển sinh hoặc yêu cầu đặc biệt')
                            ->columnSpanFull(),


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
                    ->description('Thiết lập thời gian áp dụng chung cho đợt tuyển sinh')
                    ->icon('heroicon-o-clock')
                    ->schema([
                        DatePicker::make('start_date')
                            ->label('Từ ngày')
                            ->required()
                            ->displayFormat('d/m/Y')
                            ->format('Y-m-d')
                            ->native(false)
                            ->rule('date')
                            ->extraInputAttributes(['placeholder' => 'dd/mm/yyyy']),

                        DatePicker::make('end_date')
                            ->label('Đến ngày')
                            ->required()
                            ->displayFormat('d/m/Y')
                            ->format('Y-m-d')
                            ->native(false)
                            ->rule('date')
                            ->after('start_date')
                            ->extraInputAttributes(['placeholder' => 'dd/mm/yyyy']),

                        DatePicker::make('enrollment_deadline')
                            ->label('Hạn chót hoàn thiện hồ sơ')
                            ->displayFormat('d/m/Y')
                            ->format('Y-m-d')
                            ->native(false)
                            ->rule('date')
                            ->after('end_date')
                            ->extraInputAttributes(['placeholder' => 'dd/mm/yyyy']),
                    ])
                    ->columns(3)
                    ->collapsible(),

                Section::make('📊 Quản lý chỉ tiêu năm')
                    ->description('Tạo và liên kết chỉ tiêu năm với đợt tuyển sinh này. Chỉ tiêu năm có thể được chia sẻ giữa nhiều đợt trong cùng năm.')
                    ->icon('heroicon-o-chart-bar')
                    ->schema([
                        Select::make('settings.annual_quota_year')
                            ->label('🗓️ Năm chỉ tiêu')
                            ->options(function ($get, $record) {
                                $years = \Illuminate\Support\Facades\DB::table('annual_quotas')
                                    ->whereNotNull('year')
                                    ->distinct()
                                    ->orderByDesc('year')
                                    ->pluck('year')
                                    ->mapWithKeys(fn($year) => [(string) $year => "Năm {$year}"])
                                    ->toArray();

                                return $years;
                            })
                            ->default(function ($record) {
                                $savedYear = data_get($record?->settings, 'annual_quota_year');
                                if (!empty($savedYear)) {
                                    return (string) $savedYear;
                                }

                                return (string) ($record?->start_date?->format('Y') ?? now()->format('Y'));
                            })
                            ->afterStateHydrated(function ($component, $state, $record): void {
                                if (!empty($state)) {
                                    return;
                                }

                                $fallbackYear = (string) ($record?->start_date?->format('Y') ?? now()->format('Y'));
                                $component->state($fallbackYear);
                            })
                            ->live()
                            ->searchable()
                            ->preload()
                            ->placeholder('Chọn năm chỉ tiêu...')
                            ->helperText('Chọn năm để hiển thị danh sách chỉ tiêu năm tương ứng'),

                        Select::make('settings.annual_quota_major_name')
                            ->label('🎓 Ngành')
                            ->options(function ($get, $record) {
                                $year = $get('settings.annual_quota_year');

                                if (!$year) {
                                    return [];
                                }

                                return \Illuminate\Support\Facades\DB::table('annual_quotas')
                                    ->where('year', $year)
                                    ->whereNotNull('major_name')
                                    ->distinct()
                                    ->orderBy('major_name')
                                    ->pluck('major_name', 'major_name')
                                    ->toArray();
                            })
                            ->live()
                            ->default(function ($record) {
                                $savedMajor = data_get($record?->settings, 'annual_quota_major_name');
                                if (!empty($savedMajor)) {
                                    return $savedMajor;
                                }

                                return $record?->quotas()->orderByDesc('id')->value('major_name');
                            })
                            ->afterStateHydrated(function ($component, $state, $record): void {
                                if (!empty($state)) {
                                    return;
                                }

                                $fallbackMajor = $record?->quotas()->orderByDesc('id')->value('major_name');
                                if (!empty($fallbackMajor)) {
                                    $component->state($fallbackMajor);
                                }
                            })
                            ->searchable()
                            ->preload()
                            ->placeholder('Chọn ngành...')
                            ->helperText('Chọn ngành cần áp dụng cho đợt tuyển sinh'),

                        Select::make('settings.annual_quota_program_name')
                            ->label('🏫 Hệ đào tạo')
                            ->options(function ($get, $record) {
                                $year = $get('settings.annual_quota_year');
                                $major = $get('settings.annual_quota_major_name');

                                if (!$year) {
                                    return [];
                                }

                                $query = \Illuminate\Support\Facades\DB::table('annual_quotas')
                                    ->where('year', $year)
                                    ->whereNotNull('program_name');

                                if (!empty($major)) {
                                    $query->where('major_name', $major);
                                }

                                return $query->distinct()
                                    ->orderBy('program_name')
                                    ->pluck('program_name')
                                    ->mapWithKeys(fn($value) => [$value => self::getProgramLabel($value)])
                                    ->toArray();
                            })
                            ->live()
                            ->default(function ($record) {
                                $savedProgram = data_get($record?->settings, 'annual_quota_program_name');
                                if (!empty($savedProgram)) {
                                    return $savedProgram;
                                }

                                return $record?->quotas()->orderByDesc('id')->value('program_name');
                            })
                            ->afterStateHydrated(function ($component, $state, $record): void {
                                if (!empty($state)) {
                                    return;
                                }

                                $fallbackProgram = $record?->quotas()->orderByDesc('id')->value('program_name');
                                if (!empty($fallbackProgram)) {
                                    $component->state($fallbackProgram);
                                }
                            })
                            ->searchable()
                            ->preload()
                            ->placeholder('Chọn hệ đào tạo...')
                            ->helperText('Chọn hệ tương ứng để xem đúng chỉ tiêu'),

                        TextInput::make('settings.intake_target_quota')
                            ->label('🎯 Chỉ tiêu theo đợt')
                            ->numeric()
                            ->minValue(0)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, callable $set, callable $get, $record): void {
                                if (!is_numeric($state)) {
                                    return;
                                }

                                $year = (int) ($get('settings.annual_quota_year') ?? 0);
                                $major = $get('settings.annual_quota_major_name');
                                $program = $get('settings.annual_quota_program_name');

                                if (!$year || empty($major) || empty($program)) {
                                    return;
                                }

                                $annualTarget = (int) \App\Models\AnnualQuota::query()
                                    ->where('year', $year)
                                    ->where('major_name', $major)
                                    ->where('program_name', $program)
                                    ->value('target_quota');

                                if ($annualTarget <= 0) {
                                    $set('settings.intake_target_quota', 0);
                                    return;
                                }

                                $allocatedToOtherIntakes = \App\Models\Quota::query()
                                    ->when(
                                        !empty($record?->id),
                                        fn($query) => $query->where('intake_id', '!=', $record->id)
                                    )
                                    ->where('major_name', $major)
                                    ->where('program_name', $program)
                                    ->whereHas('intake', fn($query) => $query->whereYear('start_date', $year))
                                    ->sum('target_quota');

                                $remainingTarget = max(0, $annualTarget - (int) $allocatedToOtherIntakes);
                                $requested = max(0, (int) $state);

                                if ($requested > $remainingTarget) {
                                    $set('settings.intake_target_quota', $remainingTarget);

                                    Notification::make()
                                        ->title('Chỉ tiêu đợt vượt mức còn lại')
                                        ->body("Bạn nhập {$requested}, nhưng chỉ còn {$remainingTarget} theo chỉ tiêu năm. Hệ thống tự giới hạn về {$remainingTarget}.")
                                        ->warning()
                                        ->send();
                                }
                            })
                            ->placeholder('Nhập chỉ tiêu cho riêng đợt này')
                            ->helperText('Nếu để trống, hệ thống tự lấy phần chỉ tiêu còn lại của năm cho Ngành/Hệ đã chọn.'),


                        \Filament\Forms\Components\Placeholder::make('annual_quotas_info')
                            ->label('📋 Xem trước chỉ tiêu năm')
                            ->content(function ($get, $record) {
                                $selectedYear = $get('settings.annual_quota_year');
                                $selectedMajor = $get('settings.annual_quota_major_name');
                                $selectedProgram = $get('settings.annual_quota_program_name');

                                if (!$selectedYear) {
                                    return new \Illuminate\Support\HtmlString('<p class="text-gray-500 text-sm">Vui lòng chọn năm chỉ tiêu để xem dữ liệu.</p>');
                                }

                                if (!$selectedMajor || !$selectedProgram) {
                                    return new \Illuminate\Support\HtmlString('<p class="text-gray-500 text-sm">Chọn đầy đủ Ngành và Hệ đào tạo để xem chỉ tiêu cụ thể.</p>');
                                }

                                $year = (string) $selectedYear;
                                $query = \Illuminate\Support\Facades\DB::table('annual_quotas')
                                    ->where('year', $year)
                                    ->where('major_name', $selectedMajor)
                                    ->where('program_name', $selectedProgram);
                                
                                $quotas = $query->select(
                                        'annual_quotas.id',
                                        'annual_quotas.major_name',
                                        'annual_quotas.program_name',
                                        'annual_quotas.target_quota',
                                        'annual_quotas.current_quota',
                                        'annual_quotas.status'
                                    )
                                    ->get();

                                if ($quotas->isEmpty()) {
                                    return new \Illuminate\Support\HtmlString("
                                        <div class='p-4 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg border border-yellow-200 dark:border-yellow-800'>
                                            <p class='text-yellow-700 dark:text-yellow-300 font-medium text-sm'>⚠️ Chưa có chỉ tiêu cho {$selectedMajor} - " . self::getProgramLabel($selectedProgram) . " năm {$year}.</p>
                                            <p class='text-yellow-600 dark:text-yellow-400 text-xs mt-1'>Vui lòng chọn tổ hợp Ngành/Hệ khác hoặc tạo mới trong mục Chỉ tiêu năm.</p>
                                        </div>
                                    ");
                                }

                                $html = "<div class='space-y-2'>";
                                $totalTarget = 0;
                                $totalCurrent = 0;

                                foreach ($quotas as $q) {
                                    $programLabel = self::getProgramLabel($q->program_name);
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
                                                    <span class='font-semibold text-gray-900 dark:text-white text-sm'>{$q->major_name}</span>
                                                    <span class='text-xs text-gray-500 dark:text-gray-400 ml-1'>({$programLabel})</span>
                                                </div>
                                                <span class='px-2 py-1 text-xs rounded-full bg-{$statusColor}-100 text-{$statusColor}-700 dark:bg-{$statusColor}-900/30 dark:text-{$statusColor}-400'>
                                                    " . ($q->status === 'active' ? 'Đang tuyển' : ($q->status === 'full' ? 'Đã đủ' : 'Tạm dừng')) . "
                                                </span>
                                            </div>
                                            <div class='mt-2'>
                                                <div class='flex justify-between text-xs text-gray-600 dark:text-gray-300 mb-1'>
                                                    <span>Đã tuyển: {$q->current_quota}/{$q->target_quota}</span>
                                                    <span>Còn lại: <strong class='text-{$progressColor}-600'>{$remaining}</strong></span>
                                                </div>
                                                <div class='w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1.5'>
                                                    <div class='bg-{$progressColor}-500 h-1.5 rounded-full' style='width: {$percent}%'></div>
                                                </div>
                                            </div>
                                        </div>
                                    ";
                                }

                                $totalPercent = $totalTarget > 0 ? round(($totalCurrent / $totalTarget) * 100, 1) : 0;
                                $url = route('filament.admin.resources.annual-quotas.index') . "?tableFilters[year][value]={$year}";

                                $programLabel = self::getProgramLabel($selectedProgram);
                                $html .= "
                                    <div class='mt-3 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800'>
                                        <div class='flex justify-between items-center'>
                                            <span class='font-semibold text-blue-700 dark:text-blue-300 text-sm'>📊 {$selectedMajor} - {$programLabel} ({$year})</span>
                                            <span class='text-blue-600 dark:text-blue-400 text-sm'>{$totalCurrent}/{$totalTarget} ({$totalPercent}%)</span>
                                        </div>
                                        <div class='mt-2 text-xs'>
                                            <a href='{$url}' target='_blank' class='text-blue-600 hover:underline'>→ Quản lý chỉ tiêu năm {$year}</a>
                                        </div>
                                    </div>
                                </div>";

                                return new \Illuminate\Support\HtmlString($html);
                            })
                            ->columnSpanFull()
                            ->visible(fn($get) => $get('settings.annual_quota_year')),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->collapsed(false),

            ]);
    }
}
