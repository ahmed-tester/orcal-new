<?php

namespace App\Filament\Resources\ForeignEmployees\Tables;

use App\Services\EmployeeAgeService;
use App\Services\PhoneService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ForeignEmployeesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with([
                'user',
                'details.guarantor',
                'company',
                'jobTitle',
                'department',
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
                    ->description(fn ($record) => ($record->user?->phone ? '📱 ' . PhoneService::format($record->user->phone) : '') . ($record->user?->email ? ' | ✉️ ' . $record->user->email : '')),

                TextColumn::make('details.nationality')
                    ->label(__('employees.fields.nationality'))
                    ->searchable()
                    ->badge()
                    ->formatStateUsing(function (?string $state): string {
                        if (!$state) return '—';
                        return \Illuminate\Support\Facades\Lang::has('employees.nationalities.' . $state)
                            ? __('employees.nationalities.' . $state)
                            : $state;
                    })
                    ->color('info'),

                EmployeeAgeService::makeTableColumn(),

                TextColumn::make('details.iqama_number')
                    ->label(__('employees.fields.iqama_number'))
                    ->searchable()
                    ->copyable()
                    ->icon('heroicon-m-identification'),

                TextColumn::make('details.iqama_expiry')
                    ->label(__('employees.fields.iqama_expiry'))
                    ->date('Y-m-d')
                    ->sortable()
                    ->badge()
                    ->color(function ($state) {
                        if (!$state) return 'gray';
                        $days = (int) \Carbon\Carbon::today()->diffInDays(\Carbon\Carbon::parse($state), false);
                        return match (true) {
                            $days < 0 || $days <= 30 => 'danger',
                            $days <= 90              => 'warning',
                            default                  => 'success',
                        };
                    })
                    ->icon(function ($state) {
                        if (!$state) return null;
                        $days = (int) \Carbon\Carbon::today()->diffInDays(\Carbon\Carbon::parse($state), false);
                        return match (true) {
                            $days < 0   => 'heroicon-m-x-circle',
                            $days <= 30 => 'heroicon-m-exclamation-triangle',
                            $days <= 90 => 'heroicon-m-clock',
                            default     => 'heroicon-m-check-circle',
                        };
                    })
                    ->description(function ($state) {
                        if (!$state) return null;
                        $days = (int) \Carbon\Carbon::today()->diffInDays(\Carbon\Carbon::parse($state), false);
                        return match (true) {
                            $days < 0  => 'منتهية منذ ' . abs($days) . ' يوم',
                            $days == 0 => 'تنتهي اليوم',
                            default    => 'متبقي ' . $days . ' يوم',
                        };
                    }),

                TextColumn::make('company.trade_name')
                    ->label(__('employees.fields.company'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('jobTitle.name')
                    ->label(__('employees.fields.job_title'))
                    ->searchable(),

                TextColumn::make('details.guarantor.name')
                    ->label(__('guarantors.single'))
                    ->placeholder('—')
                    ->badge()
                    ->color('info')
                    ->url(fn ($record) => $record->details?->guarantor_id ? route('filament.admin.pages.general-reports', [
                        'reportType' => 'guarantors',
                        'guarantorId' => $record->details->guarantor_id,
                    ]) : null)
                    ->openUrlInNewTab()
                    ->toggleable(),

                TextColumn::make('basic_salary')
                    ->label(__('employees.fields.basic_salary'))
                    ->money('SAR')
                    ->sortable(),

                TextColumn::make('status')
                    ->label(__('employees.fields.status'))
                    ->badge(),
            ])
            ->filters([
                SelectFilter::make('nationality')
                    ->label(__('employees.fields.nationality'))
                    ->options(__('employees.nationalities'))
                    ->query(function ($query, array $data) {
                        if (!empty($data['value'])) {
                            $query->whereHas('details', function ($q) use ($data) {
                                $q->where('nationality', $data['value']);
                            });
                        }
                    })
                    ->searchable()
                    ->preload(),

                SelectFilter::make('guarantor_id')
                    ->label(__('guarantors.filters.filter_by_guarantor'))
                    ->options(function () {
                        $companyIds = \App\Services\CompanyFormService::getAuthorizedCompanyIds();
                        $query = \App\Models\Guarantor::query()->where('status', 'active')->whereNotNull('name');
                        if (!empty($companyIds)) {
                            $query->whereIn('company_id', $companyIds);
                        }
                        return $query->pluck('name', 'id');
                    })
                    ->query(function ($query, array $data) {
                        if (!empty($data['value'])) {
                            $query->whereHas('details', function ($q) use ($data) {
                                $q->where('guarantor_id', $data['value']);
                            });
                        }
                    })
                    ->searchable()
                    ->preload(),

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
                Action::make('guarantor_report')
                    ->label(__('guarantors.single'))
                    ->icon('heroicon-o-shield-check')
                    ->color('info')
                    ->visible(fn ($record) => !empty($record->details?->guarantor_id))
                    ->tooltip('عرض تقرير الضامن والمكفولين')
                    ->url(fn ($record) => route('filament.admin.pages.general-reports', [
                        'reportType' => 'guarantors',
                        'guarantorId' => $record->details?->guarantor_id,
                    ]))
                    ->openUrlInNewTab(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->label(__('employees.actions.delete_selected')),
                ]),
            ]);
    }
}
