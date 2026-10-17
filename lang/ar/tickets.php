<?php

return [
    'navigation_label' => 'تذاكر الدعم والمساعدة',
    'navigation_group' => 'الدعم الفني والمساعدة',
    'model_label' => 'تذكرة دعم',
    'model_plural_label' => 'تذاكر الدعم',
    
    'unknown_user' => 'مستخدم غير معروف',
    'system' => 'النظام الآلي',
    'staff_member' => 'فريق الدعم الفني',
    'internal_note' => 'ملاحظة داخلية خاصة بفريق الدعم',
    'internal_note_hint' => 'هذه الملاحظة سرية ولن يراها سوى مسؤولي وفريق الدعم الفني.',
    
    'fields' => [
        'ticket_number' => 'رقم التذكرة',
        'user' => 'صاحب التذكرة',
        'creator' => 'منشئ التذكرة',
        'company' => 'الشركة / المؤسسة',
        'assigned_to' => 'الموظف المكلف',
        'category' => 'القسم / التصنيف',
        'priority' => 'الأولوية',
        'status' => 'حالة التذكرة',
        'subject' => 'عنوان التذكرة',
        'description' => 'تفاصيل المشكلة / الطلب',
        'attachments' => 'المرفقات والمستندات',
        'last_reply_at' => 'تاريخ آخر رد',
        'created_at' => 'تاريخ الفتح',
        'updated_at' => 'آخر تحديث',
        'resolved_at' => 'تاريخ الحل',
        'closed_at' => 'تاريخ الإغلاق',
        'reply_message' => 'نص الرد',
        'is_internal_note' => 'ملاحظة داخلية (فريق الدعم فقط)',
        'update_status_on_reply' => 'تحديث حالة التذكرة بعد الرد',
    ],

    'sections' => [
        'ticket_info' => 'معلومات التذكرة الأساسية',
        'ticket_management' => 'بيانات الإسناد والمعالجة',
        'ticket_attachments' => 'المرفقات والملفات',
        'conversation' => 'سجل المحادثة والردود',
        'add_reply' => 'إضافة رد جديد',
    ],

    'statuses' => [
        'open' => 'مفتوحة / جديدة',
        'in_progress' => 'قيد المعالجة',
        'waiting_user_reply' => 'بانتظار رد المستخدم',
        'resolved' => 'تم الحل',
        'closed' => 'مغلقة',
    ],

    'priorities' => [
        'low' => 'منخفضة',
        'medium' => 'متوسطة',
        'high' => 'مرتفعة',
        'urgent' => 'عاجلة / حرجة',
    ],

    'categories' => [
        'technical' => 'دعم فني وبرمجي',
        'hr' => 'شؤون الموظفين والموارد البشرية',
        'finance' => 'المالية والحسابات والرواتب',
        'general' => 'استفسارات عامة',
        'complaint' => 'شكوى أو بلاغ',
        'suggestion' => 'اقتراح وتطوير',
    ],

    'filters' => [
        'my_tickets' => 'تذاكري فقط',
        'assigned_to_me' => 'المكلف بها شخصياً',
        'open_tickets' => 'التذاكر المفتوحة والنشطة',
        'all' => 'جميع التذاكر',
    ],

    'actions' => [
        'view_ticket' => 'عرض التذكرة والردود',
        'reply' => 'إرسال الرد',
        'assign_to_me' => 'استلام التذكرة وتكليفي بها',
        'assign' => 'إسناد لموظف',
        'close_ticket' => 'إغلاق التذكرة',
        'resolve_ticket' => 'تعليم كمحلولة',
        'reopen_ticket' => 'إعادة فتح التذكرة',
        'download_attachment' => 'تحميل المرفق',
    ],

    'notifications' => [
        'new_ticket_title' => 'تذكرة دعم جديدة',
        'new_ticket_body' => 'تم إنشاء تذكرة جديدة :number بعنوان ":subject" بواسطة :user',
        
        'staff_replied_title' => 'رد جديد على تذكرتك :number',
        'staff_replied_body' => 'قام فريق الدعم الفني بالرد على تذكرتك ":subject"',
        
        'user_replied_title' => 'رد جديد من المستخدم على :number',
        'user_replied_body' => 'أضاف :user رداً جديداً على التذكرة ":subject"',
        
        'internal_note_title' => 'ملاحظة داخلية على :number',
        'internal_note_body' => 'أضاف :user ملاحظة داخلية على التذكرة ":subject"',
        
        'status_changed_title' => 'تحديث حالة التذكرة :number',
        'status_changed_body' => 'تم تغيير حالة التذكرة ":subject" إلى (:status)',
        
        'assigned_title' => 'تم إسناد تذكرة إليك :number',
        'assigned_body' => 'تم تكليفك بمتابعة وحل التذكرة ":subject"',
        
        'reply_sent_success' => 'تم إرسال ردك بنجاح.',
        'status_updated_success' => 'تم تحديث حالة التذكرة بنجاح.',
        'ticket_assigned_success' => 'تم إسناد التذكرة بنجاح.',
    ],
];
