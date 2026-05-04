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

    <!-- Quick Stats Row -->
    @if (!empty($this->getQuickStats()))
    <div class="dashboard-welcome-stats">
        @foreach ($this->getQuickStats() as $stat)
        <div class="dashboard-welcome-stat">
            <div class="dashboard-welcome-stat-icon">
                @if ($stat['icon'] === 'heroicon-o-credit-card')
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-5 h-5">
                    <rect x="1" y="4" width="22" height="16" rx="2" ry="2" />
                    <line x1="1" y1="10" x2="23" y2="10" />
                </svg>
                @elseif ($stat['icon'] === 'heroicon-o-academic-cap')
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-5 h-5">
                    <path d="M12 14l9-5-9-5-9 5 9 5z" />
                    <path d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z" />
                </svg>
                @elseif ($stat['icon'] === 'heroicon-o-banknotes')
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-5 h-5">
                    <path d="M12 3c-4.97 0-9 3.582-9 8s4.03 8 9 8c4.97 0 9-3.582 9-8s-4.03-8-9-8z" />
                    <path d="M12 6v12" />
                    <path d="M15 9.5a3.5 3.5 0 00-6 0c0 3 6 1.5 6 5a3.5 3.5 0 01-6 0" />
                </svg>
                @elseif ($stat['icon'] === 'heroicon-o-user-group')
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-5 h-5">
                    <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4-4v2" />
                    <circle cx="9" cy="7" r="4" />
                    <path d="M23 21v-2a4 4 0 00-3-3.87" />
                    <path d="M16 3.13a4 4 0 010 7.75" />
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