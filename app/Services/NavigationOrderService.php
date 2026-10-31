<?php

namespace App\Services;

use App\Models\Setting;
use Filament\Navigation\NavigationGroup;
use Illuminate\Support\Facades\Cache;

class NavigationOrderService
{
    public const SETTING_KEY = 'navigation_order';

    public const DEFAULT_GROUP_KEYS = [
        'system_group',
        'org_group',
        'hr_group',
        'finance_group',
        'public_finance_and_services',
        'tasks_group',
        'contracts_group',
        'violations_group',
        'funds_group',
        'letters_group',
        'reports_group',
        'faq_group',
        'support_group',
    ];

    /**
     * Get the ordered group keys (saved or default with merge).
     */
    public static function getOrderedGroupKeys(): array
    {
        return rescue(function () {
            $data = static::getStoredConfig();
            $savedGroups = $data['groups'] ?? [];

            if (empty($savedGroups) || ! is_array($savedGroups)) {
                return static::DEFAULT_GROUP_KEYS;
            }

            // Keep valid saved groups in their saved order
            $ordered = array_values(array_intersect($savedGroups, static::DEFAULT_GROUP_KEYS));

            // Append any default group that is missing from saved order
            $missing = array_diff(static::DEFAULT_GROUP_KEYS, $ordered);

            return array_merge($ordered, array_values($missing));
        }, static::DEFAULT_GROUP_KEYS, false) ?: static::DEFAULT_GROUP_KEYS;
    }

    /**
     * Build the array of NavigationGroup objects for Filament AdminPanelProvider.
     */
    public static function navigationGroups(): array
    {
        $keys = static::getOrderedGroupKeys();

        return array_map(function (string $key): NavigationGroup {
            return NavigationGroup::make()
                ->label(fn () => __("navigation.{$key}"));
        }, $keys);
    }

    /**
     * Get the stored item order mapping: [itemKey => sortPosition].
     */
    public static function itemOrder(): array
    {
        return rescue(function () {
            $data = static::getStoredConfig();
            $items = $data['items'] ?? [];

            return is_array($items) ? $items : [];
        }, [], false) ?: [];
    }

    /**
     * Save the order of groups and items.
     *
     * @param  array<int, string>  $groups
     * @param  array<string, int>  $items
     */
    public static function save(array $groups, array $items): void
    {
        // Clean groups: only allow known default keys
        $cleanGroups = array_values(array_intersect($groups, static::DEFAULT_GROUP_KEYS));
        // Append missing if any
        $missing = array_diff(static::DEFAULT_GROUP_KEYS, $cleanGroups);
        $finalGroups = array_merge($cleanGroups, array_values($missing));

        // Clean items: ensure integer positions
        $cleanItems = [];
        foreach ($items as $key => $pos) {
            if (is_string($key) && $key !== '') {
                $cleanItems[$key] = (int) $pos;
            }
        }

        $payload = [
            'groups' => $finalGroups,
            'items' => $cleanItems,
        ];

        Setting::updateOrCreate(
            ['key' => static::SETTING_KEY],
            [
                'group' => 'navigation',
                'label' => __('navigation_order.page_title'),
                'value' => json_encode($payload, JSON_UNESCAPED_UNICODE),
                'is_active' => true,
            ]
        );

        Cache::forget('setting_'.static::SETTING_KEY);
        Cache::forget('navigation_order_config');
        Cache::forget('navigation_order_catalog_'.auth()->id());
    }

    /**
     * Reset the order to system defaults.
     */
    public static function reset(): void
    {
        $setting = Setting::where('key', static::SETTING_KEY)->first();
        if ($setting) {
            $setting->update([
                'value' => null,
            ]);
        }

        Cache::forget('setting_'.static::SETTING_KEY);
        Cache::forget('navigation_order_config');
        Cache::forget('navigation_order_catalog_'.auth()->id());
    }

    /**
     * Get stored raw configuration from Setting/Cache.
     */
    public static function getStoredConfig(): array
    {
        return Cache::rememberForever('navigation_order_config', function () {
            $raw = Setting::get(static::SETTING_KEY);
            if (empty($raw)) {
                return [];
            }

            if (is_array($raw)) {
                return $raw;
            }

            $decoded = json_decode($raw, true);

            return is_array($decoded) ? $decoded : [];
        });
    }

    /**
     * Build catalog structure for the admin management UI.
     * Returns an array of groups, each with its translated label, key, and list of items.
     */
    public static function getCatalog(): array
    {
        $cacheKey = 'navigation_order_catalog_'.(auth()->id() ?? 'guest');

        return Cache::remember($cacheKey, 600, function () {
            $orderedKeys = static::getOrderedGroupKeys();

            // Build reverse map from labels in different locales to group keys
            $groupKeyByLabel = [];
            foreach (static::DEFAULT_GROUP_KEYS as $key) {
                $labelAr = __("navigation.{$key}", [], 'ar');
                $labelEn = __("navigation.{$key}", [], 'en');
                $currentLabel = __("navigation.{$key}");
                $groupKeyByLabel[$labelAr] = $key;
                $groupKeyByLabel[$labelEn] = $key;
                $groupKeyByLabel[$currentLabel] = $key;
                $groupKeyByLabel[$key] = $key;
            }

            // Initialize groups in ordered sequence
            $catalog = [];
            foreach ($orderedKeys as $key) {
                $catalog[$key] = [
                    'key' => $key,
                    'label' => __("navigation.{$key}"),
                    'items' => [],
                ];
            }

            // Fetch groups and items from the panel navigation
            $groups = filament()->getNavigation();

            foreach ($groups as $group) {
                $groupLabel = $group->getLabel();

                // Exclude items without a group (e.g. Dashboard) as Filament handles them outside groups
                if (blank($groupLabel)) {
                    continue;
                }

                $targetKey = $groupKeyByLabel[$groupLabel] ?? null;

                if (! $targetKey) {
                    $targetKey = 'dyn_'.md5($groupLabel);
                    if (! isset($catalog[$targetKey])) {
                        $catalog[$targetKey] = [
                            'key' => $targetKey,
                            'label' => $groupLabel,
                            'items' => [],
                        ];
                    }
                }

                foreach ($group->getItems() as $item) {
                    if (blank($item->getGroup())) {
                        continue;
                    }

                    $itemKey = $item->getKey() ?: $item->getUrl() ?: $item->getLabel();

                    $catalog[$targetKey]['items'][] = [
                        'key' => $itemKey,
                        'label' => (string) $item->getLabel(),
                        'icon' => is_string($item->getIcon()) ? $item->getIcon() : 'heroicon-o-document-text',
                    ];
                }
            }

            return array_values($catalog);
        });
    }
}
