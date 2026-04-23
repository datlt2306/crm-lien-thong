<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use App\Filament\Widgets\AdminKpiStats;
use App\Filament\Widgets\RevenueOverTime;
use App\Filament\Widgets\RecentPayments;
use App\Filament\Widgets\CollaboratorRevenueChart;
use App\Filament\Widgets\CtvUnifiedStats;
use App\Filament\Widgets\AccountantPendingReceipts;
use App\Filament\Widgets\AccountantCashFlow;
use App\Filament\Widgets\AccountantFinancialSummary;
use App\Filament\Widgets\SimpleAccountantStats;
use App\Filament\Widgets\SimplePaymentsTable;
use App\Filament\Widgets\KpiComparisonWidget;
use App\Filament\Widgets\OptimizedDashboardLoader;
use App\Filament\Widgets\SimpleRevenueChart;
use App\Filament\Widgets\SimpleCollaboratorChart;
use App\Filament\Widgets\CollaboratorStatsWidget;
use Illuminate\Support\Facades\Auth;

class Dashboard extends BaseDashboard {




    public function getTitle(): string {
        return 'Dashboard';
    }

    protected function getHeaderWidgets(): array {
        return [
            \App\Filament\Widgets\DashboardFiltersWidget::class,
        ];
    }

    public function getWidgets(): array {
        $user = Auth::user();
        $role = $user?->role;

        if ($user->can('report_view_all')) {
            return [
                \App\Filament\Widgets\FinanceKpiStats::class,
                \App\Filament\Widgets\MajorDistributionChart::class,
                RevenueOverTime::class,
                CollaboratorRevenueChart::class,
                RecentPayments::class,
            ];
        }

        if ($role === 'ctv') {
            return [
                CtvUnifiedStats::class,
                RevenueOverTime::class,
                RecentPayments::class,
            ];
        }

        if ($user->can('report_view_finance')) {
            return [
                \App\Filament\Widgets\FinanceKpiStats::class,
                \App\Filament\Widgets\MajorDistributionChart::class,
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
