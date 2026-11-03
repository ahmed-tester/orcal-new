<?php

return [

    'enums' => [
        'status' => [
            'active' => 'Active',
            'closed' => 'Closed',
        ],
        'voucher_type' => [
            'deposit'    => 'Deposit Voucher',
            'withdrawal' => 'Withdrawal Voucher',
        ],
        'source_type' => [
            'company' => 'Linked to Company',
            'free'    => 'Free Entry',
        ],
    ],

    'asset_funds' => [
        'single' => 'Asset Fund',
        'plural' => 'Asset Funds',
        'sections' => [
            'basic_info'      => 'Fund Basic Information',
            'basic_info_desc' => 'Set the fund name, manager, and establishment date',
            'management'      => 'Fund Management & Responsibilities',
            'management_desc' => 'Set the fund manager, accountant, and deposit/withdrawal terms',
        ],
        'fields' => [
            'fund_number'          => 'Fund Number',
            'fund_name'            => 'Fund Name',
            'established_date'     => 'Established Date',
            'description'          => 'Fund Description',
            'rules_and_conditions' => 'Deposit & Withdrawal Terms',
            'manager_name'         => 'Fund Manager Name',
            'manager_employee'     => 'Fund Manager (Saudi Employee)',
            'accountant_name'      => 'Fund Accountant Name',
            'accountant_employee'  => 'Fund Accountant (Saudi Employee)',
            'status'               => 'Fund Status',
            'notes'                => 'Notes',
            'balance'              => 'Current Balance',
            'total_deposits'       => 'Total Deposits',
            'total_withdrawals'    => 'Total Withdrawals',
            'vouchers_count'       => 'Vouchers Count',
        ],
        'helpers' => [
            'manager_employee'    => 'Select the fund manager from the system Saudi employees to grant permissions',
            'accountant_employee' => 'Select the fund accountant from the system Saudi employees to grant permissions',
        ],
    ],

    'asset_fund_vouchers' => [
        'single' => 'Asset Fund Voucher',
        'plural' => 'Asset Fund Vouchers',
        'sections' => [
            'voucher_info'       => 'Voucher Basic Information',
            'voucher_info_desc'  => 'Set the voucher type, target fund, and registration date',
            'source_info'        => 'Amount Source / Destination',
            'source_info_desc'   => 'Specify whether the amount is linked to a company or entered freely',
            'items'              => 'Amount Details',
            'items_desc'         => 'Enter the amount breakdown in the table below',
        ],
        'fields' => [
            'fund'           => 'Fund',
            'voucher_number' => 'Voucher Number',
            'type'           => 'Voucher Type',
            'voucher_date'   => 'Voucher Date',
            'total_amount'   => 'Total Amount',
            'source_type'    => 'Source / Destination Type',
            'company'        => 'Company',
            'party_name'     => 'Party / Entity Name',
            'notes'          => 'Notes & Description',
        ],
        'helpers' => [
            'party_name'   => 'Clearly state where the amount came from or where it went',
            'total_amount' => 'Auto-calculated from the sum of detail rows',
        ],
        'placeholders' => [
            'party_name' => 'Party name or reason for deposit / withdrawal...',
        ],
    ],

    'asset_fund_voucher_items' => [
        'actions' => [
            'add_item' => 'Add Detail Item',
        ],
        'fields' => [
            'description' => 'Description',
            'percentage'  => 'Percentage %',
            'sub_total'   => 'Sub Total',
            'extra_1'     => 'Other 1',
            'extra_2'     => 'Other 2',
            'row_total'   => 'Row Total',
        ],
    ],

    'statement' => [
        'title'           => 'Fund Movement Statement',
        'subtitle'        => 'View all operations on the fund during a specified period',
        'filter_section'  => 'Statement Filter Options',
        'results_section' => 'Statement Results',
        'summary_section' => 'Movement Summary',
        'fields' => [
            'fund'        => 'Fund',
            'date_from'   => 'From Date',
            'date_to'     => 'To Date',
            'filter_type' => 'Operation Type',
        ],
        'filter_types' => [
            'all'        => 'All (Deposits + Withdrawals)',
            'deposit'    => 'Deposits Only',
            'withdrawal' => 'Withdrawals Only',
        ],
        'summary' => [
            'total_deposits'    => 'Total Deposits',
            'total_withdrawals' => 'Total Withdrawals',
            'net_balance'       => 'Net Balance',
        ],
        'actions' => [
            'export_pdf'   => 'Print / PDF',
            'apply_filter' => 'Apply Filter',
        ],
        'no_results' => 'No movements found for the selected period',
        'select_fund_placeholder' => '— Select Fund —',
        'please_select_fund' => 'Select a fund from the list above to view its statement',
        'currency' => 'SAR',
        'unauthorized' => 'You are not authorized to view this fund statement.',
    ],

    'notifications' => [
        'fund_created'    => 'Fund created successfully.',
        'fund_updated'    => 'Fund updated successfully.',
        'fund_deleted'    => 'Fund deleted successfully.',
        'voucher_created' => 'Voucher registered successfully.',
        'voucher_updated' => 'Voucher updated successfully.',
        'voucher_deleted' => 'Voucher deleted successfully.',
    ],
];

