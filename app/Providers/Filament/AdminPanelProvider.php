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
use Filament\View\PanelsRenderHook;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('')
            ->brandName(fn () => rescue(fn () => \App\Models\Setting::getActive('site_name') ?: \App\Models\Setting::getActive('system_name'), 'مجموعة فواز القابضة', false) ?: 'مجموعة فواز القابضة')
            ->login(\App\Filament\Pages\Auth\Login::class)
            ->profile()
            ->colors([
                'primary' => Color::hex('#082c50ff'),
            ])
            ->databaseNotifications()
            ->databaseNotificationsPolling('30s')
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn () => view('filament.custom-sidebar')
            )
            ->renderHook(
                PanelsRenderHook::GLOBAL_SEARCH_BEFORE,
                fn () => view('filament.topbar-dual-date')
            )
            ->renderHook(
                PanelsRenderHook::SIDEBAR_NAV_START,
                fn () => view('filament.sidebar-search')
            )
            ->navigationGroups([
                \Filament\Navigation\NavigationGroup::make()
                    ->label(fn () => __('navigation.system_group')),
                \Filament\Navigation\NavigationGroup::make()
                    ->label(fn () => __('navigation.org_group')),
                \Filament\Navigation\NavigationGroup::make()
                    ->label(fn () => __('navigation.hr_group')),
                \Filament\Navigation\NavigationGroup::make()
                    ->label(fn () => __('navigation.finance_group')),
                \Filament\Navigation\NavigationGroup::make()
                    ->label(fn () => __('navigation.public_finance_and_services')),
                \Filament\Navigation\NavigationGroup::make()
                    ->label(fn () => __('navigation.tasks_group')),
                \Filament\Navigation\NavigationGroup::make()
                    ->label(fn () => __('navigation.contracts_group')),
                \Filament\Navigation\NavigationGroup::make()
                    ->label(fn () => __('navigation.violations_group')),
                \Filament\Navigation\NavigationGroup::make()
                    ->label(fn () => __('navigation.funds_group')),
                \Filament\Navigation\NavigationGroup::make()
                    ->label(fn () => __('navigation.reports_group')),
                \Filament\Navigation\NavigationGroup::make()
                    ->label(fn () => __('navigation.faq_group')),
                \Filament\Navigation\NavigationGroup::make()
                    ->label(fn () => __('navigation.support_group')),
            ])
            ->navigationItems([
                \Filament\Navigation\NavigationItem::make(fn () => __('navigation.system_logs') ?: 'سجل الأخطاء والعمليات')
                    ->url(fn (): string => \Illuminate\Support\Facades\Route::has('log-viewer.index') ? route('log-viewer.index') : '#')
                    ->icon('heroicon-o-bug-ant')
                    ->group(fn () => __('navigation.system_group'))
                    ->sort(99)
                    ->visible(fn (): bool => \Illuminate\Support\Facades\Route::has('log-viewer.index') && (auth()->user()?->isSuperAdminOrOwner() ?? false)),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->widgets([
                \App\Filament\Widgets\NotificationsBannerWidget::class,
                \App\Filament\Widgets\FinancialStatsOverviewWidget::class,
                \App\Filament\Widgets\StatsOverviewWidget::class,
            ])
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
            ]);
    }
}
