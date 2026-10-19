<?php

namespace App\Filament\Resources\Leaves\Schemas;

use App\Enums\LeaveStatus;
use App\Models\LeaveType;
use App\Services\EmployeeSelectionService;
use App\Services\LeaveBalanceService;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class LeaveForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                // 1. تحديد طريقة اختيار الموظف والمصدر
                EmployeeSelectionService::makeSelectionSection(),

                // 2. بيانات طلب الإجازة
                Section::make(__('leaves.sections.request_data'))
                    ->columns(3)
                    ->schema([
                        Select::make('leave_type_id')
                            ->label(__('leaves.fields.leave_type'))
                            ->options(fn () => LeaveBalanceService::getLeaveTypeOptions())
                            ->default(fn () => LeaveBalanceService::getDedicatedAnnualLeaveTypeId())
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (callable $get, callable $set, $record) {
                                $empId = $get('employee_id');
                                $typeId = $get('leave_type_id');
                                if ($empId) {
                                    $calc = LeaveBalanceService::calculateBalance($empId, $typeId, $record?->id);
                                    $set('available_days_display', $calc['available_days']);
                                }
                            }),

                        Select::make('status')
                            ->label(__('leaves.fields.status'))
                            ->options(LeaveStatus::class)
                            ->default(LeaveStatus::PENDING)
                            ->required()
                            ->live(),

                        TextInput::make('available_days_display')
                            ->label(__('leaves.fields.available_days'))
                            ->readOnly()
                            ->dehydrated(false)
                            ->suffix(' ' . __('leaves.units.day'))
                            ->placeholder(__('leaves.fields.select_employee_first'))
                            ->formatStateUsing(function ($state, callable $get, $record) {
                                $empId = $get('employee_id');
                                $typeId = $get('leave_type_id');
                                if ($empId) {
                                    $calc = LeaveBalanceService::calculateBalance($empId, $typeId, $record?->id);
                                    return $calc['available_days'];
                                }
                                return $state;
                            })
                            ->afterStateHydrated(function ($component, callable $get, $record) {
                                $empId = $get('employee_id');
                                $typeId = $get('leave_type_id');
                                if ($empId) {
                                    $calc = LeaveBalanceService::calculateBalance($empId, $typeId, $record?->id);
                                    $component->state($calc['available_days']);
                                }
                            })
                            ->helperText(function (callable $get, $record) {
                                $empId = $get('employee_id');
                                $typeId = $get('leave_type_id');
                                if (!$empId) {
                                    return null;
                                }
                                $calc = LeaveBalanceService::calculateBalance($empId, $typeId, $record?->id);
                                return $calc['summary_text'];
                            }),

                        DatePicker::make('start_date')
                            ->label(__('leaves.fields.start_date'))
                            ->default(now())
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn (callable $get, callable $set) => static::syncEndDate($get, $set)),

                        DatePicker::make('end_date')
                            ->label(__('leaves.fields.end_date'))
                            ->default(now())
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn (callable $get, callable $set) => static::syncDays($get, $set)),

                        TextInput::make('days')
                            ->label(__('leaves.fields.days'))
                            ->numeric()
                            ->minValue(1)
                            ->default(1)
                            ->dehydrateStateUsing(fn ($state) => max(1, (int) ($state ?: 1)))
                            ->suffix(' ' . __('leaves.units.day'))
                            ->required()
                            ->live(debounce: 500)
                            ->afterStateUpdated(fn (callable $get, callable $set) => static::syncEndDate($get, $set))
                            ->rules([
                                fn (callable $get, $record) => function (string $attribute, $value, \Closure $fail) use ($get, $record) {
                                    $empId = $get('employee_id');
                                    $typeId = $get('leave_type_id');
                                    if ($empId && $value) {
                                        $calc = LeaveBalanceService::calculateBalance($empId, $typeId, $record?->id);
                                        if ($value > $calc['available_days'] && !LeaveBalanceService::canUserOverrideLeaveBalance()) {
                                            $fail(__('leaves.validation.balance_exceeded_no_override', [
                                                'days' => $value,
                                                'available' => $calc['available_days'],
                                            ]));
                                        }
                                    }
                                },
                            ])
                            ->helperText(function (callable $get, $record) {
                                $empId = $get('employee_id');
                                $typeId = $get('leave_type_id');
                                $days = (float) ($get('days') ?? 0);
                                if (!$empId || !$days) {
                                    return null;
                                }
                                $calc = LeaveBalanceService::calculateBalance($empId, $typeId, $record?->id);
                                if ($days > $calc['available_days']) {
                                    if (LeaveBalanceService::canUserOverrideLeaveBalance()) {
                                        $msg = __('leaves.notifications.manager_override_warning', [
                                            'days' => $days,
                                            'available' => $calc['available_days'],
                                        ]);
                                        return new HtmlString("<span style='color: #d97706; font-weight: 700;'>{$msg}</span>");
                                    } else {
                                        $msg = __('leaves.notifications.balance_exceeded_warning', [
                                            'available' => $calc['available_days'],
                                        ]);
                                        return new HtmlString("<span style='color: #dc2626; font-weight: 700;'>{$msg}</span>");
                                    }
                                }
                                return null;
                            }),

                        Textarea::make('reason')
                            ->label(__('leaves.fields.reason'))
                            ->placeholder(__('leaves.fields.reason_placeholder'))
                            ->rows(2)
                            ->columnSpan(2),

                        TextInput::make('rejection_reason')
                            ->label(__('leaves.fields.rejection_reason'))
                            ->placeholder(__('leaves.fields.rejection_reason_placeholder'))
                            ->visible(fn (callable $get) => $get('status') === 'rejected')
                            ->columnSpan(1),

                        FileUpload::make('attachment_file')
                            ->label(__('leaves.fields.attachments'))
                            ->disk('public')
                            ->directory('leaves')
                            ->acceptedFileTypes(\App\Models\Setting::getAllowedUploadMimeTypes())
                            ->maxSize(\App\Models\Setting::getMaxUploadSizeKb())
                            ->openable()
                            ->downloadable()
                            ->columnSpanFull(),
                    ]),

                // 3. بيانات العودة ومباشرة العمل (في وضع التعديل فقط)
                Section::make(__('leaves.sections.return_data'))
                    ->description(__('leaves.sections.return_data_desc'))
                    ->icon('heroicon-o-arrow-left-on-rectangle')
                    ->visible(fn (string $operation) => $operation === 'edit')
                    ->columns(2)
                    ->schema([
                        DatePicker::make('actual_return_date')
                            ->label(__('leaves.fields.actual_return_date'))
                            ->native(false)
                            ->live()
                            ->placeholder(__('leaves.fields.actual_return_date_placeholder')),

                        TextInput::make('return_difference_info')
                            ->label(__('leaves.fields.return_diff_info'))
                            ->disabled()
                            ->dehydrated(false)
                            ->afterStateHydrated(function (callable $set, $record) {
                                if ($record && $record->actual_return_date && $record->end_date) {
                                    $diff = $record->end_date->diffInDays($record->actual_return_date, false);
                                    $unit = abs($diff) == 1 ? __('leaves.units.day') : __('leaves.units.days');
                                    if ($diff < 0) {
                                        $set('return_difference_info', __('leaves.return_statuses.early', ['days' => abs($diff), 'unit' => $unit]) . ' ✓');
                                    } elseif ($diff === 0) {
                                        $set('return_difference_info', __('leaves.return_statuses.on_time') . ' ✓');
                                    } else {
                                        $set('return_difference_info', __('leaves.return_statuses.late', ['days' => $diff, 'unit' => $unit]) . ' ⚠️');
                                    }
                                } elseif ($record && $record->end_date && now()->startOfDay()->greaterThan($record->end_date)) {
                                    $d = $record->end_date->diffInDays(now()->startOfDay());
                                    $unit = $d == 1 ? __('leaves.units.day') : __('leaves.units.days');
                                    $set('return_difference_info', __('leaves.return_statuses.overdue', ['days' => $d, 'unit' => $unit]) . ' ⚠️');
                                } else {
                                    $set('return_difference_info', __('leaves.fields.return_diff_pending'));
                                }
                            })
                            ->live()
                            ->formatStateUsing(function (callable $get) {
                                $actual = $get('actual_return_date');
                                $end = $get('end_date');
                                if ($actual && $end) {
                                    try {
                                        $a = Carbon::parse($actual)->startOfDay();
                                        $e = Carbon::parse($end)->startOfDay();
                                        $diff = $e->diffInDays($a, false);
                                        $unit = abs($diff) == 1 ? __('leaves.units.day') : __('leaves.units.days');
                                        if ($diff < 0) {
                                            return __('leaves.return_statuses.early', ['days' => abs($diff), 'unit' => $unit]) . ' ✓';
                                        } elseif ($diff === 0) {
                                            return __('leaves.return_statuses.on_time') . ' ✓';
                                        } else {
                                            return __('leaves.return_statuses.late', ['days' => $diff, 'unit' => $unit]) . ' ⚠️';
                                        }
                                    } catch (\Exception) {}
                                }
                                return __('leaves.fields.return_diff_pending');
                            }),
                    ]),
            ]);
    }

    /**
     * Synchronize the leave end date based on start date and requested days.
     */
    public static function syncEndDate(callable $get, callable $set): void
    {
        $endDate = LeaveBalanceService::calculateEndDate($get('start_date'), $get('days'));
        if ($endDate) {
            $set('end_date', $endDate);
        } elseif ($get('start_date') && $get('end_date')) {
            static::syncDays($get, $set);
        }
    }

    /**
     * Synchronize the requested days count based on start and end dates.
     */
    public static function syncDays(callable $get, callable $set): void
    {
        $days = LeaveBalanceService::calculateDays($get('start_date'), $get('end_date'));
        if ($days !== null) {
            $set('days', $days);
        }
    }
}

