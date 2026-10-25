<?php

namespace App\Filament\Components;

use App\Enums\ApprovalStatus;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;

class ApprovalActions
{
    /**
     * Create a reusable Approve action for Filament tables.
     */
    public static function approve(
        string $permission,
        ?string $label = null,
        ?string $successTitle = null,
        ?string $translationKey = null
    ): Action {
        $finalLabel = $label
            ?? ($translationKey ? __("{$translationKey}.approve") : null)
            ?? __('finance.approval_actions.approve');

        $finalSuccessTitle = $successTitle
            ?? ($translationKey ? __("{$translationKey}.approved_success") : null)
            ?? __('finance.approval_actions.approved_success');

        return Action::make('approve')
            ->label($finalLabel)
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->visible(fn (Model $record) => auth()->user()?->can($permission)
                && method_exists($record, 'isApproved')
                && ! $record->isApproved())
            ->requiresConfirmation()
            ->action(function (Model $record) use ($finalSuccessTitle) {
                if (method_exists($record, 'approve')) {
                    $record->approve();
                } else {
                    $record->status = ApprovalStatus::APPROVED;
                    $record->save();
                }

                Notification::make()
                    ->title($finalSuccessTitle)
                    ->success()
                    ->send();
            });
    }

    /**
     * Create a reusable Unapprove action for Filament tables.
     */
    public static function unapprove(
        string $permission,
        ?string $label = null,
        ?string $warningTitle = null,
        ?string $translationKey = null
    ): Action {
        $finalLabel = $label
            ?? ($translationKey ? __("{$translationKey}.unapprove") : null)
            ?? __('finance.approval_actions.unapprove');

        $finalWarningTitle = $warningTitle
            ?? ($translationKey ? __("{$translationKey}.unapproved_success") : null)
            ?? __('finance.approval_actions.unapproved_success');

        return Action::make('unapprove')
            ->label($finalLabel)
            ->icon('heroicon-o-arrow-path')
            ->color('gray')
            ->visible(fn (Model $record) => auth()->user()?->can($permission)
                && method_exists($record, 'isApproved')
                && $record->isApproved())
            ->requiresConfirmation()
            ->action(function (Model $record) use ($finalWarningTitle) {
                if (method_exists($record, 'unapprove')) {
                    $record->unapprove();
                } else {
                    $record->status = ApprovalStatus::UNAPPROVED;
                    $record->save();
                }

                Notification::make()
                    ->title($finalWarningTitle)
                    ->warning()
                    ->send();
            });
    }

    /**
     * Generate both Approve and Unapprove actions.
     *
     * @param  string  $permission  Permission required to approve/unapprove.
     * @param  string|null  $translationKey  Optional translation prefix (e.g. 'debts.collections.actions').
     * @param  string|null  $approveLabel  Custom label for approve.
     * @param  string|null  $unapproveLabel  Custom label for unapprove.
     * @param  string|null  $approvedSuccessTitle  Custom title for approve notification.
     * @param  string|null  $unapprovedSuccessTitle  Custom title for unapprove notification.
     * @return array<int, Action>
     */
    public static function make(
        string $permission,
        ?string $translationKey = null,
        ?string $approveLabel = null,
        ?string $unapproveLabel = null,
        ?string $approvedSuccessTitle = null,
        ?string $unapprovedSuccessTitle = null
    ): array {
        return [
            static::approve($permission, $approveLabel, $approvedSuccessTitle, $translationKey),
            static::unapprove($permission, $unapproveLabel, $unapprovedSuccessTitle, $translationKey),
        ];
    }
}
