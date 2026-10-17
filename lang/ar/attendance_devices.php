<?php

return [
    'single' => 'جهاز بصمة',
    'plural' => 'أجهزة البصمة',
    'navigation_label' => 'أجهزة البصمة (Biometric)',
    'sections' => [
        'device_info' => 'معلومات جهاز البصمة',
        'device_info_desc' => 'إعدادات الاتصال والتعريف بجهاز البصمة (ZKTeco ADMS / Push)',
    ],
    'fields' => [
        'name' => 'اسم الجهاز / الموقع',
        'code' => 'الرقم التسلسلي (Serial Number / SN)',
        'code_helper' => 'يجب أن يطابق تماماً الـ Serial Number الموجود على ظهر الجهاز أو في شاشته',
        'company' => 'الشركة التابع لها الجهاز',
        'branch' => 'الفرع',
        'model' => 'موديل الجهاز',
        'device_type' => 'نوع التحقق المدعوم',
        'ip_address' => 'عنوان IP المحلي (اختياري)',
        'location' => 'موقع التركيب',
        'status' => 'حالة الجهاز',
        'last_seen_at' => 'آخر نبضة / اتصال بالسيرفر',
    ],
    'placeholders' => [
        'name' => 'مثال: جهاز المدخل الرئيسي - فرع الرياض',
        'code' => 'مثال: CL542300188 أو ZK-SIM-001',
        'model' => 'مثال: ZKTeco F34 Lite / MB20 / MB460',
        'ip' => 'مثال: 192.168.1.200',
        'location' => 'مثال: بوابة الدخول 1',
        'unspecified' => 'غير محدد',
    ],
    'types' => [
        'fingerprint' => 'بصمة إصبع فقط',
        'face' => 'بصمة وجه فقط',
        'fingerprint_face' => 'بصمة إصبع + وجه',
        'rfid_card' => 'بطاقة RFID / كود',
    ],
    'statuses' => [
        'active' => 'نشط ويعمل',
        'inactive' => 'معطل / غير نشط',
        'maintenance' => 'تحت الصيانة',
    ],
    'actions' => [
        'create' => 'إضافة جهاز بصمة جديد',
    ],
];
