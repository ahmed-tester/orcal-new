# 📁 قائمة الرفع الذكية لموديول «الأداء» و«المعاملات والخدمات» (Production)

> 💡 **فكرة هذه القائمة:**  
> تم حصر التعديلات والكود الخاص بـ **الأداء** و**تصعيدات الأداء ومتابعتها** و**المعاملات والخدمات والتدفق المالي** وتجميعها بذكاء:
> - **المجلدات ذات التعديلات الكثيفة:** تم جمعها في **مجلدات رئيسية** تُرفع كاملة دفعة واحدة لضمان عدم نسيان أي ملف فرعي أو واجهة.
> - **المجلدات المشتركة الحساسة:** تم استخراج الملفات الخاصة بالتعديلات فقط (مثل 6 ملفات مايجريشن فقط، و14 موديلاً فقط) لتجنب رفع المجلد كاملاً بدون داعٍ.
> 
> *(في VS Code، اضغط `Ctrl + Click` على أي مجلد أو ملف لفتحه مباشرة)*

---

## أولاً: المجلدات الكاملة (ارفع المجلد كاملاً دفعة واحدة) 📦

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
- 📄 [resources/views/system/seed-performance-demo.blade.php](resources/views/system/seed-performance-demo.blade.php) *(واجهة زر زراعة البيانات التجريبية للأداء والتصعيدات)* 🌟 *(جديد)*
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

### 3. قاعدة البيانات - المايجريشن (6 ملفات فقط - لا ترفع مجلد migrations كاملاً):
- 📄 [database/migrations/2026_11_15_100000_create_performance_escalations_table.php](database/migrations/2026_11_15_100000_create_performance_escalations_table.php) *(جدول تصعيدات وملاحظات الأداء ومتابعتها)* 🌟 *(جديد)*
- 📄 [database/migrations/2026_11_08_100000_add_workflow_status_to_service_transactions_table.php](database/migrations/2026_11_08_100000_add_workflow_status_to_service_transactions_table.php) *(حالات سير المعاملات)*
- 📄 [database/migrations/2026_11_08_110000_create_unified_settlement_and_suspense_tables.php](database/migrations/2026_11_08_110000_create_unified_settlement_and_suspense_tables.php) *(جداول التسويات والمبالغ المعلقة)*
- 📄 [database/migrations/2026_11_08_120000_create_unified_deficiency_tasks_table.php](database/migrations/2026_11_08_120000_create_unified_deficiency_tasks_table.php) *(جدول مهام النواقص)*
- 📄 [database/migrations/2026_11_08_130000_create_unified_audit_logs_table.php](database/migrations/2026_11_08_130000_create_unified_audit_logs_table.php) *(جدول سجلات التتبع والتدقيق)*
- 📄 [database/migrations/2026_11_11_100000_create_financial_workflow_tables.php](database/migrations/2026_11_11_100000_create_financial_workflow_tables.php) *(جداول السندات المالية وتسليم الكاش)*

### 4. قاعدة البيانات - السيدرز (5 ملفات فقط - لا ترفع مجلد seeders كاملاً):
- 📄 [database/seeders/PerformanceEscalationDemoSeeder.php](database/seeders/PerformanceEscalationDemoSeeder.php) *(سيدر بيانات تجريبية لتصعيدات الأداء)* 🌟 *(جديد)*
- 📄 [database/seeders/PerformanceDashboardDemoSeeder.php](database/seeders/PerformanceDashboardDemoSeeder.php) *(سيدر بيانات تجريبية متكاملة للوحة الأداء)* 🌟 *(جديد)*
- 📄 [database/seeders/RolesAndPermissionsSeeder.php](database/seeders/RolesAndPermissionsSeeder.php) *(صلاحيات العمليات والخدمات وتصعيدات الأداء)* 🌟 *(مُحدث)*
- 📄 [database/seeders/OperationsDemoSeeder.php](database/seeders/OperationsDemoSeeder.php) *(بيانات العمليات والتسليمات)*
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
- 📄 [routes/console.php](routes/console.php) *(جدولة أمر الفحص اليومي لتصعيدات الأداء `app:check-performance-escalations`)* 🌟 *(جديد)*
- 📄 [routes/web.php](routes/web.php) *(مسارات الإنتاج)*
- 📄 [routes/test.php](routes/test.php) *(مسارات المزامنة والتجربة للمطورين وزر سيدر الأداء التجريبي)* 🌟 *(مُحدث)*

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

## ⚙️ الأوامر بعد الانتهاء من الرفع على السيرفر:
```bash
php artisan migrate --force
php artisan db:seed --class=RolesAndPermissionsSeeder --force
php artisan db:seed --class=ServicesCatalogSeeder --force
php artisan optimize:clear
```

### ⚡ أوامر فحص وتشغيل إضافية (اختيارية):
```bash
# لتشغيل فحص وتوليد تصعيدات الأداء لأول مرة يدوياً على السيرفر:
php artisan app:check-performance-escalations

# في حال ظهور رسالة "Cannot declare class X" على الاستضافة المشتركة (وفق دليل النظام AGENTS.md):
# قم بعمل Restart لنسخة PHP من لوحة التحكم cPanel لتفريغ كاش الـ OPcache للملفات والكلاسات الجديدة.
```
