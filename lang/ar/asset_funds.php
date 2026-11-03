<?php

return [

    'enums' => [
        'status' => [
            'active' => 'نشط / ساري',
            'closed' => 'مغلق',
        ],
        'voucher_type' => [
            'deposit'    => 'سند إيداع',
            'withdrawal' => 'سند سحب',
        ],
        'source_type' => [
            'company' => 'ربط بمنشأة',
            'free'    => 'إدخال حر',
        ],
    ],

    'asset_funds' => [
        'single' => 'صندوق أصول',
        'plural' => 'صناديق الأصول',
        'sections' => [
            'basic_info'      => 'بيانات الصندوق الأساسية',
            'basic_info_desc' => 'تحديد اسم الصندوق ومدير الصندوق وتاريخ إنشائه',
            'management'      => 'إدارة الصندوق والمسؤوليات',
            'management_desc' => 'تحديد مدير الصندوق ومحاسبه وشروط الإيداع والسحب',
        ],
        'fields' => [
            'fund_number'        => 'رقم الصندوق',
            'fund_name'          => 'اسم الصندوق',
            'established_date'   => 'تاريخ الإنشاء',
            'description'        => 'نبذة تعريفية عن الصندوق',
            'rules_and_conditions' => 'شروط الإيداع والسحب',
            'manager_name'       => 'اسم مدير الصندوق',
            'manager_employee'   => 'مدير الصندوق (موظف سعودي)',
            'accountant_name'    => 'اسم محاسب الصندوق',
            'accountant_employee'=> 'محاسب الصندوق (موظف سعودي)',
            'status'             => 'حالة الصندوق',
            'notes'              => 'ملاحظات',
            'balance'            => 'الرصيد الحالي',
            'total_deposits'     => 'إجمالي الواردات',
            'total_withdrawals'  => 'إجمالي المصروفات',
            'vouchers_count'     => 'عدد السندات',
        ],
        'helpers' => [
            'manager_employee'    => 'يتم اختيار مدير الصندوق من قائمة الموظفين السعوديين المسجلين بالنظام لمنحه الصلاحيات',
            'accountant_employee' => 'يتم اختيار محاسب الصندوق من قائمة الموظفين السعوديين المسجلين بالنظام لمنحه الصلاحيات',
        ],
    ],

    'asset_fund_vouchers' => [
        'single' => 'سند صندوق أصول',
        'plural' => 'سندات صندوق الأصول',
        'sections' => [
            'voucher_info'       => 'بيانات السند الأساسية',
            'voucher_info_desc'  => 'تحديد نوع السند والصندوق المستهدف وتاريخ التسجيل',
            'source_info'        => 'مصدر / وجهة المبلغ',
            'source_info_desc'   => 'تحديد ما إذا كان المبلغ مرتبطاً بمنشأة أو يُسجَّل بشكل حر',
            'items'              => 'تفاصيل المبلغ',
            'items_desc'         => 'أدخل تفاصيل وتوزيع المبلغ في الجدول أدناه',
        ],
        'fields' => [
            'fund'           => 'الصندوق',
            'voucher_number' => 'رقم السند',
            'type'           => 'نوع السند',
            'voucher_date'   => 'تاريخ السند',
            'total_amount'   => 'إجمالي المبلغ',
            'source_type'    => 'نوع المصدر / الوجهة',
            'company'        => 'المنشأة',
            'party_name'     => 'اسم الطرف / الجهة',
            'notes'          => 'ملاحظات وبيان المبلغ',
        ],
        'helpers' => [
            'party_name'   => 'اذكر من أين أتى المبلغ أو إلى أين ذهب بوضوح',
            'total_amount' => 'يُحسب تلقائياً من مجموع صفوف التفاصيل',
        ],
        'placeholders' => [
            'party_name' => 'بيان الطرف أو سبب الإيداع / السحب...',
        ],
    ],

    'asset_fund_voucher_items' => [
        'actions' => [
            'add_item' => 'إضافة بند تفصيلي',
        ],
        'fields' => [
            'description' => 'البيان / الوصف',
            'percentage'  => 'النسبة %',
            'sub_total'   => 'الإجمالي',
            'extra_1'     => 'أخرى 1',
            'extra_2'     => 'أخرى 2',
            'row_total'   => 'المجموع',
        ],
    ],

    'statement' => [
        'title'           => 'كشف حركة الصندوق',
        'subtitle'        => 'عرض كامل العمليات على الصندوق خلال فترة زمنية محددة',
        'filter_section'  => 'خيارات الكشف والفلترة',
        'results_section' => 'نتائج الكشف',
        'summary_section' => 'ملخص الحركة',
        'fields' => [
            'fund'         => 'الصندوق',
            'date_from'    => 'من تاريخ',
            'date_to'      => 'إلى تاريخ',
            'filter_type'  => 'نوع العمليات',
        ],
        'filter_types' => [
            'all'         => 'الكل (واردات + مصروفات)',
            'deposit'     => 'الواردات فقط (إيداع)',
            'withdrawal'  => 'المصروفات فقط (سحب)',
        ],
        'summary' => [
            'total_deposits'    => 'إجمالي الواردات',
            'total_withdrawals' => 'إجمالي المصروفات',
            'net_balance'       => 'الرصيد الصافي',
        ],
        'actions' => [
            'export_pdf'   => 'طباعة / PDF',
            'apply_filter' => 'تطبيق الفلتر',
        ],
        'no_results' => 'لا توجد حركات مطابقة للفترة المحددة',
        'select_fund_placeholder' => '— اختر الصندوق —',
        'please_select_fund' => 'اختر صندوقاً من القائمة أعلاه لعرض حركته',
        'currency' => 'ر.س',
        'unauthorized' => 'غير مصرح لك باستعراض كشف هذا الصندوق.',
    ],

    'notifications' => [
        'fund_created'    => 'تم إنشاء الصندوق بنجاح.',
        'fund_updated'    => 'تم تحديث بيانات الصندوق بنجاح.',
        'fund_deleted'    => 'تم حذف الصندوق بنجاح.',
        'voucher_created' => 'تم تسجيل السند بنجاح.',
        'voucher_updated' => 'تم تحديث السند بنجاح.',
        'voucher_deleted' => 'تم حذف السند بنجاح.',
    ],
];

