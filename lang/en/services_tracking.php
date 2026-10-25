<?php

return [
    'single' => 'Service Transaction',
    'plural' => 'Services & Tracking',

    'sections' => [
        'scope_and_date' => 'Service Scope & Date',
        'scope_and_date_desc' => 'Specify whether the service is internal (company-affiliated) or external',
        'internal_service' => 'Internal Service Details',
        'internal_service_desc' => 'Select company, service target (company or employee), and required service',
        'external_service' => 'External Service Details',
        'external_service_desc' => 'Enter first party, beneficiary details, and required service name',
        'financial' => 'Financials & Fees',
        'financial_desc' => 'Total service cost, paid amount, and auto-calculated remaining balance',
        'execution' => 'Execution & Administrative Tracking',
        'execution_desc' => 'Executing employee, transaction status, due dates, and notes',
    ],

    'fields' => [
        'transaction_number' => 'Transaction Number',
        'transaction_date' => 'Transaction Date',
        'scope_type' => 'Scope Type',
        'service_target' => 'Internal Service Target',
        'company' => 'Company / Entity',
        'employee' => 'Employee',
        'service' => 'Required Service',
        'service_name' => 'Service Name',
        'service_type' => 'Service Type',
        'first_party' => 'First Party (Service Provider)',
        'beneficiary_name' => 'Beneficiary Name',
        'beneficiary_phone' => 'Beneficiary Phone',
        'beneficiary_national_id' => 'ID / CR Number',
        'total_amount' => 'Total Service Cost (SAR)',
        'paid_amount' => 'Paid Amount (SAR)',
        'remaining_amount' => 'Remaining Amount (SAR)',
        'payment_status' => 'Payment Status',
        'service_status' => 'Service Status',
        'executed_by' => 'Executed By',
        'due_date' => 'Expected Completion Date',
        'completion_date' => 'Actual Completion Date',
        'notes' => 'Notes & Details',
        'status' => 'Approval Status',
        'created_at' => 'Created At',
    ],

    'actions' => [
        'approve' => 'Approve',
        'unapprove' => 'Unapprove',
        'approved_success' => 'Service transaction approved successfully',
        'unapproved_success' => 'Service transaction approval cancelled',
    ],

    'toggle_options' => [
        'internal' => 'Internal (Companies list)',
        'external' => 'External (Freeform)',
        'target_company' => 'Company-wide service',
        'target_employee' => 'Employee service',
    ],

    'badges' => [
        'internal' => 'Internal',
        'external' => 'External',
        'company' => 'Company',
        'employee' => 'Employee',
        'active' => 'Active (In Progress)',
        'completed' => 'Completed',
        'inactive' => 'Inactive / Cancelled',
        'unpaid' => 'Unpaid',
        'partial' => 'Partially Paid',
        'paid' => 'Fully Paid',
    ],

    'service_statuses' => [
        'active' => 'Active (In Progress)',
        'completed' => 'Completed',
        'inactive' => 'Inactive / Cancelled',
    ],

    'payment_statuses' => [
        'unpaid' => 'Unpaid',
        'partial' => 'Partially Paid',
        'paid' => 'Fully Paid',
    ],

    'categories' => [
        'cr_licenses' => 'CR & Licenses',
        'municipal' => 'Municipal & Permits',
        'work_permits' => 'Qiwa & Work Permits',
        'passports' => 'Passports & Iqamas',
        'visas' => 'Visas',
        'insurance' => 'Medical Insurance',
        'other' => 'Other Services',
    ],

    'quick_add' => [
        'modal_title' => 'Add New Service to Catalog',
        'name' => 'Service Name',
        'category' => 'Category',
        'target_type' => 'Target Entity',
        'price' => 'Default Price (SAR)',
        'notes' => 'Service Notes & Details',
    ],

    'placeholders' => [
        'service_name' => 'e.g., Visa Authorization / Branch CR Extraction',
        'service_type' => 'e.g., Processing / Legal Consultation',
        'first_party' => 'e.g., Services Office / Central Admin',
        'beneficiary_name' => 'Full Name of Beneficiary or Entity',
        'beneficiary_phone' => '05xxxxxxxx',
        'beneficiary_national_id' => 'National ID / Iqama / CR Number',
        'notes' => 'Terms, notes, or execution requirements',
    ],

    'table_heading' => 'Services & Tracking Log — :count transactions',
];
