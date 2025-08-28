<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard {




    public function getTitle(): string {
        return 'Dashboard';
    }

    protected function getHeaderWidgets(): array {
        return [
            \App\Filament\Widgets\CommissionOverviewWidget::class,
        ];
    }

    protected function getFooterWidgets(): array {
        return [
            \App\Filament\Widgets\CommissionChartWidget::class,
            \App\Filament\Widgets\PaymentChartWidget::class,
            \App\Filament\Widgets\StudentChartWidget::class,
            \App\Filament\Widgets\WalletTransactionChartWidget::class,
        ];
    }
}
