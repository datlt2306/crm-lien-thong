<div class="dashboard-welcome-banner">
    <!-- Greeting Section -->
    <div class="dashboard-welcome-header">
        <div class="dashboard-welcome-greeting">
            <h1 class="dashboard-welcome-title">
                {{ $this->getGreeting() }},
                <span class="dashboard-welcome-name">{{ $this->getUserName() }}</span>
            </h1>
            <p class="dashboard-welcome-subtitle">
                Vai trò: <span class="dashboard-welcome-role">{{ $this->getUserRoleLabel() }}</span>
                &mdash; {{ \Carbon\CarbonImmutable::now('Asia/Ho_Chi_Minh')->translatedFormat('l, d/m/Y') }}
            </p>
        </div>

        <!-- Filter integrated inside banner -->
        <div class="dashboard-banner-filters">
            @livewire(\App\Filament\Widgets\DashboardFiltersWidget::class)
        </div>

        <div class="dashboard-welcome-illustration">
            <svg viewBox="0 0 200 120" fill="none" xmlns="http://www.w3.org/2000/svg" class="dashboard-welcome-svg">
                <rect x="20" y="45" width="40" height="55" rx="6" fill="currentColor" opacity="0.15" />
                <rect x="70" y="30" width="40" height="70" rx="6" fill="currentColor" opacity="0.25" />
                <rect x="120" y="15" width="40" height="85" rx="6" fill="currentColor" opacity="0.35" />
                <circle cx="80" cy="15" r="8" fill="currentColor" opacity="0.2" />
                <circle cx="140" cy="10" r="6" fill="currentColor" opacity="0.3" />
                <path d="M20 100 L180 100" stroke="currentColor" stroke-width="1.5" opacity="0.15" stroke-dasharray="4 4" />
            </svg>
        </div>
    </div>

</div>