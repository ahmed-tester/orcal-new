<?php

namespace App\Filament\Navigation;

use App\Services\NavigationOrderService;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Filament\Navigation\NavigationManager;

class OrderedNavigationManager extends NavigationManager
{
    /**
     * @return array<NavigationGroup>
     */
    public function get(): array
    {
        $groups = parent::get();
        $itemOrder = NavigationOrderService::itemOrder();
        $groupKeys = NavigationOrderService::getOrderedGroupKeys();

        // Build reverse map from label to group key
        $labelToKey = [];
        foreach (NavigationOrderService::DEFAULT_GROUP_KEYS as $k) {
            $labelToKey[__("navigation.{$k}")] = $k;
            $labelToKey[__("navigation.{$k}", [], 'ar')] = $k;
            $labelToKey[__("navigation.{$k}", [], 'en')] = $k;
            $labelToKey[$k] = $k;
        }

        $groupKeyIndex = array_flip($groupKeys);

        // Sort items inside each group
        foreach ($groups as $group) {
            $items = collect($group->getItems())->values();

            if (! empty($itemOrder)) {
                $sortedItems = $items->sortBy(function (NavigationItem $item, int $index) use ($itemOrder) {
                    $key = $item->getKey() ?: $item->getUrl() ?: $item->getLabel();
                    $customSort = $itemOrder[$key] ?? null;

                    return [
                        $customSort !== null ? 0 : 1,
                        $customSort ?? PHP_INT_MAX,
                        $item->getSort() ?? 0,
                        $index,
                    ];
                })->values()->all();

                $group->items($sortedItems);
            }
        }

        // Ensure groups themselves are ordered according to the configured group keys
        usort($groups, function (NavigationGroup $a, NavigationGroup $b) use ($labelToKey, $groupKeyIndex) {
            $labelA = $a->getLabel();
            $labelB = $b->getLabel();

            // Unnamed root groups (holding Dashboard / لوحة التحكم) MUST ALWAYS stay at the very top (-1)
            if (blank($labelA) && blank($labelB)) {
                return 0;
            }
            if (blank($labelA)) {
                return -1;
            }
            if (blank($labelB)) {
                return 1;
            }

            $keyA = $labelToKey[$labelA] ?? null;
            $keyB = $labelToKey[$labelB] ?? null;

            $indexA = $keyA !== null && isset($groupKeyIndex[$keyA]) ? $groupKeyIndex[$keyA] : PHP_INT_MAX;
            $indexB = $keyB !== null && isset($groupKeyIndex[$keyB]) ? $groupKeyIndex[$keyB] : PHP_INT_MAX;

            if ($indexA === $indexB) {
                return strcmp((string) $labelA, (string) $labelB);
            }

            return $indexA <=> $indexB;
        });

        return $groups;
    }
}
