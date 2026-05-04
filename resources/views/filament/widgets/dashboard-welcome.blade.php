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

    <!-- Quick Stats Grid (8 cards) -->
    @if (!empty($this->getQuickStats()))
    <div class="dashboard-welcome-stats grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-4 mt-6">
        @foreach ($this->getQuickStats() as $stat)
        <div class="dashboard-welcome-stat">
            <div class="dashboard-welcome-stat-icon">
                @if ($stat['icon'] === 'heroicon-o-credit-card')
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-5 h-5">
                    <rect x="1" y="4" width="22" height="16" rx="2" ry="2" /><line x1="1" y1="10" x2="23" y2="10" />
                </svg>
                @elseif ($stat['icon'] === 'heroicon-o-user-plus')
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-5 h-5">
                    <path d="M16 21v-2a4 4 0 00-4-4H5a4 4 0 00-4-4v2M8 7a4 4 0 018 0M20 8v6M23 11h-6" />
                </svg>
                @elseif ($stat['icon'] === 'heroicon-o-banknotes')
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-5 h-5">
                    <rect x="2" y="6" width="20" height="12" rx="2" /><circle cx="12" cy="12" r="2" /><path d="M6 12h.01M18 12h.01" />
                </svg>
                @elseif ($stat['icon'] === 'heroicon-o-academic-cap')
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-5 h-5">
                    <path d="M22 10v6M2 10l10-5 10 5-10 5z" /><path d="M6 12v5c3 3 9 3 12 0v-5" />
                </svg>
                @elseif ($stat['icon'] === 'heroicon-o-clock')
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-5 h-5">
                    <circle cx="12" cy="12" r="10" /><polyline points="12 6 12 12 16 14" />
                </svg>
                @elseif ($stat['icon'] === 'heroicon-o-users')
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-5 h-5">
                    <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4-4v2" /><circle cx="9" cy="7" r="4" /><path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75" />
                </svg>
                @elseif ($stat['icon'] === 'heroicon-o-check-badge')
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-5 h-5">
                    <path d="M9 11l3 3L22 4M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11" />
                </svg>
                @elseif ($stat['icon'] === 'heroicon-o-arrow-path')
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-5 h-5">
                    <path d="M23 4v6h-6M1 20v-6h6M3.51 9a9 9 0 0114.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0020.49 15" />
                </svg>
                @else
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-5 h-5">
                    <circle cx="12" cy="12" r="10" />
                </svg>
                @endif
            </div>
            <div class="dashboard-welcome-stat-info">
                <span class="dashboard-welcome-stat-value">{{ $stat['value'] }}</span>
                <span class="dashboard-welcome-stat-label">{{ $stat['label'] }}</span>
            </div>
        </div>
        @endforeach
    </div>
    @endif
</div>