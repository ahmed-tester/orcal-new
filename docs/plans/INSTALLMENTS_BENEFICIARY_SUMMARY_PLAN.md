# خطة: ملخص الأقساط لكل مستفيد (بدل تكرار الأسماء) — صفحة /installments

> التاريخ: 2026-10-28 — الفرع: `refactoring`
> المرجع البصري: لقطة الشاشة المرفقة (بطاقات إحصائية + فلاتر + جدول مجمّع + نافذة تفاصيل).

---

## 1) الهدف

صفحة `/installments` الحالية تعرض **سطرًا لكل قسط**، فيتكرر اسم نفس المستفيد عدة مرات.
المطلوب:

1. **سطر واحد لكل مستفيد (شخص)** يعرض: الاسم، المجموع، رقم الإقامة/الهوية، المؤسسة، المسدد، المتبقي (مع عدد الأقساط).
2. عند الضغط على السطر (زر «عرض التفاصيل») تفتح **نافذة تفاصيل** بنفس شكل الصورة: ملخص المستفيد في الأعلى + جدول كل أقساطه (رقم القسط، التاريخ، النوع، المبلغ، المسدد، المتبقي، الحالة).

---

## 2) الوضع الحالي في الكود (نتيجة الفحص)

| العنصر | الموقع الحالي | ملاحظات |
|---|---|---|
| صفحة الأقساط | `app/Filament/Resources/Installments/` — `InstallmentResource` + `ListInstallments` (ListRecords قياسي) | الجدول في `Tables/InstallmentsTable.php` يعرض كل `Installment` كسطر مستقل |
| الموديل | `app/Models/Installment.php` | مستفيد **داخلي** (`employee_id`) أو **خارجي** (`external_name`, `external_national_id`, `external_phone`)؛ تُحسب المجاميع تلقائيًا في `calculateTotals()` |
| الجدولة الشهرية | `app/Models/InstallmentSchedule.php` | غير مطلوبة في هذه المهمة (النافذة تعرض العقود نفسها `INS-…` كما في الصورة) |
| رقم الإقامة/الهوية | `EmployeeDetail` (`national_id`, `iqama_number`) عبر `employee->details()` | للمستفيد الخارجي: `external_national_id` |
| اسم المستفيد | داخلي: `employee.user.name` (قاعدة البحث الموثقة: البحث باسم المستخدم) | خارجي: `external_name` |
| المؤسسة | `company.trade_name` (عبر `employee.company` أو `company_id`) | الخارجي غالبًا بدون مؤسسة |
| نطاق الصلاحيات | `HasCompanyScope` + `InstallmentPolicy` (`viewAny/view/create/update/delete`) + `CompanyFormService::getAuthorizedCompanyIds()` | يجب أن يرث الملخص نفس النطاق |
| **سابقة ممتازة لصفحة مخصصة داخل Resource** | `app/Filament/Resources/Faqs/Pages/ListFaqs.php` — `extends Page` مع blade مخصصة وهي **مسار index** للمورد | هذا هو النمط الذي سنقلّده |
| **نمط التصميم المطلوب موجود بالفعل** | `resources/views/filament/pages/general-reports.blade.php` (شبكة بطاقات `report-stats-grid`، فلاتر، بحث، تصدير CSV، ترقيم) | مطابق تقريبًا لشكل الصورة |
| ملفات اللغة | `lang/ar/finance.php` + `lang/en/finance.php` (قسم `installments`) | سنضيف مفاتيح `installments.summary.*` فقط |
| مجلد الخطط | `docs/plans/` | هذا الملف |

---

## 3) الشكل المستهدف

### أ) الصفحة الرئيسية (تستبدل الجدول الحالي في `/installments`)

- **4 بطاقات إحصائية** أعلى الصفحة (تُحسب من نفس الفلاتر المطبقة، لا من الصفحة المعروضة فقط):
  عدد المستفيدين — عدد الأقساط — إجمالي المسدد — إجمالي المتبقي.
- **شريط فلاتر**: بحث (الاسم / رقم الإقامة / رقم الموظف) — المؤسسة — الفرع — نوع المعاملة (قسط/سلفة) — نوع المستفيد (داخلي/خارجي) — الحالة — تصفير الفلاتر.
- **زر تصدير CSV** (بنفس نمط `GeneralReports::exportCsv` مع BOM للعربية).
- **جدول مجمّع** (سطر لكل مستفيد):

| # | اسم المستفيد | رقم الإقامة / الهوية | المؤسسة | عدد الأقساط | إجمالي المبلغ | إجمالي المسدد | المتبقي | الإجراءات |
|---|---|---|---|---|---|---|---|---|
| 1 | **إبراهيم خليل** | 2387654321 | مؤسسة فور القابضة | 4 | 3,000.00 | 2,250.00 | 750.00 | «عرض التفاصيل» |

- ترقيم صفحات: «عرض 1 إلى 10 من أصل N مستفيد».

### ب) نافذة التفاصيل (Modal — نفس شكل الصورة)

- **رأس النافذة**: اسم المستفيد، رقم الإقامة، المؤسسة، عدد الأقساط، إجمالي المبلغ، المسدد، المتبقي.
- **جدول الأقساط** الخاص به مرتبة بالتاريخ تصاعديًا:

| # | رقم القسط | التاريخ | التنفيذ | نوع القسط | المبلغ | المسدد | المتبقي | الحالة | إجراءات |
|---|---|---|---|---|---|---|---|---|---|
| 1 | INS-C90-0001 | 2026-07-15 | 2026-07-15 | كفالات | 750.00 | 750.00 | 0.00 | مسدد | تعديل / حذف |
| 2 | INS-C90-0004 | 2026-10-15 | 2026-10-15 | كفالات | 750.00 | 0.00 | 750.00 | متبقٍ | تعديل / حذف |

- «الحالة» شارة محسوبة: `remaining_amount == 0` → **مسدد** (أخضر) غير ذلك → **متبقٍ** (أحمر).
- أعمدة «تعديل/حذف» تظهر فقط لمن يملك صلاحية `update`/`delete` (نمط `ListFaqs::deleteFaq` مع Notification بالعربية).
- زر «إغلاق».

---

## 4) قرارات تحتاج تأكيدك (مع توصيتي)

1. **إظهار المستفيدين الخارجيين؟**
   الصورة بعنوان «ملخص الموظفين»، لكن النظام يدعم مستفيدين خارجيين لهم مديونيات أيضًا.
   **التوصية: إظهار الجميع** مع بادج نوع المستفيد (داخلي/خارجي) وفلتر للنوع؛ الخارجي بدون مؤسسة يظهر «—».
   (لو تريد الموظفين فقط، أضع شرط `beneficiary_type = 'internal'` وسطرًا واحدًا في الخدمة.)

2. **مصير الجدول التفصيلي الحالي (سطر لكل قسط)؟**
   **التوصية: لا نحذفه** — ننقله إلى مسار فرعي `/installments/detailed` مع زرّي تبديل بين «العرض الملخص» و«العرض التفصيلي» في رأس الصفحتين. نحافظ على كل وظائف الإدارة (تعديل جماعي… إلخ) بدون أي فقدان.

3. **عمود «التنفيذ» في نافذة التفاصيل** (كما في الصورة) — ما هو بالضبط؟
   **التوصية**: «التاريخ» = `installment_date` (تاريخ القسط)، «التنفيذ» = تاريخ إنشاء/تنفيذ العقد (`created_at`). سهل تغييره لاحقًا لو قصدك حقلًا آخر.

4. عنوان الصفحة باللغة العربية: **«ملخص الأقساط — المستفيدون»** (بدل «سجل الأقساط والسلف» في القائمة؟ **لا** — نبقي اسم عنصر القائمة كما هو، ونغيّر عنوان الصفحة فقط).

> إن لم ترد على هذه النقاط سأنفذ حسب التوصيات أعلاه.

---

## 5) خطوات التنفيذ

### الخطوة 1 — خدمة التجميع (قاعدة Services في AGENTS.md §2.b)

**ملف جديد:** `app/Services/InstallmentSummaryService.php`

- `summaryQuery(array $filters): Builder` — الاستعلام المجمّع:

```php
Installment::query()
    ->selectRaw("MIN(id) as row_id,
        beneficiary_type,
        employee_id,
        COALESCE(external_national_id, external_name) as external_key,
        COUNT(*) as installments_count,
        SUM(total_amount) as total_sum,
        SUM(paid_amount) as paid_sum,
        SUM(remaining_amount) as remaining_sum")
    ->groupByRaw("beneficiary_type, employee_id, COALESCE(external_national_id, external_name)")
```

  - مفتاح المستفيد للـ Livewire: `internal-{employee_id}` أو `external-{sha1(external_key)}` — تُفكّه دالة في الخدمة عند فتح التفاصيل (بدون تمرير أسماء في URL).
  - تطبيق نطاق الشركات: نفس منطق `HasCompanyScope` (سوبر أدمن = الكل، غيره `whereIn company_id` عبر `CompanyFormService::getAuthorizedCompanyIds()`) + فلتر الفرع عبر `employee.branch_id`.
  - الفلاتر: `search` (users.name / external_name / الأرقام الوطنية / employee_number)، `company_id`، `branch_id`، `transaction_type`، `beneficiary_type`، `status`.
  - مع eager loading: `employee.user`, `employee.details`, `employee.company` — وتُرتب افتراضيًا حسب المتبقي تنازليًا (الأكثر مديونية أولًا).
  - `paginate($perPage)` على الاستعلام المجمّع (مدعوم في MySQL، وحجم البيانات صغير).

- `getStats(array $filters): array` — القيم الأربع للبطاقات (من نفس الفلاتر قبل التجميع): عدد المستفيدين (`COUNT(DISTINCT ...)` بعد التجميع)، عدد الأقساط، مجموع المسدد، مجموع المتبقي.

- `getBeneficiaryDetails(string $key): ?array` — بيانات رأس النافذة + مصفوفة أقساط المستفيد (مع `installmentType`) مرتبة بالتاريخ.

- `exportRows(array $filters): Collection` — صفوف CSV للتصدير.

### الخطوة 2 — صفحة Filament المخصصة (نمط `ListFaqs`)

**ملف جديد:** `app/Filament/Resources/Installments/Pages/InstallmentsSummary.php`

- `extends Filament\Resources\Pages\Page` + `protected static string $resource = InstallmentResource::class;`
- `getView(): 'filament.resources.installments.pages.installments-summary'`
- `canAccess(): bool` → `auth()->user()->can('viewAny', Installment::class)` (نفس حماية المورد).
- خصائص Livewire عامة للفلاتر + `public ?string $detailsKey = null;` + `openDetails()` / `closeDetails()` + `deleteInstallment(int $id)` (تحقق صلاحية + Notification عربية — نمط `ListFaqs`).
- `getHeaderActions()`: زر «إنشاء قسط» (نفس صلاحية Create)، زر «تصدير CSV»، زر «العرض التفصيلي» (رابط إلى `/detailed`).
- حقول محسوبة: `$this->summary` (paginated)، `$this->stats`، `$this->details`.

### الخطوة 3 — نقل الجدول القديم إلى مسار فرعي

**تعديل:** `InstallmentResource::getPages()`:

```php
'index'    => InstallmentsSummary::route('/'),
'detailed' => ListInstallments::route('/detailed'),
'create'   => CreateInstallment::route('/create'),
'edit'     => EditInstallment::route('/{record}/edit'),
```

- `ListInstallments` يبقى كما هو (بدون تغييرات جوهرية) + زر رأس «العرض الملخص» يعود للصفحة الرئيسية.

### الخطوة 4 — الواجهة (Blade)

**ملف جديد:** `resources/views/filament/resources/installments/pages/installments-summary.blade.php`

- نفس بنية وتنسيق `general-reports.blade.php` (ننسخ الأنماط اللازمة داخل الملف كي لا نمسّ صفحة التقارير المستقرة): شبكة البطاقات، شريط الفلاتر، جدول RTL، الترقيم، زر التصدير.
- النافذة (Modal) عبر Alpine: `x-data` + `wire:model` على `$detailsKey`، حالة تحميل `wire:loading`، إغلاق بـ Escape/خلفية النافذة.

### الخطوة 5 — ملفات اللغة (لا نصوص مضمنة — AGENTS.md §5.5)

- إضافة `installments.summary.*` إلى `lang/ar/finance.php` و`lang/en/finance.php` بنفس المفاتيح: عناوين البطاقات، أعمدة الجدولين، أزرار (عرض التفاصيل، إغلاق، تصدير، العرض التفصيلي/الملخص)، الحالتان «مسدد/متبقٍ»، رسالة الحذف والتنبيهات.

### الخطوة 6 — الاختبارات والتحقق (AGENTS.md §5.6)

1. `php -l` على كل ملف PHP متغير + `vendor\bin\pint` عليها.
2. `php artisan view:clear` بعد تعديل الـ Blade.
3. **Feature test** بسيط: `tests/Feature/InstallmentSummaryServiceTest.php` — إنشاء مستفيد داخلي وخارجي بعدة أقساط والتأكد من صحة التجميع (الأعداد والمجاميع) وحساب البطاقات (الخدمة تتعامل مع مبالغ مالية).
4. فتح `/installments` في المتصفح: التحقق من التجميع، الفلاتر، البحث، النافذة، التصدير، الترقيم، وصلاحيات مستخدم محدود الشركة.
5. تحديث `docs/SYSTEM_CHEATSHEET.md` بالخدمة الجديدة (قاعدة Continuous Knowledge Base Sync).

---

## 6) الملفات المتأثرة

| الملف | النوع | الغرض |
|---|---|---|
| `app/Services/InstallmentSummaryService.php` | **جديد** | منطق التجميع والإحصاءات والتفاصيل والتصدير |
| `app/Filament/Resources/Installments/Pages/InstallmentsSummary.php` | **جديد** | الصفحة المخصصة (نمط ListFaqs) |
| `resources/views/filament/resources/installments/pages/installments-summary.blade.php` | **جديد** | الواجهة + النافذة |
| `app/Filament/Resources/Installments/InstallmentResource.php` | تعديل | تبديل المسارات (index/detailed) |
| `app/Filament/Resources/Installments/Pages/ListInstallments.php` | تعديل بسيط | زر العودة للعرض الملخص |
| `lang/ar/finance.php` + `lang/en/finance.php` | تعديل | مفاتيح `installments.summary.*` |
| `tests/Feature/InstallmentSummaryServiceTest.php` | **جديد** | اختبار التجميع والمجاميع |
| `docs/SYSTEM_CHEATSHEET.md` | تعديل | توثيق الخدمة الجديدة |

> لا توجد أي تغييرات على Migrations أو `composer.json`.

---

## 7) مخاطر وملاحظات

- **`only_full_group_by`**: التجميع بـ `groupByRaw` مع `COALESCE(...)` — سيتأكد التنفيذ أن كل عمود مُحدد إما مجمّعًا أو ضمن المجموعة.
- **خارجي بنفس الاسم وبدون رقم هوية**: سيندمجان في سطر واحد — حالة نادرة ومقبولة (تُحل بإدخال رقم الهوية).
- **الأداء**: البيانات قليلة (عشرات/مئات السجلات)؛ لا حاجة لمؤشرات جديدة، ونطاق الشركات يُطبق مبكرًا في الاستعلام.
- **النشر على الاستضافة المشتركة (AGENTS.md §3.6)**: المهمة تضيف ملفات أصناف جديدة (Service + Page + Blade) → بعد الرفع مباشرة: `php artisan optimize:clear` ثم إعادة تشغيل PHP من cPanel عند الحاجة.
- **نطاق المهمة**: لا نلمس صفحة سدادات الأقساط (`InstallmentPayments`) ولا التقارير العامة — إن ظهرت ملاحظات فيها تُبلَّغ منفصلة.

---

## 8) معايير القبول

1. `/installments` تعرض سطرًا واحدًا لكل مستفيد مهما كان عدد أقساطه، بالأعمدة: الاسم، رقم الإقامة/الهوية، المؤسسة، عدد الأقساط، المجموع، المسدد، المتبقي.
2. البطاقات الأربع تعكس الفلاتر المطبقة وليس الصفحة المعروضة فقط.
3. الضغط على «عرض التفاصيل» يفتح نافذة بنفس شكل الصورة (ملخص المستفيد + جدول أقساطه + إغلاق)، والحالات «مسدد/متبقٍ» صحيحة.
4. البحث والفلاتر والتصدير والترقيم تعمل، مع احترام صلاحيات الشركات والفروع.
5. `/installments/detailed` يحافظ على الجدول التفصيلي القديم بكامل وظائفه.
6. لا نص عربي مضمن في الكود — كل شيء من ملفات اللغة (ar/en).
