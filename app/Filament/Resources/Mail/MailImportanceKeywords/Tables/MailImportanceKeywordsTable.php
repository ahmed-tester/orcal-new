<?php

namespace App\Filament\Resources\Mail\MailImportanceKeywords\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MailImportanceKeywordsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('weight', 'desc')
            ->columns([
                TextColumn::make('keyword')
                    ->label(__('mail_inbox.fields.keyword'))
                    ->weight('semibold')
                    ->icon('heroicon-m-bookmark')
                    ->copyable()
                    ->searchable(),
                TextColumn::make('weight')
                    ->label(__('mail_inbox.fields.weight'))
                    ->badge()
                    ->color('info')
                    ->sortable(),
                TextColumn::make('company.trade_name')
                    ->label(__('mail_inbox.fields.company'))
                    ->placeholder(__('mail_inbox.rules.global_rule')),
                IconColumn::make('is_active')
                    ->label(__('mail_inbox.fields.is_active'))
                    ->boolean()
                    ->trueColor('success'),
                TextColumn::make('created_at')
                    ->label(__('mail_inbox.fields.created_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
