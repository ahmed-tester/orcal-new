<?php

return [
    'single' => 'Public Debt',
    'plural' => 'Public Debts Registry',

    'sections' => [
        'debtor_source' => 'Debtor Source & Info',
        'debtor_source_desc' => 'Specify whether the debt is linked to an entity/employee or an external party',
        'internal_debtor' => 'Internal Entity & Debtor Details',
        'internal_debtor_desc' => 'Company and employee associated with the debt',
        'external_legal_info' => 'External Debtor Legal Records',
        'external_legal_info_desc' => 'Official identity records for legal documentation and claims',
        'guarantor_info' => 'Guarantor Information (Optional)',
        'guarantor_info_desc' => 'Add an approved guarantor committed to settling the debt',
        'financial' => 'Financial Information & Principal Debt',
        'financial_desc' => 'Original amount, debt reason, collected, and remaining',
        'notification' => 'Due Notification & Follow-up',
        'notification_desc' => 'Assigned follow-up officer, due date, and alert notes',
    ],

    'fields' => [
        'debt_number' => 'Debt Number (Auto)',
        'debt_date' => 'Debt Inception Date',
        'due_date' => 'Due Date',
        'debtor_type' => 'Debtor Source',
        'company' => 'Company / Entity',
        'employee' => 'Debtor Employee',
        'debtor' => 'Debtor Name',
        'external_name' => 'Debtor / Entity Full Name',
        'external_national_id' => 'National ID / CR Number',
        'external_phone' => 'Contact Phone',
        'external_email' => 'Email Address',
        'external_address' => 'National Address & Residence',
        'guarantor' => 'Guarantor',
        'total_amount' => 'Total Debt Amount (SAR)',
        'paid_amount' => 'Collected Amount (SAR)',
        'remaining_amount' => 'Remaining Debt (SAR)',
        'debt_reason' => 'Debt Reason / Item',
        'description' => 'Details & Terms',
        'status' => 'Debt Status',
        'notify_employee' => 'Send Alert To',
        'notification_notes' => 'Alert & Follow-up Notes',
        'executed_by' => 'Registered By',
    ],

    'toggle_options' => [
        'internal' => 'Internal Services (Entities & Employees)',
        'external' => 'External (Direct Registry)',
    ],

    'badges' => [
        'internal' => 'Internal',
        'external' => 'External',
        'pending' => 'Due',
        'partial' => 'Partially Paid',
        'paid' => 'Fully Paid',
        'overdue' => 'Overdue',
    ],

    'statuses' => [
        'pending' => 'Due (Uncollected)',
        'partial' => 'Partially Collected',
        'paid' => 'Fully Paid',
        'overdue' => 'Overdue',
    ],

    'placeholders' => [
        'external_name' => 'Full debtor or entity name',
        'external_national_id' => 'National ID, Iqama, or 700 Number',
        'external_phone' => '05xxxxxxxx',
        'external_address' => 'City, District, National Address',
        'debt_reason' => 'e.g. Credit purchases / Contract balances',
        'notification_notes' => 'Notice: Debt due date reached — please follow up for collection',
    ],

    'table_heading' => 'Public Debts — :count debts',

    // Collections
    'collections' => [
        'single' => 'Debt Collection Voucher',
        'plural' => 'Debt Collections',
        'sections' => [
            'collection_info' => 'Collection Voucher Details',
            'collection_info_desc' => 'Voucher number, linked debt, collection date, and payment amount',
        ],
        'fields' => [
            'status' => 'Status',
            'voucher_number' => 'Voucher No. (Auto)',
            'collection_date' => 'Collection Date',
            'debt' => 'Linked Debt',
            'amount' => 'Collected Amount (SAR)',
            'payment_method' => 'Payment Method',
            'treasury_transaction' => 'Linked Treasury Receipt Voucher',
            'description' => 'Description & Notes',
            'executed_by' => 'Collected By',
        ],
        'actions' => [
            'approve' => 'Approve Voucher',
            'unapprove' => 'Unapprove Voucher',
            'approved_success' => 'Debt collection voucher approved successfully.',
            'unapproved_success' => 'Debt collection voucher approval cancelled.',
        ],
        'payment_methods' => [
            'cash' => 'Cash (Treasury)',
            'bank_transfer' => 'Bank Transfer',
            'cheque' => 'Bank Cheque',
        ],
        'table_heading' => 'Debt Collection Vouchers — :count vouchers',
    ],
];
