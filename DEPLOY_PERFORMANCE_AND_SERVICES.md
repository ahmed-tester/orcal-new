# 📁 قائمة الرفع الذكية لموديول «الأداء» و«المعاملات والخدمات» (Production)

**آخر مراجعة محلية: 2026-11-18.** الحصر مبني على `refactoring` حتى `86c71fa` والتعديلات الموجودة في Git index. راجع نسخة السيرفر لتحديد الملفات والترحيلات التي ما زالت مطلوبة.

## ترتيب الرفع على الإنتاج

1. **حدّد الدفعات قبل الرفع.** راجع نطاق كل دفعة أدناه وحالة السيرفر، وخد نسخة احتياطية من قاعدة البيانات ونسخة من الملفات اللي هتستبدلها. شغّل `php artisan migrate:status` وسجّل الترحيلات المعلّقة. أوامر السيرفر تُشغّل من `/home2/fawaz/public_html`.
2. **راجع مسارات الاختبار الموجودة على السيرفر.** استبعد `routes/test.php` وقوالب وسيدرز الديمو من الدفعة. نسخة [bootstrap/app.php](bootstrap/app.php#L13) المحلية تحمّل `routes/test.php` مباشرةً. لو نسخة الإنتاج بتسجّله بالفعل، راجع تسجيله وعطّل مسارات الديمو هناك قبل الدفعة. حذف الملف وحده مع بقاء التسجيل ممكن يمنع Laravel من الإقلاع.
3. **جهّز الدفعة ثم ادخل وضع الصيانة قبل استبدال أي ملفات.** للرفع اليدوي على مجلد التشغيل استخدم `php artisan down`، ووقّف مؤقتًا أي مجدول أو عامل طابور شغّال للدُفعة. ده يمنع المستخدمين والمهام الخلفية من تشغيل نسخة فيها ملفات قديمة وجديدة في نفس الوقت. حضّر الملفات مسبقًا لتقصير نافذة الصيانة.
4. **ارفع الاعتمادات أولًا.** ارفع ترحيلات الدفعة المعتمدة وملفات الـEnums والموديلات والـTraits والخدمات والـObservers والسياسات والترجمات والقوالب. خلّي كل مجموعة مترابطة مع بعض. مثال الطباعة: ارفع الكنترولر والقالبين والترجمات قبل صفحات الشركات وملف المسارات. مثال مراجعة التكليفات: ارفع الموديل والخدمة والـObserver والترحيل مع بقية الدفعة.
5. **كمّل ملفات الاستدعاء والمسارات.** ارفع موارد وصفحات Filament والأزرار والـProviders التابعة للدفعة، ثم `routes/web.php` بعد الكنترولرات اللي بيستخدمها. صفحات Filament يتم اكتشافها تلقائيًا، فرفع صفحة الأداء الحالية يتيحها للسوبر أدمن بعد اكتمال الدفعة. جدولة التصعيدات لها خطوة مستقلة بعد التحقق.
6. **نفّذ الترحيلات العادية بعد اكتمال الملفات.** شغّل `php artisan migrate:status` مرة تانية، وطابق كل `Pending` مع الدفعة المعتمدة. لو القائمة مطابقة، شغّل `php artisan migrate` من جلسة تفاعلية وأكّد سؤال الإنتاج. لو فيه ترحيل معلّق خارج الدفعة أو أمر فشل، فضّل في وضع الصيانة لحد تحديد السبب وحالة القاعدة والملفات. راجع إجراء الاسترداد قبل أي رجوع بعد تغييرات قاعدة البيانات.
7. **زامن الصلاحيات المعتمدة وامسح الكاش.** شغّل أوامر الصلاحيات أو الكتالوج المشروطة في آخر الملف عند الحاجة، ثم `php artisan optimize:clear`. راجع `php artisan migrate:status` وتسجيل المسارات المعنية. لو تقدر تختبر بحساب مراجعة أثناء الصيانة، افتح الشاشة الأساسية للدفعة وتأكد من الصلاحيات؛ ولو ظهر خطأ تحميل كلاس جديد، راجع تعليمات الاستضافة في آخر الملف.
8. **فعّل جدولة التصعيدات عند اعتمادها، ثم ارجع الخدمة.** بعد اكتمال الترحيلات والصلاحيات والفحص، ارفع `routes/console.php` أثناء الصيانة لو الجدولة معتمدة، ثم امسح الكاش وراجع `php artisan schedule:list`. بعدها شغّل `php artisan up` واختبر تسجيل الدخول والشاشات المتأثرة، ثم رجّع العمال والمجدول.

## حدود الدفعة المؤجلة

- **الخدمات والمتابعة والدورة المالية:** القبض، توريد واستلام الكاش، سداد الرسوم، التسويات والنواقص. جاهزية جداول الدورة المالية أُكدت في 15 نوفمبر؛ راجع `migrate:status` وحالة التفعيل الحالية. المعاملات القديمة تظل على `financial_flow = legacy` وترحيلها له قرار منفصل. [دليل الدورة المالية](docs/modules/FINANCIAL_WORKFLOW.md)
- **الأداء ومتابعة التصعيدات:** النسخة المحلية الحالية تتيح لوحة المنشآت والأقسام للسوبر أدمن حتى في الإنتاج. جدولة التصعيدات كل ساعة قد تفتح أو تغلق حالات وترسل إشعارات فعلية. راجع اعتماد الإتاحة قبل نقل الصفحة والجدولة. [دليل لوحة الأداء](docs/modules/COMPANY_DEPARTMENT_PERFORMANCE.md)
- **أداء الموظف والمشرف:** النسخة المحلية تسجّل صفحتَي أداء السعودي والأجنبي وتتيح تقرير المشرف في الإنتاج حسب الصلاحيات ونطاق الوصول. عند نشر الإتاحة ارفع تعريفَي المورد وصفحتَي الأداء وزر الأداء المشترك وخدمة تقرير المشرف معًا. تسجيل متابعة المشرف وتسليم النوبات توسعة مستقبلية. [دليل المهام والتكليفات](docs/modules/TASKS.md)

> 💡 **فكرة هذه القائمة:**  
> تم حصر التعديلات والكود الخاص بـ **الأداء** و**تصعيدات الأداء ومتابعتها** و**المعاملات والخدمات والتدفق المالي** وتجميعها بذكاء:
> - **المجلدات ذات التعديلات الكثيفة:** تم جمعها في **مجلدات رئيسية** تُرفع كاملة دفعة واحدة لضمان عدم نسيان أي ملف فرعي أو واجهة.
> - **المجلدات المشتركة:** استخدم الملفات المحددة في الدفعة المعتمدة. الرفع الكامل لـ`app/Services` و`lang` ينقل تعديلات وحدات أخرى، ومنها الاستيراد الذكي وتغييرات 18 نوفمبر الموجودة محليًا.
> 
> *(في VS Code، اضغط `Ctrl + Click` على أي مجلد أو ملف لفتحه مباشرة)*

---

## أولاً: المجلدات المجمّعة للدفعات المعتمدة 📦

1. 📂 **تصعيدات وملاحظات الأداء (المورد الجديد كاملاً بصفحات العرض والقائمة والإنفوليست والجدول):** 🌟 *(جديد)*
   - 👉 [app/Filament/Resources/PerformanceEscalations/](app/Filament/Resources/PerformanceEscalations/)

2. 📂 **قوالب وواجهات لوحة أداء الشركة والأقسام (يضم كافة كشوفات وسجلات وبطاقات ومؤشرات الأداء):** 🌟 *(مُحدث وموسع بالكامل)*
   - 👉 [resources/views/filament/pages/company-department-performance/](resources/views/filament/pages/company-department-performance/)

3. 📂 **المعاملات والخدمات (مورد المعاملات بكل صفحاته ونماذجه وجداوله ومؤشراته):**
   - 👉 [app/Filament/Resources/ServiceTransactions/](app/Filament/Resources/ServiceTransactions/)

4. 📂 **واجهات وقوالب المعاملات (لوحة العمليات والملخصات والمؤشرات المالية):**
   - 👉 [resources/views/filament/resources/service-transactions/](resources/views/filament/resources/service-transactions/)

5. 📂 **النوافذ المنبثقة التفاعلية (باركود التسليم، QR الكاش، النواقص، وسجل التدقيق):**
   - 👉 [resources/views/filament/modals/](resources/views/filament/modals/)

6. 📂 **التكليفات والمهام (المورد كاملاً مع صفحة وجدول ملخص أداء التكليفات):**
   - 👉 [app/Filament/Resources/TaskAssignments/](app/Filament/Resources/TaskAssignments/)

7. 📂 **مجلد الخدمات بالكامل (يشمل كافة كلاسات خدمات الأداء والتصعيدات والمعاملات والتدفق المالي):**
   - 👉 [app/Services/](app/Services/)

8. 📂 **مجلدات الترجمة الكاملة (عربي + إنجليزي لضمان كافة مفاتيح الأداء والتصعيدات والخدمات والصلاحيات):**
   - 👉 [lang/ar/](lang/ar/)
   - 👉 [lang/en/](lang/en/)

---

## ثانياً: الملفات الفردية المستقلة (لا ترفع مجلداتها بالكامل) 📄

### 1. شاشات وواجهات «الأداء» الفردية (في حال عدم رفع مجلد واجهات الأداء كاملاً):
- 📄 [app/Filament/Pages/CompanyDepartmentPerformance.php](app/Filament/Pages/CompanyDepartmentPerformance.php) *(شاشة أداء الشركة والأقسام - محدثة بكشوفات السندات والحركات المالية وسجلات الامتثال والقوى العاملة)*
- 📄 [resources/views/filament/pages/company-department-performance.blade.php](resources/views/filament/pages/company-department-performance.blade.php) *(القالب الرئيسي لشاشة أداء الشركة والأقسام)*
- 📄 [resources/views/filament/pages/company-department-performance/operations-overview.blade.php](resources/views/filament/pages/company-department-performance/operations-overview.blade.php) *(ملخص التشغيل والمعاملات)*
- 📄 [resources/views/filament/pages/company-department-performance/workforce-overview.blade.php](resources/views/filament/pages/company-department-performance/workforce-overview.blade.php) *(ملخص الحضور والإجازات والغياب)*
- 📄 [resources/views/filament/pages/company-department-performance/compliance-overview.blade.php](resources/views/filament/pages/company-department-performance/compliance-overview.blade.php) *(ملخص الامتثال والمهام)*
- 📄 [resources/views/filament/pages/company-department-performance/violations-overview.blade.php](resources/views/filament/pages/company-department-performance/violations-overview.blade.php) *(ملخص المخالفات والجزاءات)*
- 📄 [resources/views/filament/pages/company-department-performance/financial-overview.blade.php](resources/views/filament/pages/company-department-performance/financial-overview.blade.php) *(ملخص الأداء المالي والتحصيلات)* 🌟 *(جديد)*
- 📄 [resources/views/filament/pages/company-department-performance/financial-statement.blade.php](resources/views/filament/pages/company-department-performance/financial-statement.blade.php) *(كشف تفاصيل سندات وحركات العمليات المالية)* 🌟 *(جديد)*
- 📄 [resources/views/filament/pages/company-department-performance/treasury-statement.blade.php](resources/views/filament/pages/company-department-performance/treasury-statement.blade.php) *(كشف تفاصيل حركة الخزينة وتدفقات النقد)* 🌟 *(جديد)*
- 📄 [resources/views/filament/pages/company-department-performance/compliance-records.blade.php](resources/views/filament/pages/company-department-performance/compliance-records.blade.php) *(سجلات تفاصيل مهام الامتثال)* 🌟 *(جديد)*
- 📄 [resources/views/filament/pages/company-department-performance/compliance-metric-grid.blade.php](resources/views/filament/pages/company-department-performance/compliance-metric-grid.blade.php) *(شبكة مؤشرات الامتثال)* 🌟 *(جديد)*
- 📄 [resources/views/filament/pages/company-department-performance/financial-records.blade.php](resources/views/filament/pages/company-department-performance/financial-records.blade.php) *(سجلات تفاصيل السندات المالية)* 🌟 *(جديد)*
- 📄 [resources/views/filament/pages/company-department-performance/financial-metric-grid.blade.php](resources/views/filament/pages/company-department-performance/financial-metric-grid.blade.php) *(شبكة مؤشرات المالية)* 🌟 *(جديد)*
- 📄 [resources/views/filament/pages/company-department-performance/workforce-records.blade.php](resources/views/filament/pages/company-department-performance/workforce-records.blade.php) *(سجلات تفاصيل حضور وغياب الموظفين)* 🌟 *(جديد)*
- 📄 [resources/views/filament/pages/company-department-performance/workforce-metric-grid.blade.php](resources/views/filament/pages/company-department-performance/workforce-metric-grid.blade.php) *(شبكة مؤشرات القوى العاملة)* 🌟 *(جديد)*
- 📄 [resources/views/filament/pages/company-department-performance/statement-modal.blade.php](resources/views/filament/pages/company-department-performance/statement-modal.blade.php) *(نافذة كشف المعاملات والمهام المنبثقة - Modal وسط الشاشة بدلاً من السحب الجانبي)*
- 📄 [resources/views/filament/pages/company-department-performance/companies.blade.php](resources/views/filament/pages/company-department-performance/companies.blade.php) *(بطاقات أداء المنشآت - المستوى الأول)*
- 📄 [resources/views/filament/pages/company-department-performance/departments.blade.php](resources/views/filament/pages/company-department-performance/departments.blade.php) *(بطاقات أداء الأقسام - المستوى الثاني)*
- 📄 [resources/views/filament/pages/company-department-performance/employees.blade.php](resources/views/filament/pages/company-department-performance/employees.blade.php) *(بطاقات أداء الموظفين - المستوى الثالث)*
- 📄 [resources/views/components/performance/metric-card.blade.php](resources/views/components/performance/metric-card.blade.php) *(مكون بطاقات المؤشرات الرقمية)*
- 📄 [resources/views/components/performance/score-panel.blade.php](resources/views/components/performance/score-panel.blade.php) *(مكون لوحة التقييم والسكورينج ومعدل الأداء العام)* 🌟 *(جديد)*
- 📄 [resources/views/components/performance/rate-ring.blade.php](resources/views/components/performance/rate-ring.blade.php) *(مكون الحلقات الدائرية لمؤشرات الإنجاز)*
- 📄 [app/Filament/Pages/MyTasks.php](app/Filament/Pages/MyTasks.php) *(شاشة مهام وتكليفات الموظف)*
- 📄 [app/Filament/Pages/ViewEmployeeProfile.php](app/Filament/Pages/ViewEmployeeProfile.php) *(تكامل الأداء بالملف التعريفي للموظف)*
- 📄 [app/Filament/Concerns/HasTaskPerformanceDashboard.php](app/Filament/Concerns/HasTaskPerformanceDashboard.php) *(Trait لوحة أداء المهام)*
- 📄 [resources/views/filament/tasks/performance.blade.php](resources/views/filament/tasks/performance.blade.php) *(ودجت وبطاقات أداء المهام)*
- 📄 [resources/views/filament/resources/task-assignments/pages/task-assignments-summary.blade.php](resources/views/filament/resources/task-assignments/pages/task-assignments-summary.blade.php) *(واجهة ملخص التكليفات - Modal كشف تفاصيل التكليفات)*
- 📄 [app/Filament/Resources/SaudiEmployees/Pages/EmployeePerformance.php](app/Filament/Resources/SaudiEmployees/Pages/EmployeePerformance.php) *(صفحة أداء الموظف السعودي)*
- 📄 [app/Filament/Resources/ForeignEmployees/Pages/EmployeePerformance.php](app/Filament/Resources/ForeignEmployees/Pages/EmployeePerformance.php) *(صفحة أداء الموظف غير السعودي)*

### 2. أزرار وحركات «المعاملات وتسليم الكاش وتصعيدات الأداء والصلاحيات والكونسول»:
- 📄 [app/Filament/Actions/PerformanceEscalationActions.php](app/Filament/Actions/PerformanceEscalationActions.php) *(أزرار وحركات التصعيد والمعالجة وتمديد وحل وإغلاق تصعيدات الأداء)* 🌟 *(جديد)*
- 📄 [app/Filament/Actions/EmployeePerformanceAction.php](app/Filament/Actions/EmployeePerformanceAction.php) *(زر الانتقال السريع لكشف أداء الموظف من الجداول)* 🌟 *(جديد)*
- 📄 [app/Console/Commands/CheckPerformanceEscalationsCommand.php](app/Console/Commands/CheckPerformanceEscalationsCommand.php) *(أمر الكونسول الآلي لفحص ومزامنة التصعيدات دورياً)* 🌟 *(جديد)*
- 📄 [app/Policies/PerformanceEscalationPolicy.php](app/Policies/PerformanceEscalationPolicy.php) *(سياسات وصلاحيات تصعيدات الأداء)* 🌟 *(جديد)*
- 📄 [app/Providers/AppServiceProvider.php](app/Providers/AppServiceProvider.php) *(تسجيل سياسة تصعيدات الأداء وتهيئة التطبيق)* 🌟 *(مُحدث)*
- 📄 [app/Notifications/BaseSystemNotification.php](app/Notifications/BaseSystemNotification.php) *(تحديث فئة الإشعارات الأساسية للنظام)* 🌟 *(مُحدث)*
- 📄 [app/Filament/Components/CashHandoverActions.php](app/Filament/Components/CashHandoverActions.php) *(أزرار وحركات تسليم واستلام الكاش)*
- 📄 [app/Filament/Components/ServiceFinancialActions.php](app/Filament/Components/ServiceFinancialActions.php) *(أزرار الحركات المالية وسندات المعاملات)*
- 📄 [app/Filament/Components/UnifiedOperationsActions.php](app/Filament/Components/UnifiedOperationsActions.php) *(أزرار العمليات الموحدة)*
- 📄 [app/Filament/Resources/Roles/Schemas/RoleForm.php](app/Filament/Resources/Roles/Schemas/RoleForm.php) *(إضافة قسم صلاحيات العمليات والخدمات وتصعيدات الأداء)* 🌟 *(مُحدث)*
- 📄 [app/Observers/ServiceTransactionObserver.php](app/Observers/ServiceTransactionObserver.php) *(مراقب سير المعاملات)*
- 📄 [app/Traits/HasFinancialWorkflow.php](app/Traits/HasFinancialWorkflow.php) *(Trait التدفق المالي)*
- 📄 [app/Policies/TreasuryTransactionPolicy.php](app/Policies/TreasuryTransactionPolicy.php) *(سياسات الصلاحيات المالية)*

### 3. ترحيلات دفعة الخدمات والتصعيدات حسب حالة السيرفر:
- 📄 [database/migrations/2026_11_15_100000_create_performance_escalations_table.php](database/migrations/2026_11_15_100000_create_performance_escalations_table.php) *(جدول تصعيدات وملاحظات الأداء ومتابعتها)* 🌟 *(جديد)*
- 📄 [database/migrations/2026_11_08_100000_add_workflow_status_to_service_transactions_table.php](database/migrations/2026_11_08_100000_add_workflow_status_to_service_transactions_table.php) *(حالات سير المعاملات)*
- 📄 [database/migrations/2026_11_08_110000_create_unified_settlement_and_suspense_tables.php](database/migrations/2026_11_08_110000_create_unified_settlement_and_suspense_tables.php) *(جداول التسويات والمبالغ المعلقة)*
- 📄 [database/migrations/2026_11_08_120000_create_unified_deficiency_tasks_table.php](database/migrations/2026_11_08_120000_create_unified_deficiency_tasks_table.php) *(جدول مهام النواقص)*
- 📄 [database/migrations/2026_11_08_130000_create_unified_audit_logs_table.php](database/migrations/2026_11_08_130000_create_unified_audit_logs_table.php) *(جدول سجلات التتبع والتدقيق)*
- 📄 [database/migrations/2026_11_11_100000_create_financial_workflow_tables.php](database/migrations/2026_11_11_100000_create_financial_workflow_tables.php) *(جداول السندات المالية وتسليم الكاش)*

### 4. سيدرز الإنتاج عند الحاجة:
- 📄 [database/seeders/RolesAndPermissionsSeeder.php](database/seeders/RolesAndPermissionsSeeder.php) *(صلاحيات العمليات والخدمات وتصعيدات الأداء)* 🌟 *(مُحدث)*
- 📄 [database/seeders/ServicesCatalogSeeder.php](database/seeders/ServicesCatalogSeeder.php) *(كتالوج الخدمات الإلكترونية والحكومية)*

### 5. حالات وسير العمل (app/Enums/ - 10 ملفات فقط):
- 📄 [app/Enums/PerformanceEscalationStatus.php](app/Enums/PerformanceEscalationStatus.php) *(حالات تصعيدات الأداء: مفتوح، قيد المعالجة، تم الحل، مغلق)* 🌟 *(جديد)*
- 📄 [app/Enums/PerformanceEscalationClosureReason.php](app/Enums/PerformanceEscalationClosureReason.php) *(أسباب إغلاق التصعيد: تحسن الأداء، مبرر إداري، انتهاء الفترة)* 🌟 *(جديد)*
- 📄 [app/Enums/DepartmentScope.php](app/Enums/DepartmentScope.php) *(نطاق تحكم رؤساء الأقسام بالأداء: كامل الشركة أو مخصص)* 🌟 *(جديد)*
- 📄 [app/Enums/FinancialCashDirection.php](app/Enums/FinancialCashDirection.php) *(اتجاه النقد)*
- 📄 [app/Enums/FinancialDisbursementStatus.php](app/Enums/FinancialDisbursementStatus.php) *(حالات سندات الصرف)*
- 📄 [app/Enums/FinancialDisbursementType.php](app/Enums/FinancialDisbursementType.php) *(أنواع سندات الصرف)*
- 📄 [app/Enums/FinancialHandoverStatus.php](app/Enums/FinancialHandoverStatus.php) *(حالات تسليم الكاش)*
- 📄 [app/Enums/FinancialPaymentMethod.php](app/Enums/FinancialPaymentMethod.php) *(طرق الدفع)*
- 📄 [app/Enums/FinancialReceiptStatus.php](app/Enums/FinancialReceiptStatus.php) *(حالات سندات القبض)*
- 📄 [app/Enums/OperationWorkflowStatus.php](app/Enums/OperationWorkflowStatus.php) *(مراحل سير المعاملات)*

### 6. النماذج (app/Models/ - 14 موديلاً فقط - لا ترفع مجلد Models كاملاً):
- 📄 [app/Models/PerformanceEscalation.php](app/Models/PerformanceEscalation.php) *(موديل تصعيدات الأداء ومتابعة الحالات ومؤشرات التقييم)* 🌟 *(جديد)*
- 📄 [app/Models/FinancialReceipt.php](app/Models/FinancialReceipt.php) *(سندات قبض رسوم الخدمات - تم تحديثه اليوم لربط الحركات المالية وسجلاتها)* 🌟 *(مُحدث)*
- 📄 [app/Models/CashHandover.php](app/Models/CashHandover.php) *(تسليم النقدية والكاش)*
- 📄 [app/Models/CashHandoverItem.php](app/Models/CashHandoverItem.php) *(بنود تسليم الكاش)*
- 📄 [app/Models/FinancialDisbursement.php](app/Models/FinancialDisbursement.php) *(سندات الصرف)*
- 📄 [app/Models/FinancialDisbursementPayment.php](app/Models/FinancialDisbursementPayment.php) *(دفعات سند الصرف)*
- 📄 [app/Models/ServiceTransaction.php](app/Models/ServiceTransaction.php) *(موديل المعاملة والتدفق المالي)*
- 📄 [app/Models/UnifiedDeficiencyTask.php](app/Models/UnifiedDeficiencyTask.php) *(نواقص المعاملات والمهام المعلقة)*
- 📄 [app/Models/UnifiedSuspenseAmount.php](app/Models/UnifiedSuspenseAmount.php) *(المبالغ المعلقة وفوارق التسوية)*
- 📄 [app/Models/UnifiedAuditLog.php](app/Models/UnifiedAuditLog.php) *(سجل التدقيق والتتبع)*
- 📄 [app/Models/UnifiedBatchDeposit.php](app/Models/UnifiedBatchDeposit.php) *(حزم الإيداع البنكي)*
- 📄 [app/Models/UnifiedBatchDepositItem.php](app/Models/UnifiedBatchDepositItem.php) *(بنود حزم الإيداع)*
- 📄 [app/Models/Employee.php](app/Models/Employee.php) *(نطاقات الأداء وعلاقات المهام والخدمات)*
- 📄 [app/Models/TreasuryTransaction.php](app/Models/TreasuryTransaction.php) *(ربط حركة الخزينة بسندات الخدمات)*

### 7. المسارات (Routes) وجدولة المهام (Console):
- 📄 [routes/console.php](routes/console.php) *(جدولة `performance:check-escalations` كل ساعة؛ يُرفع عند اعتماد تفعيل التصعيدات)*
- 📄 [routes/web.php](routes/web.php) *(مسارات الإنتاج)*

### 8. ملفات الترجمة الفردية (في حال عدم رفع مجلدي lang كاملاً):
- 📄 [lang/ar/performance.php](lang/ar/performance.php) & [lang/en/performance.php](lang/en/performance.php) *(ترجمات لوحة الأداء ومؤشراتها وسجلاتها المالية والقوى العاملة)* 🌟 *(مُحدث)*
- 📄 [lang/ar/performance_escalations.php](lang/ar/performance_escalations.php) & [lang/en/performance_escalations.php](lang/en/performance_escalations.php) *(ترجمات مورد تصعيدات الأداء وحالاته وأزراره)* 🌟 *(جديد)*
- 📄 [lang/ar/permissions.php](lang/ar/permissions.php) & [lang/en/permissions.php](lang/en/permissions.php) *(مسميات صلاحيات تصعيدات الأداء)* 🌟 *(مُحدث)*

---

## 💡 ثالثاً: خيار الرفع الفردي لملفات الخدمات (في حال عدم رفع مجلد Services كاملاً)

إذا فضلت عدم رفع مجلد `app/Services/` بالكامل ورفع ملفات الأداء والتصعيدات والخدمات فقط:
- 📄 [app/Services/PerformanceEscalationService.php](app/Services/PerformanceEscalationService.php) 🌟 *(محرك تصعيدات وملاحظات الأداء السلبية والتنبيهات - جديد)*
- 📄 [app/Services/CompanyFinancialPerformanceService.php](app/Services/CompanyFinancialPerformanceService.php) 🌟 *(مؤشرات الأداء المالي للمنشأة والأقسام - جديد)*
- 📄 [app/Services/ServiceFinancialPerformanceService.php](app/Services/ServiceFinancialPerformanceService.php) 🌟 *(سجلات وتفاصيل حركات وسندات الخدمات المالية - جديد)*
- 📄 [app/Services/CompanyCompliancePerformanceService.php](app/Services/CompanyCompliancePerformanceService.php) 🌟 *(مؤشرات امتثال الأقسام وسجلات المهام)*
- 📄 [app/Services/CompanyViolationsPerformanceService.php](app/Services/CompanyViolationsPerformanceService.php) 🌟 *(مؤشرات وسجلات المخالفات الجزائية)*
- 📄 [app/Services/PerformanceScoreService.php](app/Services/PerformanceScoreService.php) 🌟 *(حساب درجات ومستويات تقييم الأداء الإجمالي)*
- 📄 [app/Services/DepartmentScopeService.php](app/Services/DepartmentScopeService.php) 🌟 *(التحقق من نطاق القسم وصلاحيات المشاهدة)*
- 📄 [app/Services/CompanyDepartmentPerformanceService.php](app/Services/CompanyDepartmentPerformanceService.php) *(خدمة شاشة أداء الشركة والأقسام)*
- 📄 [app/Services/AttendanceSummaryService.php](app/Services/AttendanceSummaryService.php) *(ملخص الحضور والدوام)*
- 📄 [app/Services/LeaveSummaryService.php](app/Services/LeaveSummaryService.php) *(ملخص الإجازات)*
- 📄 [app/Services/DebtSummaryService.php](app/Services/DebtSummaryService.php) *(ملخص المديونيات)*
- 📄 [app/Services/TreasurySummaryService.php](app/Services/TreasurySummaryService.php) *(ملخص الخزينة)*
- 📄 [app/Services/ViolationSummaryService.php](app/Services/ViolationSummaryService.php) *(ملخص المخالفات)*
- 📄 [app/Services/PayrollSheetService.php](app/Services/PayrollSheetService.php) *(مسيرات الرواتب)*
- 📄 [app/Services/Notifications/NotificationDispatcher.php](app/Services/Notifications/NotificationDispatcher.php) *(مرسل الإشعارات الموحد)*
- 📄 [app/Services/Notifications/NotificationRecipientService.php](app/Services/Notifications/NotificationRecipientService.php) *(تحديد مستلمي إشعارات الأداء)*
- 📄 [app/Services/FinancialSummaryService.php](app/Services/FinancialSummaryService.php) *(ملخص الحركات المالية)*
- 📄 [app/Services/FinancialTreasuryService.php](app/Services/FinancialTreasuryService.php)
- 📄 [app/Services/EmployeePerformanceAccessService.php](app/Services/EmployeePerformanceAccessService.php)
- 📄 [app/Services/ServicePerformanceSummaryService.php](app/Services/ServicePerformanceSummaryService.php)
- 📄 [app/Services/TaskAssignmentSummaryService.php](app/Services/TaskAssignmentSummaryService.php)
- 📄 [app/Services/CashHandoverService.php](app/Services/CashHandoverService.php)
- 📄 [app/Services/FinancialReceiptService.php](app/Services/FinancialReceiptService.php)
- 📄 [app/Services/FinancialDisbursementService.php](app/Services/FinancialDisbursementService.php)
- 📄 [app/Services/FinancialSurplusRefundService.php](app/Services/FinancialSurplusRefundService.php)
- 📄 [app/Services/ServiceReceiptCreationService.php](app/Services/ServiceReceiptCreationService.php)
- 📄 [app/Services/ServiceFinancialIndicatorsService.php](app/Services/ServiceFinancialIndicatorsService.php)
- 📄 [app/Services/ServiceFinancialWorkflowService.php](app/Services/ServiceFinancialWorkflowService.php)
- 📄 [app/Services/OperationsSettlementService.php](app/Services/OperationsSettlementService.php)
- 📄 [app/Services/OperationsReconciliationService.php](app/Services/OperationsReconciliationService.php)
- 📄 [app/Services/TreasuryCustodyService.php](app/Services/TreasuryCustodyService.php)
- 📄 [app/Services/OperationsAuditService.php](app/Services/OperationsAuditService.php)
- 📄 [app/Services/OperationsNotificationService.php](app/Services/OperationsNotificationService.php)
- 📄 [app/Services/OperationsPermissionService.php](app/Services/OperationsPermissionService.php)
- 📄 [app/Services/BarcodeService.php](app/Services/BarcodeService.php)

---

## الملفات الإضافية حسب الدفعة

### طباعة المنشآت وكشف بياناتها — 16 نوفمبر (`347529c`)

- [CompanyPrintController.php](app/Http/Controllers/CompanyPrintController.php)، [print.blade.php](resources/views/companies/print.blade.php)، [statement-print.blade.php](resources/views/companies/statement-print.blade.php).
- [EditCompany.php](app/Filament/Resources/Companies/Pages/EditCompany.php)، [ListCompanies.php](app/Filament/Resources/Companies/Pages/ListCompanies.php)، [CompaniesTable.php](app/Filament/Resources/Companies/Tables/CompaniesTable.php).
- ترجمتا `organization.php` [عربي](lang/ar/organization.php) و[إنجليزي](lang/en/organization.php)، ثم [routes/web.php](routes/web.php). راجع فرق ملف المسارات مع الإنتاج قبل استبداله، لأنه مشترك مع الخدمات ووحدات أخرى. الدفعة دي لا تحتاج ترحيلًا خاصًا بها.

### المخالفات والاستيراد الذكي — 16 و17 نوفمبر (`b1628c2` و`86c71fa`)

- [CreateViolationTool.php](app/Services/AI/Tools/CreateViolationTool.php)، [CreateViolationTransactionTool.php](app/Services/AI/Tools/CreateViolationTransactionTool.php)، وترجمتا `smart_import.php` [عربي](lang/ar/smart_import.php) و[إنجليزي](lang/en/smart_import.php). الأداتان تستخدمان المنشأة المختارة ضمن صلاحيات الحساب.
- لحالة السداد «غير مدفوعة» ارفع [ViolationPaymentStatus.php](app/Enums/ViolationPaymentStatus.php)، و[ViolationSummaryService.php](app/Services/ViolationSummaryService.php)، وترجمتَي `violations.php` [عربي](lang/ar/violations.php) و[إنجليزي](lang/en/violations.php).

### نطاق الأقسام وقوائم الموظفين والملاك — 14 نوفمبر، إذا لم تُرفع الدفعة كاملة

- `DepartmentScope` و`DepartmentScopeService` في القائمة الأساسية. معهم [Department.php](app/Models/Department.php) وملفات مورد الأقسام [CreateDepartment](app/Filament/Resources/Departments/Pages/CreateDepartment.php)، [EditDepartment](app/Filament/Resources/Departments/Pages/EditDepartment.php)، [DepartmentForm](app/Filament/Resources/Departments/Schemas/DepartmentForm.php)، [DepartmentsTable](app/Filament/Resources/Departments/Tables/DepartmentsTable.php)، [HandlesDepartmentScope](app/Filament/Resources/Departments/Traits/HandlesDepartmentScope.php)، وترجمتا `organization.php`.
- طابق ملفات [السعوديين](app/Filament/Resources/SaudiEmployees/)، [الأجانب](app/Filament/Resources/ForeignEmployees/)، [المؤقتين](app/Filament/Resources/TemporaryEmployees/) و[الملاك](app/Filament/Resources/Owners/) مع `git diff --name-only 7a0dad8..fdaa8b7`، ومعها [Owner.php](app/Models/Owner.php) والخدمات المرتبطة، ومنها [PersonCapacityService](app/Services/PersonCapacityService.php) و[OwnerSalaryService](app/Services/OwnerSalaryService.php) إذا كانت دفعة الملاك متبقية. ارفع تعريفَي مورد السعودي والأجنبي مع مداخل الأداء التابعة لهما عند نشر هذا الجزء حتى تتطابق الأزرار مع المسارات.

### بقية ملفات الأداء والتذاكر والمشرف ودليل النظام (`86c71fa`)

- **التذاكر والدعم:** [CompanyTicketsPerformanceService.php](app/Services/CompanyTicketsPerformanceService.php)، [tickets-overview.blade.php](resources/views/filament/pages/company-department-performance/tickets-overview.blade.php)، وقالب الكشف `statement-modal.blade.php` وصفحة لوحة الأداء وترجمتا `performance.php`. عند الرفع الفردي طابق قوالب لوحة الأداء المعدّلة أيضًا، وارفع [operational-rates.blade.php](resources/views/components/performance/operational-rates.blade.php) مع مكوّنات البطاقات والحلقات.
- **تقرير المشرف والمناوبات:** [SupervisorPerformance.php](app/Filament/Pages/SupervisorPerformance.php)، [SupervisorPerformanceService.php](app/Services/SupervisorPerformanceService.php)، [قالب التقرير](resources/views/filament/pages/supervisor-performance.blade.php)، [SupervisionShiftsTable.php](app/Filament/Resources/SupervisionShifts/Tables/SupervisionShiftsTable.php)، وترجمتا `supervisor_performance.php` [عربي](lang/ar/supervisor_performance.php) و[إنجليزي](lang/en/supervisor_performance.php). التقرير يتطلب `view_supervisor_performance_report` وصلاحية الوصول إلى المنشآت المعنية.
- **دليل الأداء داخل النظام:** [SystemDocumentationPage.php](app/Filament/Pages/SystemDocumentationPage.php)، [performance-guide.blade.php](resources/views/filament/pages/documentation/reports/performance-guide.blade.php)، [reports-guide.blade.php](resources/views/filament/pages/documentation/reports/reports-guide.blade.php)، وترجمتا `documentation.php` [عربي](lang/ar/documentation.php) و[إنجليزي](lang/en/documentation.php). لو الدفعة تشمل فيديو الشرح، تحقق من وجود `performance.mp4` في مسار الفيديو على الاستضافة؛ الملف غير موجود ضمن ملفات Git لهذه الدفعة.

### تغييرات 18 نوفمبر — تُرفع عند اعتماد دفعتها

- **إتاحة تقارير الأداء على الإنتاج:** ارفع [زر الأداء](app/Filament/Actions/EmployeePerformanceAction.php)، تعريفَي مورد [السعودي](app/Filament/Resources/SaudiEmployees/SaudiEmployeeResource.php) و[الأجنبي](app/Filament/Resources/ForeignEmployees/ForeignEmployeeResource.php)، صفحتَي أداء [السعودي](app/Filament/Resources/SaudiEmployees/Pages/EmployeePerformance.php) و[الأجنبي](app/Filament/Resources/ForeignEmployees/Pages/EmployeePerformance.php)، و[خدمة تقرير المشرف](app/Services/SupervisorPerformanceService.php). بعد رفع الملفات امسح الكاش واختبر الإتاحة بحساب مخوّل. هذا التغيير يكفي له رفع الملفات ومسح الكاش.
- **مراجعة بنود التكليف:** ارفع [الترحيل](database/migrations/2026_11_18_151000_add_task_item_review_workflow.php)، [TaskStatus](app/Enums/TaskStatus.php)، [TaskAssignment](app/Models/TaskAssignment.php)، [TaskAssignmentItem](app/Models/TaskAssignmentItem.php)، [TaskAssignmentItemReview](app/Models/TaskAssignmentItemReview.php)، [TaskAssignmentItemObserver](app/Observers/TaskAssignmentItemObserver.php)، و[TaskAssignmentItemReviewService](app/Services/TaskAssignmentItemReviewService.php). معها مورد التكليفات، Trait وقوالب أداء المهام، خدمات التجميع والدرجة وتقرير المشرف المعدّلة، وترجمتا `tasks.php` وصفحات الأداء المتأثرة. الصلاحيتان الجديدتان `review_task_assignment` و`extend_task_assignment_due_date` في [سيدر الأدوار](database/seeders/RolesAndPermissionsSeeder.php). [دليل المهام](docs/modules/TASKS.md)
- **لوحة الأداء حسب القسم:** صفحة `CompanyDepartmentPerformance` والخدمتان `CompanyDepartmentPerformanceService` و`PerformanceScoreService`، قوالب اللوحة ومنها [departments-overview.blade.php](resources/views/filament/pages/company-department-performance/departments-overview.blade.php)، وترجمتا `performance.php`. النسخة الحالية تتيح اللوحة للسوبر أدمن؛ احسب ده ضمن قرار الرفع.
- **صندوق الأصول:** [مورد الصناديق](app/Filament/Resources/AssetFunds/)، [مورد السندات](app/Filament/Resources/AssetFundVouchers/)، [صفحة الكشف](app/Filament/Pages/AssetFundStatement.php) و[الكنترولر](app/Http/Controllers/AssetFundStatementController.php)، موديلات [الصندوق](app/Models/AssetFund.php) و[السند](app/Models/AssetFundVoucher.php)، [Observer السند](app/Observers/AssetFundVoucherObserver.php)، سياستا [الصندوق](app/Policies/AssetFundPolicy.php) و[السند](app/Policies/AssetFundVoucherPolicy.php)، وترجمتا `asset_funds.php` [عربي](lang/ar/asset_funds.php) و[إنجليزي](lang/en/asset_funds.php). ارفع الـObserver قبل الموديل الذي يسجّله.
- **الهوية والإقامة وصفة الموظف والمالك:** [EmployeeIdentityService.php](app/Services/EmployeeIdentityService.php) مع صفحات الحفظ المعدّلة في موارد السعودي والأجنبي والمؤقت، و[PersonCapacityService.php](app/Services/PersonCapacityService.php) مع قائمتَي الموظفين والملاك عند الحاجة. قيد تفرد قاعدة البيانات ومراجعة التكرارات القديمة ما زالا معلّقين. [دليل الموظفين](docs/modules/EMPLOYEES.md)

لتحديد الملفات المحلية للدفعات، استخدم `git diff --name-only 99bf18d..HEAD` للتغييرات بعد آخر تحديث للقائمة، و`git diff --cached --name-only` لتغييرات الـindex وقت المراجعة. حالة Git المحلية وحدها لا تحدد نسخة السيرفر.

## استبعادات الإنتاج

- `routes/test.php`، `tests/`، و`resources/views/system/seed-performance-demo.blade.php`.
- سيدرز الديمو `OperationsDemoSeeder` و`PerformanceDashboardDemoSeeder` و`PerformanceEscalationDemoSeeder` و`DepartmentPerformanceDemoSeeder` و`SupervisorPerformanceDemoSeeder` و`TicketsDemoSeeder`.

## أوامر السيرفر حسب ترتيب التنفيذ

**بعد اكتمال الملفات وفي وضع الصيانة:**

```bash
php artisan migrate:status
php artisan migrate
```

شغّل `migrate` من جلسة تفاعلية فقط بعد تطابق قائمة `Pending` مع الدفعة. لو مفيش ترحيلات مطلوبة، اكتفِ بفحص الحالة.

**بعد الترحيل وعند اعتماد تغييرات الصلاحيات أو الكتالوج:**

```bash
php artisan db:seed --class=RolesAndPermissionsSeeder
php artisan db:seed --class=ServicesCatalogSeeder
```

- شغّل كل سيدر حسب الدفعة. الأول يعيد ضبط صلاحيات الأدوار المذكورة فيه؛ راجع أي تخصيصات إنتاجية قبل تشغيله. الثاني يحدّث سجلات الكتالوج المطابقة، فشغّله فقط لو تحديث الكتالوج جزء من الدفعة.
- لو تفعيل فلو الخدمات معتمد، راجع مزامنة صلاحيات العمليات عبر `OperationsPermissionService::syncPermissions()` ضمن إجراء الصلاحيات المعتمد. دي خطوة منفصلة عن سيدر الأدوار.

**امسح الكاش وافحص قبل إعادة فتح الخدمة:**

```bash
php artisan optimize:clear
php artisan migrate:status
php artisan route:list
```

عند اعتماد جدولة التصعيدات، راجع `php artisan schedule:list` خلال الصيانة. بعد نجاح الفحص شغّل `php artisan up`، وبعد اختبار الشاشات أعد تشغيل العمال والمجدول. `php artisan performance:check-escalations` تشغيل يدوي فعلي يكتب حالات وقد يرسل إشعارات؛ استخدمه عند اعتماد تشغيل التصعيدات فقط.

لو ظهر `Cannot declare class X` مباشرةً بعد الرفع، شغّل `php artisan optimize:clear` ثم أعد تشغيل PHP من cPanel لو استمرت المشكلة. [تعليمات الاستضافة](AGENTS.md#36-deployment-notes-shared-hosting-server)
