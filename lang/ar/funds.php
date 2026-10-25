<?php

return [
    'groups' => [
        'funds' => 'صناديق التكافل والادخار',
        'salifni' => 'صندوق سلفني',
        'rosca' => 'صندوق جمعية',
    ],

    'enums' => [
        'payment_status' => [
            'paid' => 'تم السداد كامل',
            'partial' => 'سداد جزئي',
            'unpaid' => 'لم يتم السداد',
        ],
        'general_status' => [
            'active' => 'نشط / ساري',
            'completed' => 'مكتمل',
            'suspended' => 'معلق / متوقف',
            'inactive' => 'غير نشط',
            'defaulted' => 'متعثر',
            'draft' => 'مسودة',
            'closed' => 'مغلق',
            'dropped' => 'منسحب',
        ],
        'installment_period' => [
            'monthly' => 'شهري',
            'yearly' => 'سنوي',
        ],
        'allocation_method' => [
            'lottery' => 'بالقرعة',
            'consensus' => 'بالتوافق والاتفاق',
            'prearranged' => 'ترتيب شهري محدد',
        ],
        'member_type' => [
            'employee' => 'موظف في المنشأة',
            'external' => 'عضو خارجي (أسرة / أصدقاء)',
        ],
    ],

    'loan_funds' => [
        'single' => 'صندوق سلفني',
        'plural' => 'صناديق سلفني',
        'sections' => [
            'basic_info' => 'بيانات الصندوق الأساسية',
            'basic_info_desc' => 'تحديد اسم الصندوق والمبلغ المستهدف وسقوف السلف المسموحة',
            'rules' => 'الشروط واللوائح التنظيمية',
        ],
        'fields' => [
            'fund_number' => 'رقم الصندوق',
            'fund_name' => 'اسم الصندوق',
            'company' => 'المنشأة / الشركة',
            'target_amount' => 'المبلغ المستهدف',
            'installment_period' => 'دورية القسط',
            'default_installments_count' => 'عدد الأقساط الافتراضي في السنة',
            'max_loan_limit' => 'الحد الأقصى للسلفة للعضو',
            'max_repayment_months' => 'أقصى مدة لسداد السلفة (شهور)',
            'status' => 'حالة الصندوق',
            'trustee_employee' => 'أمين الصندوق (موظف)',
            'trustee_name' => 'اسم أمين الصندوق',
            'rules_and_conditions' => 'الشروط واللوائح المنظمة',
            'notes' => 'ملاحظات إضافية',
        ],
    ],

    'loan_fund_members' => [
        'single' => 'عضو صندوق سلفني',
        'plural' => 'أعضاء صندوق سلفني',
        'sections' => [
            'member_info' => 'بيانات العضو المشترك',
            'financial_info' => 'بيانات الاشتراك والأقساط',
        ],
        'fields' => [
            'fund' => 'صندوق سلفني',
            'member_type' => 'نوع العضوية',
            'employee' => 'الموظف',
            'full_name' => 'الاسم الرباعي',
            'national_id' => 'رقم الهوية / الإقامة',
            'phone' => 'رقم الجوال',
            'address' => 'العنوان',
            'installments_count' => 'عدد الأقساط',
            'monthly_installment' => 'قيمة القسط الشهري',
            'total_annual_amount' => 'إجمالي المبلغ السنوي',
            'paid_amount' => 'المبلغ المسدد',
            'remaining_amount' => 'المبلغ المتبقي',
            'payment_status' => 'حالة السداد',
            'joined_date' => 'تاريخ الانضمام',
            'status' => 'حالة العضوية',
            'notes' => 'ملاحظات',
        ],
        'actions' => [
            'quick_pay' => 'تسجيل سداد قسط',
            'quick_pay_heading' => 'تسجيل سداد قسط اشتراك',
            'quick_pay_success' => 'تم تسجيل سداد القسط بنجاح وتحديث رصيد العضو.',
        ],
        'helpers' => [
            'total_annual_amount' => 'يُحسب آلياً: عدد الأقساط × قيمة القسط الشهري',
            'remaining_amount' => 'يُحسب آلياً: إجمالي المبلغ السنوي − المبلغ المسدد',
        ],
    ],

    'loan_fund_member_payments' => [
        'single' => 'قسط اشتراك سلفني',
        'plural' => 'كشف اشتراكات سلفني',
        'sections' => [
            'payment_info' => 'بيانات قسط الاشتراك وسند القبض',
        ],
        'fields' => [
            'fund' => 'صندوق سلفني',
            'member' => 'العضو المشترك',
            'voucher_number' => 'رقم السند',
            'payment_date' => 'تاريخ السداد',
            'period_month' => 'شهر الاستحقاق',
            'installment_order' => 'رقم القسط',
            'amount' => 'المبلغ المسدد',
            'payment_method' => 'طريقة السداد',
            'payment_status' => 'حالة القسط',
            'notes' => 'ملاحظات',
        ],
        'methods' => [
            'cash' => 'نقدي / خزينة',
            'bank_transfer' => 'تحويل بنكي',
            'payroll_deduction' => 'خصم من الراتب',
        ],
    ],

    'loan_fund_loans' => [
        'single' => 'سلفة من الصندوق',
        'plural' => 'صرف السلف (سلفني)',
        'sections' => [
            'loan_info' => 'بيانات السلفة والمستفيد',
            'repayment_schedule' => 'جدولة وسداد السلفة',
        ],
        'fields' => [
            'fund' => 'صندوق سلفني',
            'loan_number' => 'رقم السلفة',
            'member' => 'العضو المستفيد (من المشتركين)',
            'loan_amount' => 'مبلغ السلفة',
            'loan_date' => 'تاريخ صرف السلفة',
            'start_due_date' => 'تاريخ بداية القسط',
            'installments_count' => 'عدد الأقساط',
            'monthly_installment' => 'مبلغ القسط الشهري',
            'paid_amount' => 'إجمالي المسدد',
            'remaining_amount' => 'المبلغ المتبقي',
            'status' => 'حالة السلفة',
            'is_closed' => 'إغلاق السند بعد انتهاء السداد',
            'closure_date' => 'تاريخ إغلاق السند',
            'approved_by' => 'المعتمد',
            'notes' => 'ملاحظات وبيان السلفة',
        ],
        'helpers' => [
            'monthly_installment' => 'يُحسب آلياً: مبلغ السلفة ÷ عدد الأقساط',
            'remaining_amount' => 'يُحسب آلياً: مبلغ السلفة − إجمالي المسدد',
            'max_loan_limit_hint' => 'الحد الأقصى للسلفة للعضو: :limit ر.س.',
            'max_months_hint' => 'أقصى مدة سداد مسموحة في هذا الصندوق: :months شهر',
            'available_balance_hint' => 'السيولة المتاحة في الصندوق حالياً: :balance ر.س.',
            'target_not_reached_hint' => 'لم يكتمل تجميع المبلغ المستهدف للصندوق (:collected من أصل :target ر.س.) — لا يمكن صرف سلف حالياً',
        ],
        'validation' => [
            'max_limit_exceeded' => 'مبلغ السلفة يتجاوز الحد الأقصى المسموح به في هذا الصندوق (:limit ر.س.).',
            'max_months_exceeded' => 'عدد الأقساط يتجاوز الحد الأقصى المسموح به في هذا الصندوق (:months شهر).',
            'insufficient_fund_balance' => 'رصيد الصندوق المتاح حالياً (:available ر.س.) لا يكفي لصرف هذه السلفة.',
            'target_amount_not_reached' => 'لا يمكن صرف سلفة قبل اكتمال تجميع المبلغ المستهدف للصندوق (:target ر.س.). المحصل حتى الآن: :collected ر.س.',
        ],
    ],

    'loan_fund_loan_payments' => [
        'single' => 'سداد قسط سلفة',
        'plural' => 'سداد أقساط السلف',
        'sections' => [
            'payment_info' => 'بيانات القسط والتحصيل',
        ],
        'fields' => [
            'loan' => 'السلفة المستهدفة',
            'member' => 'العضو المستفيد (الممنوح سلفة)',
            'voucher_number' => 'رقم سند القبض',
            'installment_order' => 'رقم القسط',
            'installment_date' => 'تاريخ القسط',
            'scheduled_amount' => 'مبلغ القسط المقرر',
            'collected_amount' => 'المبلغ المحصل',
            'remaining_balance' => 'المبلغ المتبقي (آلي)',
            'is_closed' => 'إغلاق السند بعد اكتمال السداد',
            'notes' => 'ملاحظات',
        ],
        'helpers' => [
            'remaining_balance' => 'يُجلب ويُحسب تلقائياً من رصيد السلفة المتبقي',
        ],
    ],

    'rosca_funds' => [
        'single' => 'صندوق جمعية',
        'plural' => 'صناديق الجمعيات',
        'sections' => [
            'basic_info' => 'بيانات الجمعية ودورتها',
            'management' => 'المسؤول والإشراف واللوائح',
        ],
        'fields' => [
            'fund_number' => 'رقم الجمعية',
            'fund_name' => 'اسم الجمعية',
            'company' => 'المنشأة / الشركة',
            'start_date' => 'تاريخ البداية',
            'end_date' => 'تاريخ النهاية التقديري',
            'members_count' => 'عدد المشتركين (الأشهر)',
            'monthly_installment' => 'مبلغ القسط الشهري',
            'total_pool_amount' => 'إجمالي المبلغ النهائي الشهري',
            'allocation_method' => 'آلية توزيع الأدوار',
            'status' => 'حالة الجمعية',
            'assigned_manager' => 'المشرف المسؤول المفوض',
            'manager_name' => 'اسم المشرف',
            'rules_and_conditions' => 'شروط وأحكام الجمعية',
            'notes' => 'ملاحظات',
        ],
        'helpers' => [
            'total_pool_amount' => 'يُحسب آلياً: عدد المشتركين × قيمة القسط الشهري',
        ],
    ],

    'rosca_fund_members' => [
        'single' => 'عضو الجمعية',
        'plural' => 'أعضاء الجمعية',
        'sections' => [
            'member_info' => 'بيانات العضو المشترك',
            'financial_turn' => 'بيانات القسط ودور الاستلام',
        ],
        'fields' => [
            'fund' => 'صندوق الجمعية',
            'member_type' => 'نوع العضوية',
            'employee' => 'الموظف',
            'full_name' => 'الاسم الرباعي',
            'national_id' => 'رقم الهوية',
            'phone' => 'رقم الجوال',
            'address' => 'العنوان',
            'turn_order' => 'ترتيب دور الاستلام',
            'payout_month' => 'شهر الاستلام المقرر',
            'installments_count' => 'عدد الأقساط الشهرية',
            'monthly_installment' => 'مبلغ القسط',
            'total_final_amount' => 'إجمالي المبلغ المستلم',
            'paid_amount' => 'المبلغ المسدد حتى الآن',
            'remaining_amount' => 'المبلغ المتبقي',
            'has_received_payout' => 'تم استلام مبلغ الجمعية',
            'payout_date' => 'تاريخ الاستلام الفعلي',
            'status' => 'حالة العضو',
        ],
        'helpers' => [
            'total_final_amount' => 'يُحسب آلياً بحسب قيمة القسط وعدد المشتركين',
        ],
    ],

    'rosca_fund_payments' => [
        'single' => 'قسط الجمعية',
        'plural' => 'كشف سداد أقساط الجمعية',
        'sections' => [
            'payment_info' => 'بيانات سداد القسط الشهري',
        ],
        'fields' => [
            'fund' => 'صندوق الجمعية',
            'member' => 'العضو المشترك',
            'period_month' => 'شهر الاستحقاق',
            'installment_order' => 'رقم القسط',
            'scheduled_amount' => 'مبلغ القسط المستحق',
            'paid_amount' => 'المبلغ المدفوع',
            'payment_status' => 'حالة سداد القسط',
            'is_bond_closed' => 'إغلاق السند بعد انتهاء الجمعية',
            'payment_date' => 'تاريخ السداد الفعلي',
            'notes' => 'ملاحظات',
        ],
    ],

    'policy_pages' => [
        'salifni_title' => 'شروط ولوائح صندوق سلفني',
        'salifni_subtitle' => 'اللائحة التنظيمية، آلية السلف، وجداول الأقساط والاستحقاق',
        'salifni_section_title' => 'إعداد وصياغة لائحة صندوق سلفني',
        'salifni_section_desc' => 'قم بتعديل وصياغة بنود اللائحة والشروط المعتمدة من خلال المحرر الغني أدناه لتظهر لجميع المشتركين.',

        'rosca_title' => 'شروط ولوائح صندوق الجمعية',
        'rosca_subtitle' => 'الضوابط والآليات الحاكمة للجمعيات التكافلية وتوزيع الأدوار',
        'rosca_section_title' => 'إعداد وصياغة لائحة صندوق الجمعية',
        'rosca_section_desc' => 'قم بتعديل وصياغة بنود اللائحة والشروط المعتمدة من خلال المحرر الغني أدناه لتظهر لجميع المشتركين.',

        'field_title' => 'عنوان اللائحة الرئيسي',
        'field_subtitle' => 'الوصف التمهيدي',
        'field_content' => 'المحتوى والشرح',
        'field_content_placeholder' => 'اكتب شرح وتفاصيل اللائحة هنا...',

        'actions' => [
            'save' => 'حفظ وتحديث اللائحة',
            'reset_default' => 'استعادة النص النموذجي',
            'reset_salifni_heading' => 'استعادة النص النموذجي للائحة سلفني',
            'reset_rosca_heading' => 'استعادة النص النموذجي للائحة الجمعية',
            'reset_modal_desc' => 'هل أنت متأكد من استعادة النص الافتراضي المقترح للائحة؟',
        ],

        'notifications' => [
            'salifni_saved' => 'تم حفظ وتحديث لائحة صندوق سلفني بنجاح.',
            'rosca_saved' => 'تم حفظ وتحديث لائحة صندوق الجمعية بنجاح.',
            'reset_info' => 'تمت استعادة النص النموذجي، اضغط على حفظ لاعتماده.',
        ],
    ],
];
