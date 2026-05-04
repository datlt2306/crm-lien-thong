<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;
use Carbon\CarbonImmutable;

class DashboardWelcomeWidget extends Widget {
    protected string $view = 'filament.widgets.dashboard-welcome';
    protected int|string|array $columnSpan = 'full';

    public function getGreeting(): string {
        $hour = CarbonImmutable::now('Asia/Ho_Chi_Minh')->hour;

        return match (true) {
            $hour < 12 => 'Chào buổi sáng',
            $hour < 17 => 'Chào buổi chiều',
            default => 'Chào buổi tối',
        };
    }

    public function getUserName(): string {
        return Auth::user()?->name ?? 'Người dùng';
    }

    public function getUserRoleLabel(): string {
        $user = Auth::user();

        return match (true) {
            $user?->can('report_view_all') => 'Quản trị viên',
            $user?->role === 'collaborator' => 'Cộng tác viên',
            $user?->can('report_view_finance') => 'Kế toán',
            default => 'Người dùng',
        };
    }

    public function getQuickStats(): array {
        $user = Auth::user();
        $stats = [];

        if ($user?->can('report_view_all') || $user?->can('report_view_finance')) {
            $stats[] = [
                'label' => 'Payments hôm nay',
                'value' => \App\Models\Payment::whereDate('created_at', today())->count(),
                'icon' => 'heroicon-o-credit-card',
                'color' => 'amber',
            ];
            $stats[] = [
                'label' => 'Hồ sơ mới',
                'value' => \App\Models\Student::whereDate('created_at', today())->count(),
                'icon' => 'heroicon-o-academic-cap',
                'color' => 'blue',
            ];
        }

        if ($user?->role === 'collaborator') {
            $collaborator = \App\Models\Collaborator::where('email', $user->email)->first();
            if ($collaborator) {
                $stats[] = [
                    'label' => 'Hoa hồng tháng này',
                    'value' => number_format(
                        \App\Models\CommissionItem::where('collaborator_id', $collaborator->id)
                            ->whereMonth('created_at', now()->month)
                            ->sum('amount')
                    ) . ' ₫',
                    'icon' => 'heroicon-o-banknotes',
                    'color' => 'green',
                ];
                $stats[] = [
                    'label' => 'Hồ sơ của tôi',
                    'value' => \App\Models\Student::where('collaborator_id', $collaborator->id)->count(),
                    'icon' => 'heroicon-o-user-group',
                    'color' => 'purple',
                ];
            }
        }

        return $stats;
    }
}
