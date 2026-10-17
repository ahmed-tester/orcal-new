<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Channels
    |--------------------------------------------------------------------------
    | The default channels to use when an event does not specify channels.
    */
    'default_channels' => ['database'],

    /*
    |--------------------------------------------------------------------------
    | System Notification Events
    |--------------------------------------------------------------------------
    | Each event defines its supported channels, localized message templates,
    | and description of placeholders.
    */
    'events' => [
        'IqamaExpiryAlert' => [
            'name' => [
                'ar' => 'تنبيه اقتراب انتهاء الإقامة',
                'en' => 'Iqama Expiry Alert',
            ],
            'channels' => ['database', 'sms'],
            'required_permissions' => ['update_foreign_employee'],
            'database' => [
                'title' => [
                    'ar' => '[{alert_stage}] إقامة موظف',
                    'en' => '[{alert_stage}] Iqama Expiry Alert',
                ],
                'body' => [
                    'ar' => '{alert_stage}: إقامة الموظف {employee_name} برقم ({iqama_number}) {status_text} (بتاريخ {expiry_date}).',
                    'en' => '{alert_stage}: Iqama for {employee_name} ({iqama_number}) expires on {expiry_date} ({status_text}).',
                ],
            ],
            'sms' => [
                'ar' => '{alert_stage}: إقامتكم برقم {iqama_number} ستنتهي بتاريخ {expiry_date} ({status_text}). يرجى مراجعة إدارة الموارد البشرية.',
                'en' => '{alert_stage}: Your Iqama {iqama_number} expires on {expiry_date} ({status_text}). Please contact HR.',
            ],
            'placeholders' => [
                'alert_stage' => 'مرحلة التنبيه (الأول / الثاني / الحرج)',
                'employee_name' => 'اسم الموظف',
                'iqama_number' => 'رقم الإقامة',
                'expiry_date' => 'تاريخ الانتهاء',
                'days_remaining' => 'الأيام المتبقية',
                'status_text' => 'نص الحالة والمتبقي',
            ],
        ],

        'DueDateReminder' => [
            'name' => [
                'ar' => 'تنبيه استحقاق دفعة / سلفة',
                'en' => 'Due Date Reminder',
            ],
            'channels' => ['database', 'sms'],
            'required_permissions' => ['update_installment', 'update_installment_payment'],
            'database' => [
                'title' => [
                    'ar' => 'تنبيه استحقاق',
                    'en' => 'Due Date Alert',
                ],
                'body' => [
                    'ar' => 'تنبيه: سلفة {employee_name} ({code}) استحقت — الرجاء المتابعة.',
                    'en' => 'Alert: Due date for {employee_name} ({code}) has arrived — please follow up.',
                ],
            ],
            'sms' => [
                'ar' => 'تنبيه استحقاق: سلفة {employee_name} برقم {code} استحقت اليوم. الرجاء المتابعة.',
                'en' => 'Due Date Alert: Loan for {employee_name} ({code}) is due today.',
            ],
            'placeholders' => [
                'employee_name' => 'اسم الموظف / العميل',
                'code' => 'رقم المعاملة / كود السلفة',
                'amount' => 'المبلغ المستحق',
                'due_date' => 'تاريخ الاستحقاق',
            ],
        ],

        'NewServiceRequest' => [
            'name' => [
                'ar' => 'إنشاء طلب خدمة جديد',
                'en' => 'New Service Request',
            ],
            'channels' => ['database', 'sms'],
            'required_permissions' => ['update_service'],
            'database' => [
                'title' => [
                    'ar' => 'طلب خدمة جديد',
                    'en' => 'New Service Request',
                ],
                'body' => [
                    'ar' => 'تم استلام طلب لخدمة {service_name} برقم #{order_id}.',
                    'en' => 'Received request for {service_name} with number #{order_id}.',
                ],
            ],
            'sms' => [
                'ar' => 'تم استلام طلبك لخدمة {service_name} برقم #{order_id}.',
                'en' => 'Your request for {service_name} #{order_id} has been received.',
            ],
            'placeholders' => [
                'order_id' => 'رقم الطلب',
                'service_name' => 'اسم الخدمة',
            ],
        ],

        'CompanyLicenseExpiryAlert' => [
            'name' => [
                'ar' => 'تنبيه انتهاء تراخيص ووثائق المنشأة',
                'en' => 'Company License Expiry Alert',
            ],
            'channels' => ['database', 'sms'],
            'required_permissions' => ['update_company'],
            'database' => [
                'title' => [
                    'ar' => 'تنبيه ترخيص/وثيقة منشأة: {company_name}',
                    'en' => 'Company License Alert: {company_name}',
                ],
                'body' => [
                    'ar' => '{alert_stage}: وثيقة ({license_type}) للمنشأة {company_name} برقم ({license_number}) {status_text} (بتاريخ {expiry_date}).',
                    'en' => '{alert_stage}: Document ({license_type}) for {company_name} ({license_number}) {status_text} (expires on {expiry_date}).',
                ],
            ],
            'sms' => [
                'ar' => '{alert_stage}: وثيقة {license_type} للمنشأة {company_name} {status_text} (بتاريخ {expiry_date}). يرجى المتابعة والتجديد.',
                'en' => '{alert_stage}: {license_type} for {company_name} {status_text} ({expiry_date}). Please follow up.',
            ],
            'placeholders' => [
                'company_name' => 'اسم المنشأة / الفرع',
                'license_type' => 'نوع الوثيقة (سجل تجاري / رخصة بلدية / دفاع مدني / عقد إيجار)',
                'license_number' => 'رقم الوثيقة / الرخصة',
                'expiry_date' => 'تاريخ الانتهاء',
                'days_remaining' => 'الأيام المتبقية',
                'status_text' => 'نص الحالة والمتبقي',
                'alert_stage' => 'مرحلة التنبيه (الأول / الثاني / الحرج)',
            ],
        ],

        'LeaveOverdueAlert' => [
            'name' => [
                'ar' => 'تنبيه تأخر العودة من الإجازة',
                'en' => 'Leave Return Overdue Alert',
            ],
            'channels' => ['database', 'sms'],
            'required_permissions' => ['update_leave', 'change_leave_status'],
            'database' => [
                'title' => [
                    'ar' => 'تنبيه تأخر عودة موظف من الإجازة',
                    'en' => 'Leave Return Overdue Alert',
                ],
                'body' => [
                    'ar' => 'تنبيه: الموظف {employee_name} ({company_name}) تأخر عن العودة من الإجازة. كان تاريخ العودة المتوقع: {expected_return_date} (متأخر {days_overdue} يوم).',
                    'en' => 'Alert: Employee {employee_name} ({company_name}) is overdue from leave. Expected return was {expected_return_date} ({days_overdue} days overdue).',
                ],
            ],
            'sms' => [
                'ar' => 'تنبيه موارد بشرية: الموظف {employee_name} متأخر عن العودة من إجازته منذ {days_overdue} يوم. تاريخ العودة المتوقع كان {expected_return_date}.',
                'en' => 'HR Alert: Employee {employee_name} is overdue from leave by {days_overdue} days.',
            ],
            'placeholders' => [
                'employee_name' => 'اسم الموظف',
                'company_name' => 'اسم الشركة / الفرع',
                'leave_type' => 'نوع الإجازة',
                'expected_return_date' => 'تاريخ العودة المتوقع',
                'days_overdue' => 'عدد أيام التأخير',
            ],
        ],

        'LeaveReturnUpcomingAlert' => [
            'name' => [
                'ar' => 'تنبيه اقتراب موعد عودة الموظف من الإجازة',
                'en' => 'Leave Return Upcoming Alert',
            ],
            'channels' => ['database'],
            'required_permissions' => ['update_leave', 'change_leave_status'],
            'database' => [
                'title' => [
                    'ar' => 'تنبيه اقتراب عودة موظف من الإجازة',
                    'en' => 'Upcoming Leave Return Alert',
                ],
                'body' => [
                    'ar' => 'تنبيه: الموظف {employee_name} ستنتهي إجازته بتاريخ {leave_end_date} وعودته المتوقعة {expected_return_date} (متبقي {days_remaining} يوم).',
                    'en' => 'Alert: Employee {employee_name} leave ends on {leave_end_date}, expected return on {expected_return_date} ({days_remaining} days remaining).',
                ],
            ],
            'placeholders' => [
                'employee_name' => 'اسم الموظف',
                'company_name' => 'اسم الشركة / الفرع',
                'leave_end_date' => 'تاريخ نهاية الإجازة',
                'expected_return_date' => 'تاريخ العودة المتوقع',
                'days_remaining' => 'الأيام المتبقية على العودة',
            ],
        ],

        'CustomSulafAlert' => [
            'name' => [
                'ar' => 'تنبيه استحقاق سلفة مخصص',
                'en' => 'Custom Sulaf Due Alert',
            ],
            'channels' => ['database', 'sms'],
            'required_permissions' => ['update_installment', 'update_installment_payment'],
            'database' => [
                'title' => [
                    'ar' => 'تنبيه استحقاق سلفة: {employee_name}',
                    'en' => 'Sulaf Expiry Alert: {employee_name}',
                ],
                'body' => [
                    'ar' => '{custom_message}',
                    'en' => '{custom_message}',
                ],
            ],
            'sms' => [
                'ar' => '{custom_message}',
                'en' => '{custom_message}',
            ],
            'placeholders' => [
                'employee_name' => 'اسم الموظف',
                'code' => 'رقم السلفة / الكود',
                'amount' => 'قيمة السلفة',
                'due_date' => 'تاريخ الاستحقاق',
                'custom_message' => 'نص الرسالة المخصص',
            ],
        ],

        'PendingLeaveApprovalAlert' => [
            'name' => [
                'ar' => 'تنبيه طلب إجازة جديد بانتظار الاعتماد',
                'en' => 'Pending Leave Approval Alert',
            ],
            'channels' => ['database'],
            'required_permissions' => ['change_leave_status', 'update_leave'],
            'database' => [
                'title' => [
                    'ar' => 'طلب إجازة بانتظار الاعتماد',
                    'en' => 'Leave Request Pending Approval',
                ],
                'body' => [
                    'ar' => 'طلب إجازة جديد مقدم من الموظف {employee_name} ({leave_type}) للفترة من {start_date} إلى {end_date} يتطلب موافقتك.',
                    'en' => 'New leave request from {employee_name} ({leave_type}) from {start_date} to {end_date} awaits your approval.',
                ],
            ],
            'placeholders' => [
                'employee_name' => 'اسم الموظف',
                'leave_type' => 'نوع الإجازة',
                'start_date' => 'تاريخ البداية',
                'end_date' => 'تاريخ النهاية',
            ],
        ],
    ],
];
