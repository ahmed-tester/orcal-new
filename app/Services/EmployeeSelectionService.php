<?php

namespace App\Services;

use App\Enums\GeneralStatus;
use App\Enums\EmploymentType;
use App\Enums\UserType;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Owner;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Section;

class EmployeeSelectionService
{
    /**
     * Ensure owner has an associated Employee record so leaves can link to it.
     */
    public static function getEmployeeForOwner(Owner $owner): ?Employee
    {
        $employee = $owner->user?->employee;

        if (!$employee && $owner->user) {
            $employee = Employee::firstOrCreate(
                ['user_id' => $owner->user_id],
                [
                    'company_id' => $owner->company_id,
                    'status' => GeneralStatus::ACTIVE,
                    'employment_type' => EmploymentType::FULL_TIME,
                ]
            );

            if ($employee && !$employee->details) {
                $employee->details()->create([
                    'national_id' => $owner->national_id,
                    'nationality' => $owner->nationality,
                    'birth_date' => $owner->birth_date,
                ]);
            }
        }

        return $employee;
    }

    /**
     * Get employee options based on selection mode.
     */
    public static function getOptionsForMode(string $mode, ?int $companyId = null): array
    {
        if ($mode === 'by_company') {
            if (!$companyId) {
                return [];
            }

            return Employee::with(['user'])
                ->where('company_id', $companyId)
                ->get()
                ->mapWithKeys(function (Employee $record) {
                    $name = $record->user?->name ?? __('leaves.selection.employee_fallback', ['id' => $record->id]);
                    return [$record->id => $name];
                })
                ->toArray();
        }

        if ($mode === 'owners') {
            return Owner::with(['user.employee'])
                ->get()
                ->mapWithKeys(function (Owner $owner) {
                    $employee = static::getEmployeeForOwner($owner);
                    if (!$employee) {
                        return [];
                    }
                    $name = $owner->user?->name ?? __('leaves.selection.owner_fallback', ['id' => $owner->id]);
                    return [$employee->id => $name];
                })
                ->toArray();
        }

        if ($mode === 'saudi_employees') {
            $query = Employee::with(['user'])
                ->whereHas('user', fn ($q) => $q->whereIn('user_type', [UserType::SAUDI_EMPLOYEE, UserType::OWNER]));

            $user = auth()->user();
            if ($user && !$user->hasRole('Super Admin') && $user->user_type !== UserType::SUPER_ADMIN && $user->user_type !== UserType::OWNER && $user->branch_id) {
                $query->where('branch_id', $user->branch_id);
            }

            return $query->get()->mapWithKeys(function (Employee $record) {
                $name = $record->user?->name ?? __('leaves.selection.employee_fallback', ['id' => $record->id]);
                return [$record->id => $name];
            })->toArray();
        }

        // Mode: 'staff' (management and staff list)
        $query = Employee::with(['user']);
        $user = auth()->user();
        if ($user && !$user->hasRole('Super Admin') && $user->user_type !== UserType::SUPER_ADMIN && $user->user_type !== UserType::OWNER && $user->branch_id) {
            $query->where('branch_id', $user->branch_id);
        }

        return $query->get()->mapWithKeys(function (Employee $record) {
            $name = $record->user?->name ?? __('leaves.selection.employee_fallback', ['id' => $record->id]);
            return [$record->id => $name];
        })->toArray();
    }

    /**
     * Create the Employee Selection Section component.
     */
    public static function makeSelectionSection(): Section
    {
        return Section::make(__('leaves.sections.employee_selection'))
            ->description(__('leaves.selection.description'))
            ->icon('heroicon-o-user-group')
            ->columns(3)
            ->schema([
                ToggleButtons::make('employee_selection_mode')
                    ->label(__('leaves.selection.mode_label'))
                    ->options([
                        'staff' => __('leaves.selection.staff'),
                        'owners' => __('leaves.selection.owners'),
                        'saudi_employees' => __('leaves.selection.saudi_employees'),
                        'by_company' => __('leaves.selection.by_company'),
                    ])
                    ->icons([
                        'staff' => 'heroicon-m-user-group',
                        'owners' => 'heroicon-m-briefcase',
                        'saudi_employees' => 'heroicon-m-identification',
                        'by_company' => 'heroicon-m-building-office-2',
                    ])
                    ->colors([
                        'staff' => 'primary',
                        'owners' => 'info',
                        'saudi_employees' => 'success',
                        'by_company' => 'warning',
                    ])
                    ->default('staff')
                    ->inline()
                    ->grouped()
                    ->live()
                    ->columnSpanFull()
                    ->afterStateUpdated(function (callable $set) {
                        $set('employee_id', null);
                        $set('selected_company_id', null);
                    })
                    ->afterStateHydrated(function (callable $set, $record) {
                        if ($record && $record->employee) {
                            $user = $record->employee->user;
                            if ($user && ($user->isOwner() || $user->owner()->exists())) {
                                $set('employee_selection_mode', 'owners');
                            } elseif ($user && $user->user_type === UserType::SAUDI_EMPLOYEE) {
                                $set('employee_selection_mode', 'saudi_employees');
                            } elseif ($record->employee->company_id) {
                                $set('employee_selection_mode', 'by_company');
                                $set('selected_company_id', $record->employee->company_id);
                            } else {
                                $set('employee_selection_mode', 'staff');
                            }
                        }
                    }),

                Select::make('selected_company_id')
                    ->label(__('leaves.selection.company_label'))
                    ->placeholder(__('leaves.selection.select_company_placeholder'))
                    ->options(function () {
                        return Company::query()
                            ->orderBy('trade_name')
                            ->get()
                            ->mapWithKeys(fn (Company $c) => [$c->id => $c->trade_name ?: $c->name])
                            ->toArray();
                    })
                    ->searchable()
                    ->preload()
                    ->live()
                    ->dehydrated(false)
                    ->visible(fn (callable $get) => $get('employee_selection_mode') === 'by_company')
                    ->columnSpan(fn (callable $get) => 1)
                    ->afterStateUpdated(function (callable $set) {
                        $set('employee_id', null);
                    })
                    ->afterStateHydrated(function (callable $set, $record) {
                        if ($record && $record->employee && $record->employee->company_id) {
                            $set('selected_company_id', $record->employee->company_id);
                        }
                    }),

                Select::make('employee_id')
                    ->label(function (callable $get) {
                        return match ($get('employee_selection_mode')) {
                            'owners' => __('leaves.selection.select_owner'),
                            'saudi_employees' => __('leaves.selection.select_saudi_employee'),
                            'by_company' => __('leaves.selection.select_company_employee'),
                            default => __('leaves.selection.select_staff'),
                        };
                    })
                    ->placeholder(function (callable $get) {
                        if ($get('employee_selection_mode') === 'by_company' && empty($get('selected_company_id'))) {
                            return __('leaves.selection.select_company_first');
                        }
                        return __('leaves.selection.search_placeholder');
                    })
                    ->disabled(fn (callable $get) => $get('employee_selection_mode') === 'by_company' && empty($get('selected_company_id')))
                    ->columnSpan(fn (callable $get) => $get('employee_selection_mode') === 'by_company' ? 2 : 3)
                    ->searchable()
                    ->preload()
                    ->required()
                    ->live()
                    ->options(function (callable $get) {
                        $mode = $get('employee_selection_mode') ?? 'staff';
                        $companyId = $get('selected_company_id');
                        return static::getOptionsForMode($mode, $companyId);
                    })
                    ->afterStateUpdated(function ($state, callable $get, callable $set, $record) {
                        $typeId = $get('leave_type_id');
                        if ($state) {
                            $calc = \App\Services\LeaveBalanceService::calculateBalance($state, $typeId, $record?->id);
                            $set('available_days_display', $calc['available_days']);
                        } else {
                            $set('available_days_display', null);
                        }
                    }),
            ]);
    }
}
