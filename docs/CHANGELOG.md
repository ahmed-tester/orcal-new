# سجل التغييرات المهمة

المرجع للسلوك الحالي هو [دليل الوحدة](INDEX.md)، وهذا الملف يسجل سبب التغييرات والتحقق وحالة النشر. بدأ السجل في **2026-11-10**؛ ليس تاريخًا كاملًا للمشروع. إدخالات الحضور السابقة جُمعت من هذه المحادثة وطوبقت مع الملفات المتاحة، دون اختلاق أوقات أو commits.

أضف إدخال المهمة الجديدة أعلى السجل: التاريخ والمعرف، المشكلة والسلوك الناتج، الملفات المهمة، التحقق الذي نُفذ فعلًا، حالة النشر، والمتبقي. لا تضع بيانات دخول أو محتوى سجلات إنتاج حساسًا. التغييرات الشكلية البسيطة يكفي ذكرها بإيجاز عند ارتباطها بالمهمة.

## 2026-11-12 — FIN-005: تثبيت إجمالي سعر الخدمة

- يأخذ نموذج إنشاء المعاملة الداخلية السعر من دليل الخدمات عند الحفظ. يعرض نموذج التعديل الإجمالي المحفوظ للقراءة فقط ويتجاهل أي مبلغ إجمالي مُرسل مع تعديل المعاملة. يُدخل السعر يدويًا عند إنشاء معاملة خارجية.
- أُضيفت تغطية للحالات في `FinancialWorkflowTest`. تجربة واجهة الإنشاء والتعديل والنشر بانتظار المستخدم.

## 2026-11-12 — AUTH-002: إتاحة صلاحيات العمليات في شاشة الأدوار

- يعرض قسم «المالية العامة والخدمات» في [شاشة الأدوار](../app/Filament/Resources/Roles/Schemas/RoleForm.php) صلاحيات العمليات الـ14 من [خدمة الصلاحيات](../app/Services/OperationsPermissionService.php) بأسماء مترجمة بالعربية والإنجليزية. يمكن منحها أو إلغاؤها من الدور، وتظهر الصلاحيات الممنوحة سابقًا محددة عند التعديل.
- نجح `php -l` وPint وفحص الخيارات باللغتين. تحقق إنشاء دور تجريبي وقراءة الصلاحيات عند فتحه وحفظها وإلغاء اختيارها عبر إجراءات الصفحة على SQLite في الذاكرة. النشر وتجربة الشاشة على السيرفر بانتظار التحقق.

## 2026-11-12 — FIN-004: قبول نوع السداد القادم من نموذج Filament

- أوقف تحويل نوع السداد من Filament إلى نص إجراء تسجيل الرسوم في الإنتاج. تقبل [خدمة السداد](../app/Services/FinancialDisbursementService.php) الآن قيمة النوع من النموذج أو القيمة النصية. حُدّث [اختبار الدورة المالية](../tests/Feature/FinancialWorkflowTest.php) لتغطية قيمة النموذج في السداد المباشر والسداد الشخصي.
- نجح `php -l` وPint وفحص الفروقات. تعذّر بدء الاختبارات داخل العزل لأن PHPUnit لم يتمكن من قراءة ملف التحميل، ورفض المستخدم تشغيلها خارجه. النشر وتجربة النموذج على السيرفر بانتظار التحقق.
- تعرض نافذة السداد سبب رفض التسجيل وتبقى مفتوحة لتصحيح البيانات. نجح فحص PHP وPint للتعديل. أظهرت لقطة المستخدم رسالة تجاوز استحقاق الجهة عند إدخال 2,400 ريال، بينما المتبقي للجهة في المعاملة 2,000 ريال.

## 2026-11-12 — FIN-003: استلام النقد بمسح رمز الوصل

- تعرض نافذة استلام النقد حقل QR الوصل وتحمّل منه بنود الاستلام قبل إدخال التوقيع. يظهر تنبيه الرمز غير الصالح عند محاولة الإرسال. أُخفي كود المعاملة القديم في بطاقة العهدة للدورة المالية الجديدة، وأُزيل رقم الوصل من إشعارات طلب التوريد الجديدة.
- نجح فحص صياغة PHP وترجمة Blade ومسح كاش القوالب. تحقق فحص محلي من حقول نموذج الاستلام ومن عرض كود المعاملة في المسار القديم ومن نص الإشعار باللغتين. تجربة الماسح في المتصفح والنشر على السيرفر بانتظار التحقق.
- صُغّر عرض [نافذة الاستلام](../app/Filament/Components/CashHandoverActions.php) درجة واحدة. بعد المسح يظهر حقل التوقيع بجوار حقل QR عندما يتسع العرض. يفتح خيار الفائض حقل المبلغ، ويظهر المحضر ويصبح مطلوبًا للمبلغ الموجب. نجح فحص PHP وPint وفحص حالات ظهور الحقول وعدم إرسال الفائض عند إخفائه محليًا. مراجعة العرض في المتصفح بعد الرفع بانتظار التحقق.
- يقبل الحقل رقم الوصل الظاهر أو محتوى QR. نجح فحص PHP وPint، واختبار نموذج Filament على SQLite في الذاكرة أظهر التوقيع للصيغتين وأخفاه للوصل المؤكد أو المجهول. النشر بانتظار التحقق.
- يعرض نموذج الاستلام مبلغ الفائض ومحضر العد تلقائيًا عند زيادة المستلم عن المطلوب. قبل تسجيل الوارد يفصل المبلغ المنسوب للوصل عن الفائض وفق مبالغ الوصل المخزنة. تجربة الواجهة بانتظار المستخدم.
- يعرض [طلب التوريد](../app/Filament/Components/ServiceFinancialActions.php) اسم المحصل من سند القبض للقراءة فقط، وتحدد الخدمة المحصل نفسه بصفته المسلّم. نجح فحص PHP وPint. تجربة الشاشة بعد الرفع بانتظار المستخدم.
- تعرض نافذة طلب التوريد رسالة التحقق من الخدمة عند رفض الطلب، ومنها رسالة تجاوز المبلغ للعهدة المتاحة، وتبقى مفتوحة لتصحيح القيمة. نجح فحص PHP وPint محليًا. تجربة الواجهة بعد الرفع بانتظار المستخدم.

## 2026-11-12 — PERF-001: إصلاح ترجمة قالب أداء المنشآت والأقسام

- تكرر محليًا خطأ الإنتاج `unexpected token "endforeach"` عند ترجمة [قالب الصفحة](../resources/views/filament/pages/company-department-performance.blade.php). تحولت ست تعليمات `@php(...)` إلى كتل `@php … @endphp` حتى يترجم Blade تعليمات الحلقات والشروط المحيطة.
- نجح فحص PHP للقالب وللناتج الذي ولّده مترجم Blade الفعلي، ونجح `php artisan view:clear` محليًا. لم تبدأ اختبارات الخدمة لأن PHPUnit تعذر عليه قراءة ملف التحميل داخل العزل، ورُفض تشغيلها خارجه. إعادة فتح الصفحة بعد الرفع بانتظار التحقق.

## 2026-11-12 — AUTH-001: دليل ومصفوفة صلاحيات الموظفين والأدوار والرقابة الشاملة

- **النتيجة:** بعد مراجعة سيناريو الـ UAT (`ORCAL_SERVICES_AND_EMPLOYEE_PERFORMANCE_UAT_AR.md`)، تم إعداد دليل تشغيلي ورقابي تفصيلي متكامل ومطابق للكود المحلي يحدد بدقة متناهية صلاحيات ومسؤوليات كل موظف ودور في النظام:
  1. تفصيل الطبقات الثلاث الحاكمة: نوع الحساب (`UserType`)، نطاق عزل المنشآت والفروع (`CompanyAccessScopeService`)، وصلاحيات العمليات والسياسات (`Spatie Permission` + `Policies` + `OperationsPermissionService`).
  2. تغطية تفصيلية لـ 10 أدوار وظيفية تشمل: الموظف المنفذ، أمين الخزنة، مدير العمليات، مشرف النوبة الميداني، مدير الموارد البشرية، مسؤول الحضور، مدير الفرع، مسؤول العقود والمقاولات، مالك المنشأة، والمدير العام/سوبر أدمن؛ مع تفصيل الشاشات المفتوحة، الصلاحيات الممنوحة (Can Do)، الممنوعات وخطوط الدفاع الرقابية (Cannot Do)، ومسارات التسليم والاستلام بين الأدوار.
  3. مصفوفة الصلاحيات المتقاطعة (Cross-Functional Matrix) لجميع الصلاحيات القياسية والعمليات الخاصة الـ 14.
  4. قواعد الفصل الرقابي الصارم بين المهام (Segregation of Duties - SoD) لمنع التلاعب وتضارب المصالح وإرشادات الضبط الفني ومزامنة الصلاحيات عبر الـ CLI.
- **الملفات:** [دليل مصفوفة الصلاحيات والحوكمة](plans/codex/ORCAL_ROLES_AND_PERMISSIONS_DETAILED_GUIDE_AR.md)، [فهرس المعرفة](INDEX.md).
- **التحقق:** مطابقة تامة مع `OperationsPermissionService.php`، `RolesAndPermissionsSeeder.php`، `TreasuryTransactionPolicy.php`، `ServiceTransactionPolicy.php`، `CompanyAccessScopeService.php`، و`EmployeePerformanceAccessService.php`.

## 2026-11-12 — TASK-001: المرحلة أ من لوحة الموظف والتكليفات

- **النتيجة:** نُفذت المرحلة أ من [خطة تسليم أداء الموظف](plans/codex/ORCAL_EMPLOYEE_PERFORMANCE_IMPLEMENTATION_HANDOFF_AR.md) في worktree منفصل على فرع `modify/feature/employee-performance` من commit `cc5d47e`:
  1. خدمة تجميع موحدة `app/Services/TaskAssignmentSummaryService.php` تحسب سطر كل موظف وإجماليات الفترة وكشف البنود من نفس الاستعلام، مع قواعد الفترة (الاستحقاق أو الإنجاز داخل الفترة، وإضافة المتأخر المفتوح حتى نهاية الفترة)، والدرجة الموزونة ونسبة الإنجاز والإنجاز في الموعد.
  2. صفحة «لوحتي» `/my-tasks` للموظف المرتبط بالحساب، وقالب أداء مشترك يعرض اسم الموظف ومنشأته وفرعه وقسمه، وبطاقات KPI تفتح كشفًا بنفس الفلاتر، وتصدير CSV.
  3. ملخص التكليفات `/task-assignments` بنمط ملخص الحضور/الديون، ونقل السجل التفصيلي إلى `/task-assignments/detailed`، وكشف موظف داخل نافذة جانبية مع رابط للتكليف حسب الصلاحية.
  4. صفحة «الأداء» داخل ملف الموظف السعودي والأجنبي، مع زر في الجدول وصفحة التعديل، وربطها بنفس خدمة التجميع.
  5. تطبيق `CompanyAccessScopeService` وصلاحيات العرض على الاستعلامات والإجماليات والكشوف، ورفض الموظف خارج النطاق أو من المورد غير المطابق بـ404.
- **الملفات:** `app/Services/TaskAssignmentSummaryService.php`، `app/Filament/Concerns/HasTaskPerformanceDashboard.php`، `app/Filament/Pages/MyTasks.php`، `app/Filament/Resources/TaskAssignments/Pages/TaskAssignmentsSummary.php`، `app/Filament/Resources/TaskAssignments/Pages/ListTaskAssignments.php`، `app/Filament/Resources/TaskAssignments/TaskAssignmentResource.php`، صفحتا `EmployeePerformance` لموردي السعوديين والأجانب، موارد الموظفين وجداولها وصفحات تعديلها، `resources/views/filament/resources/task-assignments/pages/task-assignments-summary.blade.php`، `resources/views/filament/tasks/performance.blade.php`، `lang/ar/tasks.php`، `lang/en/tasks.php`، `tests/Feature/TaskAssignmentSummaryServiceTest.php`، [دليل المهام والتكليفات](modules/TASKS.md).
- **التحقق الفعلي:** نجح `php -l` على كل ملفات PHP المتغيرة، و`vendor/bin/pint` عليها، و`php artisan view:clear`. نجح `TaskAssignmentSummaryServiceTest` بستة اختبارات و80 تحققًا على SQLite في الذاكرة (التجميع والفترة، موظف بلا تكليفات، متأخر/مؤجل/في الموعد، بند بلا موعد استحقاق، الدرجة الموزونة، عزل الفرع والشركة، رفض الكشف خارج النطاق، صفحة الملخص والنافذة وCSV، «لوحتي»، وصفحة الأداء). عند تشغيل كامل الاختبارات نجح 70 اختبارًا وفشلت ثلاثة اختبارات قائمة لا علاقة لها بالمهمة: `ExampleTest` (تحويل `/` إلى 302) واختباران في `DebtSummaryTest` بسبب عدم إنشاء جدول `branches` في مخططه.
- **حالة النشر:** منفذ محليًا فقط في worktree غير مدفوع؛ لم يُدمج ولم يُنشر. لا migrations جديدة. عند الرفع اليدوي تُنقل الملفات من الـworktree ثم يُشغّل `php artisan optimize:clear` على الخادم، ويُتحقق من `/my-tasks` و`/task-assignments` و`/task-assignments/detailed` ومساري الأداء.
- **المتبقي:** الملاحظات الأسبوعية (البند 6) مؤجلة، والمرحلتان ب وج تنتظران دمج عمل الموديل الآخر في الدورة المالية، والمرحلة د تحتاج قرارًا إداريًا على تعريف المؤشرات. لم يُفحص التغيير في متصفح يدوي ولم يُختبر MySQL.

## 2026-11-11 — DJ-001: ملخص اليوميات حسب الفرع

- **النتيجة:** تم بناء ملخص اليوميات (قيود المبيعات والمشتريات) بمطابقة تامة لنمط ملخص الديون:
  1. أصبحت `/daily-journals` هي صفحة الملخص `DailyJournalsSummary` مع 5 بطاقات KPI (عدد القيود، عدد الفروع، إجمالي المبيعات، إجمالي المشتريات، الصافي مع شارة فائض/عجز من `net_status`)، وشريط الفلاتر الموحد (شركة/فرع متعدد الخيارات + تواريخ + بحث + نوع اليومية مختصرة/مفصلة)، وتصدير CSV، وطباعة الملخص.
  2. تحويل السجل الفردي إلى القائمة التفصيلية `/daily-journals/detailed` (`ListDailyJournals`) مع زر عودة للملخص.
  3. التجميع في `DailyJournalSummaryService` حسب `company_id + branch_id` (قيود بلا فرع تُجمع كمجموعة مستقلة) عبر `MIN(id)` مع `COUNT/SUM(sales)/SUM(purchases)/SUM(net_amount)`، ونطاق الوصول عبر `CompanyAccessScopeService::apply()`.
  4. كشف قيود فرع تفاعلي (Slide-over) مع تفاصيل التوزيع للقيود المفصلة وزر طباعة الكشف عبر `DailyJournalSummaryPrintController` (مسار `daily-journals.summary.print`).
- **الملفات:** [خطة التنفيذ](plans/codex/DAILY_JOURNALS_SUMMARY_PLAN.md)، `app/Services/DailyJournalSummaryService.php`، `app/Filament/Resources/DailyJournals/Pages/DailyJournalsSummary.php`، `app/Filament/Resources/DailyJournals/Pages/ListDailyJournals.php`، `app/Filament/Resources/DailyJournals/DailyJournalResource.php`، `app/Http/Controllers/DailyJournalSummaryPrintController.php`، `resources/views/filament/resources/daily-journals/pages/daily-journals-summary.blade.php`، `resources/views/daily-journals/partials/summary-table.blade.php`، `resources/views/daily-journals/partials/statement.blade.php`، `resources/views/daily-journals/summary-print.blade.php`، `lang/ar/finance.php`، `lang/en/finance.php`، `routes/web.php`، `tests/Feature/DailyJournalSummaryTest.php`.
- **التحقق الفعلي:** نجح `php -l` على جميع الملفات المعدلة، نجح `vendor/bin/pint` على الملفات الجديدة (مع إبقاء diff `routes/web.php` محدوداً بالإضافة فقط دون إعادة تنسيق الملف)، نجح `php artisan view:clear`، ونجحت اختبارات `tests/Feature/DailyJournalSummaryTest.php` كاملة (3 اختبارات، 39 assertions: التجميع حسب الفرع، تطابق الفلاتر بين الشاشة والطباعة، حصر النطاق والصلاحيات).
- **حالة النشر:** منفذ محلياً 2026-11-11 ولم يُنشر. عند الرفع اليدوي شغّل `php artisan optimize:clear` على الخادم (ملفات كلاسات جديدة) ثم أعد تشغيل PHP من cPanel إن ظهر خطأ declare class.

## 2026-11-11 — ROUTE-001: عزل مسارات الاختبار وتنظيف مسارات الويب

- **النتيجة:** تم عزل مسارات الاختبار والـ Seeders والتزامن التجريبي من `routes/web.php` ونقلها إلى `routes/test.php` المسجل عبر `bootstrap/app.php` بآلية `withRouting(then: ...)`. تم حذف الراوت المكرر لسند الخزينة في `web.php` واعتماد قاعدة معمارية دائمة تلزم وضع أي مسار تجريبي حصراً في `routes/test.php`.
- **الملفات:** `routes/web.php`، `routes/test.php`، `AGENTS.md`، `docs/SYSTEM_CHEATSHEET.md`.
- **التحقق:** نجح فحص النحو `php -l` على ملفات الراوتس، وتفريغ كاش المسارات والواجهات عبر `php artisan route:clear` و`view:clear`، وتأكيد ظهور المسارات عبر `php artisan route:list`.
- **حالة النشر:** تم الإنجاز محلياً وجاهز للمزامنة مع السيرفر.

## 2026-11-11 — VIO-001: ملخص المخالفات وسدادها حسب المنشأة

- **النتيجة:** تم بناء موديول ملخص المخالفات وسدادها المجمع بحسب المنشأة بمطابقة تامة لمعمارية ونمط موديول الديون:
  1. أصبحت `/violations` هي صفحة الملخص `ViolationsSummary` مع 5 بطاقات KPI إحصائية (المنشآت المسجلة، عدد المخالفات، إجمالي المبالغ، المسدد المسجل، الرصيد المتبقي)، وشريط فلاتر موحد متعدد الخيارات، وتصدير إكسيل/CSV، وطباعة الملخص المفلتر.
  2. تحويل السجل الفردي للمخالفات إلى القائمة التفصيلية في `/violations/detailed` (`ListViolations`) مع أزرار التبديل السلس.
  3. كشف حساب منشأة تفاعلي (Slide-over Modal) يفتح عند النقر على اسم الشركة ويعرض كافة مخالفاتها وحركات وسندات السداد المرتبطة بها (`ViolationTransaction`)، مع زر «سداد دفعة» يوجه إلى تسجيل حركة سداد محددة مسبقاً بالمخالفة ومبلغها المتبقي.
  4. إنشاء وحدة تحكم الطباعة `ViolationSummaryPrintController` وقوالب الطباعة المتجاوبة (`summary-print.blade.php`, `partials/summary-table.blade.php`, `partials/statement.blade.php`).
  5. إنشاء `ViolationSummaryService` لعزل وتوحيد منطق التجميع المالي وحساب المبالغ المتأخرة وتطبيق نطاق الوصول `CompanyAccessScopeService` والفلاتر الموحدة.
- **الملفات:** [خطة التنفيذ](plans/codex/ORCAL_VIOLATIONS_SUMMARY_PLAN_AR.md)، `app/Services/ViolationSummaryService.php`، `app/Filament/Resources/Violations/Pages/ViolationsSummary.php`، `app/Filament/Resources/Violations/Pages/ListViolations.php`، `app/Filament/Resources/Violations/ViolationResource.php`، `app/Filament/Resources/ViolationTransactions/Pages/CreateViolationTransaction.php`، `app/Http/Controllers/ViolationSummaryPrintController.php`، `resources/views/filament/resources/violations/pages/violations-summary.blade.php`، `resources/views/violations/summary-print.blade.php`، `resources/views/violations/partials/summary-table.blade.php`، `resources/views/violations/partials/statement.blade.php`، `lang/ar/violations.php`، `lang/en/violations.php`، `routes/web.php`.
- **التحقق الفعلي:** نجح فحص التركيب النحوي `php -l` على جميع الملفات المعدلة، ونجح فحص التنسيق القياسي `vendor/bin/pint`، ومسح كاش الواجهات عبر `php artisan view:clear`.
- **حالة النشر:** تم الإنجاز محلياً وجاهز للمراجعة والرفع اليدوي من قبل المستخدم مع تشغيل `php artisan optimize:clear` على الخادم.

## 2026-11-11 — DEBT-001: ملخص الديون والتحصيل حسب المدين

- أضيفت صفحة ملخص وكشف مدين وطباعة وتصدير CSV، وربط نموذج التحصيل القائم بالدين المختار. المتأخر يشمل الرصيد المتبقي بعد الاستحقاق حتى عند وجود سداد جزئي. حُرست طباعة الديون وسندات التحصيل بالصلاحية والنطاق.
- تم التحقق من المسارات ومسح كاش الواجهات محليًا. إعادة تشغيل الاختبارات على النسخة النهائية والنشر بانتظار المستخدم. [دليل الديون](modules/DEBTS.md).

## 2026-11-11 — DOC-003: مطابقة حصر شاشات «الملخص التجميعي» مع حالة الكود

- **السبب:** الحصر السابق لشاشات نمط الحضور كان مكتوبًا قبل تنفيذ شاشتي الإجازات والمكافآت، فصار يعرضهما كمقترحين بينما هما منفذتان، وسمّى خدمة المكافآت باسم غير الموجود.
- **ما تغيّر:** أُضيف قسم «الحالة الموثقة على الكود المحلي 2026-11-11» يوضح الشاشات الخمس التي تطبق النمط فعليًا (الحضور، المكافآت، الإجازات، الأقساط، الخزينة)، وفروق شاشة المكافآت عن المعيار، وترتيب المتبقي، وشاشات تحتاج قرارًا قبل التنفيذ. صُحح اسم الخدمة إلى `RewardSummaryService` ووُسمت المرحلة 1 بالاكتمال.
- **الملفات:** [خطة الحصر](plans/MODULES_REQUIRING_ATTENDANCE_SUMMARY_PATTERN.md)، [الفهرس](INDEX.md).
- **التحقق:** مراجعة ملفات الموردين (`EmployeeRewards`, `Leaves`, `Attendance`) و`routes/web.php` وقائمة `app/Services/*SummaryService.php` و`tests/Feature/*Summary*`. لم يُنفّذ أي تغيير على كود التطبيق، ولا `php -l` لأنه لا ملف PHP متغير.
- **حالة النشر:** لا ينطبق (تحديث توثيق فقط).
- **المتبقي:** طباعة كشف المكافآت واختبار خدمتها؛ ثم المهام والتكليفات، نوبات الإشراف، الديون، المخالفات، الصناديق، حركات الخدمات بترتيب القسم 4.

## 2026-11-11 — تحسين فتح صفحة المؤشرات المالية للخدمات

- أُصلح استدعاء قالب المؤشرات لنفسه. تظهر الصفحة قبل تحميل بيانات التقرير، وتُطبّق الفلاتر بطلب واحد. تستخدم خيارات المؤسسات والخدمات نطاق الوصول نفسه، ويقلّل استعلام التقرير بيانات القبض والتوريد المقروءة. نجح الفحص المحلي على SQLite (21 اختبارًا و90 تحققًا). قياس الأداء على السيرفر بانتظار التحقق.

## 2026-11-11 — صفحة مستقلة للمؤشرات المالية للخدمات

- زر المؤشرات في قائمة الخدمات يفتح [صفحة تقرير مستقلة](../app/Filament/Resources/ServiceTransactions/Pages/FinancialIndicators.php) بإجماليات وفلاتر مستقلة ضمن نطاق وصول المستخدم. نجح فحص PHP واختبارات الدورة المالية محليًا على SQLite (21 اختبارًا و87 تحققًا)، ومنها فتح الرابط وتصفية التاريخ ومنع الوصول. مراجعة المتصفح والنشر بانتظار التحقق.

## 2026-11-11 — FIN-002: إصلاح اسم مفتاح دفعات التعويض على MySQL

- **المشكلة:** توقفت محاولة النشر بخطأ 1059 بعد أن ولّد Laravel اسمًا يتجاوز حد MySQL. حالة الـmigration على الإنتاج `Pending`.
- **النتيجة:** سُمّي المفتاح `fin_disb_pay_disb_fk` في [الـmigration الأصلية](../database/migrations/2026_11_11_100000_create_financial_workflow_tables.php). أعيد استخدام المسار المعتاد للإنشاء بعد فحص وإزالة الجداول الجزئية الفارغة فقط. إجراء إعادة التشغيل في [دليل الوحدة](modules/FINANCIAL_WORKFLOW.md).
- **التحقق:** نجح `php -l` على الملف والاختبار، وPint على الـmigration، واختبار توليد أوامر MySQL محليًا دون اتصال بخادم. اكتمل تجهيز التعديل محليًا، وإعادة التشغيل على الإنتاج بانتظار المستخدم.

## 2026-11-11 — FIN-001: الدورة المالية الموحدة العملية (الخدمات أولًا)

- **النتيجة:** استُبدل التصميم العام السابق بخمسة جداول عملية: سندات قبض موحدة (`financial_receipts`)، وصولات توريد نقد (`cash_handovers` و`cash_handover_items`)، سدادات رسوم (`financial_disbursements`)، ودفعات تعويض (`financial_disbursement_payments`)، مرتبطة بالوحدات عبر `(source_type, source_id)`. أُضيف مسار مالي مفعّل باللقطة `metadata.financial_workflow_version = 1`، وحساب الملخص المالي من المستندات المؤكدة فقط، وقفل صف الشركة لتسلسل `balance_after`، ومنع الربط المزدوج لحركة الخزنة، وعكس معتمد بحركة معاكسة، ومهام نواقص بمفتاح `task_key` ثابت. أُخفي إجراء الباركود القديم عن المعاملات المفعّلة، ويرفض مسار استلام العهدة القديم التعامل معها.
- **الملفات:** [migration الجداول](../database/migrations/2026_11_11_100000_create_financial_workflow_tables.php)، موديلات `FinancialReceipt` و`CashHandover` و`CashHandoverItem` و`FinancialDisbursement` و`FinancialDisbursementPayment`، [FinancialTreasuryService](../app/Services/FinancialTreasuryService.php)، [FinancialReceiptService](../app/Services/FinancialReceiptService.php)، [CashHandoverService](../app/Services/CashHandoverService.php)، [FinancialDisbursementService](../app/Services/FinancialDisbursementService.php)، [FinancialSummaryService](../app/Services/FinancialSummaryService.php)، [ServiceFinancialWorkflowService](../app/Services/ServiceFinancialWorkflowService.php)، [OperationsReconciliationService](../app/Services/OperationsReconciliationService.php)، [OperationsNotificationService](../app/Services/OperationsNotificationService.php)، [ServiceTransaction](../app/Models/ServiceTransaction.php)، [ServiceTransactionObserver](../app/Observers/ServiceTransactionObserver.php)، إجراءات Filament [ServiceFinancialActions](../app/Filament/Components/ServiceFinancialActions.php) و[CashHandoverActions](../app/Filament/Components/CashHandoverActions.php)، نموذج المعاملة وتبويب الملخص، ترجمات [financial.php](../lang/ar/financial.php) و[services_tracking](../lang/ar/services_tracking.php)، [FinancialWorkflowTest](../tests/Feature/FinancialWorkflowTest.php)، [دليل الدورة المالية](modules/FINANCIAL_WORKFLOW.md).
- **التحقق الفعلي:** نجح [FinancialWorkflowTest](../tests/Feature/FinancialWorkflowTest.php) بـ13 اختبارًا / 43 تحققًا على SQLite بملف مؤقت ومigrations كاملة، ونجح `php artisan test` لباقي الاختبارات (52 ناجحًا) عدا [ExampleTest](../tests/Feature/ExampleTest.php) القائم (تحويل `/`). نجح `vendor/bin/pint` و`php -l` على الملفات المعدلة و`php artisan view:clear`. لم يُختبر MySQL أو التزامن الحقيقي أو الواجهة في متصفح.
- **الحدود والنشر:** النشر يدوي وغير مؤكد. يلزم `php artisan migrate` ثم مزامنة صلاحيات `OperationsPermissionService` ثم `php artisan optimize:clear` (ملفات خدمات/إجراءات جديدة). لا ترحيل تلقائي للبيانات القديمة؛ تبقى على المسار القديم حتى قرار منفصل. إيداع البنك وتعيميم الدورة على العقود والأقساط والديون خارج النطاق.

## 2026-11-10 — LEV-002: تأمين قرارات الملخص وتصحيح فترات الإجازات

- **النتيجة:** أُصلحت المشاكل التي وافق المستخدم على تنفيذها: الاعتماد والرفض يتحققان من صلاحية تغيير الحالة ونطاق الموظف عند الحفظ؛ تجاوز الرصيد لا يمنح صلاحية الاعتماد. توزّع أيام الإجازة بين السنوات وتتطابق التفاصيل والتصدير والطباعة، وتُزامَن سنة التفاصيل، ويعمل «كل السنوات»، ويستقل موقف اليوم عن السنة ويراعي العودة الفعلية. الرفض يتطلب سبباً ويسجل صاحب القرار ووقته، ويُمنع تكرار القرار على طلب غير معلق.
- **الملفات:** [LeaveSummaryService](../app/Services/LeaveSummaryService.php)، [LeavesSummary](../app/Filament/Resources/Leaves/Pages/LeavesSummary.php)، [واجهة الملخص](../resources/views/filament/resources/leaves/pages/leaves-summary.blade.php)، [LeavePrintController](../app/Http/Controllers/LeavePrintController.php)، [طباعة الملخص](../resources/views/leaves/summary-print.blade.php)، ترجمات leaves بالعربية والإنجليزية، [LeaveSummaryServiceTest](../tests/Feature/LeaveSummaryServiceTest.php)، [دليل الإجازات](modules/LEAVES.md)، [المرجع المشترك](SYSTEM_CHEATSHEET.md)، [LEV-002](KNOWN_ISSUES.md#lev-002)، وهذا السجل.
- **التحقق الفعلي:** نجح 18 اختباراً / 115 تحققاً باستخدام SQLite في الذاكرة؛ يشمل تشغيل واجهة Livewire وإجراءاتها ومسارات HTTP للطباعة وحالات منع الوصول. نجح php -l على ملفات PHP الستة وقالبَي Blade المعدلين، ونجح فحص Pint النهائي على ملفات PHP المحددة، ونجح php artisan view:clear. نجح تحقق Tinker مباشر لتوزيع أيام الإجازة وقراءة كل السنوات، وأُعيد اختبار تدفق Livewire بعد ضبط رسائل الخطأ داخل النافذة (اختبار واحد / 14 تحققاً).
- **الحدود والنشر:** لم يُفتح متصفح يدوي ولم يُختبر MySQL أو التزامن عليه؛ الإنتاج غير مؤكد. إجراءات السجل التفصيلي القديم خارج نطاق التغيير. لا migrations جديدة ولا تعديل بيانات قديمة أو اعتماديات. تُرفع الملفات مع بقية تنفيذ الملخص الجديد ثم يُشغل php artisan optimize:clear على السيرفر؛ تطبق تعليمات إعادة تشغيل PHP عند الحاجة. لا يثبت تقرير LEV-001 السابق تغطية هذه الحالات؛ الاختبارات الحالية توسّع تغطيته.

## 2026-11-10 — LEV-001: تطبيق نمط الموظف أولاً والأرصدة في الإجازات

- **المشكلة والنتيجة:** تحويل شاشة الإجازات من جدول مسطح لكل طلب إلى لوحة ملخص قائمة على الموظف `/leaves` تعرض سطرًا لكل موظف مصرح به برصيده السنوي والمستحق والمستهلك والمتبقي وموقفه اليومي، مع 5 بطاقات KPI، ونافذة منبثقة تفاعلية لاستعراض سجل إجازات الموظف وأرصدته، وإمكانية الاعتماد/الرفض السريع، وطباعة كشف إجازات موظف رسمي (A4 RTL)، وطباعة ملخص الفلاتر، وتصدير CSV مع BOM، مع نقل السجل التاريخي التفصيلي إلى `/leaves/detailed`.
- **الملفات:** [LeaveSummaryService](../app/Services/LeaveSummaryService.php)، [LeavesSummary](../app/Filament/Resources/Leaves/Pages/LeavesSummary.php)، [واجهة الملخص](../resources/views/filament/resources/leaves/pages/leaves-summary.blade.php)، [LeaveResource](../app/Filament/Resources/Leaves/LeaveResource.php)، [ListLeaves](../app/Filament/Resources/Leaves/Pages/ListLeaves.php)، [CreateLeave](../app/Filament/Resources/Leaves/Pages/CreateLeave.php)، [LeavePrintController](../app/Http/Controllers/LeavePrintController.php)، [كشف الموظف](../resources/views/leaves/employee-statement-print.blade.php)، [كشف الملخص](../resources/views/leaves/summary-print.blade.php)، [routes/web.php](../routes/web.php)، [ترجمة العربية](../lang/ar/leaves.php)، [ترجمة الإنجليزية](../lang/en/leaves.php)، [LeaveSummaryServiceTest](../tests/Feature/LeaveSummaryServiceTest.php)، [دليل الإجازات](modules/LEAVES.md).
- **التحقق:** نجح `php -l` لكافة ملفات PHP وBlade المعدلة، ونجح فحص التنسيق `vendor/bin/pint`، ونجحت اختبارات `LeaveSummaryServiceTest` الأربعة، وتم مسح كاش الواجهات `view:clear`.
- **الحالة:** منفذ ومتحقق منه محلياً؛ يحتاج رفع الملفات وتشغيل `php artisan optimize:clear` على السيرفر.

## 2026-11-10 — DOC-002: دراسة وحصر الشاشات المرشحة لنمط ملخص الحضور والتفاصيل

- **النتيجة:** حصر شامل لكافة موارد النظام وتصنيف الشاشات التي تعتمد جداول مسطحة وتحتاج للتحول إلى نمط «الملخص التجميعي + بطاقات KPI + نافذة التفاصيل التفاعلية + فصل السجل الخام»، مع ترتيب أولويات التنفيذ ومصفوفة المواصفات الفنية.
- **الملفات:** [خطة حصر الشاشات](plans/MODULES_REQUIRING_ATTENDANCE_SUMMARY_PATTERN.md)، وتحديث [الفهرس](INDEX.md).
- **التحقق:** فحص الروابط المحلية وتطابق أسماء الموارد والموديلات في النظام.
- **الحالة:** خطة وتوثيق؛ لا تغيير في كود PHP أو قاعدة البيانات.

## 2026-11-10 — DOC-001: إنشاء مرجع المهام المتجدد

- **النتيجة:** فهرس للمعرفة ودليلا الحضور والشركات وسجلا التغييرات والمشكلات. ترتيب قراءة وتحديث إلزامي في تعليمات المشروع لتقليل إعادة البحث والتمييز بين المنفذ والمقترح والمنشور.
- **الملفات:** [INDEX](INDEX.md)، [دليل الحضور](modules/ATTENDANCE.md)، [دليل الشركات](modules/COMPANY_ACCESS.md)، [KNOWN_ISSUES](KNOWN_ISSUES.md)، هذا السجل، [SYSTEM_CHEATSHEET](SYSTEM_CHEATSHEET.md)، [AGENTS](../AGENTS.md).
- **التحقق:** مطابقة مواضع التنفيذ والفلاتر والمسارات مع الكود الحالي. نجح فحص 107 روابط محلية وإحالاتها، وتوازن كتل الكود، و`git diff --check` للملفات السبعة. لم تُشغل اختبارات التطبيق لأن التغيير Markdown فقط.
- **الحالة:** توثيق محلي فقط؛ لا تعديل PHP أو إعدادات أو بيانات أو نشر أو تغيير فرع.
- **المتابعة:** [ATT-003](KNOWN_ISSUES.md#att-003) يسجل اختلاف وجود ملف الاختبارات عن التقرير السابق. الأدلة الأخرى مفهرسة ولم تُدقق بالكامل في هذه المهمة.

## 2026-11-10 — ATT-004: إصلاح نطاق الشركات والشهور والإضافي

- **المشكلة والنتيجة:** مسار مستخدم الشركة في الـTrait لم يقيد الحضور عبر الموظف، والقائمة الفارغة أمكن أن تترك استعلامًا مفتوحًا. استخراج [CompanyAccessScopeService](../app/Services/CompanyAccessScopeService.php) جعل [HasCompanyScope](../app/Filament/Traits/HasCompanyScope.php) وخدمة الحضور يستعملان نفس التقييد؛ المستخدم بلا فرع يظل يرى كل فروع شركاته المصرح بها.
- **الشهور:** [AttendanceSummaryService::monthStart](../app/Services/AttendanceSummaryService.php) يثبت اليوم الأول ويمنع انزلاق فبراير بسبب يوم التشغيل، وتستخدمه [صفحة الملخص](../app/Filament/Resources/Attendance/Pages/AttendanceSummary.php) في التنقل.
- **الإضافي:** تصحيح اتجاه فرق الوقت في [AttendanceForm](../app/Filament/Resources/Attendance/Schemas/AttendanceForm.php)؛ ساعة بعد نهاية الدوام تعطي +60 دقيقة. [كنترولر الطباعة](../app/Http/Controllers/AttendancePrintController.php) يعتمد على النطاق المشترك.
- **التحقق التاريخي:** نجح php -l لثمانية ملفات PHP، وPint، و11 اختبارًا/89 تحققًا في الجولة السابقة. اختبار SQL عُدّل ليتوافق مع اقتباس أسماء الجداول في SQLite وMySQL.
- **قيد التحقق الحالي:** ملف الاختبارات الموسع `AttendanceCorrectionsTest.php` غير موجود وقت إعداد هذا السجل. النتيجة السابقة لا تعني توفر هذه التغطية حاليًا؛ [ATT-003](KNOWN_ISSUES.md#att-003).
- **النشر:** التنفيذ موجود محليًا؛ لا تأكيد لرفع المجموعة النهائية. هناك كلاس خدمة جديد يجب رفعه مع مستهلكيه، ثم `php artisan optimize:clear`.
- **المتبقي:** [قواعد العطلات](KNOWN_ISSUES.md#att-001)، [فحص البيانات القديمة](KNOWN_ISSUES.md#att-002)، [حدود التعميم](KNOWN_ISSUES.md#scope-001).

## 2026-11-10 — ATT-003: ضبط فلتر حالة الموظف و«الكل»

- **النتيجة النهائية:** [AttendanceSummary](../app/Filament/Resources/Attendance/Pages/AttendanceSummary.php) يقبل employmentStatus كـ`?string` ويحول الفارغ إلى all. [واجهة الملخص](../resources/views/filament/resources/attendance/pages/attendance-summary.blade.php) تستخدم placeholder مترجمًا «الكل» وخياري active/inactive، دون تكرار أو نص false.
- **السبب:** [filter-select](../resources/views/components/filter-select.blade.php) يحول اختيار الكل إلى null؛ الخاصية النصية غير القابلة له قد تُزال بواسطة Livewire ثم تفشل قراءتها. تعطيل placeholder بالقيمة false أدى إلى ظهور النص false عندما لا توجد قيمة مطابقة.
- **التحقق التاريخي:** إعادة إنتاج مسار إسناد Livewire، والتحقق من null والنص الفارغ وall وactive وinactive ومسح الفلاتر. فحص JavaScript للمكون المجهز عند اختيار حالة ثم الرجوع للكل؛ php -l وPint ومسح كاش الواجهات نجحت في تلك الجولة.
- **النشر:** أفاد المستخدم بأن الخطأ توقف قبل تصحيح نص false؛ لا تأكيد لاحق بأن النسخة النهائية مجتمعة منشورة.

## 2026-11-10 — ATT-002: تحسين ملخص الحضور وإضافة الطباعة

- **النتيجة:** [واجهة الملخص](../resources/views/filament/resources/attendance/pages/attendance-summary.blade.php) تعرض أربع بطاقات في الصف على الكمبيوتر واثنتين على الهاتف، مع وحدات بجانب الأرقام وفلاتر متساوية العرض. إضافة زر طباعة ملخص كل النتائج المطابقة للشهر والفلاتر بجوار التصدير.
- **الملفات:** الواجهة، [AttendancePrintController](../app/Http/Controllers/AttendancePrintController.php)، [routes/web.php](../routes/web.php)، [summary-print](../resources/views/attendance/summary-print.blade.php)، وترجمتا [العربية](../lang/ar/attendance.php) و[الإنجليزية](../lang/en/attendance.php).
- **التحقق التاريخي:** تجهيز قالب الطباعة ببيانات SQLite مؤقتة، وفحص الصلاحية والعزل والفلاتر والنتائج الفارغة وإخفاء HTML داخل الأسماء، والتحقق من تعريف المسار وPHP/Pint وكاش الواجهات. ليست معاينة بصرية من المتصفح.
- **النشر:** محلي؛ لا تأكيد إجمالي لآخر مجموعة ملفات.

## 2026-11-10 — ATT-001: قصر الملخص على السعوديين وإصلاح مسح الفلاتر

- **النتيجة:** [خدمة الملخص](../app/Services/AttendanceSummaryService.php) تقيد الملخص وكشف الموظف بـ`Employee::saudi()`، وتستفيد منه المؤشرات والتصدير والطباعة. أزيل فلتر الجنسية من واجهة الملخص. مسح الفلاتر يعيد القيم الافتراضية بدل إسناد null لخصائص نصية لا تقبله.
- **الملفات الأساسية:** الخدمة، صفحة الملخص وواجهتها، والمرجع المشترك.
- **التحقق التاريخي:** فحوص مباشرة لنطاق السعودي ومسح الفلاتر وPHP/Pint. لاحقًا عولجت مشكلة القيمة الفارغة لحالة الموظف في الإدخال أعلاه.
- **حد النطاق:** لا يثبت ذلك قصر كل الاستعلامات والإجراءات القديمة على السعوديين؛ راجع [SCOPE-001](KNOWN_ISSUES.md#scope-001).
- **النشر:** لا تأكيد لحالة المجموعة النهائية؛ تحديث الملفات المحلية لا يعني رفعها.
## 2026-11-11 — تنسيق نافذة المؤشرات المالية للخدمات

- فصلت [نافذة المؤشرات](../resources/views/filament/resources/service-transactions/financial-indicators.blade.php) عناوين المجموعات والجداول، وضبطت تباعد الأعمدة ومحاذاة القيم والتمرير داخل الجدول. التغيير منفذ محليًا.

