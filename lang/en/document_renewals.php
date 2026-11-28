<?php

return [
    'navigation_label' => 'Document Renewals',
    'title' => 'Document Renewal Requests',

    'empty_state' => [
        'heading' => 'No document renewal requests',
        'description' => 'There are no renewal requests available or assigned to you within your current scope.',
    ],

    'types' => [
        'iqama' => 'Iqama Renewal',
        'commercial_registration' => 'Commercial Registration Renewal',
        'municipality' => 'Municipality License Renewal',
        'civil_defense' => 'Civil Defense License Renewal',
        'health_insurance' => 'Health Insurance Renewal',
        'work_permit' => 'Work Permit Renewal',
        'passport' => 'Passport Renewal',
        'employee_contract' => 'Employee Contract Renewal',
        'subscription_qiwa' => 'Qiwa Subscription Renewal',
        'subscription_absher' => 'Absher Business / Muqeem Renewal',
        'subscription_spl' => 'SPL Subscription Renewal',
    ],

    'permissions' => [
        'manage_iqama_renewals' => 'Manage Iqama Renewals',
        'manage_company_license_renewals' => 'Manage Company License Renewals (CR, Municipality, Civil Defense)',
        'manage_health_insurance_renewals' => 'Manage Employee Health Insurance Renewals',
        'manage_work_permit_renewals' => 'Manage Work Permit Renewals',
        'manage_passport_renewals' => 'Manage Passport Renewals',
        'manage_employee_contract_renewals' => 'Manage Employee Contract Renewals',
        'manage_qiwa_subscription_renewals' => 'Manage Qiwa Subscription Renewals',
        'manage_absher_subscription_renewals' => 'Manage Absher / Muqeem Subscription Renewals',
        'manage_spl_subscription_renewals' => 'Manage SPL Subscription Renewals',
    ],

    'statuses' => [
        'available' => 'Available to Claim',
        'claimed' => 'In Progress',
        'pending_treasury' => 'Pending Treasury Approval',
        'returned' => 'Returned for Correction',
        'completed' => 'Completed & Approved',
        'cancelled' => 'Cancelled',
    ],

    'fee_modes' => [
        'with_fee' => 'With Renewal Fee',
        'no_fee' => 'No Fee — No Disbursement',
    ],

    'payment_methods' => [
        'cash' => 'Cash',
        'bank_transfer' => 'Bank Transfer',
    ],

    'settings' => [
        'section_title' => 'Document Renewal & Scoring Settings',
        'section_desc' => 'Configure request lead days, execution deadline from claim, task weight, and late completion score percentage.',
        'lead_days' => 'Lead Days Before Expiry',
        'lead_days_helper' => 'Zero creates requests upon expiry only; a positive value creates requests this many days before expiry.',
        'deadline_days' => 'Execution Deadline Days (From Claim)',
        'deadline_days_helper' => 'Starts from the manager claim date and is locked once without resetting on return.',
        'task_weight' => 'Renewal Task Weight',
        'task_weight_helper' => 'Full score awarded when the accepted submission is delivered on time.',
        'late_completion_percentage' => 'Late Completion Score Share (%)',
        'late_completion_percentage_helper' => 'Awarded share of task weight (0–100%) for late completion, not a daily penalty.',
        'tracking_start_date' => 'Historical Expiry Cutoff Date (Optional)',
        'tracking_start_date_helper' => 'Leave empty to include all due documents, or specify a start date to exclude older expiries.',
    ],

    'layer_one' => [
        'permissions_label' => 'Document Renewal Permissions for Administration Manager',
        'permissions_helper' => 'Granted directly to the Layer 1 manager user and transferred automatically upon replacement.',
        'kept_other_admin_title' => 'Previous Manager Permissions Retained',
        'kept_other_admin_body' => 'The previous manager still manages another active Layer 1 administration, so permissions were granted to the new manager and kept for the previous one.',
        'role_inherited_warning_title' => 'Role-Inherited Renewal Permissions Detected',
        'role_inherited_warning_body' => 'Some renewal permissions are inherited via a general role; the role was not modified automatically.',
    ],

    'catalog' => [
        'system_task_description' => 'Reserved system task catalog entry for :type.',
        'errors' => [
            'cannot_modify_system_task' => 'Cannot modify the code of a reserved system renewal task.',
            'cannot_deactivate_system_task' => 'Cannot deactivate a reserved system renewal task.',
            'cannot_delete_system_task' => 'Cannot delete a reserved system renewal task to preserve evaluation history.',
        ],
    ],

    'request' => [
        'title_format' => ':type — :target',
        'description_format' => ':type request for :target (cycle expiry: :expiry).',
    ],

    'invoice' => [
        'draft_notes' => 'Draft renewal expense invoice for :type — :target',
        'fee_line_item' => ':type Fees',
        'no_fee_badge' => 'No Fee — No Disbursement',
    ],

    'treasury' => [
        'disbursement_description' => 'Renewal fee disbursement for :type — :target (Invoice :invoice)',
    ],

    'columns' => [
        'task_code' => 'Request Code',
        'company' => 'Company',
        'document_type' => 'Document Type',
        'target' => 'Document Owner',
        'cycle_expiry' => 'Cycle Expiry',
        'due_date' => 'Due Date',
        'status' => 'Status',
        'assignee' => 'Assigned Manager',
        'fee_summary' => 'Fee / Mode',
        'proposed_expiry' => 'Proposed Expiry',
    ],

    'filters' => [
        'status' => 'Request Status',
        'document_type' => 'Document Type',
        'mine_only' => 'My Claimed Requests Only',
    ],

    'actions' => [
        'view_details' => 'Details',
        'claim' => 'Claim & Start',
        'claim_success' => 'Renewal request claimed and task assignment created.',
        'claim_already_yours' => 'This request is already claimed by you.',
        'submit_treasury' => 'Complete & Submit to Treasury',
        'submit_success' => 'Renewal details and invoice submitted for treasury approval.',
        'approve_treasury' => 'Approve & Close Renewal',
        'approve_success' => 'Final approval completed, document expiry updated, and task closed.',
        'return_treasury' => 'Return for Correction',
        'return_success' => 'Renewal request returned to assignee for correction.',
        'reassign' => 'Reassign Request',
        'reassign_success' => 'Renewal request reassigned while preserving task item and invoice identity.',
    ],

    'fields' => [
        'fee_mode' => 'Fee Mode',
        'amount' => 'Total Renewal Fee',
        'new_expiry_date' => 'New Expiry Date',
        'payment_method' => 'Payment Method',
        'reference_number' => 'Reference / Voucher Number',
        'proof_path' => 'Payment Proof (Optional)',
        'document_attachment' => 'Renewed Document Attachment (Optional)',
        'notes' => 'Notes',
        'return_reason' => 'Return Reason',
        'approval_note' => 'Approval Note (Optional)',
        'target_employee' => 'New Assignee',
        'reassignment_reason' => 'Reassignment Reason',
    ],

    'details' => [
        'modal_heading' => 'Document Renewal Request Details',
        'close' => 'Close',
        'submission_history' => 'Submission & Review History',
    ],

    'notifications' => [
        'available_title' => 'Document Renewal Request Available',
        'available_body' => 'A :type request for :target (cycle expiry: :expiry) is available to claim.',
        'pending_treasury_title' => 'Document Renewal Pending Treasury Approval',
        'pending_treasury_body' => ':employee submitted :title for treasury review and approval.',
        'returned_title' => 'Document Renewal Returned for Correction',
        'returned_body' => ':title was returned by treasury. Reason: :reason',
        'approved_title' => 'Document Renewal Approved & Closed',
        'approved_body' => ':title has been approved and expiry updated to :expiry (Score: :score).',
    ],

    'errors' => [
        'invalid_request_type' => 'The selected record is not a document renewal request.',
        'already_claimed_or_closed' => 'This request has already been claimed by another manager or closed.',
        'not_eligible_to_claim' => 'You are not eligible to claim this request under the current hierarchy and permissions.',
        'source_missing' => 'The underlying source document record could not be found.',
        'source_expiry_changed' => 'The source document expiry date changed outside this workflow; please verify before proceeding.',
        'cannot_submit' => 'You are not authorized to submit this renewal request.',
        'invalid_fee_mode' => 'You must explicitly choose a fee mode (with fee or no fee).',
        'new_expiry_required' => 'New expiry date is required.',
        'invalid_new_expiry' => 'The new expiry date format is invalid.',
        'new_expiry_must_be_after_cycle' => 'The new expiry date must be after the current cycle expiry date.',
        'no_fee_must_be_zero' => 'No-fee renewals must have a zero amount and no linked financial transaction.',
        'positive_fee_required' => 'A positive fee amount is required, or explicitly select the no-fee mode.',
        'invalid_linked_invoice' => 'The linked invoice is not a valid renewal expense invoice.',
        'no_fee_conflicts_with_payment' => 'Cannot switch to no-fee mode when the invoice already has financial impact.',
        'cannot_review_treasury' => 'You are not authorized to perform treasury review on this request or attempted self-approval.',
        'return_reason_required' => 'Return reason is required.',
        'stale_submission_version' => 'The submission version changed during review; please refresh and try again.',
        'submission_fingerprint_mismatch' => 'Invoice or renewal data changed after submission; please resubmit before approval.',
        'already_approved' => 'This renewal request has already been approved.',
        'cannot_resolve_via_reconciliation' => 'Document renewal requests cannot be closed via general service deficiency reconciliation.',
        'use_renewal_reassignment' => 'Document renewal task items cannot be transferred via general transfer; use the dedicated renewal reassignment.',
        'cannot_reassign' => 'You are not authorized to reassign this renewal request.',
        'reassignment_reason_required' => 'Reassignment reason is required.',
        'not_claimed_yet' => 'This request has not been claimed yet.',
        'reassign_same_employee' => 'The selected employee is already the current assignee.',
        'target_missing_renewal_permission' => 'The selected employee does not hold the required renewal permission for this document type.',
        'renewal_invoice_locked_for_receipt' => 'Renewal expense invoices cannot accept receipts or handovers from the revenue workflow.',
    ],
];
