<?php

namespace App\Services\AI\Tools;

use App\Enums\GeneralStatus;
use App\Enums\UserType;
use App\Models\Employee;
use App\Models\EmployeeDetail;
use App\Models\User;
use App\Services\AI\Contracts\GeminiToolInterface;
use App\Services\AI\EmployeeNameMatcher;
use App\Services\CompanyFormService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CreateForeignEmployeeTool implements GeminiToolInterface
{
    protected static ?string $cachedPasswordHash = null;

    protected function getCachedPasswordHash(): string
    {
        return self::$cachedPasswordHash ??= Hash::make(Str::random(24));
    }

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
                    'name_en' => [
                        'type' => 'STRING',
                        'description' => __('smart_import.tools.create_foreign_employee.name_en_desc'),
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
        $nameEn = trim($arguments['name_en'] ?? '');
        $iqamaNumber = preg_replace('/[^0-9]/', '', $arguments['iqama_number'] ?? '');
        $nationality = trim($arguments['nationality'] ?? '');
        $passportNumber = trim($arguments['passport_number'] ?? '');
        $phone = trim($arguments['phone'] ?? '');
        $email = trim($arguments['email'] ?? '');
        $salary = isset($arguments['basic_salary']) ? (float) $arguments['basic_salary'] : 0.0;
        $iqamaExpiry = trim($arguments['iqama_expiry'] ?? '');
        $workPermitNumber = trim($arguments['work_permit_number'] ?? '');
        $sponsorName = trim($arguments['sponsor_name'] ?? '');

        // Always split and clean bilingual names (handles attached texts like "MOHAMMED SAHIDمحمد شاهد")
        $bilingual = EmployeeNameMatcher::splitBilingualName($name);
        if ($bilingual['arabic'] !== '' && $bilingual['latin'] !== '') {
            $name = $bilingual['arabic'];
            if (empty($nameEn)) {
                $nameEn = $bilingual['latin'];
            }
        } elseif ($bilingual['arabic'] === '' && $bilingual['latin'] !== '' && empty($nameEn)) {
            $nameEn = $bilingual['latin'];
        }

        // English-only rows: keep the Latin name as the primary user name
        // instead of rejecting the whole row.
        if ($name === '' && $nameEn !== '') {
            $name = $nameEn;
        }

        // Clean $nameEn if it accidentally contained Arabic letters
        if (! empty($nameEn)) {
            $enBilingual = EmployeeNameMatcher::splitBilingualName($nameEn);
            if ($enBilingual['latin'] !== '') {
                $nameEn = $enBilingual['latin'];
            }
        }

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
        if (empty($banned) || ! is_array($banned)) {
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

        if (! str_starts_with($iqamaNumber, '2')) {
            return [
                'success' => false,
                'message' => __('smart_import.tools.create_foreign_employee.iqama_must_start_with_2', ['name' => $name]),
            ];
        }

        // 4. Verify Iqama uniqueness
        $existingIqama = EmployeeDetail::where('iqama_number', $iqamaNumber)->orWhere('national_id', $iqamaNumber)->with('employee.user')->first();
        if ($existingIqama) {
            $conflictingEmpName = $existingIqama->employee?->user?->name ?? __('smart_import.company_match.unknown');
            $conflictingEmpNumber = $existingIqama->employee?->employee_number ?? '';
            Log::warning('[SmartImport] Duplicate Foreign Employee Iqama Detected', [
                'candidate_name' => $name,
                'iqama_number' => $iqamaNumber,
                'conflicting_employee_id' => $existingIqama->employee_id,
                'conflicting_employee_name' => $conflictingEmpName,
                'conflicting_employee_number' => $conflictingEmpNumber,
            ]);

            return [
                'success' => false,
                'message' => __('smart_import.tools.create_foreign_employee.duplicate_iqama', [
                    'name' => $name,
                    'iqama' => $iqamaNumber,
                    'existing_name' => $conflictingEmpName,
                    'existing_number' => $conflictingEmpNumber,
                ]),
            ];
        }

        // 5. Verify passport uniqueness if provided
        if (! empty($passportNumber)) {
            $existingPassport = EmployeeDetail::where('passport_number', $passportNumber)->with('employee.user')->first();
            if ($existingPassport) {
                $conflictingEmpName = $existingPassport->employee?->user?->name ?? __('smart_import.company_match.unknown');
                Log::warning('[SmartImport] Duplicate Foreign Employee Passport Detected', [
                    'candidate_name' => $name,
                    'passport_number' => $passportNumber,
                    'conflicting_employee_name' => $conflictingEmpName,
                ]);

                return [
                    'success' => false,
                    'message' => __('smart_import.tools.create_foreign_employee.duplicate_passport', [
                        'name' => $name,
                        'passport' => $passportNumber,
                        'existing_name' => $conflictingEmpName,
                    ]),
                ];
            }
        }

        // 6. Verify phone uniqueness if provided
        if (! empty($phone)) {
            $existingUser = User::where('phone', $phone)->first();
            if ($existingUser) {
                Log::warning('[SmartImport] Duplicate Foreign Employee Phone Detected', [
                    'candidate_name' => $name,
                    'phone' => $phone,
                    'conflicting_user_id' => $existingUser->id,
                    'conflicting_user_name' => $existingUser->name,
                ]);

                return [
                    'success' => false,
                    'message' => __('smart_import.tools.create_foreign_employee.duplicate_phone', [
                        'name' => $name,
                        'phone' => $phone,
                        'existing_name' => $existingUser->name,
                        'existing_id' => $existingUser->id,
                    ]),
                ];
            }
        }

        try {
            return DB::transaction(function () use ($name, $nameEn, $iqamaNumber, $nationality, $passportNumber, $phone, $email, $salary, $iqamaExpiry, $workPermitNumber, $sponsorName, $arguments) {
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

                $userEmail = ! empty($email) ? $email : ('for_emp_'.Str::random(8).'@company.local');
                $user = User::create([
                    'name' => $name,
                    'email' => $userEmail,
                    'phone' => $phone ?: null,
                    'password' => $this->getCachedPasswordHash(),
                    'user_type' => UserType::FOREIGN_EMPLOYEE,
                    'status' => 'active',
                ]);

                $employee = Employee::create([
                    'user_id' => $user->id,
                    'company_id' => $companyId,
                    'basic_salary' => $salary,
                    'status' => GeneralStatus::ACTIVE,
                    'metadata' => ! empty($nameEn) ? ['name_en' => $nameEn] : null,
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
                    ]).$companySuffix,
                    'employee_id' => $employee->id,
                    'employee_number' => $employee->employee_number,
                    'company_id' => $companyId,
                    'company_name' => $companyTradeName,
                    'company_status' => $companyStatus,
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
