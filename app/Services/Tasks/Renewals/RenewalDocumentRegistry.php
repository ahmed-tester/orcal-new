<?php

namespace App\Services\Tasks\Renewals;

use App\Enums\GeneralStatus;
use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeDetail;
use App\Models\Setting;
use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use InvalidArgumentException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RenewalDocumentRegistry
{
    public const TYPE_IQAMA = 'iqama';

    public const TYPE_COMMERCIAL_REGISTRATION = 'commercial_registration';

    public const TYPE_MUNICIPALITY = 'municipality';

    public const TYPE_CIVIL_DEFENSE = 'civil_defense';

    public const TYPE_HEALTH_INSURANCE = 'health_insurance';

    public const TYPE_WORK_PERMIT = 'work_permit';

    public const TYPE_PASSPORT = 'passport';

    public const TYPE_EMPLOYEE_CONTRACT = 'employee_contract';

    public const TYPE_SUBSCRIPTION_QIWA = 'subscription_qiwa';

    public const TYPE_SUBSCRIPTION_ABSHER = 'subscription_absher';

    public const TYPE_SUBSCRIPTION_SPL = 'subscription_spl';

    public const SETTING_LEAD_DAYS = 'document_renewal_lead_days';

    public const SETTING_DEADLINE_DAYS = 'document_renewal_deadline_days';

    public const SETTING_TASK_WEIGHT = 'document_renewal_task_weight';

    public const SETTING_LATE_COMPLETION_PERCENTAGE = 'document_renewal_late_completion_percentage';

    public const SETTING_TRACKING_START_DATE = 'document_renewal_tracking_start_date';

    /**
     * @return array<string, array{
     *     type: string,
     *     source_class: class-string<Model>,
     *     expiry_field: string,
     *     json_path: ?string,
     *     platform: ?string,
     *     number_field: ?string,
     *     supports_hijri: bool,
     *     task_code: string,
     *     permission: string,
     *     label_key: string
     * }>
     */
    public static function definitions(): array
    {
        return [
            self::TYPE_IQAMA => [
                'type' => self::TYPE_IQAMA,
                'source_class' => EmployeeDetail::class,
                'expiry_field' => 'iqama_expiry',
                'json_path' => null,
                'platform' => null,
                'number_field' => 'iqama_number',
                'supports_hijri' => true,
                'task_code' => 'SYS-REN-IQAMA',
                'permission' => 'manage_iqama_renewals',
                'label_key' => 'document_renewals.types.iqama',
            ],
            self::TYPE_COMMERCIAL_REGISTRATION => [
                'type' => self::TYPE_COMMERCIAL_REGISTRATION,
                'source_class' => Company::class,
                'expiry_field' => 'commercial_registration_expiry',
                'json_path' => null,
                'platform' => null,
                'number_field' => 'commercial_registration_number',
                'supports_hijri' => true,
                'task_code' => 'SYS-REN-CR',
                'permission' => 'manage_company_license_renewals',
                'label_key' => 'document_renewals.types.commercial_registration',
            ],
            self::TYPE_MUNICIPALITY => [
                'type' => self::TYPE_MUNICIPALITY,
                'source_class' => Company::class,
                'expiry_field' => 'municipality_license_expiry',
                'json_path' => null,
                'platform' => null,
                'number_field' => 'municipality_license_number',
                'supports_hijri' => true,
                'task_code' => 'SYS-REN-MUN',
                'permission' => 'manage_company_license_renewals',
                'label_key' => 'document_renewals.types.municipality',
            ],
            self::TYPE_CIVIL_DEFENSE => [
                'type' => self::TYPE_CIVIL_DEFENSE,
                'source_class' => Company::class,
                'expiry_field' => 'civil_defense_license_expiry',
                'json_path' => null,
                'platform' => null,
                'number_field' => 'civil_defense_license_number',
                'supports_hijri' => true,
                'task_code' => 'SYS-REN-CD',
                'permission' => 'manage_company_license_renewals',
                'label_key' => 'document_renewals.types.civil_defense',
            ],
            self::TYPE_HEALTH_INSURANCE => [
                'type' => self::TYPE_HEALTH_INSURANCE,
                'source_class' => EmployeeDetail::class,
                'expiry_field' => 'insurance_expiry',
                'json_path' => null,
                'platform' => null,
                'number_field' => null,
                'supports_hijri' => true,
                'task_code' => 'SYS-REN-INSURANCE',
                'permission' => 'manage_health_insurance_renewals',
                'label_key' => 'document_renewals.types.health_insurance',
            ],
            self::TYPE_WORK_PERMIT => [
                'type' => self::TYPE_WORK_PERMIT,
                'source_class' => EmployeeDetail::class,
                'expiry_field' => 'work_permit_expiry',
                'json_path' => null,
                'platform' => null,
                'number_field' => 'work_permit_number',
                'supports_hijri' => true,
                'task_code' => 'SYS-REN-WORK-PERMIT',
                'permission' => 'manage_work_permit_renewals',
                'label_key' => 'document_renewals.types.work_permit',
            ],
            self::TYPE_PASSPORT => [
                'type' => self::TYPE_PASSPORT,
                'source_class' => EmployeeDetail::class,
                'expiry_field' => 'passport_expiry',
                'json_path' => null,
                'platform' => null,
                'number_field' => 'passport_number',
                'supports_hijri' => true,
                'task_code' => 'SYS-REN-PASSPORT',
                'permission' => 'manage_passport_renewals',
                'label_key' => 'document_renewals.types.passport',
            ],
            self::TYPE_EMPLOYEE_CONTRACT => [
                'type' => self::TYPE_EMPLOYEE_CONTRACT,
                'source_class' => Employee::class,
                'expiry_field' => 'contract_end_date',
                'json_path' => null,
                'platform' => null,
                'number_field' => 'employee_number',
                'supports_hijri' => true,
                'task_code' => 'SYS-REN-CONTRACT',
                'permission' => 'manage_employee_contract_renewals',
                'label_key' => 'document_renewals.types.employee_contract',
            ],
            self::TYPE_SUBSCRIPTION_QIWA => [
                'type' => self::TYPE_SUBSCRIPTION_QIWA,
                'source_class' => Company::class,
                'expiry_field' => 'metadata',
                'json_path' => 'subscriptions.qiwa.expiry_date',
                'platform' => 'qiwa',
                'number_field' => 'commercial_registration_number',
                'supports_hijri' => false,
                'task_code' => 'SYS-REN-QIWA',
                'permission' => 'manage_qiwa_subscription_renewals',
                'label_key' => 'document_renewals.types.subscription_qiwa',
            ],
            self::TYPE_SUBSCRIPTION_ABSHER => [
                'type' => self::TYPE_SUBSCRIPTION_ABSHER,
                'source_class' => Company::class,
                'expiry_field' => 'metadata',
                'json_path' => 'subscriptions.absher.expiry_date',
                'platform' => 'absher',
                'number_field' => 'commercial_registration_number',
                'supports_hijri' => false,
                'task_code' => 'SYS-REN-ABSHER',
                'permission' => 'manage_absher_subscription_renewals',
                'label_key' => 'document_renewals.types.subscription_absher',
            ],
            self::TYPE_SUBSCRIPTION_SPL => [
                'type' => self::TYPE_SUBSCRIPTION_SPL,
                'source_class' => Company::class,
                'expiry_field' => 'metadata',
                'json_path' => 'subscriptions.spl.expiry_date',
                'platform' => 'spl',
                'number_field' => 'commercial_registration_number',
                'supports_hijri' => false,
                'task_code' => 'SYS-REN-SPL',
                'permission' => 'manage_spl_subscription_renewals',
                'label_key' => 'document_renewals.types.subscription_spl',
            ],
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function allTypes(): array
    {
        return array_keys(self::definitions());
    }

    public static function isValidType(string $type): bool
    {
        return array_key_exists($type, self::definitions());
    }

    /**
     * @return array{
     *     type: string,
     *     source_class: class-string<Model>,
     *     expiry_field: string,
     *     json_path: ?string,
     *     platform: ?string,
     *     number_field: ?string,
     *     supports_hijri: bool,
     *     task_code: string,
     *     permission: string,
     *     label_key: string
     * }
     */
    public static function definition(string $type): array
    {
        $definitions = self::definitions();

        if (! isset($definitions[$type])) {
            throw new InvalidArgumentException("Unsupported document renewal type: {$type}");
        }

        return $definitions[$type];
    }

    public static function label(string $type): string
    {
        return __(self::definition($type)['label_key']);
    }

    public static function permissionFor(string $type): string
    {
        return self::definition($type)['permission'];
    }

    /**
     * @return array<int, string>
     */
    public static function allPermissionKeys(): array
    {
        return array_values(array_unique(array_map(
            static fn (array $def): string => $def['permission'],
            self::definitions()
        )));
    }

    /**
     * @return array<string, string>
     */
    public static function permissionOptions(): array
    {
        $options = [];
        foreach (self::allPermissionKeys() as $key) {
            $options[$key] = __('document_renewals.permissions.'.$key);
        }

        return $options;
    }

    public static function isRenewalPermission(string $permissionName): bool
    {
        return in_array($permissionName, self::allPermissionKeys(), true);
    }

    /**
     * Register renewal permission definitions only (never auto-assign to any role).
     *
     * @return array<int, string>
     */
    public static function syncPermissionDefinitions(): array
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $created = [];
        foreach (self::allPermissionKeys() as $key) {
            Permission::firstOrCreate([
                'name' => $key,
                'guard_name' => 'web',
            ]);
            $created[] = $key;
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        return $created;
    }

    /**
     * @return array<int, string>
     */
    public static function reservedTaskCodes(): array
    {
        return array_values(array_map(
            static fn (array $def): string => $def['task_code'],
            self::definitions()
        ));
    }

    public static function isReservedTaskCode(?string $taskCode): bool
    {
        return filled($taskCode) && in_array($taskCode, self::reservedTaskCodes(), true);
    }

    /**
     * Ensure and retrieve the system catalog Task for a document type.
     * Must NOT be invoked during --dry-run or plain UI reads.
     */
    public static function ensureSystemTask(string $type): Task
    {
        $def = self::definition($type);
        $code = $def['task_code'];

        $existing = Task::query()->where('task_code', $code)->first();
        if ($existing) {
            return $existing;
        }

        try {
            return Task::query()->create([
                'task_code' => $code,
                'name' => self::label($type),
                'default_weight' => 0.00,
                'description' => __('document_renewals.catalog.system_task_description', [
                    'type' => self::label($type),
                ]),
                'status' => GeneralStatus::ACTIVE,
            ]);
        } catch (QueryException) {
            return Task::query()->where('task_code', $code)->firstOrFail();
        }
    }

    public static function extractExpiryDate(string $type, Model $source): ?string
    {
        $def = self::definition($type);

        if ($def['json_path'] !== null) {
            $raw = data_get($source->metadata, $def['json_path']);
            if (! is_string($raw) || trim($raw) === '') {
                return null;
            }

            try {
                if (! Carbon::canBeCreatedFromFormat(trim($raw), 'Y-m-d')) {
                    return null;
                }

                return Carbon::createFromFormat('Y-m-d', trim($raw))->toDateString();
            } catch (\Throwable) {
                return null;
            }
        }

        $value = $source->{$def['expiry_field']} ?? null;
        if (blank($value)) {
            return null;
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    public static function extractDocumentNumber(string $type, Model $source): ?string
    {
        $def = self::definition($type);
        if ($def['number_field'] === null) {
            return null;
        }

        $value = $source->{$def['number_field']} ?? null;

        return filled($value) ? (string) $value : null;
    }

    public static function buildTaskKey(
        int $companyId,
        string $sourceClass,
        int $sourceId,
        string $documentType,
        string $expiryDate
    ): string {
        $normalizedExpiry = Carbon::parse($expiryDate)->toDateString();
        $sourceAlias = match ($sourceClass) {
            EmployeeDetail::class => 'emp_detail',
            Employee::class => 'employee',
            Company::class => 'company',
            default => strtolower(class_basename($sourceClass)),
        };

        return sprintf(
            'doc_renewal:c%d:%s:%d:%s:%s',
            $companyId,
            $sourceAlias,
            $sourceId,
            $documentType,
            $normalizedExpiry
        );
    }

    public static function leadDays(): int
    {
        return max(0, (int) (Setting::get(self::SETTING_LEAD_DAYS, 0) ?? 0));
    }

    public static function deadlineDays(): int
    {
        return max(1, (int) (Setting::get(self::SETTING_DEADLINE_DAYS, 7) ?? 7));
    }

    public static function taskWeight(): float
    {
        return max(0.01, round((float) (Setting::get(self::SETTING_TASK_WEIGHT, 10) ?? 10), 2));
    }

    public static function lateCompletionPercentage(): float
    {
        $value = (float) (Setting::get(self::SETTING_LATE_COMPLETION_PERCENTAGE, 50) ?? 50);

        return round(min(100.0, max(0.0, $value)), 2);
    }

    public static function trackingStartDate(): ?string
    {
        $raw = Setting::get(self::SETTING_TRACKING_START_DATE);
        if (! is_string($raw) || trim($raw) === '') {
            return null;
        }

        try {
            return Carbon::parse(trim($raw))->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @return array{lead_days: int, deadline_days: int, task_weight: float, late_completion_percentage: float}
     */
    public static function settingsSnapshot(): array
    {
        return [
            'lead_days' => self::leadDays(),
            'deadline_days' => self::deadlineDays(),
            'task_weight' => self::taskWeight(),
            'late_completion_percentage' => self::lateCompletionPercentage(),
        ];
    }
}
