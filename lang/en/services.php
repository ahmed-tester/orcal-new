<?php

return [
    'single' => 'Service',
    'plural' => 'Services Price List',
    'sections' => [
        'info' => 'Service & Price Information',
        'info_desc' => 'Define service name, company and approved customer price',
    ],
    'fields' => [
        'company' => 'Company',
        'name' => 'Service Name',
        'price' => 'Price (SAR)',
        'status' => 'Status',
        'notes' => 'Additional Notes',
    ],
    'statuses' => [
        'active' => 'Active',
        'inactive' => 'Inactive',
    ],
    'scope_type' => 'Service Scope Type',
    'scope_general' => 'General Service across all entities (Free / Unlinked)',
    'scope_company' => 'Dedicated Company Service (Linked to Company)',
    'beneficiary_name' => 'Beneficiary / Customer Name (Optional)',
    'beneficiary_placeholder' => 'Enter beneficiary name if available or leave blank...',
    'name_placeholder' => 'e.g. Iqama Renewal, Visa Issue, Work Permit...',
    'general' => 'General',
    'estimated_gov_fee' => 'Estimated Government Fee (SAR)',
];
