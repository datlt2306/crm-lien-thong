<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use App\Filament\Widgets\DashboardWelcomeWidget;
use App\Filament\Widgets\RevenueOverTime;
use App\Filament\Widgets\RecentPayments;
use App\Filament\Widgets\CollaboratorRevenueChart;
use App\Filament\Widgets\CtvUnifiedStats;
use App\Filament\Widgets\SimplePaymentsTable;
use Illuminate\Support\Facades\Auth;

class Dashboard extends BaseDashboard {
    public function getTitle(): string {
        return 'Tổng quan';
    }

    protected function getHeaderWidgets(): array {
        return [
            DashboardWelcomeWidget::class,
        ];
    }

    /**
     * @return int|array<string, int>
     */
    public function getColumns(): int|array {
        return [
            'default' => 1,
            'sm' => 2,
            'lg' => 3,
            'xl' => 4,
        ];
    }

    public function getWidgets(): array {
        $user = Auth::user();
        $role = $user?->role;

        if ($user->can('report_view_all')) {
            return [
                \App\Filament\Widgets\FinanceKpiStats::class,
                \App\Filament\Widgets\StudentDemographicsChart::class,
                \App\Filament\Widgets\IntakeRecruitmentChart::class,
                RevenueOverTime::class,
                CollaboratorRevenueChart::class,
                RecentPayments::class,
            ];
        }

        if ($role === 'collaborator') {
            return [
                CtvUnifiedStats::class,
                RevenueOverTime::class,
                RecentPayments::class,
            ];
        }

        if ($user->can('report_view_finance')) {
            return [
                \App\Filament\Widgets\FinanceKpiStats::class,
                \App\Filament\Widgets\StudentDemographicsChart::class,
                \App\Filament\Widgets\IntakeRecruitmentChart::class,
                RevenueOverTime::class,
                SimplePaymentsTable::class,
            ];
        }

        return [
            \App\Filament\Widgets\FinanceKpiStats::class,
            RevenueOverTime::class,
            RecentPayments::class,
        ];
    }

    protected function getFooterWidgets(): array {
        return [];
    }
}
