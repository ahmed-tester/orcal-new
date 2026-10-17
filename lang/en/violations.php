<?php

return [
    // ====================================================
    // Violation Categories
    // ====================================================
    'categories' => [
        'single' => 'Violation Category',
        'plural' => 'Violation Categories & Classifications',
        'sections' => [
            'category_info' => 'Category Information',
            'category_info_desc' => 'Category name, description and status',
        ],
        'fields' => [
            'name' => 'Category Name (Arabic)',
            'name_en' => 'Category Name (English)',
            'description' => 'Description',
            'icon' => 'Icon',
            'is_active' => 'Active',
            'sort_order' => 'Sort Order',
            'types_count' => 'Types Count',
        ],
        'placeholders' => [
            'name' => 'e.g. Administrative Violations',
            'name_en' => 'e.g. Administrative Violations',
            'description' => 'Brief description of this category...',
        ],
        'table_heading' => 'Violation Categories — :count categories',
    ],

    // ====================================================
    // Violation Types
    // ====================================================
    'types' => [
        'single' => 'Violation Type',
        'plural' => 'Violation Types & Names',
        'sections' => [
            'type_info' => 'Violation Type Information',
            'type_info_desc' => 'Parent category, name and description',
        ],
        'fields' => [
            'category' => 'Category',
            'name' => 'Type Name (Arabic)',
            'name_en' => 'Type Name (English)',
            'description' => 'Description',
            'is_active' => 'Active',
            'sort_order' => 'Sort Order',
        ],
        'placeholders' => [
            'name' => 'e.g. Warning / Corrective / Financial',
            'name_en' => 'e.g. Warning / Corrective / Financial',
            'description' => 'Brief description of this type...',
        ],
        'table_heading' => 'Violation Types — :count types',
    ],

    // ====================================================
    // Violations
    // ====================================================
    'violations' => [
        'single' => 'Violation',
        'plural' => 'Violations Register',
        'sections' => [
            'company_info' => 'Company Information',
            'company_info_desc' => 'Select the company and its data will be fetched automatically',
            'violation_info' => 'Violation Details',
            'violation_info_desc' => 'Category, type, number, date, issuing authority and amount',
            'stages' => 'Violation Stages & Periods',
            'stages_desc' => 'Auto-calculated from violation date, can be modified',
            'payment' => 'Payment Status',
            'payment_desc' => 'Payment status, paid and remaining amounts',
        ],
        'fields' => [
            'violation_number' => 'Violation No. (Auto)',
            'company' => 'Company / Entity',
            'commercial_registration' => 'Commercial Registration No.',
            'company_phone' => 'Company Phone',
            'company_address' => 'Company Address',
            'category' => 'Violation Category',
            'type' => 'Violation Type',
            'external_violation_number' => 'External Violation Number',
            'violation_date' => 'Violation Date',
            'issuing_authority' => 'Issuing Authority',
            'violation_amount' => 'Violation Amount (SAR)',
            'description' => 'Description',
            'attachment' => 'Attachment',
            'correction_deadline' => 'Correction Period (14 days)',
            'objection_deadline' => 'Objection Period (60 days)',
            'grace_deadline' => 'Grace Period (90 days)',
            'legal_deadline' => 'Legal Deadline',
            'admin_execution_date' => 'Admin Execution Date',
            'stage' => 'Current Stage',
            'payment_status' => 'Payment Status',
            'paid_amount' => 'Paid Amount (SAR)',
            'remaining_amount' => 'Remaining Amount (SAR)',
            'closure_date' => 'Closure Date',
            'recorded_by' => 'Recorded By',
            'notes' => 'Notes',
        ],
        'placeholders' => [
            'auto_number' => 'Auto (e.g. VIO-C1-0001)',
            'external_number' => 'Violation number from issuing authority',
            'issuing_authority' => 'e.g. Municipality / Ministry of Labor / GOSI...',
            'description' => 'Violation details and description...',
            'notes' => 'Additional notes...',
        ],
        'helpers' => [
            'correction_deadline' => 'Auto-calculated = Violation date + 14 days',
            'objection_deadline' => 'Auto-calculated = Violation date + 60 days',
            'grace_deadline' => 'Auto-calculated = Violation date + 90 days',
            'legal_deadline' => 'Alert when exceeding 60 days',
            'remaining_amount' => 'Remaining = Total Amount − Paid Amount',
        ],
        'table_heading' => 'Violations Register — :count violations',
    ],

    // ====================================================
    // Violation Stages
    // ====================================================
    'stages' => [
        'correction' => 'Correction Period (14 days)',
        'objection' => 'Objection Period (60 days)',
        'grace' => 'Grace Period (90 days)',
        'legal' => 'Legal Deadline',
        'admin_execution' => 'Administrative Execution',
        'suspended' => 'Payment Due / Suspended',
    ],

    // ====================================================
    // Payment Statuses
    // ====================================================
    'payment_statuses' => [
        'pending' => 'Active — Pending Payment',
        'partial' => 'Active — Partial Payment',
        'paid' => 'Closed — Fully Paid',
    ],

    // ====================================================
    // Violation Treasury (Transactions)
    // ====================================================
    'transactions' => [
        'single' => 'Violation Fund Transaction',
        'plural' => 'Violations Collection Fund',
        'sections' => [
            'transaction_info' => 'Transaction Details',
            'transaction_info_desc' => 'Receipt or disbursement voucher for violation amounts with fund balance update',
        ],
        'fields' => [
            'voucher_number' => 'Voucher No. (Auto)',
            'transaction_date' => 'Transaction Date',
            'company' => 'Company / Entity',
            'violation' => 'Linked Violation',
            'transaction_type' => 'Voucher Type',
            'amount' => 'Amount (SAR)',
            'description' => 'Statement / Description',
            'executed_by' => 'Executed By',
            'balance_after' => 'Fund Balance After (Auto)',
        ],
        'placeholders' => [
            'auto_voucher' => 'Auto (e.g. VT-C1-0001)',
            'description' => 'Transaction statement and details...',
        ],
        'table_heading' => 'Violation Fund Transactions — :count transactions',
        'stats' => [
            'total_collected' => 'Total Collected',
            'total_collected_desc' => 'All receipt vouchers',
            'total_disbursed' => 'Total Disbursed',
            'total_disbursed_desc' => 'All payment & settlement vouchers',
            'current_balance' => 'Current Fund Balance',
            'current_balance_desc' => 'Collected − Disbursed',
            'pending_violations' => 'Pending Violations',
            'pending_violations_desc' => 'Violations not fully paid',
            'unit' => 'SAR',
        ],
        'reports' => [
            'title' => 'Violation Fund Reports',
            'all' => 'All',
            'by_company' => 'By Company',
            'by_period' => 'By Period',
        ],
    ],

    // ====================================================
    // Transaction Types
    // ====================================================
    'transaction_types' => [
        'incoming' => 'Receipt Voucher (Add to Fund)',
        'outgoing' => 'Disbursement Voucher (Pay Authority)',
    ],

    // ====================================================
    // Violation Regulations
    // ====================================================
    'regulations' => [
        'single' => 'Regulation / Policy',
        'plural' => 'Violations Regulations & Policies',
        'sections' => [
            'regulation_info' => 'Regulation Information',
            'regulation_info_desc' => 'Title and explanatory content',
        ],
        'fields' => [
            'title' => 'Title (Arabic)',
            'title_en' => 'Title (English)',
            'content' => 'Content & Explanation',
            'content_en' => 'Content (English)',
            'sort_order' => 'Sort Order',
            'is_active' => 'Active',
        ],
        'placeholders' => [
            'title' => 'Regulation or policy title',
            'content' => 'Write regulation details and explanation here...',
        ],
        'table_heading' => 'Violations Regulations — :count regulations',
    ],

    // ====================================================
    // Notifications
    // ====================================================
    'notifications' => [
        'legal_deadline_warning_title' => 'Warning: Legal Deadline Exceeded',
        'legal_deadline_warning_body' => 'Violation :number for :company has exceeded 60 days of the legal deadline',
        'admin_execution_title' => 'Alert: Administrative Execution Stage',
        'admin_execution_body' => 'Violation :number has entered administrative execution stage',
        'suspended_title' => 'Alert: Payment Due / Suspended',
        'suspended_body' => 'Violation :number for :company is now payment due',
        'stage_expiring_title' => 'Alert: Stage Expiring Soon',
        'stage_expiring_body' => 'Violation :number — :stage expires in :days days',
    ],
];
