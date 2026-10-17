<?php

namespace App\Filament\Resources\WeeklyNotes\Tables;

use App\Enums\WeeklyNoteStatus;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class WeeklyNotesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('voucher_number')
                    ->label(__('weekly_notes.fields.voucher_number'))
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('gray'),

                TextColumn::make('note_date')
                    ->label(__('weekly_notes.fields.note_date'))
                    ->date()
                    ->sortable(),

                TextColumn::make('company.trade_name')
                    ->label(__('weekly_notes.fields.company'))
                    ->badge()
                    ->searchable()
                    ->sortable(),

                TextColumn::make('title')
                    ->label(__('weekly_notes.fields.title'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->wrap(),

                TextColumn::make('assignedEmployee.user.name')
                    ->label(__('weekly_notes.fields.assigned_to'))
                    ->placeholder('-')
                    ->searchable(),

                TextColumn::make('status')
                    ->label(__('weekly_notes.fields.status'))
                    ->badge(),
            ])
            ->filters([
                SelectFilter::make('company_id')
                    ->label(__('weekly_notes.fields.company'))
                    ->relationship('company', 'trade_name'),

                SelectFilter::make('status')
                    ->label(__('weekly_notes.fields.status'))
                    ->options(collect(WeeklyNoteStatus::cases())->mapWithKeys(fn (WeeklyNoteStatus $status) => [$status->value => $status->getLabel()])->toArray()),
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
