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
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Validation\ValidationException;

class CashHandoverActions
{
    /**
     * Treasury screen: receive and confirm a submitted cash handover with the
     * confirmed amount per receipt, after entering or scanning the voucher.
     */
    public static function receiveHeaderAction(): Action
    {
        return Action::make('receive_cash_handover')
            ->label(__('financial.actions.confirm_handover'))
            ->icon('heroicon-o-inbox-arrow-down')
            ->color('success')
            ->modalWidth('2xl')
            ->visible(fn () => auth()->user()?->can('confirm_cash_handover') ?? false)
            ->form([
                Hidden::make('confirmation_key')->default(fn () => CashHandoverService::newConfirmationKey()),

                Grid::make(['default' => 1, 'sm' => 2])
                    ->schema([
                        TextInput::make('handover_scan')
                            ->label(__('financial.fields.scan_handover_qr'))
                            ->prefixIcon('heroicon-o-qr-code')
                            ->autocomplete(false)
                            ->autofocus()
                            ->extraInputAttributes(['x-on:keydown.enter.prevent.stop' => ''])
                            ->columnSpan(fn (Get $get): int|string => filled($get('items')) ? 1 : 'full')
                            ->required()
                            ->live(debounce: 400)
                            ->afterStateUpdated(function (?string $state, callable $set): void {
                                $handover = static::findScannedHandover($state);

                                $set('signature_code', null);
                                $set('items', $handover ? static::confirmedItemsState($handover) : []);
                                $set('extra_amount', null);
                                $set('extra_note', null);
                            }),

                        TextInput::make('signature_code')
                            ->label(__('financial.fields.handover_signature'))
                            ->helperText(__('financial.handover_qr.signature_input_hint'))
                            ->password()
                            ->autocomplete('new-password')
                            ->maxLength(4)
                            ->extraInputAttributes(['inputmode' => 'numeric'])
                            ->visible(fn (Get $get): bool => filled($get('items')))
                            ->required(),
                    ]),

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
                            ->minValue(0)
                            ->live(debounce: 300)
                            ->afterStateUpdated(function (Get $get, Set $set): void {
                                $items = collect($get('../../items') ?? []);
                                [, $extra] = CashHandoverService::splitCountedAmounts(
                                    $items->pluck('confirmed_amount', 'item_id')->all(),
                                    $items->pluck('requested_amount', 'item_id')->all(),
                                );

                                $set('../../extra_amount', $extra);
                            })
                            ->required(),
                    ])
                    ->columns(2)
                    ->defaultItems(0)
                    ->visible(fn (Get $get): bool => filled($get('items')))
                    ->addable(false)
                    ->deletable(false)
                    ->columnSpanFull(),

                TextInput::make('extra_amount')
                    ->label(__('financial.fields.extra_amount'))
                    ->numeric()
                    ->disabled()
                    ->visible(fn (Get $get): bool => (float) $get('extra_amount') > 0),

                TextInput::make('extra_note')
                    ->label(__('financial.fields.extra_note'))
                    ->visible(fn (Get $get): bool => (float) $get('extra_amount') > 0)
                    ->required()
                    ->maxLength(255),

                Textarea::make('notes')
                    ->label(__('financial.fields.notes'))
                    ->rows(2)
                    ->columnSpanFull(),
            ])
            ->action(function (array $data, Action $action) {
                $handover = static::findScannedHandover($data['handover_scan']);

                if (! $handover) {
                    Notification::make()->title(__('financial.errors.handover_qr_unavailable'))->danger()->send();
                    $action->halt();
                }

                $countedAmounts = collect($data['items'] ?? [])
                    ->filter(fn ($item) => ! empty($item['item_id']))
                    ->mapWithKeys(fn ($item) => [(int) $item['item_id'] => (float) $item['confirmed_amount']])
                    ->all();
                [$confirmedAmounts, $extraAmount] = CashHandoverService::splitCountedAmounts(
                    $countedAmounts,
                    $handover->items()->pluck('requested_amount', 'id')->all(),
                );

                try {
                    CashHandoverService::confirm($handover, [
                        'confirmation_key' => $data['confirmation_key'],
                        'signature_code' => $data['signature_code'] ?? '',
                        'confirmed_amounts' => $confirmedAmounts,
                        'extra_amount' => $extraAmount,
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

    private static function findScannedHandover(?string $scan): ?CashHandover
    {
        if ($scan === null || trim($scan) === '') {
            return null;
        }

        return CashHandoverService::findSubmittedByScan($scan);
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
