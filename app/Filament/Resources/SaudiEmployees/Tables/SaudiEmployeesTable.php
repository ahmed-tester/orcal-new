<?php

namespace App\Filament\Resources\SaudiEmployees\Tables;

use App\Services\EmployeeAgeService;
use App\Services\PhoneService;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SaudiEmployeesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('employee_number')
                    ->label(__('employees.fields.employee_number'))
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('gray'),

                TextColumn::make('user.name')
                    ->label(__('employees.fields.name'))
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => ($record->user?->phone ? '📱 ' . PhoneService::format($record->user->phone) : '') . ($record->user?->email ? ' | ✉️ ' . $record->user->email : '')),

                TextColumn::make('details.national_id')
                    ->label(__('employees.fields.national_id'))
                    ->searchable()
                    ->copyable()
                    ->icon('heroicon-m-identification'),

                EmployeeAgeService::makeTableColumn(),

                TextColumn::make('company.trade_name')
                    ->label(__('employees.fields.company'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('jobTitle.name')
                    ->label(__('employees.fields.job_title'))
                    ->searchable()
                    ->badge()
                    ->color('info'),

                TextColumn::make('basic_salary')
                    ->label(__('employees.fields.basic_salary'))
                    ->money('SAR')
                    ->sortable(),

                TextColumn::make('hire_date')
                    ->label(__('employees.fields.hire_date'))
                    ->date('Y-m-d')
                    ->sortable(),

                TextColumn::make('status')
                    ->label(__('employees.fields.status'))
                    ->badge(),
            ])
            ->filters([
                \App\Filament\Components\OrgSelect::tableFilterCompany('company_id', __('employees.fields.company')),
                \App\Filament\Components\OrgSelect::tableFilterBranch('branch_id', __('employees.fields.branch')),

                SelectFilter::make('department_id')
                    ->label(__('employees.fields.department'))
                    ->relationship('department', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('status')
                    ->label(__('employees.fields.status'))
                    ->options(__('employees.statuses')),
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
