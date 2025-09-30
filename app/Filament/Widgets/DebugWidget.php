<?php

namespace App\Filament\Widgets;

use App\Models\Payment;
use App\Models\Student;
use App\Models\CommissionItem;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class DebugWidget extends BaseWidget {
    protected ?string $heading = 'Debug - Kiểm tra dữ liệu';
    protected int|string|array $columnSpan = 'full';

    protected function getCards(): array {
        $user = Auth::user();
        $role = $user?->role;

        // Kiểm tra dữ liệu cơ bản
        $totalPayments = Payment::count();
        $verifiedPayments = Payment::where('status', 'verified')->count();
        $totalStudents = Student::count();
        $totalCommissions = CommissionItem::count();

        // Kiểm tra dữ liệu theo role
        $userSpecificData = [];
        if ($role === 'ctv') {
            $userId = $user->id;
            $userPayments = Payment::where('primary_collaborator_id', $userId)->count();
            $userStudents = Student::whereHas('payments', function ($query) use ($userId) {
                $query->where('primary_collaborator_id', $userId);
            })->count();
            $userSpecificData = [
                'user_payments' => $userPayments,
                'user_students' => $userStudents,
            ];
        }

        return [
            Stat::make('Tổng Payments', (string) $totalPayments)->color('info'),
            Stat::make('Payments đã xác minh', (string) $verifiedPayments)->color('success'),
            Stat::make('Tổng Students', (string) $totalStudents)->color('warning'),
            Stat::make('Tổng Commissions', (string) $totalCommissions)->color('gray'),
            Stat::make('Role hiện tại', $role ?? 'N/A')->color('primary'),
            Stat::make('User ID', (string) ($user->id ?? 'N/A'))->color('secondary'),
        ];
    }
}
