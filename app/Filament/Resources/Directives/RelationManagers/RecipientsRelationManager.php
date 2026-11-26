<?php

namespace App\Filament\Resources\Directives\RelationManagers;

use App\Models\Directives\DirectiveRecipient;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class RecipientsRelationManager extends RelationManager
{
    protected static string $relationship = 'recipients';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('directives.models.recipient.plural');
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultPaginationPageOption(10)
            ->paginated([10, 25, 50, 100])
            ->modifyQueryUsing(fn ($query) => $query->with([
                'user:id,name',
                'employee.company:id,trade_name,trade_name_en',
                'employee.branch:id,name',
            ]))
            ->defaultSort('acknowledged_at', 'desc')
            ->columns([
                TextColumn::make('employee_name')
                    ->label(__('directives.fields.employee_name'))
                    ->getStateUsing(fn (DirectiveRecipient $record): string => $record->user?->name ?? '-')
                    ->searchable(query: fn ($query, string $search) => $query->whereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%"))),

                TextColumn::make('company_name')
                    ->label(__('directives.fields.company'))
                    ->getStateUsing(fn (DirectiveRecipient $record): string => $record->employee?->company?->trade_name ?: $record->employee?->company?->trade_name_en ?: '-'),

                TextColumn::make('branch_name')
                    ->label(__('directives.fields.branch'))
                    ->getStateUsing(fn (DirectiveRecipient $record): string => $record->employee?->branch?->name ?: '-'),

                TextColumn::make('status')
                    ->label(__('directives.fields.recipient_status'))
                    ->badge()
                    ->getStateUsing(fn (DirectiveRecipient $record): string => $record->acknowledged_at
                        ? __('directives.statuses.acknowledged')
                        : __('directives.statuses.pending'))
                    ->color(fn (DirectiveRecipient $record): string => $record->acknowledged_at ? 'success' : 'danger')
                    ->icon(fn (DirectiveRecipient $record): string => $record->acknowledged_at
                        ? 'heroicon-o-check-circle'
                        : 'heroicon-o-clock'),

                TextColumn::make('acknowledged_at')
                    ->label(__('directives.fields.acknowledged_at'))
                    ->dateTime('Y-m-d H:i:s')
                    ->placeholder('—')
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('acknowledged_at')
                    ->label(__('directives.fields.recipient_status'))
                    ->nullable()
                    ->trueLabel(__('directives.statuses.acknowledged'))
                    ->falseLabel(__('directives.statuses.pending')),
            ]);
    }
}
