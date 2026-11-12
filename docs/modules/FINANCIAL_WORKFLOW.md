# دليل الدورة المالية الموحدة (القبض، العهدة، التوريد، السداد)

- **آخر مطابقة:** 2026-11-11.
- **الخطة المعتمدة:** [الخطة العملية للدورة المالية](../plans/codex/ORCAL_PRACTICAL_FINANCIAL_WORKFLOW_PLAN_AR.md).
- **النطاق الحالي:** تفعيل الوحدة على معاملات الخدمات (`service_transactions`) كأول مصدر، مع تصميم جداول تسمح بإضافة العقود والأقساط والديون لاحقًا عبر `(source_type, source_id)`.
- **حالة النشر:** بدأت محاولة الإنتاج للـmigration المالية وتوقفت بخطأ MySQL 1059. بقيت `Pending` حسب `migrate:status`. رُقّع الملف محليًا، وإعادة التشغيل على الإنتاج غير مؤكدة.

## الغرض

دورة مالية واحدة تجمع: سند القبض ← عهدة المحصل ← وصل توريد النقد للخزينة ← سداد رسوم الجهة ← مطالبة سداد شخصي للموظف وتعويضها الجزئي. الأرقام تُحسب من المستندات المؤكدة فقط، ولا تُشتق من `paid_amount` القديم ولا تُنشئ حركة نقد وهمية.

## نقاط الدخول

| الطبقة | الملف |
| --- | --- |
| الجداول | [migration](../../database/migrations/2026_11_11_100000_create_financial_workflow_tables.php) |
| الموديلات | [FinancialReceipt](../../app/Models/FinancialReceipt.php)، [CashHandover](../../app/Models/CashHandover.php)، [CashHandoverItem](../../app/Models/CashHandoverItem.php)، [FinancialDisbursement](../../app/Models/FinancialDisbursement.php)، [FinancialDisbursementPayment](../../app/Models/FinancialDisbursementPayment.php) |
| القبض | [FinancialReceiptService](../../app/Services/FinancialReceiptService.php) |
| التوريد | [CashHandoverService](../../app/Services/CashHandoverService.php) |
| السداد والتعويض | [FinancialDisbursementService](../../app/Services/FinancialDisbursementService.php) |
| الخزنة | [FinancialTreasuryService](../../app/Services/FinancialTreasuryService.php) |
| الأرقام | [FinancialSummaryService](../../app/Services/FinancialSummaryService.php) |
| التفعيل والصلاحيات | [ServiceFinancialWorkflowService](../../app/Services/ServiceFinancialWorkflowService.php) |
| المطابقة | [OperationsReconciliationService](../../app/Services/OperationsReconciliationService.php) |
| واجهة العمليات | [ServiceFinancialActions](../../app/Filament/Components/ServiceFinancialActions.php) |
| واجهة الخزنة | [CashHandoverActions](../../app/Filament/Components/CashHandoverActions.php) |
| الملخص | [financial-summary.blade.php](../../resources/views/filament/resources/service-transactions/financial-summary.blade.php) |
| الاختبارات | [FinancialWorkflowTest](../../tests/Feature/FinancialWorkflowTest.php) |

## قواعد العمل

1. **التفعيل:** تُنشأ لقطة مالية في `ServiceTransaction.metadata` عند إنشاء المعاملة (`financial_workflow_version = 1`). المعاملات التي تحمل `metadata.financial_flow = legacy` تبقى على المسار القديم. لا يُقرأ `paid_amount` كمصدر حقيقة. استلام العهدة القديم بالماسح (`TreasuryCustodyService::receiveCustody`) يرفض أي معاملة مفعّلة ويوجّه لوصل التوريد الجديد.
2. **اللقطة المالية:** `client_due_amount`، `expected_fee_amount` (أو `null` إذا لم تُعتمد قاعدة الرسوم)، `fee_beneficiary_reference`، واعتماد الشروط. حصة المكتب لا تُحصّل من العميل.
3. **سند القبض:** عند تسجيل قبض نقدي بصلاحية `record_service_receipt`، يُحفظ السند مؤكدًا ويُحتسب فورًا من رصيد العميل وعهدة على المحصل المحدد. إذا اختير طلب التوريد مع إنشاء المعاملة، يُنشأ الوصل في الحفظ نفسه. تُسجل حركة الخزنة عند استلام التوريد بصلاحية `confirm_cash_handover`. القبض غير النقدي يتطلب صلاحية `confirm_service_receipt` والوجهة والمرجع والإثبات عند التأكيد. السندات النقدية المعلقة قبل التعديل يمكن تأكيدها بالإجراء الحالي؛ لا يوجد تحديث جماعي لها. تقبل خدمة التسجيل طريقة الدفع كحالة `FinancialPaymentMethod` أو قيمتها النصية، وتُحسب بصمة `request_key` من القيمة النصية.
4. **العهدة:** لكل سند قبض نقدي مؤكد: المبلغ − المحجوز في طلبات توريد قائمة − المؤكد توريده. لا يتجاوز طلب التوريد المتاح، ويُقفل السند أثناء الطلب.
   في نافذة طلب التوريد، يُعرض اسم المحصل من سند القبض المختار للقراءة فقط، ويُسجل المحصل بصفته الموظف المسلّم لهذا الطلب. يمكن تعديل مبلغ الطلب حتى حد العهدة المتاحة. عند رفض الطلب بسبب المبلغ، تعرض النافذة سبب الرفض والحد المتاح وتبقى مفتوحة لتصحيح القيمة.
5. **التوريد:** يُؤكد بمعرّف `confirmation_key` ومبالغ مؤكدة لكل بند، وينشئ **حركة خزنة واردة واحدة**. النقص يسجل `difference_amount` سالبًا. الزيادة تتطلب محضرًا وتُنشئ مبلغًا معلقًا في `unified_suspense_amounts` ولا تُعتبر ربحًا.
6. **السداد:** تقبل خدمة السداد نوع `FinancialDisbursementType` من النموذج أو قيمته النصية. `treasury_direct` يقيّد خزنة صادرة فورًا. `employee_personal` يبقى `pending_approval` ثم `approved` ثم يُعوّض على دفعات. كل دفعة تعويض لها `request_key` وحركة خزنة، ولا تُحسب مرة ثانية في تكلفة الرسوم.
   عند رفض تسجيل السداد تعرض النافذة رسالة التحقق، مثل تجاوز الرسوم المتبقية أو المبلغ المموّل، وتبقى مفتوحة لتصحيح البيانات.
7. **حدود الرسوم:** لا سداد بدون قاعدة رسوم معتمدة (`expected_fee_amount !== null`). المبلغ ≤ المتبقي من الاستحقاق، و`treasury_direct` ≤ النقد الممول المتاح بعد حجز الفائض ومستحق الموظف.
8. **العكس:** لا حذف ولا تعديل صامت. العكس حركة معاكسة معتمدة وربط بالأصل، ويُستثنى الأصل من الحسابات.
9. **الخزنة:** [FinancialTreasuryService](../../app/Services/FinancialTreasuryService.php) هو الكاتب الوحيد لحركات المسار المالي؛ يقفل صف الشركة وتسلسل `balance_after`، ويرفض الصرف الذي يجعل الرصيد سالبًا، ويمنع ربط حركة واحدة بأكثر من مستند.
10. **المطابقة:** تقرأ الأرقام من الملخص المالي فقط، وتستخدم `task_key` ثابتًا (`svc-fin:{id}:{type}`) لمنع تكرار مهام النواقص.

## الصلاحيات

أُضيفت إلى [OperationsPermissionService](../../app/Services/OperationsPermissionService.php) مع توزيع افتراضي على الأدوار:

`record_service_receipt`، `confirm_service_receipt`، `request_cash_handover`، `confirm_cash_handover`، `record_service_disbursement`، `approve_self_paid_disbursement`، `pay_employee_reimbursement`، `reverse_financial_transaction`.

تظهر صلاحيات العمليات في **الأدوار والصلاحيات ← المالية العامة والخدمات** ويمكن اختيارها لكل دور عند الإنشاء أو التعديل. الخدمات تتحقق من الصلاحية + الشركة المصرح بها + الفرع عند وجود قيد، ولا تعتبر قائمة الشركات الفارغة وصولًا مفتوحًا.

## الترجمة والواجهة

- كل النصوص عبر `lang/ar/financial.php` و`lang/en/financial.php` (مفاتيح متطابقة)، ونصوص الإشعارات عبر `services_tracking.notifications`.
- إجراءات Filament: تسجيل قبض، طلب توريد، تسجيل سداد، اعتماد مطالبة، صرف تعويض في جدول معاملات الخدمات، واستلام أو رفض التوريد في شاشة الخزنة. في نافذة الاستلام، يمسح المستخدم QR الوصل ثم يدخل كود التوقيع الخاص به. يظهر خطأ الرمز عند الإرسال، وتظهر حالة الاستلام من دون كود المعاملة القديم في الدورة الجديدة. إشعار طلب التوريد الجديد يعرض المبلغ من دون رقم الوصل.
- يُحمّل الوصل بكتابة رقمه الظاهر أو بمسح رمز QR. بعد تحميله، يظهر حقل الوصل والتوقيع جنبًا إلى جنب عندما يتسع العرض. عند إدخال مبلغ مستلم أعلى من المطلوب، يظهر الفائض المحسوب ومحضر العد المطلوب تلقائيًا. عند التأكيد تُقرأ مبالغ الوصل من قاعدة البيانات لتحديد ما يُنسب إلى كل بند وما يُسجل كفائض معلّق.
- يأخذ نموذج إنشاء المعاملة الداخلية السعر من دليل الخدمات عند الحفظ. يُعرض إجمالي المعاملة المحفوظة للقراءة فقط. إدخال الإجمالي اليدوي متاح عند إنشاء معاملة خارجية.
- تبويب «الملخص المالي» في نموذج المعاملة يعرض الأرقام للقراءة فقط.
- زر «المؤشرات المالية للخدمات» في قائمة الخدمات يفتح [صفحة التقرير](../../app/Filament/Resources/ServiceTransactions/Pages/FinancialIndicators.php) في التبويب نفسه. تبدأ الصفحة بكل معاملات الدورة المالية ضمن نطاق المؤسسة والفرع المصرّح به، وتوفر فلاتر مستقلة للمؤسسة والخدمة وتاريخ المعاملة، وإجماليات مع تفصيل حسب المؤسسة والخدمة والأدوار. تُطبّق صلاحية عرض قائمة الخدمات أيضًا على رابط التقرير المباشر. التغيير محلي بتاريخ 2026-11-11.
- تظهر الصفحة بحالة تحميل، ثم يجلب Livewire خيارات الفلاتر والمؤشرات بعد أول عرض. تُعاد قراءة التقرير عند الضغط على «تطبيق الفلاتر»، وخيارات المؤسسة والخدمة تستند إلى نطاق المعاملات المصرح بها. يحمل استعلام المؤشرات الأعمدة التي تحتاجها الحسابات، ويقصر علاقات القبض والتوريد على المؤكد. قياس زمن التحميل على MySQL ببيانات السيرفر بانتظار التحقق.

## الاختبارات والتحقق

- [FinancialWorkflowTest](../../tests/Feature/FinancialWorkflowTest.php): 13 اختبارًا / 43 تحققًا تغطي عزل العهدة بين معاملتين، تكرار التأكيد، تجاوز الحجز، التوريد الموزع، النقص والزيادة، السداد غير الممول، توزيعين على نفس المخصص، اعتماد السداد الشخصي والتعويض الجزئي، حراسة `request_key`، الصلاحيات، العكس المعتمد، وغياب قاعدة الرسوم.
- `php artisan test` نجح 52 اختبارًا وفشل [ExampleTest](../../tests/Feature/ExampleTest.php) فقط (تحويل `/` إلى تسجيل الدخول) وهو سلوك قائم سابق.
- شُغّل `php artisan view:clear` و`vendor/bin/pint` على الملفات المعدلة و`php -l` على ملفات PHP.
- الاختبارات تعمل على SQLite بملف مؤقت مع migrations كاملة، وليس MySQL؛ لم يُختبر التزامن الحقيقي على MySQL.

## المتطلبات والحدود

- **استئناف نشر 2026-11-11:** تستخدم [الـmigration المالية](../../database/migrations/2026_11_11_100000_create_financial_workflow_tables.php) اسم المفتاح الصريح `fin_disb_pay_disb_fk`. بعد نسخة احتياطية ومع إيقاف الكتابة، تحقّق من أن الـmigration لا تزال `Pending` وأن الجداول الخمسة التي تخصها مفقودة أو خالية تمامًا. احذف آثار المحاولة الأولى فقط بدءًا بالجداول التابعة، ثم ارفع الملف المعدّل وشغّل `php artisan migrate --path=database/migrations/2026_11_11_100000_create_financial_workflow_tables.php`. أوقف الإجراء إذا ظهر أي سجل أو إذا كانت الـmigration مسجلة. أمر `migrate:rollback` سيستهدف دفعة ناجحة سابقة.

- **قبل النشر:** `php artisan migrate` (جداول جديدة + عمود `task_key`)، ثم `php artisan optimize:clear`، ثم مزامنة الصلاحيات عبر `OperationsPermissionService`. الملفات الجديدة (Observers/Services/Filament) تتطلب `optimize:clear` بعد الرفع.
- **البيانات القديمة:** لا تُرحّل تلقائيًا. المعاملات القديمة تبقى على المسار القديم حتى قرار منفصل.
- **خارج النطاق:** إيداع البنك، تعميم الدورة على العقود/الأقساط/الديون، وربط حركة خزنة موجودة من الواجهة (الخدمة موجودة: `linkExisting`).
- **ملاحظة مسئول التوريد:** الإشعارات تستخدم سياسة `update` على المعاملة؛ توجيه الإشعار لدور الخزنة تحديدًا يحتاج تحسينًا لاحقًا.
