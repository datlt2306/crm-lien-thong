<?php

namespace App\Filament\Pages\Reports;

use App\Models\Payment;
use App\Models\CommissionItem;
use App\Models\Student;
use App\Models\Collaborator;
use App\Services\DashboardCacheService;
use Carbon\CarbonImmutable;
use Filament\Pages\Page;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Storage;

class AdvancedReports extends Page implements HasForms {
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';
    protected static string $view = 'filament.pages.reports.advanced-reports';
    protected static ?string $title = 'Báo cáo nâng cao';
    protected static ?string $navigationLabel = 'Báo cáo nâng cao';
    protected static ?string $navigationGroup = 'Báo cáo';

    public ?array $data = [];

    public function mount(): void {
        $this->form->fill([
            'date_range' => 'last_30_days',
            'from_date' => null,
            'to_date' => null,
            'report_type' => 'revenue',
            'format' => 'excel',
            'group_by' => 'day',
            'include_comparison' => false,
        ]);
    }

    public function form(Form $form): Form {
        return $form
            ->schema([
                Section::make('Thiết lập báo cáo')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('date_range')
                                    ->label('Khoảng thời gian')
                                    ->options([
                                        'today' => 'Hôm nay',
                                        'last_7_days' => '7 ngày qua',
                                        'last_30_days' => '30 ngày qua',
                                        'this_month' => 'Tháng này',
                                        'last_month' => 'Tháng trước',
                                        'this_year' => 'Năm nay',
                                        'custom' => 'Tùy chỉnh',
                                    ])
                                    ->reactive()
                                    ->required(),

                                Select::make('report_type')
                                    ->label('Loại báo cáo')
                                    ->options([
                                        'revenue' => 'Báo cáo doanh thu',
                                        'commission' => 'Báo cáo hoa hồng',
                                        'students' => 'Báo cáo học viên',
                                        'collaborators' => 'Báo cáo CTV',
                                        'financial' => 'Báo cáo tài chính tổng hợp',
                                    ])
                                    ->required(),

                                DatePicker::make('from_date')
                                    ->label('Từ ngày')
                                    ->visible(fn (callable $get) => $get('date_range') === 'custom')
                                    ->required(fn (callable $get) => $get('date_range') === 'custom'),

                                DatePicker::make('to_date')
                                    ->label('Đến ngày')
                                    ->visible(fn (callable $get) => $get('date_range') === 'custom')
                                    ->required(fn (callable $get) => $get('date_range') === 'custom'),

                                Select::make('group_by')
                                    ->label('Nhóm theo')
                                    ->options([
                                        'day' => 'Theo ngày',
                                        'week' => 'Theo tuần',
                                        'month' => 'Theo tháng',
                                        'year' => 'Theo năm',
                                    ])
                                    ->required(),

                                Select::make('format')
                                    ->label('Định dạng xuất')
                                    ->options([
                                        'excel' => 'Excel (.xlsx)',
                                        'pdf' => 'PDF (.pdf)',
                                        'csv' => 'CSV (.csv)',
                                    ])
                                    ->required(),

                                Select::make('include_comparison')
                                    ->label('Bao gồm so sánh')
                                    ->boolean()
                                    ->default(false),
                            ]),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    protected function getHeaderActions(): array {
        return [
            Action::make('generate_report')
                ->label('Tạo báo cáo')
                ->icon('heroicon-o-document-arrow-down')
                ->color('primary')
                ->action('generateReport'),
        ];
    }

    public function generateReport(): void {
        $data = $this->form->getState();
        
        try {
            $reportData = $this->prepareReportData($data);
            
            switch ($data['format']) {
                case 'excel':
                    $this->generateExcelReport($reportData, $data);
                    break;
                case 'pdf':
                    $this->generatePdfReport($reportData, $data);
                    break;
                case 'csv':
                    $this->generateCsvReport($reportData, $data);
                    break;
            }
            
            Notification::make()
                ->title('Báo cáo đã được tạo thành công')
                ->success()
                ->send();
                
        } catch (\Exception $e) {
            Notification::make()
                ->title('Lỗi khi tạo báo cáo')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function prepareReportData(array $data): array {
        [$from, $to] = $this->getDateRange($data);
        
        switch ($data['report_type']) {
            case 'revenue':
                return $this->getRevenueData($from, $to, $data['group_by']);
            case 'commission':
                return $this->getCommissionData($from, $to, $data['group_by']);
            case 'students':
                return $this->getStudentsData($from, $to, $data['group_by']);
            case 'collaborators':
                return $this->getCollaboratorsData($from, $to, $data['group_by']);
            case 'financial':
                return $this->getFinancialData($from, $to, $data['group_by']);
            default:
                return [];
        }
    }

    protected function getDateRange(array $data): array {
        $tz = DashboardCacheService::getTimezone();
        $now = CarbonImmutable::now($tz);
        
        switch ($data['date_range']) {
            case 'today':
                return [$now->startOfDay(), $now->endOfDay()];
            case 'last_7_days':
                return [$now->subDays(6)->startOfDay(), $now->endOfDay()];
            case 'last_30_days':
                return [$now->subDays(29)->startOfDay(), $now->endOfDay()];
            case 'this_month':
                return [$now->startOfMonth(), $now->endOfMonth()];
            case 'last_month':
                return [$now->subMonth()->startOfMonth(), $now->subMonth()->endOfMonth()];
            case 'this_year':
                return [$now->startOfYear(), $now->endOfYear()];
            case 'custom':
                $from = $data['from_date'] ? CarbonImmutable::parse($data['from_date'], $tz)->startOfDay() : $now->subDays(29)->startOfDay();
                $to = $data['to_date'] ? CarbonImmutable::parse($data['to_date'], $tz)->endOfDay() : $now->endOfDay();
                return [$from, $to];
            default:
                return [$now->subDays(29)->startOfDay(), $now->endOfDay()];
        }
    }

    protected function getRevenueData($from, $to, $groupBy): array {
        $data = [];
        $current = $from->copy();
        
        while ($current->lte($to)) {
            $start = $current->copy();
            $end = $current->copy();
            
            switch ($groupBy) {
                case 'day':
                    $end->endOfDay();
                    $label = $current->format('d/m/Y');
                    break;
                case 'week':
                    $end->endOfWeek();
                    $label = 'Tuần ' . $current->week . ' - ' . $current->year;
                    break;
                case 'month':
                    $end->endOfMonth();
                    $label = $current->format('m/Y');
                    break;
                case 'year':
                    $end->endOfYear();
                    $label = $current->year;
                    break;
            }
            
            $revenue = Payment::where('status', 'verified')
                ->whereBetween('created_at', [$start, $end])
                ->sum('amount');
                
            $data[] = [
                'period' => $label,
                'revenue' => $revenue,
                'count' => Payment::where('status', 'verified')
                    ->whereBetween('created_at', [$start, $end])
                    ->count(),
            ];
            
            switch ($groupBy) {
                case 'day':
                    $current->addDay();
                    break;
                case 'week':
                    $current->addWeek();
                    break;
                case 'month':
                    $current->addMonth();
                    break;
                case 'year':
                    $current->addYear();
                    break;
            }
        }
        
        return $data;
    }

    protected function getCommissionData($from, $to, $groupBy): array {
        $data = [];
        $current = $from->copy();
        
        while ($current->lte($to)) {
            $start = $current->copy();
            $end = $current->copy();
            
            switch ($groupBy) {
                case 'day':
                    $end->endOfDay();
                    $label = $current->format('d/m/Y');
                    break;
                case 'week':
                    $end->endOfWeek();
                    $label = 'Tuần ' . $current->week . ' - ' . $current->year;
                    break;
                case 'month':
                    $end->endOfMonth();
                    $label = $current->format('m/Y');
                    break;
                case 'year':
                    $end->endOfYear();
                    $label = $current->year;
                    break;
            }
            
            $commission = CommissionItem::where('status', 'paid')
                ->whereBetween('updated_at', [$start, $end])
                ->sum('amount');
                
            $data[] = [
                'period' => $label,
                'commission' => $commission,
                'count' => CommissionItem::where('status', 'paid')
                    ->whereBetween('updated_at', [$start, $end])
                    ->count(),
            ];
            
            switch ($groupBy) {
                case 'day':
                    $current->addDay();
                    break;
                case 'week':
                    $current->addWeek();
                    break;
                case 'month':
                    $current->addMonth();
                    break;
                case 'year':
                    $current->addYear();
                    break;
            }
        }
        
        return $data;
    }

    protected function getStudentsData($from, $to, $groupBy): array {
        $data = [];
        $current = $from->copy();
        
        while ($current->lte($to)) {
            $start = $current->copy();
            $end = $current->copy();
            
            switch ($groupBy) {
                case 'day':
                    $end->endOfDay();
                    $label = $current->format('d/m/Y');
                    break;
                case 'week':
                    $end->endOfWeek();
                    $label = 'Tuần ' . $current->week . ' - ' . $current->year;
                    break;
                case 'month':
                    $end->endOfMonth();
                    $label = $current->format('m/Y');
                    break;
                case 'year':
                    $end->endOfYear();
                    $label = $current->year;
                    break;
            }
            
            $newStudents = Student::whereBetween('created_at', [$start, $end])->count();
            $paidStudents = Student::whereHas('payments', function ($query) use ($start, $end) {
                $query->where('status', 'verified')
                      ->whereBetween('created_at', [$start, $end]);
            })->count();
                
            $data[] = [
                'period' => $label,
                'new_students' => $newStudents,
                'paid_students' => $paidStudents,
                'conversion_rate' => $newStudents > 0 ? round(($paidStudents / $newStudents) * 100, 2) : 0,
            ];
            
            switch ($groupBy) {
                case 'day':
                    $current->addDay();
                    break;
                case 'week':
                    $current->addWeek();
                    break;
                case 'month':
                    $current->addMonth();
                    break;
                case 'year':
                    $current->addYear();
                    break;
            }
        }
        
        return $data;
    }

    protected function getCollaboratorsData($from, $to, $groupBy): array {
        $collaborators = Collaborator::with(['user', 'commissionItems' => function ($query) use ($from, $to) {
            $query->where('status', 'paid')
                  ->whereBetween('updated_at', [$from, $to]);
        }])->get();
        
        $data = [];
        foreach ($collaborators as $collaborator) {
            $totalCommission = $collaborator->commissionItems->sum('amount');
            $studentCount = Student::whereHas('payments', function ($query) use ($collaborator) {
                $query->where('collaborator_id', $collaborator->user_id);
            })->count();
            
            $data[] = [
                'name' => $collaborator->user->name,
                'email' => $collaborator->user->email,
                'total_commission' => $totalCommission,
                'student_count' => $studentCount,
                'avg_commission_per_student' => $studentCount > 0 ? round($totalCommission / $studentCount, 2) : 0,
            ];
        }
        
        return $data;
    }

    protected function getFinancialData($from, $to, $groupBy): array {
        $data = [];
        $current = $from->copy();
        
        while ($current->lte($to)) {
            $start = $current->copy();
            $end = $current->copy();
            
            switch ($groupBy) {
                case 'day':
                    $end->endOfDay();
                    $label = $current->format('d/m/Y');
                    break;
                case 'week':
                    $end->endOfWeek();
                    $label = 'Tuần ' . $current->week . ' - ' . $current->year;
                    break;
                case 'month':
                    $end->endOfMonth();
                    $label = $current->format('m/Y');
                    break;
                case 'year':
                    $end->endOfYear();
                    $label = $current->year;
                    break;
            }
            
            $revenue = Payment::where('status', 'verified')
                ->whereBetween('created_at', [$start, $end])
                ->sum('amount');
                
            $commission = CommissionItem::where('status', 'paid')
                ->whereBetween('updated_at', [$start, $end])
                ->sum('amount');
                
            $data[] = [
                'period' => $label,
                'revenue' => $revenue,
                'commission' => $commission,
                'net_profit' => $revenue - $commission,
                'commission_rate' => $revenue > 0 ? round(($commission / $revenue) * 100, 2) : 0,
            ];
            
            switch ($groupBy) {
                case 'day':
                    $current->addDay();
                    break;
                case 'week':
                    $current->addWeek();
                    break;
                case 'month':
                    $current->addMonth();
                    break;
                case 'year':
                    $current->addYear();
                    break;
            }
        }
        
        return $data;
    }

    protected function generateExcelReport(array $data, array $config): void {
        // Implementation for Excel export
        $filename = 'bao_cao_' . $config['report_type'] . '_' . now()->format('Y_m_d_H_i_s') . '.xlsx';
        
        // This would need a proper Excel export class
        // For now, we'll create a simple CSV-like structure
        $this->generateCsvReport($data, $config);
    }

    protected function generatePdfReport(array $data, array $config): void {
        $filename = 'bao_cao_' . $config['report_type'] . '_' . now()->format('Y_m_d_H_i_s') . '.pdf';
        
        // Simple PDF generation without external library
        $html = view('reports.pdf-template', [
            'data' => $data,
            'config' => $config,
            'title' => 'Báo cáo ' . $this->getReportTitle($config['report_type']),
        ])->render();
        
        // For now, save as HTML file
        Storage::disk('public')->put('reports/' . str_replace('.pdf', '.html', $filename), $html);
        
        Notification::make()
            ->title('Báo cáo đã được tạo')
            ->body('File: ' . str_replace('.pdf', '.html', $filename))
            ->success()
            ->send();
    }

    protected function generateCsvReport(array $data, array $config): void {
        $filename = 'bao_cao_' . $config['report_type'] . '_' . now()->format('Y_m_d_H_i_s') . '.csv';
        
        // Create CSV content
        $csvContent = $this->arrayToCsv($data);
        
        Storage::disk('public')->put('reports/' . $filename, $csvContent);
        
        Notification::make()
            ->title('Báo cáo CSV đã được tạo')
            ->body('File: ' . $filename)
            ->success()
            ->send();
    }

    protected function arrayToCsv(array $data): string {
        if (empty($data)) {
            return '';
        }
        
        $csv = '';
        $headers = array_keys($data[0]);
        $csv .= implode(',', $headers) . "\n";
        
        foreach ($data as $row) {
            $csv .= implode(',', array_values($row)) . "\n";
        }
        
        return $csv;
    }

    protected function getReportTitle(string $type): string {
        return match ($type) {
            'revenue' => 'Doanh thu',
            'commission' => 'Hoa hồng',
            'students' => 'Học viên',
            'collaborators' => 'Cộng tác viên',
            'financial' => 'Tài chính tổng hợp',
            default => 'Báo cáo',
        };
    }
}
