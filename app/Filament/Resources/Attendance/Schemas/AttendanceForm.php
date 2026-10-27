<?php

namespace App\Filament\Resources\Attendance\Schemas;

use App\Enums\AttendanceSource;
use App\Enums\AttendanceStatus;
use App\Models\Employee;
use App\Services\AttendancePenaltyService;
use App\Services\CompanyFormService;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AttendanceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make(__('attendance.sections.entry_data'))
                    ->columns(3)
                    ->schema([
                        CompanyFormService::makeEmployeeSelect(
                            label: __('attendance.fields.employee'),
                            required: true,
                            saudiOnly: true,
                        )
                            ->live()
                            ->afterStateUpdated(fn (callable $set, callable $get) => static::calculateAttendanceMetrics($set, $get)),

                        DatePicker::make('date')
                            ->label(__('attendance.fields.date'))
                            ->default(now())
                            ->live()
                            ->afterStateUpdated(fn (callable $set, callable $get) => static::calculateAttendanceMetrics($set, $get))
                            ->required(),

                        Select::make('status')
                            ->label(__('attendance.fields.status'))
                            ->options(AttendanceStatus::class)
                            ->default(AttendanceStatus::PRESENT)
                            ->required(),

                        TimePicker::make('check_in')
                            ->label(__('attendance.fields.check_in'))
                            ->default('08:00')
                            ->seconds(false)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (callable $set, callable $get) => static::calculateAttendanceMetrics($set, $get)),

                        TimePicker::make('check_out')
                            ->label(__('attendance.fields.check_out'))
                            ->default('17:00')
                            ->seconds(false)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (callable $set, callable $get) => static::calculateAttendanceMetrics($set, $get)),

                        TextInput::make('late_hours')
                            ->label(__('attendance.fields.late_hours'))
                            ->numeric()
                            ->default(0)
                            ->dehydrateStateUsing(fn ($state) => (float) ($state ?? 0))
                            ->helperText('يتم احتساب دقائق التأخير آلياً بناءً على مواعيد الفرع وفترة السماح')
                            ->suffix(' '.__('attendance.units.minute')),

                        TextInput::make('overtime_hours')
                            ->label(__('attendance.fields.overtime_hours'))
                            ->numeric()
                            ->default(0)
                            ->dehydrateStateUsing(fn ($state) => (float) ($state ?? 0))
                            ->suffix(' '.__('attendance.units.minute')),

                        TextInput::make('total_hours')
                            ->label(__('attendance.fields.total_hours'))
                            ->numeric()
                            ->default(8.5)
                            ->dehydrateStateUsing(fn ($state) => (float) ($state ?? 0))
                            ->suffix(' '.__('attendance.units.hour')),

                        Select::make('check_in_source')
                            ->label(__('attendance.fields.source'))
                            ->options(AttendanceSource::class)
                            ->default(AttendanceSource::FINGERPRINT),

                        Select::make('device_id')
                            ->label(__('attendance.fields.device'))
                            ->relationship('device', 'name')
                            ->placeholder(__('attendance.placeholders.manual_no_device'))
                            ->searchable()
                            ->preload(),

                        Textarea::make('notes')
                            ->label(__('attendance.fields.notes'))
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    /**
     * Calculate late minutes, total hours, and overtime live.
     */
    public static function calculateAttendanceMetrics(callable $set, callable $get): void
    {
        $checkIn = $get('check_in');
        $checkOut = $get('check_out');
        $date = $get('date') ?? now()->toDateString();
        $employeeId = $get('employee_id');

        if (! $checkIn) {
            return;
        }

        $employee = $employeeId ? Employee::with('branch')->find($employeeId) : null;
        $branch = $employee?->branch;
        $branchSettings = AttendancePenaltyService::getBranchSettings($branch);

        $checkInStr = $branchSettings['check_in_time'] ?? '08:00';
        $toleranceMinutes = (int) ($branchSettings['late_grace_minutes'] ?? 15);

        try {
            $dateStr = Carbon::parse($date)->toDateString();
            $standardStartTime = Carbon::parse("{$dateStr} {$checkInStr}");
            $graceEndTime = $standardStartTime->copy()->addMinutes($toleranceMinutes);
            $actualCheckIn = Carbon::parse("{$dateStr} {$checkIn}");

            $lateMinutes = 0;
            if ($actualCheckIn->gt($graceEndTime)) {
                $lateMinutes = (int) round(abs((float) $actualCheckIn->diffInMinutes($standardStartTime)));
            }

            $set('late_hours', $lateMinutes);
            $set('overtime_hours', 0);

            // Automatically set status to Late or Present if currently in an active state
            $currentStatus = $get('status');
            if ($currentStatus instanceof AttendanceStatus) {
                $currentStatus = $currentStatus->value;
            }

            if (in_array($currentStatus, [AttendanceStatus::PRESENT->value, AttendanceStatus::LATE->value, null, 'present', 'late'])) {
                $set('status', $lateMinutes > 0 ? AttendanceStatus::LATE->value : AttendanceStatus::PRESENT->value);
            }

            // Calculate total hours and overtime if check_out is provided
            if ($checkOut) {
                $actualCheckOut = Carbon::parse("{$dateStr} {$checkOut}");
                if ($actualCheckOut->gt($actualCheckIn)) {
                    $totalMinutes = $actualCheckIn->diffInMinutes($actualCheckOut);
                    $totalHours = round($totalMinutes / 60, 2);
                    $set('total_hours', $totalHours);

                    $checkOutStr = $branchSettings['check_out_time'] ?? '16:00';
                    $standardEndTime = Carbon::parse("{$dateStr} {$checkOutStr}");
                    if ($actualCheckOut->gt($standardEndTime)) {
                        $overtimeMinutes = (int) round($actualCheckOut->diffInMinutes($standardEndTime));
                        $set('overtime_hours', $overtimeMinutes);
                    } else {
                        $set('overtime_hours', 0);
                    }
                }
            }
        } catch (\Throwable $e) {
            // Silently catch parsing issues
        }
    }
}
