<?php

namespace App\Services\AI\Tools;

use App\Enums\GeneralStatus;
use App\Enums\UserType;
use App\Models\Employee;
use App\Models\EmployeeDetail;
use App\Models\User;
use App\Services\AI\Contracts\GeminiToolInterface;
use App\Services\CompanyFormService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CreateSaudiEmployeeTool implements GeminiToolInterface
{
    protected static ?string $cachedPasswordHash = null;

    protected function getCachedPasswordHash(): string
    {
        return self::$cachedPasswordHash ??= Hash::make(Str::random(24));
    }

    public function getName(): string
    {
        return 'create_saudi_employee';
    }

    public function getDeclaration(): array
    {
        return [
            'name' => $this->getName(),
            'description' => __('smart_import.tools.create_saudi_employee.description'),
            'parameters' => [
                'type' => 'OBJECT',
                'properties' => [
                    'name' => [
                        'type' => 'STRING',
                        'description' => __('smart_import.tools.create_saudi_employee.name_desc'),
                    ],
                    'company_name' => [
                        'type' => 'STRING',
                        'description' => __('smart_import.tools.create_saudi_employee.company_name_desc'),
                    ],
                    'national_id' => [
                        'type' => 'STRING',
                        'description' => __('smart_import.tools.create_saudi_employee.national_id_desc'),
                    ],
                    'phone' => [
                        'type' => 'STRING',
                        'description' => __('smart_import.tools.create_saudi_employee.phone_desc'),
                    ],
                    'email' => [
                        'type' => 'STRING',
                        'description' => __('smart_import.tools.create_saudi_employee.email_desc'),
                    ],
                    'basic_salary' => [
                        'type' => 'NUMBER',
                        'description' => __('smart_import.tools.create_saudi_employee.basic_salary_desc'),
                    ],
                    'city' => [
                        'type' => 'STRING',
                        'description' => __('smart_import.tools.create_saudi_employee.city_desc'),
                    ],
                    'birth_date' => [
                        'type' => 'STRING',
                        'description' => __('smart_import.tools.create_saudi_employee.birth_date_desc'),
                    ],
                ],
                'required' => ['name', 'national_id'],
            ],
        ];
    }

    public function execute(array $arguments): array
    {
        $name = trim($arguments['name'] ?? '');
        $nationalId = preg_replace('/[^0-9]/', '', $arguments['national_id'] ?? '');
        $phone = trim($arguments['phone'] ?? '');
        $email = trim($arguments['email'] ?? '');
        $salary = isset($arguments['basic_salary']) ? (float) $arguments['basic_salary'] : 0.0;
        $city = trim($arguments['city'] ?? '');
        $birthDate = trim($arguments['birth_date'] ?? '');

        // 1. Validate employee name
        if (empty($name)) {
            return ['success' => false, 'message' => __('smart_import.tools.create_saudi_employee.name_required')];
        }

        // 2. Validate Saudi National ID (10 digits starting with 1)
        if (strlen($nationalId) !== 10) {
            return [
                'success' => false,
                'message' => __('smart_import.tools.create_saudi_employee.id_length_error', ['name' => $name]),
            ];
        }

        if (str_starts_with($nationalId, '2')) {
            return [
                'success' => false,
                'message' => __('smart_import.tools.create_saudi_employee.id_starts_with_2_error', ['name' => $name, 'id' => $nationalId]),
            ];
        }

        if (! str_starts_with($nationalId, '1')) {
            return [
                'success' => false,
                'message' => __('smart_import.tools.create_saudi_employee.id_must_start_with_1', ['name' => $name]),
            ];
        }

        // 3. Verify national ID uniqueness
        $existingDetail = EmployeeDetail::where('national_id', $nationalId)->with('employee.user')->first();
        if ($existingDetail) {
            $conflictingEmpName = $existingDetail->employee?->user?->name ?? __('smart_import.company_match.unknown');
            $conflictingEmpNumber = $existingDetail->employee?->employee_number ?? '';
            Log::warning('[SmartImport] Duplicate Saudi National ID Detected', [
                'candidate_name' => $name,
                'national_id' => $nationalId,
                'conflicting_employee_id' => $existingDetail->employee_id,
                'conflicting_employee_name' => $conflictingEmpName,
                'conflicting_employee_number' => $conflictingEmpNumber,
            ]);

            return [
                'success' => false,
                'message' => __('smart_import.tools.create_saudi_employee.duplicate_id', [
                    'name' => $name,
                    'id' => $nationalId,
                    'existing_name' => $conflictingEmpName,
                    'existing_number' => $conflictingEmpNumber,
                ]),
            ];
        }

        // 4. Verify phone uniqueness if provided
        if (! empty($phone)) {
            $existingUser = User::where('phone', $phone)->first();
            if ($existingUser) {
                Log::warning('[SmartImport] Duplicate Saudi Employee Phone Detected', [
                    'candidate_name' => $name,
                    'phone' => $phone,
                    'conflicting_user_id' => $existingUser->id,
                    'conflicting_user_name' => $existingUser->name,
                ]);

                return [
                    'success' => false,
                    'message' => __('smart_import.tools.create_saudi_employee.duplicate_phone', [
                        'name' => $name,
                        'phone' => $phone,
                        'existing_name' => $existingUser->name,
                        'existing_id' => $existingUser->id,
                    ]),
                ];
            }
        }

        try {
            return DB::transaction(function () use ($name, $nationalId, $phone, $email, $salary, $city, $birthDate, $arguments) {
                $companyName = trim($arguments['company_name'] ?? '');
                $fallbackCompanyId = ! empty($arguments['company_id']) ? (int) $arguments['company_id'] : null;
                $companyRes = CompanyFormService::resolveOrCreateCompanyByName($companyName, $fallbackCompanyId);
                $companyId = $companyRes['company_id'];
                $companyTradeName = $companyRes['company_name'];
                $companyStatus = $companyRes['status'];

                $companySuffix = match ($companyStatus) {
                    'created' => __('smart_import.company_match.created_suffix', ['company' => $companyTradeName]),
                    'ambiguous' => __('smart_import.company_match.ambiguous_suffix', ['company' => $companyTradeName]),
                    'none' => __('smart_import.company_match.no_company_suffix'),
                    default => ($companyTradeName ? " ({$companyTradeName})" : ''),
                };

                $userEmail = ! empty($email) ? $email : ('sa_emp_'.Str::random(8).'@company.local');
                $user = User::create([
                    'name' => $name,
                    'email' => $userEmail,
                    'phone' => $phone ?: null,
                    'password' => $this->getCachedPasswordHash(),
                    'user_type' => UserType::SAUDI_EMPLOYEE,
                    'status' => 'active',
                ]);

                $employee = Employee::create([
                    'user_id' => $user->id,
                    'company_id' => $companyId,
                    'basic_salary' => $salary,
                    'status' => GeneralStatus::ACTIVE,
                ]);

                EmployeeDetail::create([
                    'employee_id' => $employee->id,
                    'national_id' => $nationalId,
                    'nationality' => __('smart_import.tools.create_saudi_employee.default_nationality'),
                    'city' => $city ?: null,
                    'birth_date' => $birthDate ?: null,
                ]);

                return [
                    'success' => true,
                    'message' => __('smart_import.tools.create_saudi_employee.success_message', ['name' => $name, 'number' => $employee->employee_number]).$companySuffix,
                    'employee_id' => $employee->id,
                    'employee_number' => $employee->employee_number,
                    'company_id' => $companyId,
                    'company_name' => $companyTradeName,
                    'company_status' => $companyStatus,
                    'type' => 'saudi',
                ];
            });
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => __('smart_import.tools.create_saudi_employee.failed_message', ['name' => $name, 'error' => $e->getMessage()]),
            ];
        }
    }
}
