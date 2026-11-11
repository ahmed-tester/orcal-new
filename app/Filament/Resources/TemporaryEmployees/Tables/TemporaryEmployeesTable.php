<?php

namespace App\Filament\Resources\TemporaryEmployees\Tables;

use App\Filament\Components\OrgSelect;
use App\Models\Employee;
use App\Services\EmployeeAgeService;
use App\Services\EmployeeServiceDurationService;
use App\Services\PhoneService;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class TemporaryEmployeesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->modifyQueryUsing(fn ($query) => $query->with([
                'user',
                'details',
                'company',
                'jobTitle',
                'department',
                'branch',
            ]))
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
                    ->description(function ($record) {
                        $parts = [];
                        if (! empty($record->metadata['name_en'])) {
                            $parts[] = '🔤 '.$record->metadata['name_en'];
                        }
                        if ($record->user?->phone) {
                            $parts[] = '📱 '.PhoneService::format($record->user->phone);
                        }
                        if ($record->user?->email) {
                            $parts[] = '✉️ '.$record->user->email;
                        }

                        return implode(' | ', $parts);
                    }),

                TextColumn::make('registration_source')
                    ->label('طريقة التسجيل')
                    ->state(function (Employee $record) {
                        $source = $record->metadata['registration_source'] ?? 'manual';

                        return match ($source) {
                            'from_owner' => 'جلب من المُلّاك',
                            'from_saudi_employee' => 'جلب من موظف سعودي',
                            'manual' => 'تسجيل حر',
                            default => 'تسجيل حر',
                        };
                    })
                    ->badge()
                    ->color(function (Employee $record) {
                        $source = $record->metadata['registration_source'] ?? 'manual';

                        return match ($source) {
                            'from_owner' => 'warning',
                            'from_saudi_employee' => 'info',
                            'manual' => 'success',
                            default => 'gray',
                        };
                    })
                    ->icon(function (Employee $record) {
                        $source = $record->metadata['registration_source'] ?? 'manual';

                        return match ($source) {
                            'from_owner' => 'heroicon-m-briefcase',
                            'from_saudi_employee' => 'heroicon-m-user',
                            'manual' => 'heroicon-m-pencil-square',
                            default => 'heroicon-m-pencil-square',
                        };
                    }),

                TextColumn::make('details.national_id')
                    ->label(__('employees.fields.national_id'))
                    ->placeholder('—')
                    ->searchable()
                    ->copyable()
                    ->icon('heroicon-m-identification'),

                EmployeeAgeService::makeTableColumn(),

                TextColumn::make('company.trade_name')
                    ->label(__('employees.fields.company'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('job_title')
                    ->label(__('employees.fields.job_title'))
                    ->state(fn ($record) => $record->metadata['job_title'] ?? $record->jobTitle?->name ?? '—')
                    ->badge()
                    ->color('info'),

                TextColumn::make('basic_salary')
                    ->label(__('employees.fields.basic_salary'))
                    ->money('SAR')
                    ->sortable(),

                EmployeeServiceDurationService::makeDaysServedTableColumn(),
                EmployeeServiceDurationService::makeTerminationReasonTableColumn(),

                TextColumn::make('status')
                    ->label(__('employees.fields.status'))
                    ->badge(),
            ])
            ->filters([
                OrgSelect::tableFilterCompany('company_id', __('employees.fields.company')),
                OrgSelect::tableFilterBranch('branch_id', __('employees.fields.branch')),

                SelectFilter::make('department_id')
                    ->label(__('employees.fields.department'))
                    ->relationship('department', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('registration_source')
                    ->label('طريقة التسجيل')
                    ->options([
                        'manual' => 'تسجيل حر',
                        'from_owner' => 'جلب من المُلّاك',
                        'from_saudi_employee' => 'جلب من موظف سعودي',
                    ])
                    ->query(function ($query, array $data) {
                        if (! empty($data['value'])) {
                            $query->where('metadata->registration_source', $data['value']);
                        }
                    }),

                SelectFilter::make('status')
                    ->label(__('employees.fields.status'))
                    ->options(__('employees.statuses')),

                EmployeeServiceDurationService::makeTerminationReasonTableFilter(),
            ])
            ->recordActions([
                EditAction::make()
                    ->label(__('employees.actions.edit')),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->label(__('employees.actions.delete_selected')),
                ]),
            ]);
    }
}

