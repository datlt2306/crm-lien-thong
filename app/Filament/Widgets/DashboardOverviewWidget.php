<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class DashboardOverviewWidget extends Widget {




    protected int | string | array $columnSpan = 'full';

    public function getViewData(): array {
        return [
            'commissionChart' => CommissionChartWidget::class,
            'paymentChart' => PaymentChartWidget::class,
            'studentChart' => StudentChartWidget::class,
            'walletTransactionChart' => WalletTransactionChartWidget::class,
        ];
    }
}
