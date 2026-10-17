<?php

return [
    'single' => 'معاملة خدمة',
    'plural' => 'الخدمات والمتابعة',

    'sections' => [
        'scope_and_date'        => 'نطاق وتاريخ الخدمة',
        'scope_and_date_desc'   => 'تحديد ما إذا كانت الخدمة داخلية تابعة لمنشأة أو خارجية حرة',
        'internal_service'      => 'بيانات الخدمة الداخلية',
        'internal_service_desc' => 'تحديد المنشأة وجهة الخدمة (المنشأة أو الموظف) والخدمة المطلوبة',
        'external_service'      => 'بيانات الخدمة الخارجية (تسجيل حر)',
        'external_service_desc' => 'تسجيل بيانات الطرف الأول، المستفيد، واسم الخدمة المطلوبة',
        'financial'             => 'البيانات المالية والأتعاب',
        'financial_desc'        => 'إجمالي سعر الخدمة، المبلغ المدفوع، والمتبقي المحسوب آلياً',
        'execution'             => 'الإنجاز والمتابعة الإدارية',
        'execution_desc'        => 'الموظف المنفذ، حالة إنجاز المعاملة، ومواعيد الاستحقاق والملاحظات',
    ],

    'fields' => [
        'transaction_number'     => 'رقم المعاملة (تلقائي)',
        'transaction_date'       => 'تاريخ المعاملة',
        'scope_type'             => 'نطاق الخدمة',
        'service_target'         => 'جهة الخدمة الداخلية',
        'company'                => 'المؤسسة / الشركة',
        'employee'               => 'الموظف المعني',
        'service'                => 'الخدمة المطلوبة',
        'service_name'           => 'اسم الخدمة',
        'service_type'           => 'نوع وتصنيف الخدمة',
        'first_party'            => 'الطرف الأول (مقدم الخدمة)',
        'beneficiary_name'       => 'المستفيد من الخدمة',
        'beneficiary_phone'      => 'رقم جوال المستفيد',
        'beneficiary_national_id'=> 'رقم الهوية / السجل التجاري',
        'total_amount'           => 'إجمالي سعر وتكلفة الخدمة (ر.س)',
        'paid_amount'            => 'المبلغ المدفوع (ر.س)',
        'remaining_amount'       => 'المبلغ المتبقي (ر.س)',
        'payment_status'         => 'حالة السداد',
        'service_status'         => 'حالة المعاملة',
        'executed_by'            => 'الموظف المنفذ',
        'due_date'               => 'تاريخ الإنجاز المتوقع',
        'completion_date'        => 'تاريخ الإنجاز الفعلي',
        'notes'                  => 'ملاحظات ومجريات المعاملة',
        'created_at'             => 'تاريخ التسجيل',
    ],

    'toggle_options' => [
        'internal' => 'خدمات داخلية (من قائمة المنشآت)',
        'external' => 'خدمات خارجية (من خارج المنشآت - إنشاء حر)',
        'target_company'  => 'خدمات تخص المنشأة ككل',
        'target_employee' => 'خدمات تخص منسوبي المنشأة',
    ],

    'badges' => [
        'internal'  => 'منشآت',
        'external'  => 'خارجي',
        'company'   => 'المنشأة',
        'employee'  => 'موظف',
        'active'    => 'نشط (قيد الإجراء)',
        'completed' => 'مكتملة',
        'inactive'  => 'غير نشط / ملغاة',
        'unpaid'    => 'غير مسدد',
        'partial'   => 'سداد جزئي',
        'paid'      => 'مسدد بالكامل',
    ],

    'service_statuses' => [
        'active'    => 'نشط (قيد الإجراء)',
        'completed' => 'مكتملة ومنجزة',
        'inactive'  => 'غير نشط / ملغاة',
    ],

    'payment_statuses' => [
        'unpaid'  => 'غير مسدد',
        'partial' => 'مسدد جزئياً',
        'paid'    => 'مسدد بالكامل',
    ],

    'categories' => [
        'cr_licenses'    => 'سجلات وتراخيص',
        'municipal'      => 'بلديات ورخص بلدية',
        'work_permits'   => 'قوى ورخص عمل',
        'passports'      => 'جوازات وإقامات',
        'visas'          => 'تأشيرات',
        'insurance'      => 'تأمين طبي',
        'other'          => 'خدمات أخرى',
    ],

    'quick_add' => [
        'modal_title' => 'إضافة خدمة جديدة إلى الدليل',
        'name'        => 'اسم الخدمة',
        'category'    => 'التصنيف',
        'target_type' => 'الجهة المستهدفة',
        'price'       => 'سعر الخدمة الافتراضي (ر.س)',
        'notes'       => 'ملاحظات وتفاصيل الخدمة',
    ],

    'placeholders' => [
        'service_name'           => 'مثال: تفويض تأشيرة / استخراج سجل فرعي',
        'service_type'           => 'مثال: تعقيب وتخليص / استشارات قانونية',
        'first_party'            => 'مثال: مكتب الخدمات / الإدارة المركزية',
        'beneficiary_name'       => 'الاسم الرباعي للمستفيد أو اسم الجهة',
        'beneficiary_phone'      => '05xxxxxxxx',
        'beneficiary_national_id'=> 'رقم الهوية الوطنية / الإقامة / السجل التجاري',
        'notes'                  => 'تفاصيل شروط الخدمة، الملاحظات، أو متطلبات الإنجاز',
    ],

    'table_heading' => 'سجل متابعة الخدمات والمعاملات — :count معاملة',
];
