<?php

namespace App\Filament\Resources\Directives\Tables;

use App\Models\Directive;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class DirectivesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultPaginationPageOption(10)
            ->paginated([10, 25, 50, 100])
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('title')
                    ->label(__('directives.fields.title'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (Directive $record): string => $record->category?->localizedName() ?? ''),

                TextColumn::make('target_type')
                    ->label(__('directives.fields.target_type'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => __("directives.targets.{$state}"))
                    ->color(fn (string $state): string => match ($state) {
                        Directive::TARGET_ALL      => 'info',
                        Directive::TARGET_SELECTED => 'warning',
                        default                    => 'gray',
                    }),

                TextColumn::make('acknowledgment_status')
                    ->label(__('directives.fields.acknowledgment_status'))
                    ->badge()
                    ->getStateUsing(fn (Directive $record): string => $record->acknowledgmentStatusMeta()['label'])
                    ->color(fn (Directive $record): string => $record->acknowledgmentStatusMeta()['color'])
                    ->icon(fn (Directive $record): string => $record->acknowledgmentStatusMeta()['icon']),

                TextColumn::make('acknowledgment_progress')
                    ->label(__('directives.fields.acknowledgment_progress'))
                    ->getStateUsing(fn (Directive $record): string => $record->acknowledgmentStatusMeta()['progress'])
                    ->color('gray'),

                TextColumn::make('creator.name')
                    ->label(__('directives.fields.created_by'))
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label(__('directives.fields.is_active'))
                    ->boolean()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label(__('directives.fields.created_at'))
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label(__('directives.fields.is_active')),

                SelectFilter::make('target_type')
                    ->label(__('directives.fields.target_type'))
                    ->options([
                        Directive::TARGET_ALL      => __('directives.targets.all'),
                        Directive::TARGET_SELECTED => __('directives.targets.selected'),
                    ]),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
            ]);
    }
}
