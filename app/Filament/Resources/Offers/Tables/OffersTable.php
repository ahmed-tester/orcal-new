<?php

namespace App\Filament\Resources\Offers\Tables;

use App\Enums\OfferStatus;
use App\Enums\OfferType;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class OffersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('offer_type')
                    ->label(__('offers.fields.offer_type'))
                    ->badge()
                    ->sortable(),

                TextColumn::make('offer_number')
                    ->label(__('offers.fields.offer_number'))
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->badge()
                    ->color('gray')
                    ->icon('heroicon-m-document-text'),

                TextColumn::make('company.trade_name')
                    ->label(__('offers.fields.company'))
                    ->badge()
                    ->color('primary')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('customer.name')
                    ->label(__('offers.fields.customer'))
                    ->placeholder('—')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('subject')
                    ->label(__('offers.fields.subject'))
                    ->searchable()
                    ->limit(35)
                    ->tooltip(fn ($record) => $record->subject),

                TextColumn::make('offer_date')
                    ->label(__('offers.fields.offer_date'))
                    ->date('Y-m-d')
                    ->sortable(),

                TextColumn::make('status')
                    ->label(__('offers.fields.status'))
                    ->badge(),

                TextColumn::make('manager.user.name')
                    ->label(__('offers.fields.manager'))
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('offer_type')
                    ->label(__('offers.fields.offer_type'))
                    ->options(OfferType::class),

                SelectFilter::make('company_id')
                    ->label(__('offers.fields.company'))
                    ->relationship('company', 'trade_name'),

                SelectFilter::make('status')
                    ->label(__('offers.fields.status'))
                    ->options(OfferStatus::class),
            ])
            ->recordActions([
                \Filament\Actions\Action::make('print')
                    ->label(__('offers.actions.print_preview'))
                    ->icon('heroicon-m-printer')
                    ->color('info')
                    ->iconButton()
                    ->tooltip(__('offers.actions.print_preview'))
                    ->url(fn ($record) => route('offers.print', $record))
                    ->openUrlInNewTab(),
                \Filament\Actions\Action::make('download')
                    ->label(__('offers.actions.download_pdf'))
                    ->icon('heroicon-m-arrow-down-tray')
                    ->color('success')
                    ->iconButton()
                    ->tooltip(__('offers.actions.download_pdf'))
                    ->url(fn ($record) => route('offers.pdf', $record))
                    ->openUrlInNewTab(),
                EditAction::make()
                    ->label(__('offers.actions.edit'))
                    ->iconButton()
                    ->tooltip(__('offers.actions.edit')),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->label(__('offers.actions.delete_selected')),
                ]),
            ])
            ->defaultSort('offer_date', 'desc');
    }
}
