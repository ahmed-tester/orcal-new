<?php

namespace App\Services;

use Filament\Facades\Filament;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use ReflectionMethod;
use Throwable;

class SidebarBadgeService
{
    protected const VERSION_CACHE_KEY = 'sidebar_badges:version';

    protected const CACHE_TTL_MINUTES = 5;

    /**
     * Tables whose mutations should not trigger sidebar badge cache invalidation.
     *
     * @var array<int, string>
     */
    protected const IGNORED_TABLES = [
        'cache',
        'cache_locks',
        'jobs',
        'failed_jobs',
        'sessions',
        'notifications',
        'unified_audit_logs',
        'user_favorites',
        'directive_recipients',
    ];

    protected static bool $invalidatedThisRequest = false;

    /**
     * Attach cached record-count badges to sidebar items that do not already define a custom badge.
     *
     * @param  array<NavigationGroup>  $groups
     */
    public static function applyToGroups(array $groups): void
    {
        if (! auth()->check()) {
            return;
        }

        $countsByUrl = static::getCountsByUrl();

        if ($countsByUrl === []) {
            return;
        }

        foreach ($groups as $group) {
            foreach ($group->getItems() as $item) {
                if (! $item instanceof NavigationItem || $item->getBadge() !== null) {
                    continue;
                }

                $url = rtrim((string) ($item->getUrl() ?? ''), '/');
                $count = $countsByUrl[$url] ?? null;

                if ($count !== null && $count > 0) {
                    $item->badge((string) $count, 'gray');
                }
            }
        }
    }

    /**
     * Resolve (and cache) the record counts keyed by resource index URL for the current user.
     *
     * @return array<string, int>
     */
    public static function getCountsByUrl(): array
    {
        $user = auth()->user();
        $panel = Filament::getCurrentPanel();

        if (! $user || ! $panel) {
            return [];
        }

        $version = (int) Cache::get(self::VERSION_CACHE_KEY, 1);
        $cacheKey = "sidebar_badges:v{$version}:p{$panel->getId()}:u{$user->getAuthIdentifier()}";

        return Cache::remember($cacheKey, now()->addMinutes(self::CACHE_TTL_MINUTES), function () use ($panel): array {
            $counts = [];

            foreach ($panel->getResources() as $resource) {
                if (! static::shouldCountResource($resource)) {
                    continue;
                }

                try {
                    $url = rtrim((string) $resource::getUrl('index'), '/');
                    $counts[$url] = (int) $resource::getEloquentQuery()->count();
                } catch (Throwable) {
                    continue;
                }
            }

            return $counts;
        });
    }

    /**
     * Invalidate cached sidebar counts when a relevant model is created, deleted, or restored.
     */
    public static function invalidateForModel(Model $model): void
    {
        if (static::$invalidatedThisRequest) {
            return;
        }

        if (in_array($model->getTable(), self::IGNORED_TABLES, true)) {
            return;
        }

        static::invalidate();
    }

    /**
     * Bump the global sidebar badge cache version.
     */
    public static function invalidate(): void
    {
        static::$invalidatedThisRequest = true;

        $next = ((int) Cache::get(self::VERSION_CACHE_KEY, 1)) + 1;
        Cache::put(self::VERSION_CACHE_KEY, $next, now()->addDay());
    }

    protected static function shouldCountResource(string $resource): bool
    {
        if (! $resource::shouldRegisterNavigation() || ! $resource::canViewAny()) {
            return false;
        }

        return ! static::hasCustomBadge($resource);
    }

    protected static function hasCustomBadge(string $resource): bool
    {
        try {
            $method = new ReflectionMethod($resource, 'getNavigationBadge');

            return $method->getDeclaringClass()->getName() === $resource;
        } catch (Throwable) {
            return false;
        }
    }
}
