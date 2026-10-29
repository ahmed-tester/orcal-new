<?php

namespace App\Filament\Resources\SaudiEmployees\Tables;

use App\Enums\UserType;
use App\Filament\Components\OrgSelect;
use App\Filament\Resources\ForeignEmployees\ForeignEmployeeResource;
use App\Filament\Resources\SaudiEmployees\SaudiEmployeeResource;
use App\Services\EmployeeAgeService;
use App\Services\PhoneService;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Lang;

class SaudiEmployeesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->modifyQueryUsing(fn ($query) => $query->with([
                'user',
                'details.guarantor',
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

                TextColumn::make('employee_type')
                    ->label(__('employees.fields.employee_type'))
                    ->state(function ($record) {
                        return $record->user?->user_type === UserType::SAUDI_EMPLOYEE
                            ? __('employees.fields.saudi_type')
                            : ($record->details?->nationality
                                ? (Lang::has('employees.nationalities.'.$record->details->nationality)
                                    ? __('employees.nationalities.'.$record->details->nationality)
                                    : $record->details->nationality)
                                : __('employees.fields.foreign_type'));
                    })
                    ->badge()
                    ->color(fn ($record) => $record->user?->user_type === UserType::SAUDI_EMPLOYEE ? 'success' : 'info')
                    ->icon(fn ($record) => $record->user?->user_type === UserType::SAUDI_EMPLOYEE ? 'heroicon-m-user' : 'heroicon-m-globe-alt'),

                TextColumn::make('id_or_iqama')
                    ->label(__('employees.fields.national_id').' / '.__('employees.fields.iqama_number'))
                    ->state(fn ($record) => $record->details?->national_id ?? $record->details?->iqama_number ?? '—')
                    ->searchable(query: function ($query, string $search) {
                        $query->whereHas('details', function ($q) use ($search) {
                            $q->where('national_id', 'like', "%{$search}%")
                                ->orWhere('iqama_number', 'like', "%{$search}%");
                        });
                    })
                    ->copyable()
                    ->icon('heroicon-m-identification')
                    ->description(function ($record) {
                        if ($record->user?->user_type === UserType::SAUDI_EMPLOYEE || empty($record->details?->iqama_expiry)) {
                            return null;
                        }
                        $expiry = $record->details->iqama_expiry;
                        $days = (int) Carbon::today()->diffInDays(Carbon::parse($expiry), false);

                        return match (true) {
                            $days < 0 => __('employees.fields.expiry_status.expired', ['days' => abs($days)]),
                            $days == 0 => __('employees.fields.expiry_status.today'),
                            default => __('employees.fields.expiry_status.remaining', ['days' => $days]),
                        };
                    }),

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
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('basic_salary')
                    ->label(__('employees.fields.basic_salary'))
                    ->money('SAR')
                    ->sortable(),

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

                SelectFilter::make('nationality')
                    ->label(__('employees.fields.nationality'))
                    ->options(__('employees.nationalities'))
                    ->query(function ($query, array $data) {
                        if (! empty($data['value'])) {
                            $query->whereHas('details', function ($q) use ($data) {
                                $q->where('nationality', $data['value']);
                            });
                        }
                    })
                    ->searchable()
                    ->preload(),

                SelectFilter::make('status')
                    ->label(__('employees.fields.status'))
                    ->options(__('employees.statuses')),
            ])
            ->recordActions([
                EditAction::make()
                    ->label(__('employees.actions.edit'))
                    ->url(fn ($record) => $record->user?->user_type === UserType::FOREIGN_EMPLOYEE
                        ? ForeignEmployeeResource::getUrl('edit', ['record' => $record])
                        : SaudiEmployeeResource::getUrl('edit', ['record' => $record])
                    ),

                Action::make('guarantor_report')
                    ->label(__('guarantors.single'))
                    ->icon('heroicon-o-shield-check')
                    ->color('info')
                    ->visible(fn ($record) => ! empty($record->details?->guarantor_id))
                    ->tooltip(__('guarantors.report.title'))
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
