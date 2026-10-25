<?php

return [
    'groups' => [
        'funds' => 'Mutual Funds & Societies',
        'salifni' => 'Salifni Fund',
        'rosca' => 'ROSCA Society Fund',
    ],

    'enums' => [
        'payment_status' => [
            'paid' => 'Fully Paid',
            'partial' => 'Partially Paid',
            'unpaid' => 'Unpaid',
        ],
        'general_status' => [
            'active' => 'Active',
            'completed' => 'Completed',
            'suspended' => 'Suspended',
            'inactive' => 'Inactive',
            'defaulted' => 'Defaulted',
            'draft' => 'Draft',
            'closed' => 'Closed',
            'dropped' => 'Dropped',
        ],
        'installment_period' => [
            'monthly' => 'Monthly',
            'yearly' => 'Yearly',
        ],
        'allocation_method' => [
            'lottery' => 'Lottery',
            'consensus' => 'Consensus',
            'prearranged' => 'Prearranged Order',
        ],
        'member_type' => [
            'employee' => 'Company Employee',
            'external' => 'External Member (Family / Friends)',
        ],
    ],

    'loan_funds' => [
        'single' => 'Salifni Fund',
        'plural' => 'Salifni Funds',
        'sections' => [
            'basic_info' => 'Basic Fund Information',
            'basic_info_desc' => 'Define fund name, target pool, and maximum loan parameters',
            'rules' => 'Rules & Regulations',
        ],
        'fields' => [
            'fund_number' => 'Fund Number',
            'fund_name' => 'Fund Name',
            'company' => 'Company / Entity',
            'target_amount' => 'Target Pool Amount',
            'installment_period' => 'Payment Frequency',
            'default_installments_count' => 'Annual Installments Count',
            'max_loan_limit' => 'Maximum Loan Limit per Member',
            'max_repayment_months' => 'Maximum Repayment Months',
            'status' => 'Status',
            'trustee_employee' => 'Fund Trustee (Employee)',
            'trustee_name' => 'Trustee Name',
            'rules_and_conditions' => 'Regulations & Bylaws',
            'notes' => 'Additional Notes',
        ],
    ],

    'loan_fund_members' => [
        'single' => 'Salifni Fund Member',
        'plural' => 'Salifni Fund Members',
        'sections' => [
            'member_info' => 'Member Profile',
            'financial_info' => 'Subscription & Installments',
        ],
        'fields' => [
            'fund' => 'Salifni Fund',
            'member_type' => 'Member Type',
            'employee' => 'Employee',
            'full_name' => 'Full Name',
            'national_id' => 'National ID / Iqama',
            'phone' => 'Phone Number',
            'address' => 'Address',
            'installments_count' => 'Installments Count',
            'monthly_installment' => 'Monthly Amount',
            'total_annual_amount' => 'Total Annual Amount',
            'paid_amount' => 'Paid Amount',
            'remaining_amount' => 'Remaining Amount',
            'payment_status' => 'Payment Status',
            'joined_date' => 'Join Date',
            'status' => 'Status',
            'notes' => 'Notes',
        ],
        'actions' => [
            'quick_pay' => 'Record Subscription Payment',
            'quick_pay_heading' => 'Record Member Subscription Payment',
            'quick_pay_success' => 'Subscription payment recorded and member balance updated successfully.',
        ],
        'helpers' => [
            'total_annual_amount' => 'Calculated automatically: Installments count × Monthly amount',
            'remaining_amount' => 'Calculated automatically: Total annual amount − Paid amount',
        ],
    ],

    'loan_fund_member_payments' => [
        'single' => 'Subscription Payment',
        'plural' => 'Subscription Payment Sheet',
        'sections' => [
            'payment_info' => 'Subscription Payment Details',
        ],
        'fields' => [
            'fund' => 'Salifni Fund',
            'member' => 'Fund Member',
            'voucher_number' => 'Voucher Number',
            'payment_date' => 'Payment Date',
            'period_month' => 'Due Month',
            'installment_order' => 'Installment #',
            'amount' => 'Amount Paid',
            'payment_method' => 'Payment Method',
            'payment_status' => 'Payment Status',
            'notes' => 'Notes',
        ],
        'methods' => [
            'cash' => 'Cash / Treasury',
            'bank_transfer' => 'Bank Transfer',
            'payroll_deduction' => 'Payroll Deduction',
        ],
    ],

    'loan_fund_loans' => [
        'single' => 'Fund Loan',
        'plural' => 'Granted Loans (Salifni)',
        'sections' => [
            'loan_info' => 'Loan Details & Beneficiary',
            'repayment_schedule' => 'Repayment Schedule',
        ],
        'fields' => [
            'fund' => 'Salifni Fund',
            'loan_number' => 'Loan Number',
            'member' => 'Beneficiary Member (Registered Only)',
            'loan_amount' => 'Loan Amount',
            'loan_date' => 'Disbursement Date',
            'start_due_date' => 'First Due Date',
            'installments_count' => 'Installments Count',
            'monthly_installment' => 'Monthly Installment',
            'paid_amount' => 'Total Paid',
            'remaining_amount' => 'Remaining Balance',
            'status' => 'Loan Status',
            'is_closed' => 'Close Voucher after Completion',
            'closure_date' => 'Closure Date',
            'approved_by' => 'Approved By',
            'notes' => 'Notes',
        ],
        'helpers' => [
            'monthly_installment' => 'Auto-calculated: Loan Amount ÷ Installments Count',
            'remaining_amount' => 'Auto-calculated: Loan Amount − Total Paid',
        ],
    ],

    'loan_fund_loan_payments' => [
        'single' => 'Loan Repayment',
        'plural' => 'Loan Repayments',
        'sections' => [
            'payment_info' => 'Repayment Details',
        ],
        'fields' => [
            'loan' => 'Target Loan',
            'member' => 'Beneficiary Member (Active Loan Only)',
            'voucher_number' => 'Voucher Number',
            'installment_order' => 'Installment Number',
            'installment_date' => 'Payment Date',
            'scheduled_amount' => 'Scheduled Amount',
            'collected_amount' => 'Collected Amount',
            'remaining_balance' => 'Remaining Balance (Auto)',
            'is_closed' => 'Close Voucher on Completion',
            'notes' => 'Notes',
        ],
        'helpers' => [
            'remaining_balance' => 'Auto-retrieved from current remaining loan balance',
        ],
    ],

    'rosca_funds' => [
        'single' => 'ROSCA Society Fund',
        'plural' => 'ROSCA Society Funds',
        'sections' => [
            'basic_info' => 'Society Cycle Information',
            'management' => 'Management & Rules',
        ],
        'fields' => [
            'fund_number' => 'Fund Code',
            'fund_name' => 'Society Name',
            'company' => 'Company / Entity',
            'start_date' => 'Start Date',
            'end_date' => 'Estimated End Date',
            'members_count' => 'Members Count (Months)',
            'monthly_installment' => 'Monthly Installment',
            'total_pool_amount' => 'Total Monthly Pool',
            'allocation_method' => 'Turn Allocation Method',
            'status' => 'Status',
            'assigned_manager' => 'Assigned Manager',
            'manager_name' => 'Manager Name',
            'rules_and_conditions' => 'Society Rules & Bylaws',
            'notes' => 'Notes',
        ],
        'helpers' => [
            'total_pool_amount' => 'Auto-calculated: Members Count × Monthly Installment',
        ],
    ],

    'rosca_fund_members' => [
        'single' => 'ROSCA Member',
        'plural' => 'ROSCA Members',
        'sections' => [
            'member_info' => 'Member Profile',
            'financial_turn' => 'Turn & Installment Details',
        ],
        'fields' => [
            'fund' => 'ROSCA Fund',
            'member_type' => 'Member Type',
            'employee' => 'Employee',
            'full_name' => 'Full Name',
            'national_id' => 'National ID',
            'phone' => 'Phone Number',
            'address' => 'Address',
            'turn_order' => 'Turn Order',
            'payout_month' => 'Scheduled Payout Month',
            'installments_count' => 'Monthly Installments Count',
            'monthly_installment' => 'Monthly Installment',
            'total_final_amount' => 'Total Payout Amount',
            'paid_amount' => 'Paid So Far',
            'remaining_amount' => 'Remaining Amount',
            'has_received_payout' => 'Payout Received',
            'payout_date' => 'Actual Payout Date',
            'status' => 'Status',
        ],
        'helpers' => [
            'total_final_amount' => 'Auto-calculated: Installments Count × Monthly Installment',
        ],
    ],

    'rosca_fund_payments' => [
        'single' => 'ROSCA Payment',
        'plural' => 'ROSCA Monthly Payment Sheet',
        'sections' => [
            'payment_info' => 'Payment Details',
        ],
        'fields' => [
            'fund' => 'ROSCA Fund',
            'member' => 'Society Member',
            'period_month' => 'Due Month',
            'installment_order' => 'Installment #',
            'scheduled_amount' => 'Scheduled Amount',
            'paid_amount' => 'Paid Amount',
            'payment_status' => 'Payment Status',
            'is_bond_closed' => 'Close Voucher after Cycle Ends',
            'payment_date' => 'Actual Payment Date',
            'notes' => 'Notes',
        ],
    ],

    'policy_pages' => [
        'salifni_title' => 'Salifni Fund Policy & Regulations',
        'salifni_subtitle' => 'By-laws, loan eligibility criteria, and repayment tables',
        'salifni_section_title' => 'Formulate Salifni Fund By-laws & Regulations',
        'salifni_section_desc' => 'Edit and formulate the approved policy articles and terms via the rich editor below.',

        'rosca_title' => 'ROSCA Society Fund Policy & Regulations',
        'rosca_subtitle' => 'Mutual savings bylaws, turn allocation criteria, and obligations',
        'rosca_section_title' => 'Formulate ROSCA Society By-laws & Regulations',
        'rosca_section_desc' => 'Edit and formulate the approved society articles and terms via the rich editor below.',

        'field_title' => 'Main Policy Title',
        'field_subtitle' => 'Introductory Subtitle',
        'field_content' => 'Content & Description',
        'field_content_placeholder' => 'Write policy explanation and terms here...',

        'actions' => [
            'save' => 'Save & Update Policy',
            'reset_default' => 'Restore Default Template',
            'reset_salifni_heading' => 'Restore Default Salifni Template',
            'reset_rosca_heading' => 'Restore Default ROSCA Template',
            'reset_modal_desc' => 'Are you sure you want to restore the default proposed policy template?',
        ],

        'notifications' => [
            'salifni_saved' => 'Salifni fund policy saved and updated successfully.',
            'rosca_saved' => 'ROSCA society fund policy saved and updated successfully.',
            'reset_info' => 'Default template restored. Click Save to apply changes.',
        ],
    ],
];
