<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class OptimizedDashboardLoader extends Widget {
    protected string $view = 'filament.widgets.optimized-dashboard-loader';
    protected int|string|array $columnSpan = 'full';
    protected ?string $pollingInterval = null; // Không polling

    public function mount(): void {
        // Preload các data cần thiết
        $this->preloadDashboardData();
    }

    protected function preloadDashboardData(): void {
        $user = Auth::user();
        $role = $user?->role;

        // Preload cache cho các widget chính
        $cacheKeys = [];

        if (in_array($role, ['super_admin', 'admin'])) {
            $cacheKeys = [
                'admin:kpi',
                'admin:chart',
                'admin:collab_revenue',
                'kpi:comparison'
            ];
        } elseif ($role === 'ctv') {
            $cacheKeys = [
                "ctv:personal:{$user->id}",
                "ctv:wallet:{$user->id}",
                "ctv:students:{$user->id}"
            ];
        } elseif ($role === 'accountant') {
            $cacheKeys = [
                'accountant:pending_receipts',
                'accountant:financial_summary',
                'accountant:cash_flow'
            ];
        }

        // Warm up cache
        foreach ($cacheKeys as $key) {
            if (!cache()->has($key)) {
                // Trigger cache generation
                $this->warmupCache($key, $role, $user->id ?? 0);
            }
        }
    }

    protected function warmupCache(string $key, string $role, int $userId): void {
        // Logic để warm up cache cho từng loại widget
        // Điều này sẽ giúp giảm thời gian load lần đầu
    }
}
