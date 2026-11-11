<?php

namespace App\Services;

use App\Models\Employee;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;

class EmployeeServiceDurationService
{
    /**
     * Calculate days served from hire date to end date (termination date, contract end date, or today).
     */
    public static function calculateDaysServed(mixed $hireDate, mixed $endDate = null): ?int
    {
        if (empty($hireDate)) {
            return null;
        }

        try {
            $start = $hireDate instanceof Carbon ? $hireDate : Carbon::parse($hireDate);
            $end = ! empty($endDate)
                ? ($endDate instanceof Carbon ? $endDate : Carbon::parse($endDate))
                : Carbon::today();

            $diff = (int) $start->diffInDays($end, false);

            return $diff >= 0 ? $diff : 0;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Calculate formatted contract duration between hire date and contract end date.
     */
    public static function calculateContractDuration(mixed $hireDate, mixed $contractEndDate): ?string
    {
        if (empty($hireDate) || empty($contractEndDate)) {
            return null;
        }

        $days = static::calculateDaysServed($hireDate, $contractEndDate);

        return $days !== null ? static::formatDaysServed($days) : null;
    }

    /**
     * Format days count into human-readable Arabic string (years, months, days).
     */
    public static function formatDaysServed(int $days): string
    {
        if ($days === 0) {
            return '0 يوم';
        }

        $years = intdiv($days, 365);
        $remDays = $days % 365;
        $months = intdiv($remDays, 30);
        $finalDays = $remDays % 30;

        $parts = [];
        if ($years > 0) {
            $parts[] = $years.' '.($years === 1 ? 'سنة' : ($years === 2 ? 'سنتان' : ($years <= 10 ? 'سنوات' : 'سنة')));
        }
        if ($months > 0) {
            $parts[] = $months.' '.($months === 1 ? 'شهر' : ($months === 2 ? 'شهران' : ($months <= 10 ? 'أشهر' : 'شهر')));
        }
        if ($finalDays > 0 || empty($parts)) {
            $parts[] = $finalDays.' '.($finalDays === 1 ? 'يوم' : ($finalDays === 2 ? 'يومان' : ($finalDays <= 10 ? 'أيام' : 'يوم')));
        }

        return "{$days} يوم (".implode(' و ', $parts).')';
    }

    /**
     * Calculate how much time has passed since resignation/termination date.
     * Fulfills: "وعند البحث عنه مستقبلا يظهر لي تاريخ الاستقالة من اجل اعرف كم مر عليه لو اردت توظيفه من جديد"
     */
    public static function calculateTimeSinceTermination(mixed $terminationDate): ?string
    {
        if (empty($terminationDate)) {
            return null;
        }

        try {
            $date = $terminationDate instanceof Carbon ? $terminationDate : Carbon::parse($terminationDate);
            $today = Carbon::today();

            if ($date->isFuture()) {
                $daysUntil = (int) $today->diffInDays($date);

                return "متبقي {$daysUntil} يوم على موعد الاستقالة / الانتهاء";
            }

            $days = (int) $date->diffInDays($today);
            if ($days === 0) {
                return 'اليوم (تاريخ الاستقالة / الانقطاع اليوم)';
            }

            $years = intdiv($days, 365);
            $rem = $days % 365;
            $months = intdiv($rem, 30);
            $finalDays = $rem % 30;

            $parts = [];
            if ($years > 0) {
                $parts[] = $years.' '.($years === 1 ? 'سنة' : ($years === 2 ? 'سنتان' : ($years <= 10 ? 'سنوات' : 'سنة')));
            }
            if ($months > 0) {
                $parts[] = $months.' '.($months === 1 ? 'شهر' : ($months === 2 ? 'شهران' : ($months <= 10 ? 'أشهر' : 'شهر')));
            }
            if ($finalDays > 0 || empty($parts)) {
                $parts[] = $finalDays.' '.($finalDays === 1 ? 'يوم' : ($finalDays === 2 ? 'يومان' : ($finalDays <= 10 ? 'أيام' : 'يوم')));
            }

            return 'منذ '.implode(' و ', $parts)." ({$days} يوم مضت)";
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Get predefined termination reasons options.
     * Fulfills: "خيارات آلية ( استقالة - عدم الرغبة الموظف في مواصلة العمل - انتهاء العقد - عدم مباشرة العمل - غياب - أخرى ... الخ )"
     */
    public static function getTerminationReasons(): array
    {
        return [
            'resignation' => __('employees.termination_reasons.resignation'),
            'unwillingness_to_continue' => __('employees.termination_reasons.unwillingness_to_continue'),
            'contract_expired' => __('employees.termination_reasons.contract_expired'),
            'failure_to_report' => __('employees.termination_reasons.failure_to_report'),
            'absence' => __('employees.termination_reasons.absence'),
            'other' => __('employees.termination_reasons.other'),
        ];
    }

    /**
     * Synchronize duration fields reactively in Filament form.
     */
    public static function syncDurationFields(callable $get, callable $set, bool $syncContractDuration = false): void
    {
        $hire = $get('hire_date');
        $term = $get('termination_date');
        $contractEnd = $get('contract_end_date');
        $days = static::calculateDaysServed($hire, $term ?? $contractEnd);
        $set('days_served', $days !== null ? static::formatDaysServed($days) : null);
        $set('time_since_termination', static::calculateTimeSinceTermination($term));

        if ($syncContractDuration) {
            $set('contract_duration', static::calculateContractDuration($hire, $contractEnd));
        }
    }

    /**
     * Create the auto-calculated contract duration form field.
     */
    public static function makeContractDurationField(): TextInput
    {
        return TextInput::make('contract_duration')
            ->label(__('employees.fields.contract_duration'))
            ->readOnly()
            ->dehydrated(true)
            ->placeholder(__('employees.placeholders.auto_calculated'))
            ->formatStateUsing(function ($state, callable $get, $record) {
                $hire = $get('hire_date') ?? $record?->hire_date;
                $contractEnd = $get('contract_end_date') ?? $record?->contract_end_date;

                return static::calculateContractDuration($hire, $contractEnd) ?? $state;
            });
    }

    /**
     * Create the duration and termination form fields.
     */
    public static function makeFormFields(bool $autoContractDuration = false): array
    {
        return [
            DatePicker::make('hire_date')
                ->label(__('employees.fields.hire_date'))
                ->native(false)
                ->live()
                ->afterStateUpdated(fn ($state, callable $get, callable $set) => static::syncDurationFields($get, $set, $autoContractDuration)),

            DatePicker::make('contract_end_date')
                ->label(__('employees.fields.contract_end_date'))
                ->native(false)
                ->live()
                ->afterStateUpdated(fn ($state, callable $get, callable $set) => static::syncDurationFields($get, $set, $autoContractDuration)),

            DatePicker::make('termination_date')
                ->label(__('employees.fields.termination_date'))
                ->helperText('حدد تاريخ الاستقالة أو انتهاء الخدمة لحساب المدة تلقائياً')
                ->native(false)
                ->live()
                ->afterStateUpdated(fn ($state, callable $get, callable $set) => static::syncDurationFields($get, $set, $autoContractDuration)),

            TextInput::make('days_served')
                ->label(__('employees.fields.days_served'))
                ->readOnly()
                ->dehydrated(false)
                ->placeholder(__('employees.placeholders.auto_calculated'))
                ->formatStateUsing(function ($state, callable $get, $record) {
                    $hire = $get('hire_date') ?? $record?->hire_date;
                    $term = $get('termination_date') ?? $record?->termination_date;
                    $contractEnd = $get('contract_end_date') ?? $record?->contract_end_date;
                    $days = static::calculateDaysServed($hire, $term ?? $contractEnd);

                    return $days !== null ? static::formatDaysServed($days) : null;
                }),

            TextInput::make('time_since_termination')
                ->label(__('employees.fields.time_since_termination'))
                ->readOnly()
                ->dehydrated(false)
                ->placeholder(__('employees.placeholders.time_since_termination_placeholder'))
                ->formatStateUsing(function ($state, callable $get, $record) {
                    $term = $get('termination_date') ?? $record?->termination_date;

                    return static::calculateTimeSinceTermination($term);
                })
                ->visible(fn (callable $get, $record) => ! empty($get('termination_date') ?? $record?->termination_date)),

            Select::make('termination_reason')
                ->label(__('employees.fields.termination_reason'))
                ->options(static::getTerminationReasons())
                ->searchable()
                ->preload()
                ->live()
                ->nullable(),

            TextInput::make('termination_reason_other')
                ->label(__('employees.fields.termination_reason_other'))
                ->placeholder(__('employees.placeholders.specify_reason'))
                ->visible(fn (callable $get) => $get('termination_reason') === 'other')
                ->required(fn (callable $get) => $get('termination_reason') === 'other'),
        ];
    }

    /**
     * Create the table column for days served.
     */
    public static function makeDaysServedTableColumn(): TextColumn
    {
        return TextColumn::make('days_served')
            ->label(__('employees.fields.days_served'))
            ->state(function (Employee $record) {
                $days = static::calculateDaysServed(
                    $record->hire_date,
                    $record->termination_date ?? $record->contract_end_date
                );

                return $days !== null ? ($days.' '.__('employees.fields.days_served_unit')) : '—';
            })
            ->description(function (Employee $record) {
                if ($record->termination_date) {
                    $termStr = $record->termination_date->format('Y-m-d');
                    $elapsed = static::calculateTimeSinceTermination($record->termination_date);
                    $reasonLabel = null;
                    if (! empty($record->termination_reason)) {
                        $reasons = static::getTerminationReasons();
                        $reasonLabel = $reasons[$record->termination_reason] ?? $record->termination_reason;
                        if ($record->termination_reason === 'other' && ! empty($record->termination_reason_other)) {
                            $reasonLabel .= " ({$record->termination_reason_other})";
                        }
                    }
                    $parts = ["🛑 استقالة: {$termStr}"];
                    if ($elapsed) {
                        $parts[] = $elapsed;
                    }
                    if ($reasonLabel) {
                        $parts[] = "السبب: {$reasonLabel}";
                    }

                    return implode(' | ', $parts);
                }

                if ($record->contract_end_date && $record->contract_end_date->isPast()) {
                    $endStr = $record->contract_end_date->format('Y-m-d');
                    $elapsed = static::calculateTimeSinceTermination($record->contract_end_date);

                    return "⌛ انتهى العقد: {$endStr} ({$elapsed})";
                }

                if ($record->hire_date) {
                    $hireStr = $record->hire_date->format('Y-m-d');

                    return "🟢 على رأس العمل (عيّن: {$hireStr})";
                }

                return null;
            })
            ->searchable(query: function ($query, string $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('hire_date', 'like', "%{$search}%")
                        ->orWhere('termination_date', 'like', "%{$search}%")
                        ->orWhere('contract_end_date', 'like', "%{$search}%");
                });
            })
            ->toggleable();
    }

    /**
     * Create the table column for termination reason.
     */
    public static function makeTerminationReasonTableColumn(): TextColumn
    {
        return TextColumn::make('termination_reason')
            ->label(__('employees.fields.termination_reason'))
            ->state(function (Employee $record) {
                if (empty($record->termination_reason)) {
                    return '—';
                }
                $reasons = static::getTerminationReasons();

                return $reasons[$record->termination_reason] ?? $record->termination_reason;
            })
            ->badge()
            ->color(function (Employee $record) {
                return match ($record->termination_reason) {
                    'resignation', 'unwillingness_to_continue' => 'warning',
                    'contract_expired' => 'info',
                    'failure_to_report', 'absence' => 'danger',
                    default => 'gray',
                };
            })
            ->description(function (Employee $record) {
                if ($record->termination_reason === 'other' && ! empty($record->termination_reason_other)) {
                    return $record->termination_reason_other;
                }

                return null;
            })
            ->toggleable(isToggledHiddenByDefault: true);
    }

    /**
     * Create the table filter for termination reason.
     */
    public static function makeTerminationReasonTableFilter(): SelectFilter
    {
        return SelectFilter::make('termination_reason')
            ->label(__('employees.fields.termination_reason'))
            ->options(static::getTerminationReasons())
            ->query(function ($query, array $data) {
                if (! empty($data['value'])) {
                    $query->where('metadata->termination_reason', $data['value']);
                }
            });
    }
}

