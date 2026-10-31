<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\Login;
use App\Filament\Widgets\FinancialStatsOverviewWidget;
use App\Filament\Widgets\NotificationsBannerWidget;
use App\Filament\Widgets\StatsOverviewWidget;
use App\Models\Setting;
use App\Services\NavigationOrderService;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationItem;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('')
            ->brandName(fn () => rescue(fn () => Setting::getActive('site_name') ?: Setting::getActive('system_name'), 'مجموعة فواز القابضة', false) ?: 'مجموعة فواز القابضة')
            ->login(Login::class)
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
            ->navigationGroups(NavigationOrderService::navigationGroups())
            ->navigationItems([
                NavigationItem::make(fn () => __('navigation.system_logs') ?: 'سجل الأخطاء والعمليات')
                    ->key('system_logs')
                    ->url(fn (): string => Route::has('log-viewer.index') ? route('log-viewer.index') : '#')
                    ->icon('heroicon-o-bug-ant')
                    ->group(fn () => __('navigation.system_group'))
                    ->sort(99)
                    ->visible(fn (): bool => Route::has('log-viewer.index') && (auth()->user()?->isSuperAdminOrOwner() ?? false)),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->widgets([
                NotificationsBannerWidget::class,
                FinancialStatsOverviewWidget::class,
                StatsOverviewWidget::class,
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
