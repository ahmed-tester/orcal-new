<?php

namespace App\Filament\Resources\SaudiEmployees\Pages;

use App\Filament\Actions\EmployeePerformanceAction;
use App\Filament\Resources\SaudiEmployees\SaudiEmployeeResource;
use App\Models\User;
use App\Services\EmployeeAgeService;
use App\Services\Payroll\EmployeeCompensationEligibilityService;
use App\Services\EmployeeIdentityService;
use App\Services\Payroll\EmployeeSalaryProfileService;
use App\Services\PersonCapacityService;
use App\Services\PhoneService;
use App\Services\Payroll\SaudiCompensationGrantService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class EditSaudiEmployee extends EditRecord
{
    protected static string $resource = SaudiEmployeeResource::class;

    /**
     * @var array<string, mixed>
     */
    protected array $pendingSalarySettings = [];

    protected function getHeaderActions(): array
    {
        return [
            Action::make('manageCompensationEligibility')
                ->label(__('employees.compensation_eligibility.action_label'))
                ->icon('heroicon-o-shield-check')
                ->color('warning')
                ->visible(fn (): bool => EmployeeCompensationEligibilityService::canManage(auth()->user(), $this->getRecord()))
                ->authorize(fn (): bool => EmployeeCompensationEligibilityService::canManage(auth()->user(), $this->getRecord()))
                ->modalHeading(__('employees.compensation_eligibility.modal_heading'))
                ->modalDescription(__('employees.compensation_eligibility.modal_description'))
                ->modalWidth('lg')
                ->form([
                    Placeholder::make('current_eligibility_status')
                        ->label(__('employees.compensation_eligibility.current_status'))
                        ->content(fn (): string => EmployeeCompensationEligibilityService::isCurrentlyEligible($this->getRecord())
                            ? __('employees.compensation_eligibility.status_eligible')
                            : __('employees.compensation_eligibility.status_not_eligible')),

                    Radio::make('decision')
                        ->label(__('employees.compensation_eligibility.decision'))
                        ->options([
                            'enable' => __('employees.compensation_eligibility.decisions.enable'),
                            'revoke' => __('employees.compensation_eligibility.decisions.revoke'),
                        ])
                        ->default(fn (): string => EmployeeCompensationEligibilityService::isCurrentlyEligible($this->getRecord()) ? 'revoke' : 'enable')
                        ->inline()
                        ->required(),

                    DatePicker::make('effective_date')
                        ->label(__('employees.compensation_eligibility.effective_date'))
                        ->default(now()->toDateString())
                        ->required(),

                    Textarea::make('reason')
                        ->label(__('employees.compensation_eligibility.reason'))
                        ->rows(3)
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $actor = auth()->user();
                    if (! $actor) {
                        return;
                    }

                    EmployeeCompensationEligibilityService::setEligibility(
                        actor: $actor,
                        employee: $this->getRecord(),
                        isEligible: ($data['decision'] ?? 'revoke') === 'enable',
                        effectiveDate: (string) $data['effective_date'],
                        reason: (string) $data['reason'],
                    );

                    Notification::make()
                        ->title(__('employees.compensation_eligibility.saved_notification'))
                        ->success()
                        ->send();
                }),

            EmployeePerformanceAction::make(SaudiEmployeeResource::class)->record($this->getRecord())->color('gray'),

            Action::make('grantCompensationMinutes')
                ->label(__('employees.compensation_grants.action_label'))
                ->icon('heroicon-o-clock')
                ->color('success')
                ->visible(fn (): bool => EmployeeCompensationEligibilityService::canManage(auth()->user(), $this->getRecord()))
                ->authorize(fn (): bool => EmployeeCompensationEligibilityService::canManage(auth()->user(), $this->getRecord()))
                ->modalHeading(__('employees.compensation_grants.modal_heading'))
                ->modalDescription(__('employees.compensation_grants.modal_description'))
                ->modalWidth('lg')
                ->form([
                    Select::make('recurrence')
                        ->label(__('employees.compensation_grants.recurrence'))
                        ->options(__('employees.compensation_grants.recurrences'))
                        ->default(SaudiCompensationGrantService::RECURRENCE_DAILY)
                        ->live()
                        ->afterStateUpdated(function (?string $state, callable $set): void {
                            if ($state === SaudiCompensationGrantService::RECURRENCE_MONTHLY) {
                                $set('ends_on', null);
                            }
                        })
                        ->required(),

                    TextInput::make('minutes')
                        ->label(__('employees.compensation_grants.minutes'))
                        ->numeric()
                        ->integer()
                        ->minValue(1)
                        ->required(),

                    DatePicker::make('starts_on')
                        ->label(__('employees.compensation_grants.starts_on'))
                        ->default(now()->toDateString())
                        ->required(),

                    DatePicker::make('ends_on')
                        ->label(__('employees.compensation_grants.ends_on'))
                        ->visible(fn (callable $get): bool => $get('recurrence') !== SaudiCompensationGrantService::RECURRENCE_MONTHLY),

                    Textarea::make('reason')
                        ->label(__('employees.compensation_grants.reason'))
                        ->rows(2)
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $actor = auth()->user();
                    if (! $actor) {
                        return;
                    }

                    SaudiCompensationGrantService::createGrant($actor, $this->getRecord(), $data);

                    Notification::make()
                        ->title(__('employees.compensation_grants.saved_notification'))
                        ->success()
                        ->send();
                }),

            Action::make('revokeCompensationGrant')
                ->label(__('employees.compensation_grants.revoke_action_label'))
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(function (): bool {
                    if (! EmployeeCompensationEligibilityService::canManage(auth()->user(), $this->getRecord())) {
                        return false;
                    }

                    return collect(SaudiCompensationGrantService::getOperationalGrants($this->getRecord()))
                        ->contains(fn (array $g): bool => ($g['status'] ?? '') === 'active');
                })
                ->authorize(fn (): bool => EmployeeCompensationEligibilityService::canManage(auth()->user(), $this->getRecord()))
                ->modalHeading(__('employees.compensation_grants.revoke_modal_heading'))
                ->modalDescription(__('employees.compensation_grants.revoke_modal_description'))
                ->modalWidth('lg')
                ->form([
                    Select::make('grant_id')
                        ->label(__('employees.compensation_grants.select_grant'))
                        ->options(function (): array {
                            return collect(SaudiCompensationGrantService::getOperationalGrants($this->getRecord()))
                                ->filter(fn (array $g): bool => ($g['status'] ?? '') === 'active')
                                ->mapWithKeys(function (array $g): array {
                                    $rec = __('employees.compensation_grants.recurrences.'.($g['recurrence'] ?? 'once'));
                                    $label = "{$rec} — {$g['minutes']}m ({$g['starts_on']})";

                                    return [$g['grant_id'] => $label];
                                })
                                ->all();
                        })
                        ->required(),

                    DatePicker::make('effective_date')
                        ->label(__('employees.compensation_grants.revoke_effective_date'))
                        ->default(now()->toDateString())
                        ->required(),

                    Textarea::make('reason')
                        ->label(__('employees.compensation_grants.revoke_reason'))
                        ->rows(2)
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $actor = auth()->user();
                    if (! $actor) {
                        return;
                    }

                    SaudiCompensationGrantService::revokeGrant(
                        actor: $actor,
                        employee: $this->getRecord(),
                        grantId: (string) $data['grant_id'],
                        effectiveDate: (string) $data['effective_date'],
                        reason: (string) $data['reason'],
                    );

                    Notification::make()
                        ->title(__('employees.compensation_grants.revoked_notification'))
                        ->success()
                        ->send();
                }),

            DeleteAction::make()->label(__('employees.actions.delete')),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $employee = $this->record;
        $user = $employee->user;
        $details = $employee->details;

        if ($user) {
            $data['user_name'] = $user->name;
            $data['user_email'] = $user->email;
            $data['user_phone'] = $user->phone;
            $data['roles'] = $user->roles->pluck('name')->toArray();
        }

        if ($details) {
            $data['national_id'] = $details->national_id;
            $data['profession'] = $details->profession;
            $data['address'] = $details->address;
            $data['birth_date'] = $details->birth_date?->format('Y-m-d');
            $data['age'] = EmployeeAgeService::calculateAge($details->birth_date);
        }

        $data['name_en'] = $employee->metadata['name_en'] ?? null;
        $data['person_capacity'] = PersonCapacityService::resolveKey($employee, 'employee');

        $data = array_merge($data, EmployeeSalaryProfileService::getFormData($employee));

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $employee = $this->record;
        EmployeeIdentityService::ensureAvailable('national_id', $this->form->getRawState()['national_id'] ?? null, $employee);
        $userId = $employee->user_id;

        $metadata = $employee->metadata ?? [];
        if (array_key_exists('name_en', $data)) {
            if (! empty($data['name_en'])) {
                $metadata['name_en'] = trim($data['name_en']);
            } else {
                unset($metadata['name_en']);
            }
            unset($data['name_en']);
        }

        $rawState = $this->form->getRawState();
        $this->pendingSalarySettings = [
            'basic_salary' => $rawState['basic_salary'] ?? $employee->basic_salary,
            'allowances_amount' => $rawState['allowances_amount'] ?? $employee->allowances_amount,
            'bonuses_amount' => $rawState['bonuses_amount'] ?? $employee->bonuses_amount,
            'other_allowances_amount' => $rawState['other_allowances_amount'] ?? $employee->other_allowances_amount,
            'overtime_hourly_rate' => array_key_exists('overtime_hourly_rate', $rawState) ? $rawState['overtime_hourly_rate'] : $employee->overtime_hourly_rate,
            'compensation_hourly_rate' => array_key_exists('compensation_hourly_rate', $rawState) ? $rawState['compensation_hourly_rate'] : $employee->compensation_hourly_rate,
            'deduction_basis' => $rawState['deduction_basis'] ?? $employee->deduction_basis,
            'daily_work_hours' => array_key_exists('daily_work_hours', $rawState) ? $rawState['daily_work_hours'] : $employee->daily_work_hours,
            'weekly_work_hours' => array_key_exists('weekly_work_hours', $rawState) ? $rawState['weekly_work_hours'] : $employee->weekly_work_hours,
        ];

        $capacity = $data['person_capacity'] ?? $rawState['person_capacity'] ?? null;
        $data['metadata'] = PersonCapacityService::mergeIntoMetadata($metadata, $capacity, 'employee');
        unset(
            $data['person_capacity'],
            $data['basic_salary'],
            $data['daily_work_hours'],
            $data['weekly_work_hours'],
            $data['allowances_amount'],
            $data['bonuses_amount'],
            $data['other_allowances_amount'],
            $data['total_salary_display'],
            $data['overtime_hourly_rate'],
            $data['compensation_hourly_rate'],
            $data['deduction_basis']
        );

        if (! empty($data['user_email'])) {
            $email = trim($data['user_email']);
            if (User::where('email', $email)->where('id', '!=', $userId)->exists()) {
                throw ValidationException::withMessages([
                    'user_email' => __('employees.fields.errors.email_exists'),
                ]);
            }
        }

        if (! empty($data['user_phone'])) {
            $normalizedPhone = PhoneService::normalize($data['user_phone']);
            if ($normalizedPhone && User::where('phone', $normalizedPhone)->where('id', '!=', $userId)->exists()) {
                throw ValidationException::withMessages([
                    'user_phone' => __('validation.unique', ['attribute' => __('employees.fields.phone')]),
                ]);
            }
        }

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return DB::transaction(function () use ($record, $data): Model {
            $updated = parent::handleRecordUpdate($record, $data);

            if (EmployeeSalaryProfileService::isEligibleSaudiEmployee($updated)) {
                $updated = EmployeeSalaryProfileService::saveForSaudiEmployee(
                    $updated,
                    $this->pendingSalarySettings,
                    auth()->user()
                );
            }

            return $updated;
        });
    }

    protected function afterSave(): void
    {
        $data = $this->form->getRawState();
        $employee = $this->record;

        if ($employee->user) {
            $userUpdate = [
                'name' => $data['user_name'] ?? $employee->user->name,
                'email' => ! empty($data['user_email']) ? $data['user_email'] : $employee->user->email,
                'phone' => PhoneService::normalize($data['user_phone'] ?? $employee->user->phone),
            ];

            if (! empty($data['user_password'])) {
                $userUpdate['password'] = Hash::make($data['user_password']);
            }

            $employee->user->update($userUpdate);

            if (isset($data['roles'])) {
                $employee->user->syncRoles($data['roles']);
            }
        }

        $employee->details()->updateOrCreate(
            ['employee_id' => $employee->id],
            [
                'nationality' => 'saudi',
                'national_id' => $data['national_id'] ?? null,
                'profession' => $data['profession'] ?? null,
                'birth_date' => $data['birth_date'] ?? null,
                'metadata' => ! empty($data['birth_date']) ? ['age' => EmployeeAgeService::calculateAge($data['birth_date'])] : null,
                'address' => $data['address'] ?? null,
            ]
        );
    }
}
