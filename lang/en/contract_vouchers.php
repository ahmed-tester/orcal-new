<?php

return [
    'single' => 'Voucher / Payment Certificate',
    'plural' => 'Payment Vouchers & Certificates',
    'title' => 'Payment Vouchers & Certificates',

    'sections' => [
        'general_info' => 'Essential Details',
        'general_info_desc' => 'Select document type, date, amount, and the related party',
        'payment_details' => 'Payment Data & Method',
        'payment_details_desc' => 'Details of cash payments, cheques, or bank transfers',
        'contract_details' => 'Contract & Payment Request Data',
        'contract_details_desc' => 'Flexible fields to document the contract subject and payment request details',
        'notes_section' => 'Additional Notes',
    ],

    'types' => [
        'receipt' => 'Receipt Voucher — Certificate',
        'payment' => 'Payment Voucher',
        'request' => 'Payment Request',
    ],

    'payment_methods' => [
        'cash' => 'Cash',
        'check' => 'Cheque',
        'bank_transfer' => 'Bank Transfer',
    ],

    'statuses' => [
        'draft' => 'Draft',
        'approved' => 'Approved & Posted',
        'cancelled' => 'Cancelled',
    ],

    'fields' => [
        'voucher_type' => 'Document / Voucher Type',
        'voucher_number' => 'Voucher No.',
        'voucher_date' => 'Voucher Date',
        'amount' => 'Amount',
        'company' => 'Related Company',
        'offer' => 'Linked Contract (optional)',
        'customer' => 'Customer / Beneficiary Party',
        'payment_method' => 'Payment Method',
        'delivered_by' => 'Delivered By',
        'received_by' => 'Received By',
        'check_number' => 'Cheque No.',
        'bank_name' => 'Bank Name',
        'due_date' => 'Cheque Due Date',
        'transfer_bank_name' => 'Transfering / Transferred Bank',
        'account_number' => 'Account No. / IBAN',
        'reference_number' => 'Transaction Reference / Transfer No.',
        'first_party_name' => 'First Party Name',
        'second_party_name' => 'Second Party Name',
        'contract_number' => 'Contract No.',
        'contract_date' => 'Contract Date',
        'contract_subject' => 'Contract Subject',
        'payment_reason' => 'Payment Request Reason',
        'notes' => 'Notes',
        'status' => 'Voucher Status',
        'created_by' => 'Created By',
    ],

    'actions' => [
        'create' => 'Create New Voucher',
        'edit' => 'Edit',
        'delete' => 'Delete',
        'print' => 'Print Voucher',
        'save_and_print' => 'Save & Print Voucher',
        'download_pdf' => 'Download PDF',
        'approve' => 'Approve & Post to Treasury',
        'convert_receipt' => 'Convert to Receipt Voucher',
    ],

    'messages' => [
        'approved_success' => 'Voucher approved and treasury transaction posted successfully.',
        'already_approved' => 'This voucher is already approved and posted to the treasury.',
    ],

    'placeholders' => [
        'auto' => 'Auto (Auto)',
        'amount' => '0.00',
        'delivered_by' => 'Name of the person who delivered the amount...',
        'received_by' => 'Name of the amount recipient...',
        'check_number' => 'Bank cheque number...',
        'bank_name' => 'e.g. Al Rajhi Bank / NCB...',
        'account_number' => 'Account number or IBAN...',
        'reference_number' => 'Transfer or reference number...',
        'first_party' => 'First party (contractor / executor)...',
        'second_party' => 'Second party (owner / customer)...',
        'contract_number' => 'Contract number...',
        'contract_subject' => 'Description of the contract subject and scope...',
        'payment_reason' => 'Statement and reason of the due payment and progress stage...',
        'notes' => 'Any additional notes or terms...',
    ],
];
