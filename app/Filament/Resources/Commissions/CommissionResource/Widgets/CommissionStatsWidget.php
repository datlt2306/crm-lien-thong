<?php

namespace App\Filament\Resources\Commissions\CommissionResource\Widgets;

use App\Models\CommissionItem;
use App\Models\Collaborator;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class CommissionStatsWidget extends BaseWidget {
    protected function getStats(): array {
        $user = Auth::user();
        if (!$user) return [];

        $query = CommissionItem::query()
            ->where('commission_items.status', CommissionItem::STATUS_PAYABLE);

        // Nếu là CTV, chỉ tính tiền của chính mình
        if ($user->role === 'ctv') {
            $collaborator = Collaborator::where('email', $user->email)->first();
            if ($collaborator) {
                $query->where('recipient_collaborator_id', $collaborator->id);
            } else {
                $query->whereNull('id');
            }
        }

        $totalAmount = (float) $query->sum('amount');
        $studentCount = $query->count();

        // Thống kê theo Hệ đào tạo
        $programStats = $query->clone()
            ->selectRaw("
                CASE 
                    WHEN UPPER(COALESCE(meta->>'program_type', '')) = 'REGULAR' THEN 'Chính quy'
                    WHEN UPPER(COALESCE(meta->>'program_type', '')) = 'PART_TIME' THEN 'VHVL'
                    WHEN UPPER(COALESCE(meta->>'program_type', '')) = 'DISTANCE' THEN 'Từ xa'
                    ELSE 'Khác'
                END as label,
                count(*) as count
            ")
            ->groupBy('label')
            ->pluck('count', 'label')
            ->toArray();

        $programDescription = [];
        foreach ($programStats as $label => $count) {
            $programDescription[] = "{$label}: {$count}";
        }

        // Thống kê theo Ngành học (lấy từ Student qua Commission)
        $majorStats = $query->clone()
            ->join('commissions', 'commission_items.commission_id', '=', 'commissions.id')
            ->join('students', 'commissions.student_id', '=', 'students.id')
            ->selectRaw('students.major as label, count(*) as count')
            ->groupBy('students.major')
            ->orderByDesc('count')
            ->take(3) // Chỉ lấy 3 ngành nhiều nhất để tránh quá tải UI
            ->pluck('count', 'label')
            ->toArray();

        $majorDescription = [];
        foreach ($majorStats as $label => $count) {
            $majorDescription[] = "{$label}: {$count}";
        }

        return [
            Stat::make('Tổng tiền có thể nhận', number_format($totalAmount, 0, ',', '.') . ' VNĐ')
                ->description('Tổng hoa hồng đang chờ thanh toán')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),

            Stat::make('Số hồ sơ đủ điều kiện', "{$studentCount} học viên")
                ->description($programDescription ? implode(' | ', $programDescription) : 'Chưa có hồ sơ')
                ->descriptionIcon('heroicon-m-academic-cap')
                ->color('info'),

            Stat::make('Ngành học tiêu biểu', $majorStats ? array_key_first($majorStats) : 'N/A')
                ->description($majorDescription ? implode(' | ', $majorDescription) : 'Không có dữ liệu ngành')
                ->descriptionIcon('heroicon-m-tag')
                ->color('warning'),
        ];
    }
}
