<?php

return [
    'models' => [
        'directive' => [
            'singular' => 'Directive',
            'plural' => 'Directives & Alerts',
        ],
        'category' => [
            'singular' => 'Directive Category',
            'plural' => 'Directive Categories',
        ],
        'recipient' => [
            'singular' => 'Recipient',
            'plural' => 'Employee Acknowledgments',
        ],
    ],

    'sections' => [
        'directive_details' => 'Directive Details',
        'directive_details_desc' => 'Write the directive, choose a category, and specify target employees who must acknowledge it.',
        'target_audience' => 'Target Audience',
        'category_details' => 'Directive Category Details',
        'category_details_desc' => 'Manage directive categories and badge colors independently from mail categories.',
    ],

    'fields' => [
        'title' => 'Directive Title',
        'category' => 'Category',
        'description' => 'Description / Message',
        'target_type' => 'Target Audience',
        'employees' => 'Select Employees',
        'is_active' => 'Active',
        'created_by' => 'Sent By',
        'created_at' => 'Sent At',
        'acknowledgment_status' => 'Status',
        'acknowledgment_progress' => 'Acknowledged',
        'employee_name' => 'Employee Name',
        'company' => 'Company',
        'branch' => 'Branch',
        'recipient_status' => 'Status',
        'acknowledged_at' => 'Acknowledged At',
        'name_ar' => 'Arabic Name',
        'name_en' => 'English Name',
        'color' => 'Badge Color',
        'sort_order' => 'Sort Order',
        'directives_count' => 'Directives Count',
    ],

    'targets' => [
        'all' => 'All Organization Employees',
        'selected' => 'Selected Employees',
    ],

    'statuses' => [
        'completed' => 'Fully Acknowledged',
        'partial' => 'Partially Acknowledged',
        'unread' => 'Not Seen Yet',
        'no_recipients' => 'No Recipients',
        'acknowledged' => 'Acknowledged',
        'pending' => 'Pending',
    ],

    'colors' => [
        'gray' => 'Gray',
        'primary' => 'Primary',
        'info' => 'Info',
        'success' => 'Success',
        'warning' => 'Warning',
        'danger' => 'Danger',
    ],

    'helpers' => [
        'target_all' => 'This directive will appear as a mandatory popup for all active employees in your authorized organization scope.',
        'target_selected' => 'Choose one or more employees; the popup cannot be dismissed until they click Agree.',
    ],

    'actions' => [
        'view_recipients' => 'Track Acknowledgments',
        'acknowledge' => 'Agree / Acknowledge',
    ],

    'popup' => [
        'header_title' => 'Mandatory Directive & Alert',
        'mandatory_notice' => 'Please read carefully and click Agree to continue.',
        'counter' => 'Directive :current of :total',
        'sender' => 'From',
        'sent_at' => 'Sent At',
        'acknowledged_toast' => 'Your acknowledgment has been recorded.',
    ],

    'labels' => [
        'employee_fallback' => 'Employee #:id',
    ],

    'validation' => [
        'category_has_directives' => 'Cannot delete this category while directives are linked to it.',
    ],
];
