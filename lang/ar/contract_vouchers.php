<?php

return [
    'single' => 'سند / مستخلص',
    'plural' => 'الفواتير والمستخلصات',
    'title'  => 'الفواتير والمستخلصات',

    'sections' => [
        'general_info' => 'البيانات الأساسية',
        'general_info_desc' => 'تحديد نوع المستند، التاريخ، المبلغ، والطرف المعني',
        'payment_details' => 'بيانات وطريقة الدفع',
        'payment_details_desc' => 'تفاصيل الدفع النقدي، الشيكات، أو الحوالات البنكية',
        'contract_details' => 'بيانات التعاقد وطلب الدفعة',
        'contract_details_desc' => 'حقول مرنة لتوثيق موضوع العقد وتفاصيل وسبب طلب الدفعة',
        'notes_section' => 'ملاحظات إضافية',
    ],

    'types' => [
        'receipt' => 'سند قبض - مستخلص',
        'payment' => 'سند صرف',
        'request' => 'طلب دفعة',
    ],

    'payment_methods' => [
        'cash'          => 'نقداً',
        'check'         => 'شيك',
        'bank_transfer' => 'تحويل بنكي',
    ],

    'statuses' => [
        'draft'     => 'مسودة',
        'approved'  => 'معتمد ومُرحل',
        'cancelled' => 'ملغي',
    ],

    'fields' => [
        'voucher_type'       => 'نوع المستند / السند',
        'voucher_number'     => 'رقم السند',
        'voucher_date'       => 'تاريخ السند',
        'amount'             => 'المبلغ المالي',
        'company'            => 'المنشأة التابعة',
        'offer'              => 'العقد المرتبط (اختياري)',
        'customer'           => 'العميل / الطرف المستفيد',
        'payment_method'     => 'طريقة الدفع',
        'delivered_by'       => 'اسم من سلّم المبلغ',
        'received_by'        => 'اسم المستلم',
        'check_number'       => 'رقم الشيك',
        'bank_name'          => 'اسم البنك',
        'due_date'           => 'تاريخ استحقاق الشيك',
        'transfer_bank_name' => 'اسم البنك المحول منه / إليه',
        'account_number'     => 'رقم الحساب / الآيبان',
        'reference_number'   => 'رقم مرجع العملية / رقم الحوالة',
        'first_party_name'   => 'اسم الطرف الأول',
        'second_party_name'  => 'اسم الطرف الثاني',
        'contract_number'    => 'رقم العقد',
        'contract_date'      => 'تاريخ العقد',
        'contract_subject'   => 'موضوع العقد',
        'payment_reason'     => 'سبب طلب الدفعة',
        'notes'              => 'ملاحظات',
        'status'             => 'حالة السند',
        'created_by'         => 'أُنشئ بواسطة',
    ],

    'actions' => [
        'create'          => 'إنشاء مستند جديد',
        'edit'            => 'تعديل',
        'delete'          => 'حذف',
        'print'           => 'طباعة السند',
        'download_pdf'    => 'تحميل PDF',
        'approve'         => 'اعتماد وترحيل للخزينة',
        'convert_receipt' => 'تحويل إلى سند قبض',
    ],

    'messages' => [
        'approved_success' => 'تم اعتماد السند وترحيل الحركة للخزينة بنجاح.',
        'already_approved' => 'هذا السند معتمد بالفعل ومرحل للخزينة.',
    ],

    'placeholders' => [
        'auto'             => 'توليد تلقائي (Auto)',
        'amount'           => '0.00',
        'delivered_by'     => 'اسم الشخص الذي قام بتسليم المبلغ...',
        'received_by'      => 'اسم المستلم للمبلغ...',
        'check_number'     => 'رقم الشيك البنكي...',
        'bank_name'        => 'مثال: مصرف الراجحي / البنك الأهلي...',
        'account_number'   => 'رقم الحساب أو الآيبان...',
        'reference_number' => 'رقم الحوالة أو المرجع...',
        'first_party'      => 'الطرف الأول (المقاول / المنفذ)...',
        'second_party'     => 'الطرف الثاني (المالك / العميل)...',
        'contract_number'  => 'رقم العقد...',
        'contract_subject' => 'وصف موضوع العقد ومجاله...',
        'payment_reason'   => 'بيان وسبب استحقاق الدفعة ومرحلة الإنجاز...',
        'notes'            => 'أي ملاحظات أو شروط إضافية...',
    ],
];
