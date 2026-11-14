<?php

return [
    'title' => 'أداء المنشآت والأقسام',
    'navigation' => 'أداء المنشآت والأقسام',
    'subtitle' => 'متابعة شاملة ومجمّعة لأداء الخدمات التشغيلية، نواقص المعاملات، والتكليفات الإدارية على مستوى المنشآت والأقسام.',

    // Cards
    'cards' => [
        'active_services' => 'الخدمات الجارية',
        'active_services_hint' => 'معاملات نشطة خلال الفترة حسب تاريخ المعاملة',
        'completed_services' => 'الخدمات المكتملة',
        'completed_services_hint' => 'معاملات مكتملة تشغيلياً داخل الفترة',
        'overdue_services' => 'الخدمات المتأخرة',
        'overdue_services_hint' => 'معاملات تجاوزت موعد الاستحقاق وقبل اليوم',
        'on_time_rate' => 'الإنجاز في الموعد',
        'on_time_rate_hint' => 'المكتمل في موعده ÷ المكتمل ذو تاريخ استحقاق',
        'open_deficiencies' => 'نواقص الخدمات',
        'open_deficiencies_hint' => 'نواقص المعاملات المفتوحة المسجلة في الفترة',
        'tasks' => 'التكليفات الإدارية',
        'tasks_completion_rate' => 'إنجاز التكليفات',
        'tasks_completion_rate_hint' => 'المكتمل حتى نهاية الفترة ÷ المستحق فيها',
        'no_data' => 'لا توجد بيانات كافية',
    ],

    'overview' => [
        'navigation' => 'أقسام لوحة الأداء',
        'operations' => 'ملخص التشغيل',
        'workforce' => 'الحضور والإجازات',
        'attendance_month' => 'ملخص الحضور لشهر :month',
        'leaves_year' => 'ملخص الإجازات لسنة :year',
        'attendance_details' => 'فتح ملخص الحضور',
        'leaves_details' => 'فتح ملخص الإجازات',
        'approved_leave_days' => 'أيام الإجازات المعتمدة',
        'period_note' => 'يتحدد شهر الحضور وسنة الإجازات من بداية فترة التقرير. حالة الإجازة اليوم تُحسب بتاريخ اليوم. البحث النصي والتنقل بين الأقسام خاصان بملخص التشغيل.',
    ],

    // Filters & Notes
    'filters' => [
        'title' => 'تصفية وبحث الأداء',
        'company' => 'المنشأة',
        'branch' => 'الفرع',
        'date_from' => 'من تاريخ',
        'date_to' => 'إلى تاريخ',
        'search' => 'بحث...',
        'branch_note' => 'الخدمات تُفلتر حسب فرع المستفيد المسجّل حاليًا. المعاملات اللي فرعها غير محدد لا تظهر عند اختيار فرع.',
        'all_companies' => 'جميع المنشآت المصرح بها',
        'all_branches' => 'جميع الفروع',
        'reset' => 'إعادة ضبط',
    ],

    // Tables & Navigation
    'levels' => [
        'companies' => 'المنشآت',
        'departments' => 'أقسام منشأة: :company',
        'employees' => 'موظفو قسم: :department (:company)',
        'back_to_companies' => 'العودة للمنشآت',
        'back_to_departments' => 'العودة للأقسام',
        'view_departments' => 'عرض الأقسام',
        'view_employees' => 'عرض الموظفين',
        'team_badge' => 'فريقك في القسم',
        'unassigned_or_no_department' => 'سجلات غير مسندة / بلا قسم',
    ],

    // Columns
    'columns' => [
        'company' => 'المنشأة',
        'department' => 'القسم',
        'employee' => 'الموظف',
        'job_title' => 'المسمى الوظيفي',
        'nationality' => 'الجنسية',
        'saudi' => 'سعودي',
        'foreign' => 'غير سعودي',
        'active_services' => 'خدمات جارية',
        'completed_services' => 'خدمات مكتملة',
        'overdue_services' => 'خدمات متأخرة',
        'on_time_rate' => 'في الموعد',
        'open_deficiencies' => 'النواقص',
        'tasks_due' => 'مهام مستحقة',
        'tasks_completed' => 'مهام مكتملة',
        'task_completion_rate' => 'إنجاز المهام',
        'employees_count' => 'الموظفون',
        'departments_count' => 'الأقسام',
        'actions' => 'إجراءات',
        'view_profile' => 'ملف الأداء',
        'view_statement' => 'كشف',
    ],

    // Statements / Kashf
    'kashf' => [
        'title' => 'كشف تفصيلي بالسجلات',
        'services_title' => 'كشف الخدمات (:type)',
        'deficiencies_title' => 'كشف نواقص المعاملات',
        'tasks_title' => 'كشف التكليفات (:type)',
        'transaction_number' => 'رقم المعاملة',
        'service' => 'الخدمة',
        'beneficiary' => 'المستفيد',
        'executor' => 'المنفّذ',
        'status' => 'الحالة',
        'transaction_date' => 'تاريخ المعاملة',
        'due_date' => 'الاستحقاق',
        'completion_date' => 'الإنجاز',
        'pending_action' => 'الخطوة المنتظرة',
        'deficiency_type' => 'نوع النقص',
        'responsible' => 'المسؤول',
        'task_name' => 'المهمة',
        'assignment_number' => 'رقم التكليف',
        'weight' => 'الوزن',
        'score' => 'الدرجة',
        'empty' => 'لا توجد سجلات مطابقة.',
        'close' => 'إغلاق',
    ],

    'types' => [
        'due' => 'المستحقة',
        'active' => 'الجارية',
        'completed' => 'المكتملة',
        'overdue' => 'المتأخرة',
        'on_time' => 'في الموعد',
        'all' => 'الكل',
        'postponed' => 'المؤجلة',
    ],

    // Empty States
    'empty' => [
        'hint' => 'جرّب تعديل معايير البحث أو اختيار فترة زمنية أخرى.',
        'no_departments' => 'لا توجد أقسام مسجلة تحت هذه المنشأة وفق التصفية الحالية.',
        'no_employees' => 'لا يوجد موظفون مسجلون تحت هذا القسم وفق معايير التصفية.',
    ],

    // Dynamic Fallbacks
    'fallback' => [
        'company' => 'منشأة #:id',
        'employee' => 'موظف #:id',
    ],

    // Supervisors View
    'supervisors' => [
        'title' => 'تقييمات المشرف والمستفيدين',
        'departments_summary' => 'ملخص الأقسام المعروضة',
        'average_on_time_rate' => 'متوسط الإنجاز في الموعد',
        'average_task_completion_rate' => 'متوسط إنجاز التكليفات',
        'completed_services_share' => 'حصة الخدمات المكتملة',
        'completed_services_share_hint' => 'المكتملة من الجارية والمكتملة',
        'enter_department' => 'عرض موظفي القسم',
        'view_details' => 'كشف الخدمات',
        'department_lead' => 'مسؤول القسم',
        'lead_keywords' => ['مدير', 'مشرف', 'مسؤول'],
    ],
];
