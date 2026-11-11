<?php

return [
    'default_channels' => ['database'],
    'sms' => [
        'enabled' => env('NOTIFICATIONS_SMS_ENABLED', false),
        'provider' => env('NOTIFICATIONS_SMS_PROVIDER', 'mock'),
        'api_url' => env('SMS_API_URL', ''),
        'api_key' => env('SMS_API_KEY', ''),
        'sender_name' => env('SMS_SENDER_NAME', 'ORCAL'),
    ],
    'events' => [
        'IqamaExpiryAlert' => [
            'name' => 'notifications.events.IqamaExpiryAlert.name',
            'channels' => [
                0 => 'database',
            ],
            'required_permissions' => [
                0 => 'update_foreign_employee',
            ],
            'database' => [
                'title' => 'notifications.events.IqamaExpiryAlert.database.title',
                'body' => 'notifications.events.IqamaExpiryAlert.database.body',
            ],
            'sms' => 'notifications.events.IqamaExpiryAlert.sms',
            'placeholders' => [
                0 => 'alert_stage',
                1 => 'employee_name',
                2 => 'iqama_number',
                3 => 'expiry_date',
                4 => 'days_remaining',
                5 => 'status_text',
            ],
        ],
        'DueDateReminder' => [
            'name' => 'notifications.events.DueDateReminder.name',
            'channels' => [
                0 => 'database',
            ],
            'required_permissions' => [
                0 => 'update_installment',
                1 => 'update_installment_payment',
            ],
            'database' => [
                'title' => 'notifications.events.DueDateReminder.database.title',
                'body' => 'notifications.events.DueDateReminder.database.body',
            ],
            'sms' => 'notifications.events.DueDateReminder.sms',
            'placeholders' => [
                0 => 'employee_name',
                1 => 'code',
                2 => 'amount',
                3 => 'due_date',
            ],
        ],
        'NewServiceRequest' => [
            'name' => 'notifications.events.NewServiceRequest.name',
            'channels' => [
                0 => 'database',
            ],
            'required_permissions' => [
                0 => 'update_service',
            ],
            'database' => [
                'title' => 'notifications.events.NewServiceRequest.database.title',
                'body' => 'notifications.events.NewServiceRequest.database.body',
            ],
            'sms' => 'notifications.events.NewServiceRequest.sms',
            'placeholders' => [
                0 => 'order_id',
                1 => 'service_name',
            ],
        ],
        'CompanyLicenseExpiryAlert' => [
            'name' => 'notifications.events.CompanyLicenseExpiryAlert.name',
            'channels' => [
                0 => 'database',
            ],
            'required_permissions' => [
                0 => 'update_company',
            ],
            'database' => [
                'title' => 'notifications.events.CompanyLicenseExpiryAlert.database.title',
                'body' => 'notifications.events.CompanyLicenseExpiryAlert.database.body',
            ],
            'sms' => 'notifications.events.CompanyLicenseExpiryAlert.sms',
            'placeholders' => [
                0 => 'company_name',
                1 => 'license_type',
                2 => 'license_number',
                3 => 'expiry_date',
                4 => 'days_remaining',
                5 => 'status_text',
                6 => 'alert_stage',
            ],
        ],
        'LeaveOverdueAlert' => [
            'name' => 'notifications.events.LeaveOverdueAlert.name',
            'channels' => [
                0 => 'database',
            ],
            'required_permissions' => [
                0 => 'update_leave',
            ],
            'database' => [
                'title' => 'notifications.events.LeaveOverdueAlert.database.title',
                'body' => 'notifications.events.LeaveOverdueAlert.database.body',
            ],
            'sms' => 'notifications.events.LeaveOverdueAlert.sms',
            'placeholders' => [
                0 => 'employee_name',
                1 => 'company_name',
                2 => 'leave_type',
                3 => 'expected_return_date',
                4 => 'days_overdue',
            ],
        ],
        'LeaveReturnUpcomingAlert' => [
            'name' => 'notifications.events.LeaveReturnUpcomingAlert.name',
            'channels' => [
                0 => 'database',
            ],
            'required_permissions' => [
                0 => 'update_leave',
            ],
            'database' => [
                'title' => 'notifications.events.LeaveReturnUpcomingAlert.database.title',
                'body' => 'notifications.events.LeaveReturnUpcomingAlert.database.body',
            ],
            'placeholders' => [
                0 => 'employee_name',
                1 => 'company_name',
                2 => 'leave_end_date',
                3 => 'expected_return_date',
                4 => 'days_remaining',
            ],
        ],
        'CustomSulafAlert' => [
            'name' => 'notifications.events.CustomSulafAlert.name',
            'channels' => [
                0 => 'database',
            ],
            'required_permissions' => [
                0 => 'update_installment',
                1 => 'update_installment_payment',
            ],
            'database' => [
                'title' => 'notifications.events.CustomSulafAlert.database.title',
                'body' => 'notifications.events.CustomSulafAlert.database.body',
            ],
            'sms' => 'notifications.events.CustomSulafAlert.sms',
            'placeholders' => [
                0 => 'employee_name',
                1 => 'code',
                2 => 'amount',
                3 => 'due_date',
                4 => 'custom_message',
            ],
        ],
        'PendingLeaveApprovalAlert' => [
            'name' => 'notifications.events.PendingLeaveApprovalAlert.name',
            'channels' => [
                0 => 'database',
            ],
            'required_permissions' => [
                0 => 'update_leave',
            ],
            'database' => [
                'title' => 'notifications.events.PendingLeaveApprovalAlert.database.title',
                'body' => 'notifications.events.PendingLeaveApprovalAlert.database.body',
            ],
            'placeholders' => [
                0 => 'employee_name',
                1 => 'leave_type',
                2 => 'start_date',
                3 => 'end_date',
            ],
        ],
    ],
];
