<?php

namespace App\Filament\Resources\Mail\MailTrustedDomains\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MailTrustedDomainsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('domain_pattern')
                    ->label(__('mail_inbox.fields.domain_pattern'))
                    ->badge()
                    ->color('success')
                    ->copyable()
                    ->searchable(),
                TextColumn::make('organization_name')
                    ->label(__('mail_inbox.fields.organization_name'))
                    ->searchable()
                    ->placeholder('-'),
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
