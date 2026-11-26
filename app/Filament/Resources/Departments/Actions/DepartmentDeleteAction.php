<?php

namespace App\Filament\Resources\Departments\Actions;

use App\Filament\Resources\Departments\DepartmentResource;
use App\Models\Department;
use App\Services\DepartmentDeletionService;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\ValidationException;

class DepartmentDeleteAction
{
    public static function makeSingle(): Action
    {
        return Action::make('delete_department')
            ->label(__('filament-actions::delete.single.label'))
            ->icon('heroicon-m-trash')
            ->color('danger')
            ->visible(fn (Department $record): bool => auth()->user()?->can('delete', $record) ?? false)
            ->modalHeading(__('organization.departments.deletion.modal.title'))
            ->modalWidth('5xl')
            ->modalSubmitActionLabel(__('organization.departments.deletion.modal.confirm_button'))
            ->form(fn (Department $record): array => static::buildFormSchema(
                DepartmentDeletionService::analyzeImpact($record)
            ))
            ->action(function (array $data, Department $record, Action $action): void {
                static::executeAction($data, collect([$record]), $action, redirectAfter: true);
            });
    }

    public static function makeBulk(): BulkAction
    {
        return BulkAction::make('delete')
            ->label(__('filament-actions::delete.multiple.label'))
            ->icon('heroicon-m-trash')
            ->color('danger')
            ->authorizeIndividualRecords('delete')
            ->visible(fn (): bool => auth()->user()?->can('delete_department') ?? false)
            ->modalHeading(__('organization.departments.deletion.modal.title'))
            ->modalWidth('5xl')
            ->modalSubmitActionLabel(__('organization.departments.deletion.modal.confirm_button'))
            ->form(fn (Collection $records): array => static::buildFormSchema(
                DepartmentDeletionService::analyzeBulkImpact($records)
            ))
            ->action(function (array $data, Collection $records, BulkAction $action): void {
                static::executeAction($data, $records, $action, redirectAfter: false);
            })
            ->deselectRecordsAfterCompletion();
    }

    /**
     * @param  array<string, mixed>  $impact
     * @return array<int, Component>
     */
    protected static function buildFormSchema(array $impact): array
    {
        $schema = [
            Placeholder::make('impact_summary')
                ->hiddenLabel()
                ->content(new HtmlString(view('filament.departments.delete-impact-summary', [
                    'impact' => $impact,
                ])->render())),
        ];

        if (! $impact['has_blockers']) {
            return $schema;
        }

        $targets = $impact['compatible_targets'] ?? [];
        $hasTargets = $targets !== [];

        $options = [
            'transfer' => __('organization.departments.deletion.options.transfer'),
            'archive_all' => __('organization.departments.deletion.options.archive_all'),
        ];

        $schema[] = Radio::make('resolution_mode')
            ->label(__('organization.departments.deletion.options.label'))
            ->options($options)
            ->default($hasTargets ? 'transfer' : 'archive_all')
            ->disableOptionWhen(fn (string $value): bool => $value === 'transfer' && ! $hasTargets)
            ->live()
            ->required();

        if (! $hasTargets) {
            $schema[] = Placeholder::make('no_compatible_target_notice')
                ->hiddenLabel()
                ->content(new HtmlString(
                    '<div style="border-radius: 0.75rem; border: 1px solid #fcd34d; background: #fffbeb; padding: 0.65rem 0.875rem; font-size: 0.78rem; font-weight: 600; color: #92400e;">'
                    .e(__('organization.departments.deletion.modal.no_compatible_target'))
                    .'</div>'
                ))
                ->visible(fn ($get): bool => $get('resolution_mode') === 'transfer');
        } else {
            $schema[] = Select::make('target_department_id')
                ->label(__('organization.departments.deletion.fields.target_department'))
                ->options($targets)
                ->searchable()
                ->required(fn ($get): bool => $get('resolution_mode') === 'transfer')
                ->visible(fn ($get): bool => $get('resolution_mode') === 'transfer')
                ->helperText(__('organization.departments.deletion.modal.transfer_scope_notice'));
        }

        $schema[] = Placeholder::make('second_confirmation_warning')
            ->hiddenLabel()
            ->content(new HtmlString(
                '<div style="border-radius: 0.75rem; border: 1px solid #fca5a5; background: #fef2f2; padding: 0.75rem 0.875rem; font-size: 0.84rem; font-weight: 700; color: #991b1b;">'
                .e(__('organization.departments.deletion.modal.second_confirmation_notice'))
                .'</div>'
            ))
            ->visible(fn ($get): bool => $get('resolution_mode') === 'archive_all');

        $firstRoot = $impact['roots'][0] ?? ['name' => '', 'code' => ''];
        $expectedHint = $impact['is_bulk']
            ? __('organization.departments.deletion.fields.confirmation_hint_bulk')
            : __('organization.departments.deletion.fields.confirmation_hint_single', [
                'name' => $firstRoot['name'],
                'code' => $firstRoot['code'] ?: '-',
            ]);

        $schema[] = TextInput::make('confirmation_text')
            ->label(__('organization.departments.deletion.fields.confirmation_text'))
            ->helperText($expectedHint)
            ->required(fn ($get): bool => $get('resolution_mode') === 'archive_all')
            ->visible(fn ($get): bool => $get('resolution_mode') === 'archive_all');

        return $schema;
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  Collection<int, Department>  $records
     */
    protected static function executeAction(array $data, Collection $records, Action|BulkAction $action, bool $redirectAfter): void
    {
        try {
            $impact = DepartmentDeletionService::analyzeBulkImpact($records);

            if (! $impact['has_blockers']) {
                DepartmentDeletionService::archiveWithDependents($records, '', false);
                Notification::make()
                    ->success()
                    ->title(__('organization.departments.deletion.notifications.archived_success'))
                    ->send();
            } elseif (! array_key_exists('resolution_mode', $data)) {
                throw ValidationException::withMessages([
                    'confirmation_text' => __('organization.departments.deletion.errors.impact_changed_requires_confirmation'),
                ]);
            } elseif (($data['resolution_mode'] ?? null) === 'transfer') {
                $targetId = (int) ($data['target_department_id'] ?? 0);
                DepartmentDeletionService::transferAndArchive($records, $targetId);
                Notification::make()
                    ->success()
                    ->title(__('organization.departments.deletion.notifications.transferred_and_archived'))
                    ->send();
            } else {
                DepartmentDeletionService::archiveWithDependents(
                    $records,
                    (string) ($data['confirmation_text'] ?? ''),
                    true
                );
                Notification::make()
                    ->success()
                    ->title(__('organization.departments.deletion.notifications.archived_with_dependents'))
                    ->send();
            }

            if ($redirectAfter) {
                $action->redirect(DepartmentResource::getUrl('index'));
            }
        } catch (ValidationException $e) {
            Notification::make()
                ->danger()
                ->title(collect($e->errors())->flatten()->first() ?? __('organization.departments.deletion.errors.delete_failed'))
                ->send();

            $action->halt();
        }
    }
}
