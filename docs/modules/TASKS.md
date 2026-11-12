# المهام والتكليفات وأداء الموظف

**آخر مراجعة محلية:** 2026-11-12 (المرحلة أ فقط). **النشر:** لم يُنشر؛ التغييرات غير مدفوعة في worktree فرع `modify/feature/employee-performance` المبني من `cc5d47e`.

> مسارات الكود في هذا الدليل موجودة الآن داخل `D:\company projects\orcal-performance` وليست بعد في شجرة المشروع الأصلية. روابط `../` أدناه تشير إلى المسار النسبي نفسه بعد دمج الفرع.

## المداخل

- **لوحتي:** صفحة `MyTasks` على المسار `/my-tasks` داخل مجموعة **المهام**. تعرض اسم الحساب الحالي من علاقة `User::employee()`، وتتطلب وجود سجل موظف مرتبط بالحساب.
- **سجل التكليفات:** `/task-assignments` أصبح صفحة الملخص `TaskAssignmentsSummary`، والسجل التفصيلي انتقل إلى `/task-assignments/detailed` (`ListTaskAssignments`). لا تغيير على توزيع التكليفات وتعديلها في `TaskAssignmentResource`.
- **ملف الموظف:** صفحة **الأداء** داخل ملف الموظف السعودي والأجنبي (`/saudi-employees/{record}/performance` و`/foreign-employees/{record}/performance`)، مع زر في جدول الموظفين وفي صفحة التعديل.
- **كشف ملخص التكليفات:** الضغط على اسم الموظف في `TaskAssignmentsSummary` يفتح نفس كشف المهام في نافذة جانبية.

## التجميع

- [TaskAssignmentSummaryService](../../app/Services/TaskAssignmentSummaryService.php) هو المصدر الوحيد للأرقام في المداخل الأربعة: سطر الموظف، الإجماليات، الكشف، وتصدير CSV.
- القياس على مستوى `task_assignment_items`، والإسناد عبر `task_assignments.employee_id`.
- مجموعة البنود للفترة تشمل كل بند تاريخ استحقاقه أو تاريخ إنجازه داخل الفترة، وتضيف البنود المفتوحة (`pending` أو `in_progress`) التي تجاوز استحقاقها اليوم ولم يتجاوز نهاية الفترة. البنود المؤجلة لا تُعد متأخرة وتظهر بعدّادها.
- الفترة الافتراضية هي الشهر الحالي. تقبل الخدمة `month` بصيغة `Y-m` أو `date_from`/`date_to`؛ الشهر غير الصالح يعود للشهر الحالي.
- المؤشرات:
  - **درجة التكليف الموزونة:** `sum(score) / sum(weight) * 100`، وصفر عند غياب الأوزان.
  - **نسبة الإنجاز:** المكتمل داخل الفترة ÷ المستحق داخل الفترة، وفارغة عند عدم وجود مستحق.
  - **الإنجاز في الموعد:** المكتمل قبل أو في تاريخ الاستحقاق ÷ المكتمل الذي يملك تاريخَي الاستحقاق والإنجاز، وفارغة عند غياب المقام.
  - **متأخرة الآن / مؤجلة / جارية / معلقة:** من حالة البند وتاريخ الاستحقاق فقط.
- يظهر سطر لكل موظف غير مؤقت داخل النطاق، حتى لو لم يكن لديه أي تكليف في الفترة، بقيم صفرية ونسب فارغة.

## النطاق والصلاحيات

- كل استعلام يمر عبر [CompanyAccessScopeService](../../app/Services/CompanyAccessScopeService.php): الموظفون مباشرة بحقول `company_id`/`branch_id`، والتكليفات عبر علاقة `employee`.
- صفحات الملخص والأداء تتحقق من صلاحية `viewAny` على `TaskAssignment` أو من صلاحية مورد الموظف عند الفتح وأثناء طلبات Livewire.
- كشف ملخص التكليفات يرفض الموظف خارج نطاق المستخدم بـ404، وكذلك فتح ملف موظف من مورد لا يطابق نوعه (سعودي/أجنبي).
- «لوحتي» تتحقق أن سجل الموظف يخص الحساب الحالي، ولا تسمح بفتح سجل موظف آخر.

## الملفات

- [TaskAssignmentSummaryService](../../app/Services/TaskAssignmentSummaryService.php)
- [HasTaskPerformanceDashboard](../../app/Filament/Concerns/HasTaskPerformanceDashboard.php)
- [MyTasks](../../app/Filament/Pages/MyTasks.php)
- [TaskAssignmentsSummary](../../app/Filament/Resources/TaskAssignments/Pages/TaskAssignmentsSummary.php)
- [EmployeePerformance للسعوديين](../../app/Filament/Resources/SaudiEmployees/Pages/EmployeePerformance.php)
- [EmployeePerformance للأجانب](../../app/Filament/Resources/ForeignEmployees/Pages/EmployeePerformance.php)
- [قالب الملخص](../../resources/views/filament/resources/task-assignments/pages/task-assignments-summary.blade.php)
- [قالب الأداء المشترك](../../resources/views/filament/tasks/performance.blade.php)
- ترجمات `tasks.summary` و`tasks.performance` في `lang/ar/tasks.php` و`lang/en/tasks.php`.

## التحقق

- نجح `php -l` على جميع ملفات PHP المتغيرة، ونجح `vendor/bin/pint` عليها، ونجح `php artisan view:clear`.
- نجح [TaskAssignmentSummaryServiceTest](../../tests/Feature/TaskAssignmentSummaryServiceTest.php) بستة اختبارات و80 تحققًا على SQLite في الذاكرة، ويغطي: التجميع والفترة، موظفًا بلا تكليفات، متأخرًا ومؤجلًا، الإنجاز في الموعد وخارجه، بندًا بلا موعد استحقاق (مفتوح يُستبعد، ومكتمل يُحتسب مع استبعاده من النسب)، الدرجة الموزونة، عزل الفرع/الشركة، رفض الكشف خارج النطاق، تشغيل صفحة الملخص والنافذة وتصدير CSV، «لوحتي»، وصفحة الأداء من ملف الموظف.
- عند تشغيل كامل الاختبارات نجح 70 اختبارًا، وفشلت ثلاثة اختبارات قائمة لا علاقة لها بهذه المهمة: `ExampleTest` (تحويل `/` إلى 302) واختباران في `DebtSummaryTest` لعدم إنشاء جدول `branches` في مخططه. أُعيد تشغيل `DebtSummaryTest` منفردًا بنفس النتيجة.
- لم يُفحص التغيير في متصفح يدوي، ولم يُختبر MySQL أو التزامن عليه.

## النشر والمتبقي

- لا توجد migrations جديدة. عند النشر اليدوي تُرفع الملفات ثم يُشغّل `php artisan optimize:clear` على الخادم لالتقاط الخدمات والصفحات الجديدة، ثم يُتحقق من `/my-tasks` و`/task-assignments` و`/task-assignments/detailed` ومساري الأداء.
- «الملاحظات الأسبوعية» (البند 6 من الخطة) مؤجلة. المرحلة ب (الخدمات ولوحة المشرف) والمرحلة ج (البطاقات المالية) تنتظران دمج عمل الموديل الآخر في الدورة المالية، والمرحلة د تحتاج قرارًا إداريًا على تعريف المؤشرات.
