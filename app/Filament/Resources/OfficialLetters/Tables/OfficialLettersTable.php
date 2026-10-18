<?php

namespace App\Filament\Resources\OfficialLetters\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Actions\Action;

class OfficialLettersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('letter_number')
                    ->label(__('official_letters.fields.letter_number'))
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->badge()
                    ->color('gray')
                    ->icon('heroicon-m-document-text'),

                TextColumn::make('letter_date')
                    ->label(__('official_letters.fields.letter_date'))
                    ->date('Y-m-d')
                    ->sortable(),

                TextColumn::make('subject')
                    ->label(__('official_letters.fields.subject'))
                    ->searchable()
                    ->limit(50)
                    ->weight('medium'),

                TextColumn::make('company.trade_name')
                    ->label(__('official_letters.fields.company'))
                    ->placeholder('—')
                    ->badge()
                    ->color('info'),

                TextColumn::make('status')
                    ->label(__('official_letters.fields.status'))
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'approved' => __('official_letters.statuses.approved'),
                        'sent'     => __('official_letters.statuses.sent'),
                        default    => __('official_letters.statuses.draft'),
                    })
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'approved' => 'success',
                        'sent'     => 'info',
                        default    => 'gray',
                    }),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('official_letters.fields.status'))
                    ->options([
                        'draft'    => __('official_letters.statuses.draft'),
                        'approved' => __('official_letters.statuses.approved'),
                        'sent'     => __('official_letters.statuses.sent'),
                    ]),

                SelectFilter::make('company_id')
                    ->label(__('official_letters.fields.company'))
                    ->relationship('company', 'trade_name'),
            ])
            ->recordActions([
                Action::make('print')
                    ->label(__('official_letters.actions.print'))
                    ->icon('heroicon-m-printer')
                    ->color('info')
                    ->iconButton()
                    ->tooltip(__('official_letters.actions.print'))
                    ->url(fn ($record) => route('official_letters.print', $record))
                    ->openUrlInNewTab(),

                Action::make('download')
                    ->label(__('offers.actions.download_pdf') ?: 'تحميل PDF')
                    ->icon('heroicon-m-arrow-down-tray')
                    ->color('success')
                    ->iconButton()
                    ->tooltip(__('offers.actions.download_pdf') ?: 'تحميل PDF')
                    ->url(fn ($record) => route('official_letters.pdf', $record))
                    ->openUrlInNewTab(),

                EditAction::make()
                    ->iconButton()
                    ->tooltip(__('official_letters.actions.edit')),

                DeleteAction::make()
                    ->iconButton()
                    ->tooltip(__('official_letters.actions.delete')),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('letter_date', 'desc');
    }
}
