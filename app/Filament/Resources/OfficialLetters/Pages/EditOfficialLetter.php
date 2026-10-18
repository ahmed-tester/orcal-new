<?php

namespace App\Filament\Resources\OfficialLetters\Pages;

use App\Filament\Resources\OfficialLetters\OfficialLetterResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditOfficialLetter extends EditRecord
{
    protected static string $resource = OfficialLetterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('print')
                ->label(__('official_letters.actions.print'))
                ->icon('heroicon-o-printer')
                ->color('info')
                ->url(fn () => route('official_letters.print', $this->record))
                ->openUrlInNewTab(),

            Action::make('download_pdf')
                ->label(__('offers.actions.download_pdf') ?: 'تحميل PDF')
                ->icon('heroicon-m-arrow-down-tray')
                ->color('primary')
                ->url(fn () => route('official_letters.pdf', $this->record))
                ->openUrlInNewTab(),

            DeleteAction::make(),
        ];
    }
}
