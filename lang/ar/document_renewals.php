<?php

return [
    'navigation_label' => 'تجديد المستندات',
    'title' => 'طلبات تجديد المستندات',

    'empty_state' => [
        'heading' => 'لا توجد طلبات تجديد مستندات',
        'description' => 'لا توجد طلبات تجديد متاحة أو مسندة لك ضمن نطاقك الحالي.',
    ],

    'types' => [
        'iqama' => 'تجديد الإقامة',
        'commercial_registration' => 'تجديد السجل التجاري',
        'municipality' => 'تجديد رخصة البلدية',
        'civil_defense' => 'تجديد رخصة الدفاع المدني',
        'health_insurance' => 'تجديد التأمين الطبي',
        'work_permit' => 'تجديد رخصة العمل',
        'passport' => 'تجديد جواز السفر',
        'employee_contract' => 'تجديد عقد الموظف',
        'subscription_qiwa' => 'تجديد اشتراك قوى',
        'subscription_absher' => 'تجديد اشتراك أبشر أعمال / مقيم',
        'subscription_spl' => 'تجديد اشتراك سبل',
    ],

    'permissions' => [
        'manage_iqama_renewals' => 'إدارة تجديد الإقامات',
        'manage_company_license_renewals' => 'إدارة تجديد تراخيص المنشآت (السجل والبلدية والدفاع المدني)',
        'manage_health_insurance_renewals' => 'إدارة تجديد التأمين الطبي للموظفين',
        'manage_work_permit_renewals' => 'إدارة تجديد رخص العمل',
        'manage_passport_renewals' => 'إدارة تجديد جوازات السفر',
        'manage_employee_contract_renewals' => 'إدارة تجديد عقود الموظفين',
        'manage_qiwa_subscription_renewals' => 'إدارة تجديد اشتراك قوى',
        'manage_absher_subscription_renewals' => 'إدارة تجديد اشتراك أبشر أعمال / مقيم',
        'manage_spl_subscription_renewals' => 'إدارة تجديد اشتراك سبل',
    ],

    'statuses' => [
        'available' => 'متاح للاستلام',
        'claimed' => 'قيد التنفيذ',
        'pending_treasury' => 'بانتظار اعتماد الخزنة',
        'returned' => 'مُرجع للتصحيح',
        'completed' => 'مكتمل ومعتمد',
        'cancelled' => 'ملغي',
    ],

    'fee_modes' => [
        'with_fee' => 'بتكلفة / رسوم تجديد',
        'no_fee' => 'بدون رسوم — لا يوجد صرف',
    ],

    'payment_methods' => [
        'cash' => 'نقدي',
        'bank_transfer' => 'تحويل بنكي',
    ],

    'settings' => [
        'section_title' => 'إعدادات تجديد المستندات والتقييم',
        'section_desc' => 'تحديد نافذة إنشاء طلبات التجديد، مهلة التنفيذ من الاستلام، وزن المهمة، ونسبة درجة الإنجاز المتأخر.',
        'lead_days' => 'عدد أيام التقديم قبل الانتهاء',
        'lead_days_helper' => 'صفر يعني إنشاء الطلب عند انتهاء المستند فقط؛ قيمة أكبر تنشئ الطلب قبل الانتهاء بهذا العدد من الأيام.',
        'deadline_days' => 'مهلة التنفيذ بالأيام (من الاستلام)',
        'deadline_days_helper' => 'تبدأ المهلة من تاريخ استلام المدير للطلب وتثبت مرة واحدة دون إعادة ضبط عند الإرجاع.',
        'task_weight' => 'وزن مهمة التجديد',
        'task_weight_helper' => 'الدرجة الكاملة المستحقة عند تسليم النسخة المقبولة ضمن المهلة.',
        'late_completion_percentage' => 'نسبة الدرجة المستحقة للإنجاز المتأخر (%)',
        'late_completion_percentage_helper' => 'حصة مستحقة من وزن المهمة (من 0 إلى 100%) عند التسليم بعد الموعد، وليست خصمًا يوميًا.',
        'tracking_start_date' => 'تاريخ بداية تتبع المستندات المنتهية (اختياري)',
        'tracking_start_date_helper' => 'اتركه فارغًا لتتبع كل المستندات المستحقة، أو حدد تاريخًا لاستبعاد المستندات المنتهية قبله.',
    ],

    'layer_one' => [
        'permissions_label' => 'صلاحيات تجديد المستندات لمدير الإدارة',
        'permissions_helper' => 'تُمنح مباشرة على حساب مدير إدارة الطبقة الأولى وتُنقل تلقائيًا عند استبداله.',
        'kept_other_admin_title' => 'تم الإبقاء على صلاحيات المدير السابق',
        'kept_other_admin_body' => 'المدير السابق ما زال يدير إدارة طبقة أولى أخرى نشطة، لذا تم منح الصلاحيات للمدير الجديد مع إبقائها للمدير السابق.',
        'role_inherited_warning_title' => 'تنبيه صلاحيات موروثة من دور',
        'role_inherited_warning_body' => 'يوجد صلاحيات تجديد موروثة عبر دور عام؛ لم يتم تعديل الدور العام تلقائيًا.',
    ],

    'catalog' => [
        'system_task_description' => 'مهمة نظامية ثابتة لمتابعة :type.',
        'errors' => [
            'cannot_modify_system_task' => 'لا يمكن تغيير كود مهمة تجديد نظامية محجوزة.',
            'cannot_deactivate_system_task' => 'لا يمكن تعطيل مهمة تجديد نظامية محجوزة.',
            'cannot_delete_system_task' => 'لا يمكن حذف مهمة تجديد نظامية محجوزة حفاظًا على سجل التقييم.',
        ],
    ],

    'request' => [
        'title_format' => ':type — :target',
        'description_format' => 'طلب :type لـ :target (تاريخ انتهاء الدورة: :expiry).',
    ],

    'invoice' => [
        'draft_notes' => 'مسودة فاتورة مصروف :type — :target',
        'fee_line_item' => 'رسوم :type',
        'no_fee_badge' => 'بدون رسوم — لا يوجد صرف',
    ],

    'treasury' => [
        'disbursement_description' => 'صرف رسوم :type — :target (فاتورة :invoice)',
    ],

    'columns' => [
        'task_code' => 'رقم الطلب',
        'company' => 'المؤسسة',
        'document_type' => 'نوع المستند',
        'target' => 'صاحب المستند',
        'cycle_expiry' => 'انتهاء الدورة',
        'due_date' => 'موعد التنفيذ',
        'status' => 'الحالة',
        'assignee' => 'المسؤول المستلم',
        'fee_summary' => 'الرسوم / الوضع',
        'proposed_expiry' => 'التاريخ المقترح',
    ],

    'filters' => [
        'status' => 'حالة الطلب',
        'document_type' => 'نوع المستند',
        'mine_only' => 'طلباتي المستلمة فقط',
    ],

    'actions' => [
        'view_details' => 'التفاصيل',
        'claim' => 'استلام وبدء التنفيذ',
        'claim_success' => 'تم استلام الطلب وإنشاء التكليف بنجاح',
        'claim_already_yours' => 'هذا الطلب مستلم باسمك بالفعل',
        'submit_treasury' => 'استكمال التجديد وإرسال للخزنة',
        'submit_success' => 'تم إرسال بيانات التجديد والفاتورة لاعتماد الخزنة',
        'approve_treasury' => 'اعتماد الخزنة وإغلاق التجديد',
        'approve_success' => 'تم الاعتماد النهائي وتحديث تاريخ المستند وإغلاق المهمة',
        'return_treasury' => 'إرجاع للتصحيح',
        'return_success' => 'تم إرجاع الطلب للمسؤول للتصحيح',
        'reassign' => 'إعادة إسناد الطلب',
        'reassign_success' => 'تمت إعادة إسناد الطلب بنجاح مع الحفاظ على سجل المهمة والفاتورة',
    ],

    'fields' => [
        'fee_mode' => 'وضع الرسوم',
        'amount' => 'إجمالي رسوم التجديد',
        'new_expiry_date' => 'تاريخ الانتهاء الجديد',
        'payment_method' => 'طريقة الدفع',
        'reference_number' => 'رقم المرجع / السند',
        'proof_path' => 'إثبات السداد (اختياري)',
        'document_attachment' => 'مرفق المستند المجدد (اختياري)',
        'notes' => 'ملاحظات',
        'return_reason' => 'سبب الإرجاع للتصحيح',
        'approval_note' => 'ملاحظة الاعتماد (اختياري)',
        'target_employee' => 'المسؤول الجديد',
        'reassignment_reason' => 'سبب إعادة الإسناد',
    ],

    'details' => [
        'modal_heading' => 'تفاصيل طلب تجديد المستند',
        'close' => 'إغلاق',
        'submission_history' => 'سجل التقديمات والمراجعات',
    ],

    'notifications' => [
        'available_title' => 'طلب تجديد مستند متاح للاستلام',
        'available_body' => 'يتوفر طلب :type لـ :target (انتهاء الدورة: :expiry).',
        'pending_treasury_title' => 'طلب تجديد بانتظار اعتماد الخزنة',
        'pending_treasury_body' => 'قام :employee بتقديم طلب :title للمراجعة والاعتماد المالي.',
        'returned_title' => 'تم إرجاع طلب التجديد للتصحيح',
        'returned_body' => 'أُرجع طلب :title من الخزنة. السبب: :reason',
        'approved_title' => 'تم اعتماد وإغلاق طلب التجديد',
        'approved_body' => 'تم اعتماد :title وتحديث تاريخ الانتهاء إلى :expiry (الدرجة: :score).',
    ],

    'errors' => [
        'invalid_request_type' => 'السجل المحدد ليس طلب تجديد مستند.',
        'already_claimed_or_closed' => 'تم استلام هذا الطلب بالفعل من مسؤول آخر أو تم إغلاقه.',
        'not_eligible_to_claim' => 'لست مؤهلًا لاستلام هذا الطلب وفق الهيكل الإداري وصلاحيات التجديد.',
        'source_missing' => 'تعذر العثور على سجل المستند الأصلي.',
        'source_expiry_changed' => 'تغير تاريخ انتهاء المستند الأصلي خارج المهمة؛ يجب مراجعة الحالة قبل المتابعة.',
        'cannot_submit' => 'لا تملك صلاحية تقديم هذا الطلب للمراجعة.',
        'invalid_fee_mode' => 'يجب اختيار وضع الرسوم صراحة (بتكلفة أو بدون رسوم).',
        'new_expiry_required' => 'تاريخ الانتهاء الجديد مطلوب.',
        'invalid_new_expiry' => 'صيغة تاريخ الانتهاء الجديد غير صالحة.',
        'new_expiry_must_be_after_cycle' => 'يجب أن يكون تاريخ الانتهاء الجديد بعد تاريخ انتهاء الدورة الحالية.',
        'no_fee_must_be_zero' => 'في حالة التجديد بدون رسوم يجب أن يكون المبلغ صفرًا دون ربط حركة مالية.',
        'positive_fee_required' => 'يجب إدخال مبلغ رسوم موجب أو اختيار وضع «بدون رسوم» صراحة.',
        'invalid_linked_invoice' => 'الفاتورة المرتبطة غير صالحة كفاتورة مصروف تجديد.',
        'no_fee_conflicts_with_payment' => 'لا يمكن التحويل إلى «بدون رسوم» مع وجود أثر مالي سابق على الفاتورة.',
        'cannot_review_treasury' => 'لا تملك صلاحية اعتماد الخزنة لهذا الطلب أو تحاول الاعتماد الذاتي.',
        'return_reason_required' => 'سبب الإرجاع للتصحيح إلزامي.',
        'stale_submission_version' => 'تغيرت نسخة التقديم أثناء المراجعة؛ يرجى تحديث الشاشة والمحاولة مجددًا.',
        'submission_fingerprint_mismatch' => 'تغيرت بيانات الفاتورة أو التجديد بعد التقديم؛ يجب إعادة التقديم قبل الاعتماد.',
        'already_approved' => 'تم اعتماد هذا الطلب مسبقًا.',
        'cannot_resolve_via_reconciliation' => 'لا يمكن إغلاق طلب تجديد مستند من إجراء حل نواقص الخدمات العام.',
        'use_renewal_reassignment' => 'لا يمكن تحويل بند تجديد المستند عبر التحويل العام؛ استخدم إجراء إعادة إسناد التجديد المخصص.',
        'cannot_reassign' => 'لا تملك صلاحية إعادة إسناد هذا الطلب.',
        'reassignment_reason_required' => 'سبب إعادة الإسناد إلزامي.',
        'not_claimed_yet' => 'الطلب لم يُستلم بعد.',
        'reassign_same_employee' => 'الموظف المختار هو المسؤول الحالي بالفعل.',
        'target_missing_renewal_permission' => 'الموظف المختار لا يحمل صلاحية تجديد هذا النوع من المستندات.',
        'renewal_invoice_locked_for_receipt' => 'فاتورة مصروف التجديد لا تقبل تسجيل قبض أو توريد من مسار الإيرادات العام.',
    ],
];
