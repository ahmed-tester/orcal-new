<?php

namespace App\Filament\Resources\Offers\Pages;

use App\Filament\Resources\Offers\OfferResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditOffer extends EditRecord
{
    protected static string $resource = OfferResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('print_preview')
                ->label(__('offers.actions.print_preview'))
                ->icon('heroicon-m-printer')
                ->color('gray')
                ->url(fn () => route('offers.print', $this->record))
                ->openUrlInNewTab(),

            Action::make('download_pdf')
                ->label(__('offers.actions.download_pdf'))
                ->icon('heroicon-m-arrow-down-tray')
                ->color('primary')
                ->url(fn () => route('offers.pdf', $this->record))
                ->openUrlInNewTab(),

            DeleteAction::make()
                ->label(__('offers.actions.delete'))
                ->icon('heroicon-m-trash'),
        ];
    }
}
