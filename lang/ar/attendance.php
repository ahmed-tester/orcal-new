<?php

return [
    'single' => 'سجل حضور وانصراف',
    'plural' => 'الحضور والانصراف',
    'navigation_label' => 'الحضور والانصراف',
    'title' => 'الحضور والانصراف',
    'group' => 'إدارة شؤون الموظفين',
    'breadcrumb' => 'الموارد البشرية',

    'sections' => [
        'entry_data' => 'بيانات حضور وانصراف الموظف',
        'records_history' => 'سجل الحضور والانصراف — :date',
        'employee_history' => 'سجل حضور الموظف (:name)',
        'date_history' => 'سجل الحضور ليوم (:date)',
    ],

    'tags' => [
        'branch' => 'فرع: :branch',
        'super_admin' => 'جميع الفروع (مدير عام)',
    ],

    'fields' => [
        'record_number' => 'الرقم',
        'employee' => 'اسم الموظف',
        'select_employee' => '-- اختر الموظف --',
        'date' => 'التاريخ',
        'check_in' => 'وقت الحضور',
        'check_out' => 'وقت الانصراف',
        'late_hours' => 'دقائق التأخير',
        'overtime_hours' => 'دقائق إضافية',
        'total_hours' => 'إجمالي الساعات',
        'source' => 'المصدر',
        'device' => 'جهاز البصمة',
        'status' => 'حالة الحضور',
        'notes' => 'ملاحظات',
    ],

    'placeholders' => [
        'manual_no_device' => 'يدوي / بدون جهاز',
    ],

    'units' => [
        'hour' => 'ساعة',
        'hours' => 'ساعات',
        'hour_short' => 'س',
        'minute' => 'دقيقة',
        'minutes' => 'دقائق',
    ],

    'statuses' => [
        'present'       => 'حاضر في الموعد',
        'present_short' => 'حاضر',
        'late'          => 'متأخر عن الدوام',
        'late_short'    => 'متأخر',
        'absent'        => 'غائب',
        'absent_short'  => 'غائب',
        'excused'       => 'مستأذن / بعذر',
        'excused_short' => 'مستأذن',
        'leave'         => 'إجازة معتمدة',
        'leave_short'   => 'إجازة',
    ],

    'sources' => [
        'fingerprint' => 'جهاز البصمة',
        'manual'      => 'تسجيل يدوي',
        'system'      => 'آلي (النظام)',
    ],

    'actions' => [
        'save' => 'حفظ السجل',
        'update' => 'تحديث السجل',
        'new_record' => 'سجل جديد',
        'delete' => 'حذف',
        'check_absent' => 'فحص وتسجيل الغياب',
        'checking_absent' => 'جاري الفحص...',
        'check_absent_tooltip' => 'تطبيق فحص وتسجيل الغياب لجميع موظفي الفرع تلقائياً',
        'simulate_biometric' => 'محاكاة بصمة (Test Biometric)',
        'simulate_biometric_heading' => 'تجربة واختبار استقبال بصمة جهاز ZKTeco',
        'simulate_biometric_desc' => 'سيقوم هذا الإجراء بمحاكاة إرسال إشارة بصمة من جهاز ZKTeco إلى مسار الـ API واحتساب الحضور فوراً.',
        'punch_type' => 'نوع البصمة',
        'punch_time' => 'توقيت البصمة',
        'punch_types' => [
            'check_in' => 'بصمة دخول (Check-In)',
            'check_out' => 'بصمة خروج (Check-Out)',
        ],
        'export_csv' => 'تصدير كـ CSV',
    ],

    'notifications' => [
        'absent_check_success' => 'تم فحص وتسجيل غياب الموظفين المتأخرين بنجاح',
        'absent_check_failed' => 'حدث خطأ أثناء فحص الغياب: :error',
        'save_success' => 'تم إضافة سجل الحضور بنجاح',
        'update_success' => 'تم تحديث بيانات الحضور بنجاح',
        'delete_success' => 'تم حذف السجل بنجاح',
        'delete_confirm' => 'هل أنت متأكد من حذف هذا السجل نهائياً؟',
        'edit_mode' => 'تم تفعيل وضع التعديل',
    ],

    'validation' => [
        'employee_required' => 'يرجى اختيار الموظف',
        'date_required' => 'حقل التاريخ إجباري',
    ],

    'table' => [
        'search_placeholder' => 'بحث سريع باسم الموظف، الرقم الوظيفي، أو التاريخ (مثال: 2026-09-13)...',
        'total_records' => 'إجمالي السجلات:',
        'no_records' => 'لا توجد سجلات حضور وانصراف مطابقة',
        'selected_record' => 'سجل محدد رقم #:id',
        'columns' => [
            'number' => '#',
            'employee' => 'اسم الموظف',
            'date' => 'التاريخ',
            'check_in' => 'وقت الحضور',
            'check_out' => 'وقت الانصراف',
            'late_hours' => 'التأخير',
            'overtime_hours' => 'الإضافي',
            'status' => 'الحالة',
        ],
    ],
];
