<?php

namespace App\Services\AI\Tools;

use App\Enums\GeneralStatus;
use App\Enums\UserType;
use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeDetail;
use App\Models\User;
use App\Services\AI\Contracts\GeminiToolInterface;
use App\Services\CompanyFormService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CreateForeignEmployeeTool implements GeminiToolInterface
{
    public function getName(): string
    {
        return 'create_foreign_employee';
    }

    public function getDeclaration(): array
    {
        return [
            'name' => $this->getName(),
            'description' => __('smart_import.tools.create_foreign_employee.description'),
            'parameters' => [
                'type' => 'OBJECT',
                'properties' => [
                    'name' => [
                        'type' => 'STRING',
                        'description' => __('smart_import.tools.create_foreign_employee.name_desc'),
                    ],
                    'company_name' => [
                        'type' => 'STRING',
                        'description' => __('smart_import.tools.create_foreign_employee.company_name_desc'),
                    ],
                    'iqama_number' => [
                        'type' => 'STRING',
                        'description' => __('smart_import.tools.create_foreign_employee.iqama_desc'),
                    ],
                    'nationality' => [
                        'type' => 'STRING',
                        'description' => __('smart_import.tools.create_foreign_employee.nationality_desc'),
                    ],
                    'passport_number' => [
                        'type' => 'STRING',
                        'description' => __('smart_import.tools.create_foreign_employee.passport_desc'),
                    ],
                    'phone' => [
                        'type' => 'STRING',
                        'description' => __('smart_import.tools.create_foreign_employee.phone_desc'),
                    ],
                    'email' => [
                        'type' => 'STRING',
                        'description' => __('smart_import.tools.create_foreign_employee.email_desc'),
                    ],
                    'basic_salary' => [
                        'type' => 'NUMBER',
                        'description' => __('smart_import.tools.create_foreign_employee.basic_salary_desc'),
                    ],
                    'iqama_expiry' => [
                        'type' => 'STRING',
                        'description' => __('smart_import.tools.create_foreign_employee.iqama_expiry_desc'),
                    ],
                    'work_permit_number' => [
                        'type' => 'STRING',
                        'description' => __('smart_import.tools.create_foreign_employee.work_permit_desc'),
                    ],
                    'sponsor_name' => [
                        'type' => 'STRING',
                        'description' => __('smart_import.tools.create_foreign_employee.sponsor_name_desc'),
                    ],
                ],
                'required' => ['name', 'iqama_number', 'nationality'],
            ],
        ];
    }

    public function execute(array $arguments): array
    {
        $name = trim($arguments['name'] ?? '');
        $iqamaNumber = preg_replace('/[^0-9]/', '', $arguments['iqama_number'] ?? '');
        $nationality = trim($arguments['nationality'] ?? '');
        $passportNumber = trim($arguments['passport_number'] ?? '');
        $phone = trim($arguments['phone'] ?? '');
        $email = trim($arguments['email'] ?? '');
        $salary = isset($arguments['basic_salary']) ? (float)$arguments['basic_salary'] : 0.0;
        $iqamaExpiry = trim($arguments['iqama_expiry'] ?? '');
        $workPermitNumber = trim($arguments['work_permit_number'] ?? '');
        $sponsorName = trim($arguments['sponsor_name'] ?? '');

        // 1. Validate employee name
        if (empty($name)) {
            return ['success' => false, 'message' => __('smart_import.tools.create_foreign_employee.name_required')];
        }

        // 2. Validate nationality (must be provided and non-Saudi)
        if (empty($nationality)) {
            return [
                'success' => false,
                'message' => __('smart_import.tools.create_foreign_employee.nationality_required', ['name' => $name]),
            ];
        }

        $banned = (array) __('smart_import.tools.create_foreign_employee.banned_nationalities');
        if (empty($banned) || !is_array($banned)) {
            $banned = ['saudi', 'saudi arabia', 'ksa'];
        }
        if (in_array(mb_strtolower($nationality), array_map('mb_strtolower', $banned), true)) {
            return [
                'success' => false,
                'message' => __('smart_import.tools.create_foreign_employee.saudi_nationality_rejected', [
                    'name' => $name,
                    'nationality' => $nationality,
                ]),
            ];
        }

        // 3. Validate Iqama number (10 digits starting with 2)
        if (strlen($iqamaNumber) !== 10) {
            return [
                'success' => false,
                'message' => __('smart_import.tools.create_foreign_employee.iqama_length_error', ['name' => $name]),
            ];
        }

        if (str_starts_with($iqamaNumber, '1')) {
            return [
                'success' => false,
                'message' => __('smart_import.tools.create_foreign_employee.iqama_starts_with_1_rejected', [
                    'name' => $name,
                    'iqama' => $iqamaNumber,
                ]),
            ];
        }

        if (!str_starts_with($iqamaNumber, '2')) {
            return [
                'success' => false,
                'message' => __('smart_import.tools.create_foreign_employee.iqama_must_start_with_2', ['name' => $name]),
            ];
        }

        // 4. Verify Iqama uniqueness
        $exists = EmployeeDetail::where('iqama_number', $iqamaNumber)->orWhere('national_id', $iqamaNumber)->exists();
        if ($exists) {
            return [
                'success' => false,
                'message' => __('smart_import.tools.create_foreign_employee.duplicate_iqama', [
                    'name' => $name,
                    'iqama' => $iqamaNumber,
                ]),
            ];
        }

        // 5. Verify passport uniqueness if provided
        if (!empty($passportNumber) && EmployeeDetail::where('passport_number', $passportNumber)->exists()) {
            return [
                'success' => false,
                'message' => __('smart_import.tools.create_foreign_employee.duplicate_passport', [
                    'name' => $name,
                    'passport' => $passportNumber,
                ]),
            ];
        }

        // 6. Verify phone uniqueness if provided
        if (!empty($phone) && User::where('phone', $phone)->exists()) {
            return [
                'success' => false,
                'message' => __('smart_import.tools.create_foreign_employee.duplicate_phone', [
                    'name' => $name,
                    'phone' => $phone,
                ]),
            ];
        }

        try {
            return DB::transaction(function () use ($name, $iqamaNumber, $nationality, $passportNumber, $phone, $email, $salary, $iqamaExpiry, $workPermitNumber, $sponsorName, $arguments) {
                $companyName = trim($arguments['company_name'] ?? '');
                $fallbackCompanyId = !empty($arguments['company_id']) ? (int)$arguments['company_id'] : null;
                $companyId = CompanyFormService::resolveCompanyIdByName($companyName, $fallbackCompanyId);
                $companyObj = Company::find($companyId);
                $companySuffix = $companyObj ? " ({$companyObj->trade_name})" : "";

                $userEmail = !empty($email) ? $email : ('for_emp_' . Str::random(8) . '@company.local');
                $user = User::create([
                    'name' => $name,
                    'email' => $userEmail,
                    'phone' => $phone ?: null,
                    'password' => Hash::make(Str::random(16)),
                    'user_type' => UserType::FOREIGN_EMPLOYEE,
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
                    'national_id' => $iqamaNumber,
                    'iqama_number' => $iqamaNumber,
                    'nationality' => $nationality,
                    'passport_number' => $passportNumber ?: null,
                    'iqama_expiry' => $iqamaExpiry ?: null,
                    'work_permit_number' => $workPermitNumber ?: null,
                    'sponsor_name' => $sponsorName ?: null,
                ]);

                return [
                    'success' => true,
                    'message' => __('smart_import.tools.create_foreign_employee.success_message', [
                        'name' => $name,
                        'nationality' => $nationality,
                        'number' => $employee->employee_number,
                    ]) . $companySuffix,
                    'employee_id' => $employee->id,
                    'employee_number' => $employee->employee_number,
                    'company_id' => $companyId,
                    'company_name' => $companyObj?->trade_name,
                    'type' => 'foreign',
                ];
            });
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => __('smart_import.tools.create_foreign_employee.failed_message', [
                    'name' => $name,
                    'error' => $e->getMessage(),
                ]),
            ];
        }
    }
}
