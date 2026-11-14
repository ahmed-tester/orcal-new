# 📁 قائمة الرفع الذكية لموديول «الأداء» و«المعاملات والخدمات» (Production)

> 💡 **فكرة هذه القائمة:**  
> تم حصر التعديلات والكود الخاص بـ **الأداء** و**المعاملات والخدمات والتدفق المالي** وتجميعها بذكاء:
> - **المجلدات ذات التعديلات الكثيفة:** تم جمعها في **6 مجلدات رئيسية** تُرفع كاملة دفعة واحدة.
> - **المجلدات المشتركة الحساسة:** تم استخراج الملفات الخاصة بالتعديلات فقط (مثل 5 ملفات مايجريشن فقط، و13 موديلاً فقط) لتجنب رفع المجلد كاملاً.
> 
> *(في VS Code، اضغط `Ctrl + Click` على أي مجلد أو ملف لفتحه مباشرة)*

---

## أولاً: المجلدات الكاملة (ارفع المجلد كاملاً دفعة واحدة) 📦

1. 📂 **المعاملات والخدمات (مورد المعاملات بكل صفحاته ونماذجه وجداوله ومؤشراته):**
   - 👉 [app/Filament/Resources/ServiceTransactions/](app/Filament/Resources/ServiceTransactions/)

2. 📂 **واجهات وقوالب المعاملات (لوحة العمليات والملخصات والمؤشرات المالية):**
   - 👉 [resources/views/filament/resources/service-transactions/](resources/views/filament/resources/service-transactions/)

3. 📂 **النوافذ المنبثقة التفاعلية (باركود التسليم، QR الكاش، النواقص، وسجل التدقيق):**
   - 👉 [resources/views/filament/modals/](resources/views/filament/modals/)

4. 📂 **التكليفات والمهام (المورد كاملاً مع صفحة وجدول ملخص أداء التكليفات):**
   - 👉 [app/Filament/Resources/TaskAssignments/](app/Filament/Resources/TaskAssignments/)

5. 📂 **مجلد الخدمات بالكامل (يشمل كافة كلاسات خدمات الأداء والمعاملات والتدفق المالي):**
   - 👉 [app/Services/](app/Services/)

6. 📂 **مجلدات الترجمة الكاملة (عربي + إنجليزي لضمان كافة المفاتيح الجديدة):**
   - 👉 [lang/ar/](lang/ar/)
   - 👉 [lang/en/](lang/en/)

---

## ثانياً: الملفات الفردية المستقلة (لا ترفع مجلداتها بالكامل) 📄

### 1. شاشات وواجهات «الأداء» الفردية:
- 📄 [app/Filament/Pages/CompanyDepartmentPerformance.php](app/Filament/Pages/CompanyDepartmentPerformance.php) *(شاشة أداء الشركة والأقسام)*
- 📄 [resources/views/filament/pages/company-department-performance/operations-overview.blade.php](resources/views/filament/pages/company-department-performance/operations-overview.blade.php) *(ملخص التشغيل)*
- 📄 [resources/views/filament/pages/company-department-performance/workforce-overview.blade.php](resources/views/filament/pages/company-department-performance/workforce-overview.blade.php) *(ملخص الحضور والإجازات)*
- 📄 [resources/views/filament/pages/company-department-performance.blade.php](resources/views/filament/pages/company-department-performance.blade.php) *(قالب أداء الشركة والأقسام)*
- 📄 [resources/views/filament/pages/company-department-performance/companies.blade.php](resources/views/filament/pages/company-department-performance/companies.blade.php) *(بطاقات أداء المنشآت - المستوى الأول)*
- 📄 [resources/views/filament/pages/company-department-performance/departments.blade.php](resources/views/filament/pages/company-department-performance/departments.blade.php) *(بطاقات أداء الأقسام - المستوى الثاني)*
- 📄 [resources/views/filament/pages/company-department-performance/employees.blade.php](resources/views/filament/pages/company-department-performance/employees.blade.php) *(بطاقات أداء الموظفين - المستوى الثالث)*
- 📄 [resources/views/filament/pages/company-department-performance/statement-modal.blade.php](resources/views/filament/pages/company-department-performance/statement-modal.blade.php) *(نافذة كشف المعاملات والمهام المنبثقة - Modal وسط الشاشة بدلاً من السحب الجانبي)*
- 📄 [resources/views/components/performance/rate-ring.blade.php](resources/views/components/performance/rate-ring.blade.php) *(مكون الحلقات الدائرية لمؤشرات الإنجاز)*
- 📄 [resources/views/components/performance/metric-card.blade.php](resources/views/components/performance/metric-card.blade.php) *(مكون بطاقات المؤشرات الرقمية)*
- 📄 [app/Filament/Pages/MyTasks.php](app/Filament/Pages/MyTasks.php) *(شاشة مهام وتكليفات الموظف)*
- 📄 [app/Filament/Pages/ViewEmployeeProfile.php](app/Filament/Pages/ViewEmployeeProfile.php) *(تكامل الأداء بالملف التعريفي للموظف)*
- 📄 [app/Filament/Concerns/HasTaskPerformanceDashboard.php](app/Filament/Concerns/HasTaskPerformanceDashboard.php) *(Trait لوحة أداء المهام)*
- 📄 [resources/views/filament/tasks/performance.blade.php](resources/views/filament/tasks/performance.blade.php) *(ودجت وبطاقات أداء المهام)*
- 📄 [resources/views/filament/resources/task-assignments/pages/task-assignments-summary.blade.php](resources/views/filament/resources/task-assignments/pages/task-assignments-summary.blade.php) *(واجهة ملخص التكليفات - Modal كشف تفاصيل التكليفات)*
- 📄 [app/Filament/Resources/SaudiEmployees/Pages/EmployeePerformance.php](app/Filament/Resources/SaudiEmployees/Pages/EmployeePerformance.php) *(صفحة أداء الموظف السعودي)*
- 📄 [app/Filament/Resources/ForeignEmployees/Pages/EmployeePerformance.php](app/Filament/Resources/ForeignEmployees/Pages/EmployeePerformance.php) *(صفحة أداء الموظف غير السعودي)*

### 2. أزرار وحركات «المعاملات وتسليم الكاش والصلاحيات»:
- 📄 [app/Filament/Components/CashHandoverActions.php](app/Filament/Components/CashHandoverActions.php) *(أزرار وحركات تسليم واستلام الكاش)*
- 📄 [app/Filament/Components/ServiceFinancialActions.php](app/Filament/Components/ServiceFinancialActions.php) *(أزرار الحركات المالية وسندات المعاملات)*
- 📄 [app/Filament/Components/UnifiedOperationsActions.php](app/Filament/Components/UnifiedOperationsActions.php) *(أزرار العمليات الموحدة)*
- 📄 [app/Filament/Resources/Roles/Schemas/RoleForm.php](app/Filament/Resources/Roles/Schemas/RoleForm.php) *(إضافة قسم صلاحيات العمليات والخدمات)*
- 📄 [app/Observers/ServiceTransactionObserver.php](app/Observers/ServiceTransactionObserver.php) *(مراقب سير المعاملات)*
- 📄 [app/Traits/HasFinancialWorkflow.php](app/Traits/HasFinancialWorkflow.php) *(Trait التدفق المالي)*
- 📄 [app/Policies/TreasuryTransactionPolicy.php](app/Policies/TreasuryTransactionPolicy.php) *(سياسات الصلاحيات المالية)*

### 3. قاعدة البيانات - المايجريشن (5 ملفات فقط - لا ترفع مجلد migrations كاملاً):
- 📄 [database/migrations/2026_11_08_100000_add_workflow_status_to_service_transactions_table.php](database/migrations/2026_11_08_100000_add_workflow_status_to_service_transactions_table.php) *(حالات سير المعاملات)*
- 📄 [database/migrations/2026_11_08_110000_create_unified_settlement_and_suspense_tables.php](database/migrations/2026_11_08_110000_create_unified_settlement_and_suspense_tables.php) *(جداول التسويات والمبالغ المعلقة)*
- 📄 [database/migrations/2026_11_08_120000_create_unified_deficiency_tasks_table.php](database/migrations/2026_11_08_120000_create_unified_deficiency_tasks_table.php) *(جدول مهام النواقص)*
- 📄 [database/migrations/2026_11_08_130000_create_unified_audit_logs_table.php](database/migrations/2026_11_08_130000_create_unified_audit_logs_table.php) *(جدول سجلات التتبع والتدقيق)*
- 📄 [database/migrations/2026_11_11_100000_create_financial_workflow_tables.php](database/migrations/2026_11_11_100000_create_financial_workflow_tables.php) *(جداول السندات المالية وتسليم الكاش)*

### 4. قاعدة البيانات - السيدرز (3 ملفات فقط - لا ترفع مجلد seeders كاملاً):
- 📄 [database/seeders/OperationsDemoSeeder.php](database/seeders/OperationsDemoSeeder.php) *(بيانات العمليات والتسليمات)*
- 📄 [database/seeders/ServicesCatalogSeeder.php](database/seeders/ServicesCatalogSeeder.php) *(كتالوج الخدمات الإلكترونية والحكومية)*
- 📄 [database/seeders/RolesAndPermissionsSeeder.php](database/seeders/RolesAndPermissionsSeeder.php) *(صلاحيات العمليات والخدمات والأداء)*

### 5. حالات وسير العمل (app/Enums/ - 7 ملفات فقط):
- 📄 [app/Enums/FinancialCashDirection.php](app/Enums/FinancialCashDirection.php) *(اتجاه النقد)*
- 📄 [app/Enums/FinancialDisbursementStatus.php](app/Enums/FinancialDisbursementStatus.php) *(حالات سندات الصرف)*
- 📄 [app/Enums/FinancialDisbursementType.php](app/Enums/FinancialDisbursementType.php) *(أنواع سندات الصرف)*
- 📄 [app/Enums/FinancialHandoverStatus.php](app/Enums/FinancialHandoverStatus.php) *(حالات تسليم الكاش)*
- 📄 [app/Enums/FinancialPaymentMethod.php](app/Enums/FinancialPaymentMethod.php) *(طرق الدفع)*
- 📄 [app/Enums/FinancialReceiptStatus.php](app/Enums/FinancialReceiptStatus.php) *(حالات سندات القبض)*
- 📄 [app/Enums/OperationWorkflowStatus.php](app/Enums/OperationWorkflowStatus.php) *(مراحل سير المعاملات)*

### 6. النماذج (app/Models/ - 13 موديلاً فقط - لا ترفع مجلد Models كاملاً):
- 📄 [app/Models/CashHandover.php](app/Models/CashHandover.php) *(تسليم النقدية والكاش)*
- 📄 [app/Models/CashHandoverItem.php](app/Models/CashHandoverItem.php) *(بنود تسليم الكاش)*
- 📄 [app/Models/FinancialDisbursement.php](app/Models/FinancialDisbursement.php) *(سندات الصرف)*
- 📄 [app/Models/FinancialDisbursementPayment.php](app/Models/FinancialDisbursementPayment.php) *(دفعات سند الصرف)*
- 📄 [app/Models/FinancialReceipt.php](app/Models/FinancialReceipt.php) *(سندات قبض رسوم الخدمات)*
- 📄 [app/Models/ServiceTransaction.php](app/Models/ServiceTransaction.php) *(موديل المعاملة والتدفق المالي)*
- 📄 [app/Models/UnifiedDeficiencyTask.php](app/Models/UnifiedDeficiencyTask.php) *(نواقص المعاملات والمهام المعلقة)*
- 📄 [app/Models/UnifiedSuspenseAmount.php](app/Models/UnifiedSuspenseAmount.php) *(المبالغ المعلقة وفوارق التسوية)*
- 📄 [app/Models/UnifiedAuditLog.php](app/Models/UnifiedAuditLog.php) *(سجل التدقيق والتتبع)*
- 📄 [app/Models/UnifiedBatchDeposit.php](app/Models/UnifiedBatchDeposit.php) *(حزم الإيداع البنكي)*
- 📄 [app/Models/UnifiedBatchDepositItem.php](app/Models/UnifiedBatchDepositItem.php) *(بنود حزم الإيداع)*
- 📄 [app/Models/Employee.php](app/Models/Employee.php) *(نطاقات الأداء وعلاقات المهام والخدمات)*
- 📄 [app/Models/TreasuryTransaction.php](app/Models/TreasuryTransaction.php) *(ربط حركة الخزينة بسندات الخدمات)*

### 7. المسارات (Routes):
- 📄 [routes/web.php](routes/web.php) *(مسارات الإنتاج)*
- 📄 [routes/test.php](routes/test.php) *(مسارات المزامنة والتجربة للمطورين)*

---

## 💡 ثالثاً: خيار الرفع الفردي لملفات الخدمات (في حال عدم رفع مجلد Services كاملاً)

إذا فضلت عدم رفع مجلد `app/Services/` بالكامل ورفع ملفات الأداء والخدمات فقط:
- 📄 [app/Services/CompanyDepartmentPerformanceService.php](app/Services/CompanyDepartmentPerformanceService.php)
- 📄 [app/Services/AttendanceSummaryService.php](app/Services/AttendanceSummaryService.php)
- 📄 [app/Services/LeaveSummaryService.php](app/Services/LeaveSummaryService.php)
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
- 📄 [app/Services/FinancialSummaryService.php](app/Services/FinancialSummaryService.php)
- 📄 [app/Services/FinancialTreasuryService.php](app/Services/FinancialTreasuryService.php)
- 📄 [app/Services/BarcodeService.php](app/Services/BarcodeService.php)

---

### ⚙️ الأوامر بعد الانتهاء من الرفع على السيرفر:
```bash
php artisan migrate --force
php artisan db:seed --class=RolesAndPermissionsSeeder --force
php artisan db:seed --class=ServicesCatalogSeeder --force
php artisan optimize:clear
```
