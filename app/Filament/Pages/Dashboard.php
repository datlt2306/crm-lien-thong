<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use App\Filament\Widgets\AdminKpiStats;
use App\Filament\Widgets\RevenueOverTime;
use App\Filament\Widgets\RecentPayments;
use Illuminate\Support\Facades\Auth;

class Dashboard extends BaseDashboard {




    public function getTitle(): string {
        return 'Dashboard';
    }

    protected function getHeaderWidgets(): array {
        $user = Auth::user();
        if ($user?->hasRole('admin')) {
            return [AdminKpiStats::class];
        }
        if ($user?->hasRole('ctv')) {
            return [AdminKpiStats::class];
        }
        if ($user?->hasRole('kế toán')) {
            return [AdminKpiStats::class];
        }
        return [AdminKpiStats::class];
    }

    public function getWidgets(): array {
        $user = Auth::user();
        if ($user?->hasRole('admin')) {
            return [RevenueOverTime::class];
        }
        if ($user?->hasRole('ctv')) {
            return [];
        }
        if ($user?->hasRole('kế toán')) {
            return [];
        }
        return [];
    }

    protected function getFooterWidgets(): array {
        $user = Auth::user();
        if ($user?->hasRole('admin')) {
            return [RecentPayments::class];
        }
        if ($user?->hasRole('ctv')) {
            return [RecentPayments::class];
        }
        if ($user?->hasRole('kế toán')) {
            return [RecentPayments::class];
        }
        return [RecentPayments::class];
    }
}
