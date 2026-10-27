<?php

namespace App\Filament\Resources\Owners\Tables;

use App\Enums\GeneralStatus;
use App\Services\PhoneService;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class OwnersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image')
                    ->label(__('owners.fields.image'))
                    ->circular()
                    ->disk('public')
                    ->getStateUsing(function ($record) {
                        if (! empty($record->image) && Storage::disk('public')->exists($record->image)) {
                            return $record->image;
                        }

                        return null;
                    })
                    ->defaultImageUrl(fn ($record) => 'https://ui-avatars.com/api/?name='.urlencode(mb_substr($record->user?->name ?? 'م', 0, 2)).'&background=1a4478&color=ffffff'),

                TextColumn::make('user.name')
                    ->label(__('owners.fields.name'))
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => ($record->user?->phone ? '📱 '.PhoneService::format($record->user->phone) : '').($record->user?->email ? ' | ✉️ '.$record->user->email : '')),

                TextColumn::make('company.trade_name')
                    ->label(__('owners.fields.company'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('national_id')
                    ->label(__('owners.fields.national_id'))
                    ->searchable(),

                TextColumn::make('ownership_percentage')
                    ->label(__('owners.fields.ownership_percentage'))
                    ->suffix('%')
                    ->sortable()
                    ->badge()
                    ->color('primary'),

                TextColumn::make('status')
                    ->label(__('owners.fields.status'))
                    ->badge(),
            ])
            ->filters([
                SelectFilter::make('company_id')
                    ->label(__('owners.fields.company'))
                    ->relationship('company', 'trade_name'),

                SelectFilter::make('status')
                    ->label(__('owners.fields.status'))
                    ->options(GeneralStatus::class),
            ])
            ->recordActions([
                EditAction::make()->label(__('employees.actions.edit')),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->label(__('employees.actions.delete_selected')),
                ]),
            ]);
    }
}
