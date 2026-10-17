<?php

namespace Database\Seeders;

use App\Models\ContractCategory;
use App\Services\ContractTemplateService;
use Illuminate\Database\Seeder;

class ContractCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name' => __('offers.types.quotation'),
                'code' => 'QUOTATION',
                'default_template' => ContractTemplateService::getQuotationTemplate(),
                'sort_order' => 1,
            ],
            [
                'name' => __('offers.types.supply_offer'),
                'code' => 'SUPPLY_OFFER',
                'default_template' => ContractTemplateService::getSupplyOfferTemplate(),
                'sort_order' => 2,
            ],
            [
                'name' => __('offers.types.unified_contract'),
                'code' => 'UNIFIED_CONTRACT',
                'default_template' => ContractTemplateService::getUnifiedContractTemplate(),
                'sort_order' => 3,
            ],
            [
                'name' => __('offers.types.agreement_contract'),
                'code' => 'AGREEMENT_CONTRACT',
                'default_template' => ContractTemplateService::getAgreementContractTemplate(),
                'sort_order' => 4,
            ],
            [
                'name' => __('offers.types.sale_agreement'),
                'code' => 'SALE_AGREEMENT',
                'default_template' => ContractTemplateService::getSaleAgreementTemplate(),
                'sort_order' => 5,
            ],
        ];

        foreach ($categories as $cat) {
            ContractCategory::firstOrCreate(
                ['code' => $cat['code']],
                [
                    'name' => $cat['name'],
                    'default_template' => $cat['default_template'],
                    'sort_order' => $cat['sort_order'],
                    'is_active' => true,
                ]
            );
        }
    }
}
