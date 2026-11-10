<?php

return [
    'single' => 'مالك',
    'plural' => 'الملاك',
    'sections' => [
        'info' => 'بيانات المالك الأساسية',
    ],
    'fields' => [
        'user' => 'حساب المستخدم',
        'name' => 'اسم المالك',
        'email' => 'البريد الإلكتروني',
        'phone' => 'رقم الجوال',
        'password' => 'كلمة المرور',
        'password_help' => 'اتركه فارغاً للاحتفاظ بكلمة المرور الحالية',
        'company' => 'المنشأة / الشركة',
        'national_id' => 'رقم الهوية الوطنية / الإقامة',
        'nationality' => 'الجنسية',
        'birth_date' => 'تاريخ الميلاد',
        'ownership_percentage' => 'نسبة الملكية (%)',
        'employment_date' => 'تاريخ بداية الملكية / التعيين',
        'termination_date' => 'تاريخ الانقطاع / النهاية',
        'status' => 'الحالة',
        'image' => 'صورة الهوية / المالك',
        'address' => 'العنوان',
        'registration_source' => 'طريقة التسجيل',
        'registration_manual' => 'تسجيل حر',
        'registration_from_saudi_employee' => 'جلب من الموظفين السعوديين',
        'selected_employee' => 'الموظف السعودي',
        'selected_employee_placeholder' => 'ابحث بالاسم أو رقم الهوية الوطنية...',
        'password_help_employee' => 'اختياري، اتركه فارغاً للاحتفاظ بكلمة مرور الموظف الحالية',
        'errors' => [
            'employee_data_unavailable' => 'بيانات الموظف المختار غير متوفرة.',
            'employee_already_owner' => 'هذا الموظف مسجل بالفعل كمالك في هذه المنشأة.',
        ],
    ],
    'statuses' => [
        'active' => 'نشط',
        'inactive' => 'غير نشط',
    ],
    'edit_title' => 'تعديل بيانات المالك',
    'deleted_successfully' => 'تم حذف المالك بنجاح',
];
