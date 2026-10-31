<?php

namespace App\Filament\Pages;

use App\Services\NavigationOrderService;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;

class NavigationOrderPage extends Page
{
    protected static ?string $slug = 'navigation-order';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-queue-list';

    protected static ?int $navigationSort = 5;

    protected string $view = 'filament.pages.navigation-order';
    
    // protected static bool $shouldRegisterNavigation = false;

    /**
     * @var array<int, array{key: string, label: string, items: array<int, array{key: string, label: string, icon: string, url: ?string}>}>
     */
    public array $catalog = [];

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user && ($user->isSuperAdminOrOwner() || $user->can('manage_settings') || $user->can('view_any_setting'));
    }

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.system_group');
    }

    public static function getNavigationLabel(): string
    {
        return __('navigation_order.navigation_label');
    }

    public function getTitle(): string
    {
        return __('navigation_order.page_title');
    }

    public function mount(): void
    {
        $this->loadCatalog();
    }

    public function loadCatalog(): void
    {
        $this->catalog = NavigationOrderService::getCatalog();
    }

    public function moveGroupUp(int $index): void
    {
        if ($index <= 0 || ! isset($this->catalog[$index])) {
            return;
        }

        $prev = $index - 1;
        $temp = $this->catalog[$prev];
        $this->catalog[$prev] = $this->catalog[$index];
        $this->catalog[$index] = $temp;

        $this->catalog = array_values($this->catalog);
    }

    public function moveGroupDown(int $index): void
    {
        if ($index >= count($this->catalog) - 1 || ! isset($this->catalog[$index])) {
            return;
        }

        $next = $index + 1;
        $temp = $this->catalog[$next];
        $this->catalog[$next] = $this->catalog[$index];
        $this->catalog[$index] = $temp;

        $this->catalog = array_values($this->catalog);
    }

    public function moveItemUp(int $groupIndex, int $itemIndex): void
    {
        if (! isset($this->catalog[$groupIndex]['items'][$itemIndex]) || $itemIndex <= 0) {
            return;
        }

        $prev = $itemIndex - 1;
        $temp = $this->catalog[$groupIndex]['items'][$prev];
        $this->catalog[$groupIndex]['items'][$prev] = $this->catalog[$groupIndex]['items'][$itemIndex];
        $this->catalog[$groupIndex]['items'][$itemIndex] = $temp;

        $this->catalog[$groupIndex]['items'] = array_values($this->catalog[$groupIndex]['items']);
    }

    public function moveItemDown(int $groupIndex, int $itemIndex): void
    {
        if (! isset($this->catalog[$groupIndex]['items'][$itemIndex]) || $itemIndex >= count($this->catalog[$groupIndex]['items']) - 1) {
            return;
        }

        $next = $itemIndex + 1;
        $temp = $this->catalog[$groupIndex]['items'][$next];
        $this->catalog[$groupIndex]['items'][$next] = $this->catalog[$groupIndex]['items'][$itemIndex];
        $this->catalog[$groupIndex]['items'][$itemIndex] = $temp;

        $this->catalog[$groupIndex]['items'] = array_values($this->catalog[$groupIndex]['items']);
    }

    public function applyCatalogOrder(array $newCatalog): void
    {
        if (empty($newCatalog)) {
            return;
        }

        $validated = [];
        foreach ($newCatalog as $group) {
            if (! is_array($group) || empty($group['key'])) {
                continue;
            }

            $validItems = [];
            if (! empty($group['items']) && is_array($group['items'])) {
                foreach ($group['items'] as $item) {
                    if (is_array($item) && ! empty($item['key'])) {
                        $validItems[] = $item;
                    }
                }
            }

            $group['items'] = $validItems;
            $validated[] = $group;
        }

        $this->catalog = $validated;
    }

    public function saveOrder(): void
    {
        abort_unless(static::canAccess(), 403);

        $groups = [];
        $items = [];

        foreach ($this->catalog as $group) {
            if (! is_array($group) || empty($group['key'])) {
                continue;
            }

            $groups[] = $group['key'];

            if (! empty($group['items']) && is_array($group['items'])) {
                foreach ($group['items'] as $index => $item) {
                    if (is_array($item) && ! empty($item['key'])) {
                        $items[$item['key']] = $index + 1;
                    }
                }
            }
        }

        NavigationOrderService::save($groups, $items);

        Notification::make()
            ->title(__('navigation_order.saved_successfully'))
            ->success()
            ->send();

        $this->redirect(static::getUrl(), navigate: false);
    }

    public function resetOrder(): void
    {
        abort_unless(static::canAccess(), 403);

        NavigationOrderService::reset();

        Notification::make()
            ->title(__('navigation_order.reset_successfully'))
            ->success()
            ->send();

        $this->redirect(static::getUrl(), navigate: false);
    }
}
