# دليل الحضور والانصراف

آخر مطابقة للكود المحلي: **2026-11-10**. آخر تأكيد نشر للإصلاحات النهائية: **غير متاح**.

الرجوع إلى [الفهرس](../INDEX.md)، [نطاق الشركات](COMPANY_ACCESS.md)، [التغييرات](../CHANGELOG.md)، [المشكلات](../KNOWN_ISSUES.md).

## السلوك الحالي

الصفحة الرئيسية تعرض سطرًا لكل موظف سعودي، بملخص الفترة، حتى من ليس لديه سجل. اختيار الموظف يفتح نافذة تفاصيل تشمل كل أيام الشهر؛ ليست صفحة موظف مستقلة. الجنسية محددة عبر `Employee::saudi()`، ولا يوجد فلتر جنسية في الملخص.

نطاق السعودي نفسه مشترك في [Employee](../../app/Models/Employee.php): نوع المستخدم سعودي أو مالك، أو الجنسية في التفاصيل تطابق السعودية. لا تُنشئ تعريفًا آخر للسعودي في هذه الوحدة.

الجدول التاريخي القديم باقٍ في `/attendances/detailed`. لا يفترض أن تقييد الملخص بالسعوديين يضيف تلقائيًا نفس القيد لكل استعلام في الجدول القديم أو إجراءات أخرى.

## خريطة الملفات

| الملف | المسؤولية |
| --- | --- |
| [AttendanceResource](../../app/Filament/Resources/Attendance/AttendanceResource.php) | المسارات وHasCompanyScope وتوصيل النموذج والجدول |
| [AttendanceSummary](../../app/Filament/Resources/Attendance/Pages/AttendanceSummary.php) | حالة الفلاتر والصفحات والترتيب والتصدير والتنقل بين شهور التفاصيل |
| [AttendanceSummaryService](../../app/Services/AttendanceSummaryService.php) | تجميع السجلات، المؤشرات، توليد أيام الشهر، نطاق الوصول |
| [واجهة الملخص](../../resources/views/filament/resources/attendance/pages/attendance-summary.blade.php) | البطاقات والفلاتر والجدول ونافذة الموظف وروابط الطباعة |
| [AttendanceForm](../../app/Filament/Resources/Attendance/Schemas/AttendanceForm.php) | اختيار الموظف السعودي وحساب مؤشرات أوقات الدخول والخروج |
| [CreateAttendance](../../app/Filament/Resources/Attendance/Pages/CreateAttendance.php) | تعبئة الموظف والتاريخ من رابط الإضافة |
| [ListAttendance](../../app/Filament/Resources/Attendance/Pages/ListAttendance.php) | الجدول التاريخي وإجراءاته الحالية |
| [AttendancePrintController](../../app/Http/Controllers/AttendancePrintController.php) | تفويض طلبات الطباعة وقراءة الفلاتر وتجهيز القوالب |
| [كشف الموظف](../../resources/views/attendance/employee-sheet-print.blade.php) / [كشف الملخص](../../resources/views/attendance/summary-print.blade.php) | طباعة HTML من المتصفح؛ لا تستخدم mPDF في هذين المسارين |
| [ترجمات العربية](../../lang/ar/attendance.php) / [ترجمات الإنجليزية](../../lang/en/attendance.php) | النصوص المعروضة للمستخدم |

## المسارات

| المسار | الوظيفة |
| --- | --- |
| `/attendances` | ملخص الموظفين |
| `/attendances/detailed` | الجدول التاريخي |
| `/attendances/create` | إضافة؛ يمكن إرسال `employee_id` و`date` |
| `/attendances/{record}/edit` | تعديل سجل |
| `/attendances/summary/print` | `attendance.summary.print`: كل النتائج المطابقة للفلاتر، دون التقيد بصفحة الجدول |
| `/attendances/{employee}/sheet/print` | `attendance.sheet.print`: كشف الموظف للشهر |

مسارا الطباعة معرفان في [routes/web.php](../../routes/web.php) داخل `auth`. الكنترولر يتطلب `viewAny` للحضور، وكشف الموظف يتحقق أيضًا من الوصول إليه. تعريف صلاحيات العمليات في [AttendancePolicy](../../app/Policies/AttendancePolicy.php)؛ لا يغني نطاق الشركات عن صلاحية العملية.

## الفلاتر والقيم الافتراضية

| الخاصية في الصفحة | المفتاح في الخدمة والطباعة | السلوك |
| --- | --- | --- |
| `month` | `month` | الشهر الحالي افتراضيًا؛ قائمة آخر 24 شهرًا |
| `companyId` | `company_id` | شركات متعددة؛ تضيق النطاق المصرح به ولا تمنح صلاحيات |
| `branchId` | `branch_id` | فروع متعددة داخل النطاق |
| `search` | `search` | الاسم والرقم الوظيفي والهوية/الإقامة |
| `employmentStatus` | `employment_status` | `active` أو `inactive`؛ القيمة الفارغة أو `all` تعني الكل |

`employmentStatus` معرف `?string`؛ `filterState()` يحول القيمة الفارغة إلى `all`. المكون [filter-select](../../resources/views/components/filter-select.blade.php) يضيف خيارًا فارغ القيمة من `placeholder` ويحوّل اختيار الكل إلى `null`. لذلك فلتر حالة الموظف يستخدم النص المترجم «الكل» كـplaceholder، وخياراته الصريحة هي active وinactive فقط؛ لا تستخدم `false` كنص افتراضي ولا تضف خيار كل مكررًا.

`resetFilters()` يعيد الخصائص عبر Livewire `reset()` ويضبط الشهر الحالي والصفحة الأولى، بدل إسناد null عشوائيًا إلى خصائص نصية غير قابلة له. الافتراضي 10 موظفين في الصفحة، والترتيب بأيام الغياب تنازليًا. CSV وطباعة الملخص يشملان كل النتائج ويرتبان بالغياب ثم التأخير ثم معرف الموظف؛ لا يلزمان بترتيب الجدول الذي اختاره المستخدم.

## الخدمات والحسابات

- `summaryQuery()` يربط الموظفين بتجميع جدول `attendance` عبر `leftJoinSub`؛ `getStats()` و`exportRows()` يعتمدان على نفس الاستعلام. لا يعبر تصدير Excel هنا عن ملف XLSX: الناتج CSV مع BOM لدعم العربية.
- `applyAccessScope()` يفوض إلى الخدمة المشتركة الموصوفة في [دليل الشركات](COMPANY_ACCESS.md). `canAccessEmployee()` يستخدم استعلام موظفين مقيدًا ثم `exists()`، لتطابق التفاصيل والطباعة نطاق الملخص.
- `monthStart()` يقبل `YYYY-MM` صحيحًا، ويستخدم `!Y-m` لتثبيت أول يوم عند منتصف الليل. null والشهر غير الصحيح يعودان للشهر الحالي؛ `2026-13` لا يُرحّل إلى 2027. الكنترولر يرفض صيغة الشهر غير الصحيحة في طباعة الملخص عبر validation، فلا يُفترض تطابق سياسة الإدخال في كل نقاط الدخول.
- حدود الفترة والتفاصيل والتنقل تستخدم `monthStart()`؛ لن يرث فبراير اليوم 30 من تاريخ تشغيل الكود. `resolveDateRange()` يدعم أيضًا تاريخ بداية ونهاية إذا لم يُحدد شهر، لكن واجهة الملخص الحالية شهرية.
- `late_hours` و`overtime_hours` اسمان قديمان لكن قيمتهما **دقائق**؛ `total_hours` بالساعات. `AttendanceForm::calculateAttendanceMetrics()` يستخدم إعدادات الفرع من `AttendancePenaltyService::getBranchSettings()`، والقيم الافتراضية 08:00–16:00 وسماح 15 دقيقة. لا تخلط تغيير العرض بتغيير سياسة التأخير.
- حساب الإضافي بعد نهاية الدوام أصبح من وقت النهاية إلى الخروج بفرق موجب؛ 17:00 مقابل نهاية 16:00 يعطي 60 دقيقة. الإصلاح يخص الحساب عند الإدخال، ولا يعيد حساب بيانات قديمة تلقائيًا.

## التحقق وحدوده

الملف الموجود وقت توثيق هذه النسخة: [AttendanceSummaryServiceTest](../../tests/Feature/AttendanceSummaryServiceTest.php). يغطي حدود الشهر بمحاكاة يوم 30، وخيارات الشهور، وبناء SQL، وعلاقة الحضور. تشغيله:

```powershell
& 'C:\xampp\php\php.exe' vendor/phpunit/phpunit/phpunit tests/Feature/AttendanceSummaryServiceTest.php --do-not-cache-result
```

في الجولة السابقة بتاريخ 2026-11-10 نجح 11 اختبارًا و89 تحققًا باستخدام ملف إضافي اسمه `AttendanceCorrectionsTest.php`. **هذا الملف غير موجود وقت المطابقة الحالية**؛ النتيجة تاريخية ولا تثبت توفر تلك التغطية الآن. راجع [ATT-003](../KNOWN_ISSUES.md#att-003). لم تُعد اختبارات التطبيق في مهمة التوثيق؛ لا حاجة لتشغيلها بسبب تعديلات Markdown فقط.

أي تغيير لاحق للصلاحيات أو الحسابات يحتاج حالات وصول فعلية، وطباعة، ونهاية شهر، ودقائق إضافي موجبة وصفرية. فحص SQL وحده لا يكفي لإثبات العزل أو صحة الأرقام.

## النشر والمتابعات

النشر يدوي، وآخر حالة الإصلاحات مجتمعة على السيرفر غير مؤكدة. عند نشر إصلاح النطاق ارفع [CompanyAccessScopeService](../../app/Services/CompanyAccessScopeService.php) الجديد مع الـTrait وخدمة الحضور والصفحة والنموذج والكنترولر المعدلة، ثم شغّل `php artisan optimize:clear`. راجع تعليمات الاستضافة في [AGENTS.md](../../AGENTS.md) عند مشكلة تحميل كلاس بعد الرفع.

آخر تعديلات هذه المهمة توثيق فقط، ولا تحتاج نشر PHP. قواعد العطلات والإجازات وتقييم البيانات القديمة ومراجعة تغطية الإجراءات الأخرى مسجلة في [المشكلات المفتوحة](../KNOWN_ISSUES.md).
