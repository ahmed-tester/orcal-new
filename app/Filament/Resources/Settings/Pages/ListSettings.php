<?php

namespace App\Filament\Resources\Settings\Pages;

use App\Filament\Resources\Settings\SettingResource;
use App\Models\Setting;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListSettings extends ListRecords
{
    protected static string $resource = SettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            \Filament\Actions\Action::make('reset_database')
                ->label(__('settings.manage.database_reset.button_label'))
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->visible(fn () => (auth()->user()?->isSuperAdmin() ?? false) || (auth()->user()?->can('reset_database') ?? false))
                ->requiresConfirmation()
                ->modalHeading(__('settings.manage.database_reset.modal_heading'))
                ->modalDescription(__('settings.manage.database_reset.modal_description'))
                ->modalSubmitActionLabel(__('settings.manage.database_reset.submit_label'))
                ->form([
                    \Filament\Forms\Components\Checkbox::make('confirm_reset')
                        ->label(__('settings.manage.database_reset.checkbox_label'))
                        ->required()
                        ->accepted(),
                ])
                ->action(function () {
                    try {
                        $result = \App\Services\DatabaseResetService::resetDatabase();

                        \Filament\Notifications\Notification::make()
                            ->title(__('settings.manage.database_reset.success_title'))
                            ->body(__('settings.manage.database_reset.success_body', [
                                'tables' => $result['truncated_tables_count'],
                                'admins' => $result['preserved_admins'],
                            ]))
                            ->success()
                            ->duration(10000)
                            ->send();

                        $this->redirect(static::getUrl());
                    } catch (\Throwable $e) {
                        \Filament\Notifications\Notification::make()
                            ->title(__('settings.manage.database_reset.failed_title'))
                            ->body($e->getMessage())
                            ->danger()
                            ->duration(12000)
                            ->send();
                    }
                }),
        ];
    }

    public function getTabs(): array
    {
        $groupIcons = [
            'general' => 'heroicon-m-cog-6-tooth',
            'appearance' => 'heroicon-m-paint-brush',
            'alerts' => 'heroicon-m-bell-alert',
            'uploads' => 'heroicon-m-document-arrow-up',
            'ai' => 'heroicon-m-sparkles',
            'hr' => 'heroicon-m-user-group',
            'company' => 'heroicon-m-building-office-2',
            'contracts' => 'heroicon-m-clipboard-document-check',
        ];

        $tabs = [
            'all' => Tab::make('الكل')
                ->icon('heroicon-m-squares-2x2')
                ->badge(Setting::count()),
        ];

        // Only create tabs for groups that actually have settings in the database
        $existingGroups = Setting::query()
            ->select('group')
            ->distinct()
            ->whereNotNull('group')
            ->pluck('group');

        foreach ($existingGroups as $groupKey) {
            $count = Setting::where('group', $groupKey)->count();
            $label = __('settings.groups.' . $groupKey);
            if ($label === 'settings.groups.' . $groupKey) {
                $label = ucfirst($groupKey);
            }

            $tab = Tab::make($label)
                ->badge($count)
                ->modifyQueryUsing(fn (Builder $query) => $query->where('group', $groupKey));

            if (isset($groupIcons[$groupKey])) {
                $tab->icon($groupIcons[$groupKey]);
            }

            $tabs[$groupKey] = $tab;
        }

        return $tabs;
    }
}

