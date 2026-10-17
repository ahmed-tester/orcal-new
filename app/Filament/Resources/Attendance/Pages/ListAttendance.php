<?php

namespace App\Filament\Resources\Attendance\Pages;

use App\Filament\Resources\Attendance\AttendanceResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Artisan;

class ListAttendance extends ListRecords
{
    protected static string $resource = AttendanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('markAbsent')
                ->label(__('attendance.actions.check_absent'))
                ->icon('heroicon-m-bolt')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading(__('attendance.actions.check_absent'))
                ->modalDescription(__('attendance.actions.check_absent_tooltip'))
                ->action(function () {
                    try {
                        $user = auth()->user();
                        $params = [];
                        if ($user && $user->branch_id && !$user->hasRole('Super Admin') && $user->user_type !== 'super_admin') {
                            $params['--branch_id'] = $user->branch_id;
                        }

                        Artisan::call('attendance:mark-absent', $params);

                        Notification::make()
                            ->title(__('attendance.notifications.absent_check_success'))
                            ->success()
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title(__('attendance.notifications.absent_check_failed', ['error' => $e->getMessage()]))
                            ->danger()
                            ->send();
                    }
                }),

            Action::make('simulateBiometric')
                ->label(__('attendance.actions.simulate_biometric'))
                ->icon('heroicon-m-finger-print')
                ->color('success')
                ->modalHeading(__('attendance.actions.simulate_biometric_heading'))
                ->modalDescription(__('attendance.actions.simulate_biometric_desc'))
                ->form([
                    \Filament\Forms\Components\Select::make('employee_id')
                        ->label(__('attendance.fields.employee'))
                        ->options(\App\Models\Employee::with('user')->get()->mapWithKeys(fn ($emp) => [$emp->id => $emp->user?->name ?? "موظف #{$emp->id}"]))
                        ->searchable()
                        ->preload()
                        ->required(),

                    \Filament\Forms\Components\Select::make('punch_type')
                        ->label(__('attendance.actions.punch_type'))
                        ->options([
                            'check_in' => __('attendance.actions.punch_types.check_in'),
                            'check_out' => __('attendance.actions.punch_types.check_out'),
                        ])
                        ->default('check_in')
                        ->required(),

                    \Filament\Forms\Components\TimePicker::make('punch_time')
                        ->label(__('attendance.actions.punch_time'))
                        ->default(now()->format('H:i:s'))
                        ->required(),
                ])
                ->action(function (array $data) {
                    try {
                        $employee = \App\Models\Employee::find($data['employee_id']);
                        $pin = $employee?->employee_number ?: (string)$data['employee_id'];
                        $rawStatus = ($data['punch_type'] === 'check_out') ? '1' : '0';
                        $dateStr = now()->format('Y-m-d');
                        $timeStr = "{$dateStr} {$data['punch_time']}";
                        $payload = "{$pin}\t{$timeStr}\t{$rawStatus}\t1\t0\t0";

                        // Ensure simulator device exists
                        \App\Models\AttendanceDevice::firstOrCreate(
                            ['code' => 'ZK-TEST-SIMULATOR'],
                            [
                                'company_id' => $employee?->company_id ?? 1,
                                'name' => 'جهاز بصمة تجريبي (F34 Lite)',
                                'serial_number' => 'ZK-TEST-SIMULATOR',
                                'model' => 'ZKTeco F34 Lite',
                                'status' => 'active',
                                'last_seen_at' => now(),
                            ]
                        );

                        $service = app(\App\Services\BiometricAttendanceService::class);
                        $count = $service->processAttLog('ZK-TEST-SIMULATOR', $payload);

                        Notification::make()
                            ->title("تمت محاكاة البصمة بنجاح وتسجيل {$count} حركة!")
                            ->body("الموظف: {$employee?->user?->name} | النوع: {$data['punch_type']} | الوقت: {$data['punch_time']}")
                            ->success()
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('حدث خطأ أثناء المحاكاة')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            CreateAction::make()->label(__('admin.add_new')),
        ];
    }
}
