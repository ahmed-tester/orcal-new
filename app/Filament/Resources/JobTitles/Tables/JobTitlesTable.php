<?php

namespace App\Filament\Resources\JobTitles\Tables;

use App\Enums\JobTitleScope;
use App\Models\JobTitle;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class JobTitlesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('job_code')
                    ->label(__('organization.job_titles.fields.job_code'))
                    ->searchable()
                    ->badge()
                    ->color('gray'),

                TextColumn::make('name')
                    ->label(__('organization.job_titles.fields.name'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('department.name')
                    ->label(__('organization.job_titles.fields.department'))
                    ->placeholder('—')
                    ->searchable()
                    ->badge()
                    ->color('info'),

                TextColumn::make('parent.name')
                    ->label(__('organization.job_titles.fields.parent'))
                    ->placeholder('-'),

                TextColumn::make('company_display_name')
                    ->label(__('organization.job_titles.fields.company'))
                    ->badge(fn (JobTitle $record) => $record->isGlobal())
                    ->color(fn (JobTitle $record) => $record->isGlobal() ? 'success' : null)
                    ->searchable(query: function ($query, string $search) {
                        $query->whereHas('company', fn ($q) => $q->where('trade_name', 'like', "%{$search}%"))
                            ->orWhere(function ($q) use ($search) {
                                if (str_contains('عام للمجموعة بالكامل', $search) || str_contains('عام', $search)) {
                                    $q->where('is_global', true)->orWhereNull('company_id');
                                }
                            });
                    })
                    ->sortable(),

                TextColumn::make('sort_order')
                    ->label(__('organization.job_titles.fields.sort_order'))
                    ->sortable(),

                TextColumn::make('status')
                    ->label(__('organization.job_titles.fields.status'))
                    ->badge(),
            ])
            ->filters([
                SelectFilter::make('scope')
                    ->label('نطاق المسمى')
                    ->options(JobTitleScope::class)
                    ->query(function ($query, array $data) {
                        if (! empty($data['value'])) {
                            $query->forScope($data['value']);
                        }
                    }),

                SelectFilter::make('company_id')
                    ->label(__('organization.job_titles.fields.company'))
                    ->relationship('company', 'trade_name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('department_id')
                    ->label(__('organization.job_titles.fields.department'))
                    ->relationship('department', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('status')
                    ->label(__('organization.job_titles.fields.status'))
                    ->options(\App\Enums\GeneralStatus::class),
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
