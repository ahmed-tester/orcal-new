<?php

namespace App\Filament\Actions;

use App\Models\PerformanceEscalation;
use App\Services\PerformanceEscalationService;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Gate;

class PerformanceEscalationActions
{
    public static function recordManagementAction(): Action
    {
        return Action::make('record_management_action')
            ->label(__('performance_escalations.actions.record_action'))
            ->icon('heroicon-m-clipboard-document-check')
            ->color('primary')
            ->visible(fn (?PerformanceEscalation $record): bool => $record !== null && Gate::allows('manage', $record))
            ->modalHeading(__('performance_escalations.actions.record_action_heading'))
            ->modalDescription(__('performance_escalations.actions.record_action_description'))
            ->form([
                Textarea::make('management_action')
                    ->label(__('performance_escalations.fields.management_action'))
                    ->required()
                    ->rows(4)
                    ->maxLength(2000),
            ])
            ->action(function (PerformanceEscalation $record, array $data): void {
                Gate::authorize('manage', $record);

                app(PerformanceEscalationService::class)->recordManagementAction(
                    $record,
                    auth()->user(),
                    $data['management_action'],
                );

                Notification::make()
                    ->title(__('performance_escalations.notifications.action_recorded'))
                    ->success()
                    ->send();
            });
    }
}
