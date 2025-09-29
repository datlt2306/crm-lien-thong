<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use App\Filament\Widgets\AdminKpiStats;
use App\Filament\Widgets\RevenueOverTime;
use App\Filament\Widgets\RecentPayments;
use App\Filament\Widgets\CollaboratorRevenueChart;
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
                RevenueOverTime::class,
                CollaboratorRevenueChart::class,
                RecentPayments::class,
            ];
        }

        if ($role === 'ctv') {
            return [
                AdminKpiStats::class,
                RecentPayments::class,
            ];
        }

        if ($role === 'kế toán') {
            return [
                AdminKpiStats::class,
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
