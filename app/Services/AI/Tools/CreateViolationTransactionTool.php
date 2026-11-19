<?php

namespace App\Services\AI\Tools;

use App\Enums\ViolationTransactionType;
use App\Models\Company;
use App\Models\Violation;
use App\Models\ViolationTransaction;
use App\Services\AI\Contracts\GeminiToolInterface;
use App\Services\CompanyFormService;

class CreateViolationTransactionTool implements GeminiToolInterface
{
    public function getName(): string
    {
        return 'create_violation_transaction';
    }

    public function getDeclaration(): array
    {
        return [
            'name' => $this->getName(),
            'description' => __('smart_import.tools.create_violation_transaction.description'),
            'parameters' => [
                'type' => 'OBJECT',
                'properties' => [
                    'amount' => [
                        'type' => 'NUMBER',
                        'description' => __('smart_import.tools.create_violation_transaction.amount_desc'),
                    ],
                    'violation_identifier' => [
                        'type' => 'STRING',
                        'description' => __('smart_import.tools.create_violation_transaction.violation_identifier_desc'),
                    ],
                    'transaction_type' => [
                        'type' => 'STRING',
                        'description' => __('smart_import.tools.create_violation_transaction.transaction_type_desc'),
                    ],
                    'transaction_date' => [
                        'type' => 'STRING',
                        'description' => __('smart_import.tools.create_violation_transaction.transaction_date_desc'),
                    ],
                    'description' => [
                        'type' => 'STRING',
                        'description' => __('smart_import.tools.create_violation_transaction.description_desc'),
                    ],
                ],
                'required' => ['amount'],
            ],
        ];
    }

    public function execute(array $arguments): array
    {
        try {
            $amount = (float) ($arguments['amount'] ?? 0);
            if ($amount <= 0) {
                return ['success' => false, 'message' => __('smart_import.tools.create_violation_transaction.amount_must_be_positive')];
            }

            $explicitCompanyId = ! empty($arguments['company_id']) ? (int) $arguments['company_id'] : null;

            if ($explicitCompanyId !== null && auth()->check()
                && ! in_array($explicitCompanyId, array_map('intval', CompanyFormService::getAuthorizedCompanyIds()), true)) {
                return ['success' => false, 'message' => __('violations.import.unauthorized_company')];
            }

            $companyId = $explicitCompanyId ?? auth()->user()?->company_id ?? Company::first()?->id ?? 1;

            $violationId = null;
            if (!empty($arguments['violation_identifier'])) {
                $ident = trim($arguments['violation_identifier']);
                $viol = Violation::where('company_id', $companyId)
                    ->where(function ($q) use ($ident) {
                        $q->where('id', $ident)
                          ->orWhere('violation_number', $ident);
                    })->first();
                $violationId = $viol?->id;
            }

            $typeInput = strtolower(trim($arguments['transaction_type'] ?? 'incoming'));
            $txType = ($typeInput === 'outgoing') ? ViolationTransactionType::OUTGOING : ViolationTransactionType::INCOMING;
            $txDate = !empty($arguments['transaction_date']) ? $arguments['transaction_date'] : date('Y-m-d');

            $tx = ViolationTransaction::create([
                'company_id'       => $companyId,
                'violation_id'     => $violationId,
                'transaction_type' => $txType,
                'amount'           => $amount,
                'transaction_date' => $txDate,
                'description'      => $arguments['description'] ?? __('smart_import.tools.create_violation_transaction.default_description'),
                'executed_by'      => auth()->id(),
            ]);

            $typeLabel = ($txType === ViolationTransactionType::INCOMING)
                ? __('smart_import.tools.create_violation_transaction.type_incoming')
                : __('smart_import.tools.create_violation_transaction.type_outgoing');

            return [
                'success' => true,
                'message' => __('smart_import.tools.create_violation_transaction.success_message', [
                    'type' => $typeLabel,
                    'amount' => number_format($amount, 2),
                    'voucher_number' => $tx->voucher_number,
                ]),
                'transaction_id' => $tx->id,
                'name' => __('smart_import.tools.create_violation_transaction.record_name', [
                    'voucher_number' => $tx->voucher_number,
                ]),
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => __('smart_import.tools.create_violation_transaction.failed_message', [
                    'error' => $e->getMessage(),
                ]),
            ];
        }
    }
}
