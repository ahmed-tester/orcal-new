<?php

namespace App\Filament\Components;

use App\Models\CashHandover;
use App\Services\CashHandoverService;
use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Illuminate\Validation\ValidationException;

class CashHandoverActions
{
    /**
     * Treasury screen: receive and confirm a submitted cash handover with the
     * confirmed amount per receipt. The handover is picked manually or by
     * scanning its QR code; both load the same form.
     */
    public static function receiveHeaderAction(): Action
    {
        return Action::make('receive_cash_handover')
            ->label(__('financial.actions.confirm_handover'))
            ->icon('heroicon-o-inbox-arrow-down')
            ->color('success')
            ->modalWidth('3xl')
            ->visible(fn () => auth()->user()?->can('confirm_cash_handover') ?? false)
            ->form([
                Hidden::make('confirmation_key')->default(fn () => CashHandoverService::newConfirmationKey()),

                Grid::make(2)
                    ->schema([
                        Select::make('cash_handover_id')
                            ->label(__('financial.fields.handover_receipts'))
                            ->options(fn () => CashHandover::accessibleBy()
                                ->submitted()
                                ->orderBy('id')
                                ->get()
                                ->mapWithKeys(fn (CashHandover $handover) => [
                                    $handover->id => $handover->handover_number.' • '.number_format((float) $handover->total_requested_amount, 2).' '.__('services_tracking.currency_sar'),
                                ])
                                ->all())
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set): void {
                                $set('signature_code', null);
                                $handover = CashHandover::accessibleBy()->submitted()->with('items')->find($state);

                                if (! $handover) {
                                    $set('items', []);

                                    return;
                                }

                                $set('items', static::confirmedItemsState($handover));
                            }),

                        TextInput::make('handover_scan')
                            ->label(__('financial.fields.scan_handover_qr'))
                            ->prefixIcon('heroicon-o-qr-code')
                            ->live(debounce: 400)
                            ->afterStateUpdated(function ($state, callable $set): void {
                                if (blank($state)) {
                                    return;
                                }

                                $handover = CashHandoverService::findSubmittedByScan((string) $state);

                                $set('handover_scan', null);
                                $set('signature_code', null);

                                if (! $handover) {
                                    $set('cash_handover_id', null);
                                    $set('items', []);

                                    Notification::make()
                                        ->title(__('financial.errors.handover_qr_unavailable'))
                                        ->danger()
                                        ->send();

                                    return;
                                }

                                $set('cash_handover_id', $handover->id);
                                $set('items', static::confirmedItemsState($handover));
                            }),
                    ]),

                TextInput::make('signature_code')
                    ->label(__('financial.fields.handover_signature'))
                    ->helperText(__('financial.handover_qr.signature_input_hint'))
                    ->password()
                    ->maxLength(4)
                    ->extraInputAttributes(['inputmode' => 'numeric'])
                    ->required(),

                Repeater::make('items')
                    ->label(__('financial.fields.confirmed_amounts'))
                    ->schema([
                        Hidden::make('item_id'),
                        TextInput::make('requested_amount')
                            ->label(__('financial.fields.amount'))
                            ->disabled()
                            ->dehydrated(),
                        TextInput::make('confirmed_amount')
                            ->label(__('financial.fields.confirmed_amounts'))
                            ->numeric()
                            ->required(),
                    ])
                    ->columns(2)
                    ->addable(false)
                    ->deletable(false)
                    ->columnSpanFull(),

                TextInput::make('extra_amount')
                    ->label(__('financial.fields.extra_amount'))
                    ->numeric()
                    ->default(0),

                TextInput::make('extra_note')
                    ->label(__('financial.fields.extra_note'))
                    ->maxLength(255),

                Textarea::make('notes')
                    ->label(__('financial.fields.notes'))
                    ->rows(2)
                    ->columnSpanFull(),
            ])
            ->action(function (array $data, Action $action) {
                $handover = CashHandover::accessibleBy()->findOrFail($data['cash_handover_id']);

                $confirmedAmounts = collect($data['items'] ?? [])
                    ->filter(fn ($item) => ! empty($item['item_id']))
                    ->mapWithKeys(fn ($item) => [(int) $item['item_id'] => (float) $item['confirmed_amount']])
                    ->all();

                try {
                    CashHandoverService::confirm($handover, [
                        'confirmation_key' => $data['confirmation_key'],
                        'signature_code' => $data['signature_code'],
                        'confirmed_amounts' => $confirmedAmounts,
                        'extra_amount' => (float) ($data['extra_amount'] ?? 0),
                        'extra_note' => $data['extra_note'] ?? null,
                        'notes' => $data['notes'] ?? null,
                    ]);
                } catch (ValidationException $exception) {
                    $message = $exception->errors()['signature_code'][0] ?? null;

                    if ($message === null) {
                        throw $exception;
                    }

                    Notification::make()->title($message)->danger()->send();
                    $action->halt();
                }

                Notification::make()
                    ->title(__('financial.actions.confirm_handover'))
                    ->success()
                    ->send();
            });
    }

    public static function rejectHeaderAction(): Action
    {
        return Action::make('reject_cash_handover')
            ->label(__('financial.actions.reject_handover'))
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->modalWidth('xl')
            ->visible(fn () => auth()->user()?->can('confirm_cash_handover') ?? false)
            ->form([
                Select::make('cash_handover_id')
                    ->label(__('financial.fields.handover_receipts'))
                    ->options(fn () => CashHandover::accessibleBy()
                        ->submitted()
                        ->orderBy('id')
                        ->get()
                        ->mapWithKeys(fn (CashHandover $handover) => [
                            $handover->id => $handover->handover_number.' • '.number_format((float) $handover->total_requested_amount, 2).' '.__('services_tracking.currency_sar'),
                        ])
                        ->all())
                    ->required(),

                Textarea::make('rejection_reason')
                    ->label(__('financial.fields.rejection_reason'))
                    ->required()
                    ->rows(2),
            ])
            ->action(function (array $data) {
                $handover = CashHandover::accessibleBy()->submitted()->findOrFail($data['cash_handover_id']);
                CashHandoverService::reject($handover, $data['rejection_reason']);

                Notification::make()
                    ->title(__('financial.actions.reject_handover'))
                    ->warning()
                    ->send();
            });
    }

    /**
     * Load the handover items into the confirmation form.
     *
     * @return array<int, array<string, mixed>>
     */
    private static function confirmedItemsState(CashHandover $handover): array
    {
        return $handover->items->map(fn ($item) => [
            'item_id' => $item->id,
            'requested_amount' => (float) $item->requested_amount,
            'confirmed_amount' => (float) $item->requested_amount,
        ])->all();
    }
}
