# 📁 قائمة الرفع على الإنتاج: «صندوق المخالفات» و«ملخص التكليفات» (Production)

**تاريخ الإعداد: 2026-11-21.** تجمع هذه القائمة ملفات المهام الأربع الجاهزة للرفع على سيرفر الإنتاج (`/home2/fawaz/public_html`):
1. **تاسك صندوق المخالفات** (ربط السند بمخالفة / مجموعة مخالفات / حر، مع الإصلاحات المالية والتعديل وكشف المنشأة).
2. **تاسك ملخص التكليفات وطباعتها** (الكوميت `11a8ba9`).
3. **تاسك هيكل الأقسام (الثلاث طبقات)** (منع الطبقة الرابعة، ربط المنشآت بالطبقة الثالثة، وصلاحيات السوبر أدمن).
4. **تاسك البريد الموحّد (Webmail)** (الكوميت `0e609eb`).

*(في VS Code، اضغط `Ctrl + Click` على أي ملف لفتحه مباشرة)*

---

## ترتيب الرفع على الإنتاج

1. **نسخة احتياطية ووضع الصيانة:**
   - خذ نسخة احتياطية من قاعدة البيانات والملفات المستبدلة.
   - نفّذ على السيرفر من `/home2/fawaz/public_html`:
     ```bash
     php artisan down
     php artisan migrate:status
     ```
2. **رفع ملفات الدفعتين** (القوائم أدناه):
   - ارفع الاعتمادات أولًا (`database/migrations`, `app/Enums`, `app/Models`, `app/Services`, `app/Observers`, `lang/`, `resources/views/`).
   - ثم ارفع موارد وصفحات Filament والكنترولرات و`routes/web.php`.
3. **تشغيل الترحيلات ومسح الكاش:**
   ```bash
   php artisan migrate
   php artisan optimize:clear
   php artisan up
   ```
4. **تنبيه الاستضافة المشتركة (`Cannot declare class X`):**
   - هذه الدفعة تضيف 4 كلاسات جديدة (`ViolationTransactionFormService`، `ViolationTransactionService`، `TaskAssignmentPrintController`، `TaskAssignmentStatusResolver`).
   - بعد الرفع مباشرة نفّذ `php artisan optimize:clear`؛ وإن ظهر خطأ `Cannot declare class` أعد تشغيل PHP من cPanel.

---

## أولاً: ملفات تاسك «صندوق المخالفات» (14 ملفاً)

### 1. قاعدة البيانات والنماذج والخدمات
- 📄 [database/migrations/2026_11_19_150000_make_violation_id_nullable_and_add_link_type.php](database/migrations/2026_11_19_150000_make_violation_id_nullable_and_add_link_type.php) 🌟 *(جديد — يجعل `violation_id` يقبل `NULL`)*
- 📄 [app/Models/ViolationTransaction.php](app/Models/ViolationTransaction.php) *(إدارة أحداث الإنشاء والتعديل وعكس التسوية)*
- 📄 [app/Services/ViolationTransactionService.php](app/Services/ViolationTransactionService.php) 🌟 *(جديد — التحقق والتسوية وعكسها وإعادة حساب `balance_after`)*
- 📄 [app/Services/ViolationTransactionFormService.php](app/Services/ViolationTransactionFormService.php) 🌟 *(جديد — خيارات الربط والحساب التلقائي وتطبيع البيانات)*
- 📄 [app/Services/ViolationSummaryService.php](app/Services/ViolationSummaryService.php) *(إظهار السندات المجمعة والحرة في كشف المنشأة)*
- 📄 [app/Services/ExportPdfService.php](app/Services/ExportPdfService.php) *(دعم السندات المجمعة والحرة في طباعة سند PDF)*

### 2. واجهات Filament (مورد حركات صندوق المخالفات)
- 📄 [app/Filament/Resources/ViolationTransactions/Schemas/ViolationTransactionForm.php](app/Filament/Resources/ViolationTransactions/Schemas/ViolationTransactionForm.php)
- 📄 [app/Filament/Resources/ViolationTransactions/Tables/ViolationTransactionsTable.php](app/Filament/Resources/ViolationTransactions/Tables/ViolationTransactionsTable.php)
- 📄 [app/Filament/Resources/ViolationTransactions/Pages/CreateViolationTransaction.php](app/Filament/Resources/ViolationTransactions/Pages/CreateViolationTransaction.php)
- 📄 [app/Filament/Resources/ViolationTransactions/Pages/EditViolationTransaction.php](app/Filament/Resources/ViolationTransactions/Pages/EditViolationTransaction.php)

### 3. قوالب العرض والترجمة
- 📄 [resources/views/violations/partials/statement.blade.php](resources/views/violations/partials/statement.blade.php)
- 📄 [resources/views/violations/summary-print.blade.php](resources/views/violations/summary-print.blade.php)
- 📄 [lang/ar/violations.php](lang/ar/violations.php)
- 📄 [lang/en/violations.php](lang/en/violations.php)

---

## ثانياً: ملفات تاسك «ملخص التكليفات» — الكوميت `11a8ba9` (18 ملفاً)

### 1. الـ Enums والنماذج والخدمات والـ Observers
- 📄 [app/Enums/TaskStatus.php](app/Enums/TaskStatus.php)
- 📄 [app/Enums/UserType.php](app/Enums/UserType.php)
- 📄 [app/Models/Employee.php](app/Models/Employee.php)
- 📄 [app/Models/SaudiEmployee.php](app/Models/SaudiEmployee.php)
- 📄 [app/Observers/TaskAssignmentItemObserver.php](app/Observers/TaskAssignmentItemObserver.php)
- 📄 [app/Services/TaskAssignmentItemReviewService.php](app/Services/TaskAssignmentItemReviewService.php)
- 📄 [app/Services/TaskAssignmentStatusResolver.php](app/Services/TaskAssignmentStatusResolver.php) 🌟 *(جديد)*

### 2. الكنترولر والمسارات وموارد Filament
- 📄 [app/Http/Controllers/TaskAssignmentPrintController.php](app/Http/Controllers/TaskAssignmentPrintController.php) 🌟 *(جديد)*
- 📄 [routes/web.php](routes/web.php)
- 📄 [app/Filament/Concerns/HasTaskPerformanceDashboard.php](app/Filament/Concerns/HasTaskPerformanceDashboard.php)
- 📄 [app/Filament/Resources/TaskAssignments/Pages/TaskAssignmentsSummary.php](app/Filament/Resources/TaskAssignments/Pages/TaskAssignmentsSummary.php)
- 📄 [app/Filament/Resources/TaskAssignments/Schemas/TaskAssignmentForm.php](app/Filament/Resources/TaskAssignments/Schemas/TaskAssignmentForm.php)

### 3. قوالب العرض والترجمة
- 📄 [resources/views/filament/resources/task-assignments/pages/task-assignments-summary.blade.php](resources/views/filament/resources/task-assignments/pages/task-assignments-summary.blade.php)
- 📄 [resources/views/filament/tasks/performance.blade.php](resources/views/filament/tasks/performance.blade.php)
- 📄 [resources/views/task-assignments/print-statement.blade.php](resources/views/task-assignments/print-statement.blade.php) 🌟 *(جديد)*
- 📄 [resources/views/task-assignments/print-summary.blade.php](resources/views/task-assignments/print-summary.blade.php) 🌟 *(جديد)*
- 📄 [lang/ar/tasks.php](lang/ar/tasks.php)
- 📄 [lang/en/tasks.php](lang/en/tasks.php)

---

## ثالثاً: ملفات تاسك «هيكل الأقسام (الثلاث طبقات)» (19 ملف إنتاج)

### 1. الـ Enums والـ Observers والسياسات والـ Providers
- 📄 [app/Enums/UserType.php](app/Enums/UserType.php)
- 📄 [app/Observers/DepartmentObserver.php](app/Observers/DepartmentObserver.php)
- 📄 [app/Policies/DepartmentPolicy.php](app/Policies/DepartmentPolicy.php)
- 📄 [app/Providers/AppServiceProvider.php](app/Providers/AppServiceProvider.php)

### 2. الخدمات وأدوات الـ AI والتقارير
- 📄 [app/Services/DepartmentHierarchyService.php](app/Services/DepartmentHierarchyService.php)
- 📄 [app/Services/DepartmentScopeService.php](app/Services/DepartmentScopeService.php)
- 📄 [app/Services/CompanyAccessScopeService.php](app/Services/CompanyAccessScopeService.php)
- 📄 [app/Services/CompanyFormService.php](app/Services/CompanyFormService.php)
- 📄 [app/Services/AI/Tools/CreateDepartmentTool.php](app/Services/AI/Tools/CreateDepartmentTool.php)
- 📄 [app/Services/AI/Tools/CreateJobTitleTool.php](app/Services/AI/Tools/CreateJobTitleTool.php)
- 📄 [app/Services/Reports/Types/DepartmentsReport.php](app/Services/Reports/Types/DepartmentsReport.php)

### 3. واجهات Filament (مورد الأقسام)
- 📄 [app/Filament/Resources/Departments/Pages/CreateDepartment.php](app/Filament/Resources/Departments/Pages/CreateDepartment.php)
- 📄 [app/Filament/Resources/Departments/Pages/ListDepartments.php](app/Filament/Resources/Departments/Pages/ListDepartments.php)
- 📄 [app/Filament/Resources/Departments/Schemas/DepartmentForm.php](app/Filament/Resources/Departments/Schemas/DepartmentForm.php)
- 📄 [app/Filament/Resources/Departments/Tables/DepartmentsTable.php](app/Filament/Resources/Departments/Tables/DepartmentsTable.php)

### 4. الترجمة
- 📄 [lang/ar/organization.php](lang/ar/organization.php)
- 📄 [lang/en/organization.php](lang/en/organization.php)
- 📄 [lang/ar/user_types.php](lang/ar/user_types.php)
- 📄 [lang/en/user_types.php](lang/en/user_types.php)

> **ملاحظة (اختياري):** ملفا الاختبارات [tests/Feature/DepartmentHierarchyTest.php](tests/Feature/DepartmentHierarchyTest.php) و[tests/Feature/DepartmentScopeServiceTest.php](tests/Feature/DepartmentScopeServiceTest.php) لا يلزم رفعهما على الإنتاج إلا إذا أردت مزامنة مجلد `tests/`.
> ⚠️ لا ترفع `vendor.zip` الموجود في الـ git index ضمن ملفات التاسك (إلا إذا كنت ستفكّه على السيرفر لتحديث حزمة `webklex/laravel-imap` بدلاً من تشغيل `composer install`).

---

## رابعاً: ملفات تاسك «البريد الموحّد (Webmail)» — الكوميت `0e609eb`

> ⚠️ **متطلبات إضافية على السيرفر لهذا التاسك:**
> 1. **حزمة جديدة (`webklex/laravel-imap: 6.2`):** ارفع `composer.json` و`composer.lock` وشغّل `composer install --no-dev --optimize-autoloader` على السيرفر (أو ارفع `vendor/` المحدّث).
> 2. **مزامنة الصلاحيات:** شغّل `php artisan db:seed --class=RolesAndPermissionsSeeder` بعد الرفع لتسجيل صلاحيات البريد الموحّد.
> 3. **تنبيه `bootstrap/app.php`:** راجع عدم تسجيل `routes/test.php` في نسخة الإنتاج قبل استبدال `bootstrap/app.php`.

### 1. المجلدات الجديدة الكاملة (تُرفع كاملة كما هي) 📦
- 📂 [app/Filament/Resources/Mail/](app/Filament/Resources/Mail/) 🌟 *(جديد — الحسابات، الرسائل، الكلمات المستبعدة والمهمة، النطاقات والإيميلات الموثوقة، والودجات)*
- 📂 [app/Models/Mail/](app/Models/Mail/) 🌟 *(جديد — 11 موديل)*
- 📂 [app/Policies/Mail/](app/Policies/Mail/) 🌟 *(جديد — 5 سياسات)*
- 📂 [app/Services/Mail/](app/Services/Mail/) 🌟 *(جديد — 9 خدمات)*
- 📂 [app/Enums/Mail/](app/Enums/Mail/) 🌟 *(جديد — 8 Enums)*
- 📂 [app/Jobs/Mail/](app/Jobs/Mail/) 🌟 *(جديد — 3 Jobs)*
- 📂 [app/Console/Commands/Mail/](app/Console/Commands/Mail/) 🌟 *(جديد — أمران للمزامنة والتصعيد)*
- 📂 [resources/views/filament/mail/](resources/views/filament/mail/) 🌟 *(جديد — 6 قوالب)*

### 2. قاعدة البيانات والـ Seeder والـ Composer
- 📄 [composer.json](composer.json) *(إضافة `webklex/laravel-imap: 6.2`)*
- 📄 [composer.lock](composer.lock)
- 📄 [database/migrations/2026_11_19_130000_create_mail_inbox_tables.php](database/migrations/2026_11_19_130000_create_mail_inbox_tables.php) 🌟 *(جديد)*
- 📄 [database/migrations/2026_11_19_140000_scope_existing_mail_rules_to_companies.php](database/migrations/2026_11_19_140000_scope_existing_mail_rules_to_companies.php) 🌟 *(جديد)*
- 📄 [database/migrations/2026_11_21_000000_add_folder_uid_cursors_to_email_accounts_table.php](database/migrations/2026_11_21_000000_add_folder_uid_cursors_to_email_accounts_table.php) 🌟 *(جديد)*
- 📄 [database/seeders/RolesAndPermissionsSeeder.php](database/seeders/RolesAndPermissionsSeeder.php)

### 3. الكلاسات والملفات الفردية المعدلة / الجديدة
- 📄 [app/Http/Controllers/Mail/MailAttachmentController.php](app/Http/Controllers/Mail/MailAttachmentController.php) 🌟 *(جديد)*
- 📄 [app/Mail/AccountReplyMail.php](app/Mail/AccountReplyMail.php) 🌟 *(جديد)*
- 📄 [app/Observers/MailRuleObserver.php](app/Observers/MailRuleObserver.php) 🌟 *(جديد)*
- 📄 [app/Filament/Schemas/MailSettingsForm.php](app/Filament/Schemas/MailSettingsForm.php) 🌟 *(جديد)*
- 📄 [app/Filament/Pages/ManageSettings.php](app/Filament/Pages/ManageSettings.php)
- 📄 [app/Filament/Resources/Roles/Schemas/RoleForm.php](app/Filament/Resources/Roles/Schemas/RoleForm.php)
- 📄 [app/Jobs/SyncDailyAttendanceToPayrollJob.php](app/Jobs/SyncDailyAttendanceToPayrollJob.php)
- 📄 [app/Providers/AppServiceProvider.php](app/Providers/AppServiceProvider.php)
- 📄 [app/Providers/Filament/AdminPanelProvider.php](app/Providers/Filament/AdminPanelProvider.php)
- 📄 [app/Services/CompanyFormService.php](app/Services/CompanyFormService.php)
- 📄 [app/Services/NavigationOrderService.php](app/Services/NavigationOrderService.php)
- 📄 [app/Services/Notifications/NotificationDispatcher.php](app/Services/Notifications/NotificationDispatcher.php)
- 📄 [app/Services/Notifications/NotificationRecipientService.php](app/Services/Notifications/NotificationRecipientService.php)

### 4. المسارات والإعدادات والواجهات والترجمة
- 📄 [bootstrap/app.php](bootstrap/app.php)
- 📄 [routes/mail.php](routes/mail.php) 🌟 *(جديد)*
- 📄 [routes/web.php](routes/web.php)
- 📄 [routes/console.php](routes/console.php)
- 📄 [resources/views/filament/custom-sidebar.blade.php](resources/views/filament/custom-sidebar.blade.php)
- 📄 [lang/ar/mail_inbox.php](lang/ar/mail_inbox.php) 🌟 *(جديد)*
- 📄 [lang/en/mail_inbox.php](lang/en/mail_inbox.php) 🌟 *(جديد)*
- 📄 [lang/ar/navigation.php](lang/ar/navigation.php)
- 📄 [lang/en/navigation.php](lang/en/navigation.php)

> **ملاحظة (اختياري):** مجلد الاختبارات `tests/Feature/Mail/` (7 ملفات) لا يلزم رفعه على الإنتاج.
