<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use App\Filament\Widgets\AdminKpiStats;
use App\Filament\Widgets\RevenueOverTime;
use App\Filament\Widgets\RecentPayments;
use App\Filament\Widgets\CollaboratorRevenueChart;
use App\Filament\Widgets\CtvPersonalStats;
use App\Filament\Widgets\CtvWalletWidget;
use App\Filament\Widgets\CtvStudentsWidget;
use App\Filament\Widgets\AccountantPendingReceipts;
use App\Filament\Widgets\AccountantCashFlow;
use App\Filament\Widgets\AccountantFinancialSummary;
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
        return [];
    }

    public function getWidgets(): array {
        $user = Auth::user();
        $role = $user?->role;

        if (in_array($role, ['super_admin', 'admin'])) {
            return [
                AdminKpiStats::class,
                CollaboratorStatsWidget::class,
                SimpleRevenueChart::class,
                SimpleCollaboratorChart::class,
                RecentPayments::class,
            ];
        }

        if ($role === 'ctv') {
            return [
                CtvPersonalStats::class,
                CtvWalletWidget::class,
                CtvStudentsWidget::class,
                RecentPayments::class,
            ];
        }

        if ($role === 'accountant') {
            return [
                AccountantPendingReceipts::class,
                AccountantFinancialSummary::class,
                AccountantCashFlow::class,
                RecentPayments::class,
            ];
        }

        return [
            AdminKpiStats::class,
            RecentPayments::class,
        ];
    }

    protected function getFooterWidgets(): array {
        return [];
    }
}
