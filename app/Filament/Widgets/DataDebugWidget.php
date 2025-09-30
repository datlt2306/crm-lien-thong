<?php

namespace App\Filament\Widgets;

use App\Models\Payment;
use App\Models\Student;
use App\Models\CommissionItem;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class DataDebugWidget extends BaseWidget {
    protected ?string $heading = 'Debug - Dữ liệu thực tế';
    protected int|string|array $columnSpan = 'full';

    protected function getCards(): array {
        // Kiểm tra dữ liệu payments
        $totalPayments = Payment::count();
        $verifiedPayments = Payment::where('status', 'verified')->count();
        $pendingPayments = Payment::where('status', 'submitted')->count();
        
        // Kiểm tra payments có verified_at
        $paymentsWithVerifiedAt = Payment::whereNotNull('verified_at')->count();
        
        // Kiểm tra payments có primary_collaborator_id
        $paymentsWithCollaborator = Payment::whereNotNull('primary_collaborator_id')->count();
        
        // Kiểm tra dữ liệu mẫu
        $samplePayments = Payment::where('status', 'verified')
            ->whereNotNull('verified_at')
            ->whereNotNull('primary_collaborator_id')
            ->limit(3)
            ->get(['id', 'amount', 'verified_at', 'primary_collaborator_id', 'status']);

        return [
            Stat::make('Tổng Payments', (string) $totalPayments)->color('info'),
            Stat::make('Payments đã xác minh', (string) $verifiedPayments)->color('success'),
            Stat::make('Payments chờ xác minh', (string) $pendingPayments)->color('warning'),
            Stat::make('Có verified_at', (string) $paymentsWithVerifiedAt)->color('primary'),
            Stat::make('Có collaborator', (string) $paymentsWithCollaborator)->color('secondary'),
            Stat::make('Mẫu dữ liệu', $samplePayments->count() > 0 ? 'Có' : 'Không')->color('gray'),
        ];
    }
}
