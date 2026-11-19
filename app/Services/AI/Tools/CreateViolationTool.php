<?php

namespace App\Services\AI\Tools;

use App\Enums\ViolationPaymentStatus;
use App\Enums\ViolationStage;
use App\Models\Company;
use App\Models\Violation;
use App\Models\ViolationCategory;
use App\Models\ViolationType;
use App\Services\AI\Contracts\GeminiToolInterface;
use App\Services\CompanyFormService;

class CreateViolationTool implements GeminiToolInterface
{
    public function getName(): string
    {
        return 'create_violation_record';
    }

    public function getDeclaration(): array
    {
        return [
            'name' => $this->getName(),
            'description' => __('smart_import.tools.create_violation_record.description'),
            'parameters' => [
                'type' => 'OBJECT',
                'properties' => [
                    'external_violation_number' => [
                        'type' => 'STRING',
                        'description' => __('smart_import.tools.create_violation_record.external_violation_number_desc'),
                    ],
                    'violation_amount' => [
                        'type' => 'NUMBER',
                        'description' => __('smart_import.tools.create_violation_record.violation_amount_desc'),
                    ],
                    'issuing_authority' => [
                        'type' => 'STRING',
                        'description' => __('smart_import.tools.create_violation_record.issuing_authority_desc'),
                    ],
                    'violation_date' => [
                        'type' => 'STRING',
                        'description' => __('smart_import.tools.create_violation_record.violation_date_desc'),
                    ],
                    'category_name' => [
                        'type' => 'STRING',
                        'description' => __('smart_import.tools.create_violation_record.category_name_desc'),
                    ],
                    'type_name' => [
                        'type' => 'STRING',
                        'description' => __('smart_import.tools.create_violation_record.type_name_desc'),
                    ],
                    'description' => [
                        'type' => 'STRING',
                        'description' => __('smart_import.tools.create_violation_record.description_desc'),
                    ],
                    'paid_amount' => [
                        'type' => 'NUMBER',
                        'description' => __('smart_import.tools.create_violation_record.paid_amount_desc'),
                    ],
                ],
                'required' => ['violation_amount'],
            ],
        ];
    }

    public function execute(array $arguments): array
    {
        try {
            $amount = (float) ($arguments['violation_amount'] ?? 0);
            $externalNumber = trim($arguments['external_violation_number'] ?? '');
            $authority = trim($arguments['issuing_authority'] ?? __('smart_import.tools.create_violation_record.default_authority'));
            $date = trim($arguments['violation_date'] ?? date('Y-m-d'));
            $categoryName = trim($arguments['category_name'] ?? __('smart_import.tools.create_violation_record.default_category'));
            $typeName = trim($arguments['type_name'] ?? __('smart_import.tools.create_violation_record.default_type'));
            $description = trim($arguments['description'] ?? '');
            $paid = (float) ($arguments['paid_amount'] ?? 0);

            if ($amount <= 0) {
                return ['success' => false, 'message' => __('smart_import.tools.create_violation_record.amount_must_be_positive')];
            }

            $explicitCompanyId = ! empty($arguments['company_id']) ? (int) $arguments['company_id'] : null;

            if ($explicitCompanyId !== null && auth()->check()
                && ! in_array($explicitCompanyId, array_map('intval', CompanyFormService::getAuthorizedCompanyIds()), true)) {
                return ['success' => false, 'message' => __('violations.import.unauthorized_company')];
            }

            $companyId = $explicitCompanyId ?? auth()->user()?->company_id ?? Company::first()?->id ?? 1;

            if ($explicitCompanyId !== null && auth()->check()
                && ! in_array($explicitCompanyId, CompanyFormService::getAuthorizedCompanyIds(), true)) {
                return ['success' => false, 'message' => __('violations.import.unauthorized_company')];
            }

            $companyId = $explicitCompanyId ?? auth()->user()?->company_id ?? Company::first()?->id ?? 1;

            if (!empty($externalNumber)) {
                $duplicate = Violation::where('company_id', $companyId)
                    ->where('external_violation_number', $externalNumber)
                    ->first();
                if ($duplicate) {
                    return [
                        'success' => false,
                        'message' => __('smart_import.tools.create_violation_record.duplicate_external_number', [
                            'external_number' => $externalNumber,
                            'violation_number' => $duplicate->violation_number,
                        ]),
                    ];
                }
            }

            $category = ViolationCategory::firstOrCreate(
                ['name' => $categoryName ?: __('smart_import.tools.create_violation_record.default_category')],
                ['is_active' => true]
            );

            $type = ViolationType::firstOrCreate(
                ['name' => $typeName ?: __('smart_import.tools.create_violation_record.default_type'), 'violation_category_id' => $category->id],
                ['is_active' => true]
            );

            $remaining = max(0, $amount - $paid);
            $paymentStatus = $paid >= $amount ? ViolationPaymentStatus::PAID : ($paid > 0 ? ViolationPaymentStatus::PARTIAL : ViolationPaymentStatus::UNPAID);

            $violation = Violation::create([
                'company_id' => $companyId,
                'violation_category_id' => $category->id,
                'violation_type_id' => $type->id,
                'external_violation_number' => $externalNumber ?: null,
                'violation_date' => $date,
                'issuing_authority' => $authority,
                'violation_amount' => $amount,
                'paid_amount' => $paid,
                'remaining_amount' => $remaining,
                'description' => $description ?: __('smart_import.tools.create_violation_record.default_description'),
                'stage' => ViolationStage::CORRECTION,
                'payment_status' => $paymentStatus,
                'recorded_by' => auth()->id(),
            ]);

            return [
                'success' => true,
                'message' => __('smart_import.tools.create_violation_record.success_message', [
                    'amount' => number_format($amount, 2),
                    'authority' => $authority,
                    'violation_number' => $violation->violation_number,
                ]),
                'record_name' => __('smart_import.tools.create_violation_record.record_name', [
                    'violation_number' => $violation->violation_number,
                ]),
                'details' => [
                    'amount' => number_format($amount, 2) . ' ' . __('smart_import.fields.currency'),
                    'authority' => $authority,
                    'date' => $date,
                ],
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => __('smart_import.tools.create_violation_record.failed_message', [
                    'error' => $e->getMessage(),
                ]),
            ];
        }
    }
}
