<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use App\Filament\Theme\AdminTheme;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider {
    public function panel(Panel $panel): Panel {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->brandName('CRM GTVT')
            ->font('Inter')
            ->login(\App\Filament\Pages\Auth\Login::class)
            ->colors([
                'primary' => Color::Amber,
                'gray' => Color::Slate,
                'info' => Color::Blue,
                'success' => Color::Emerald,
                'warning' => Color::Orange,
                'danger' => Color::Rose,
            ])
            ->sidebarCollapsibleOnDesktop()
            ->sidebarWidth('16rem')
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                \App\Filament\Pages\Dashboard::class,
                \App\Filament\Pages\ViewAllNotifications::class,
                \App\Filament\Pages\ViewAllMessages::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                \App\Filament\Resources\Commissions\Widgets\CommissionSummary::class,
            ])
            ->maxContentWidth('full')
            ->topNavigation(false)
            ->renderHook(
                'panels::topbar.end',
                fn(): string => view('components.user-name-display')->render() . view('components.referral-link')->render() . view('components.notification-bell')->render()
            )
            ->renderHook(
                'panels::head.end',
                fn(): string => view('filament.theme.styles')->render()
            )
            ->renderHook(
                'panels::user-menu.start',
                fn(): string => view('components.user-profile')->render()
            )
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->userMenuItems([
                'profile' => \Filament\Navigation\MenuItem::make()
                    ->label('Hồ sơ cá nhân')
                    ->icon('heroicon-o-user-circle')
                    ->url('/admin/profile-page')
                    ->sort(1),
            ])
            ->navigationGroups([
                \Filament\Navigation\NavigationGroup::make()
                    ->label('Tuyển sinh')
                    ->icon('heroicon-o-user-group'),
                \Filament\Navigation\NavigationGroup::make()
                    ->label('Cộng tác viên')
                    ->icon('heroicon-o-users'),
                \Filament\Navigation\NavigationGroup::make()
                    ->label('Tài chính')
                    ->icon('heroicon-o-banknotes'),
                \Filament\Navigation\NavigationGroup::make()
                    ->label('Hệ thống')
                    ->icon('heroicon-o-cog-6-tooth'),
            ]);
    }
}
