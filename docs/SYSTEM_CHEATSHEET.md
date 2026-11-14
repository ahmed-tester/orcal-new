# Orcal System Architecture & Component Cheat Sheet

ابدأ من [فهرس المشروع](INDEX.md)، ثم اقرأ القسم المناسب هنا ودليل الوحدة المرتبط به. تفاصيل كل موضوع لها مرجع أساسي واحد؛ لا تعتبر الخطط القديمة دليلًا على التنفيذ أو النشر. راجع أيضًا [سجل التغييرات](CHANGELOG.md) و[المشكلات المفتوحة](KNOWN_ISSUES.md) حسب نطاق المهمة.

> **تنبيه دائم للـ AI والمطورين:**
> هذا الملف هو المرجع السريع والأساسي لكافة المكونات والخدمات والـ Scopes المشتركة في النظام.
> **قاعدة إلزامية:** قبل بناء أي شاشة أو كتابة أي كود أو الاستعلام عن بيانات أساسية، يجب الرجوع لهذا الملف لاستخدام الدوال الجاهزة بدلاً من إعادة كتابتها.
> **قاعدة التحديث المستمر:** في حال تم تعديل أو إضافة أي ميزة أساسية أو خدمة مشتركة، يجب تحديث هذا الملف فوراً في نفس المهمة.

---

## 1. الموظفون والجنسيات (Employees & Nationalities)

### 1.1 فلترة الموظفين (Saudi & Foreign Scopes)
- **فلترة الموظفين السعوديين:** `Employee::scopeSaudi()`
  ```php
  Employee::query()->saudi()->get();
  // أو داخل علاقة / استعلام:
  $query->saudi();
  ```
  - **معيار التصنيف المعتمد:**
    - `user.user_type` = `UserType::SAUDI_EMPLOYEE` أو `UserType::OWNER`.
    - أو تفاصيل الموظف `employee_details.nationality` تحتوي على `سعود` أو `saudi`.

- **فلترة الموظفين غير السعوديين / الأجانب:** `Employee::scopeForeign()`
  ```php
  Employee::query()->foreign()->get();
  // أو داخل علاقة / استعلام:
  $query->foreign();
  ```
  - **معيار التصنيف المعتمد:** الموظفون الذين ليسوا ضمن نطاق السعوديين أو يملكون `user_type = FOREIGN_EMPLOYEE` وجنسية غير سعودية.

### 1.2 النماذج المتخصصة (Derived Models)
- **`App\Models\SaudiEmployee`**: يرث من `Employee` ويطبق `GlobalScope('saudi')` تلقائياً.
- **`App\Models\ForeignEmployee`**: يرث من `Employee` ويطبق `GlobalScope('foreign')` تلقائياً عبر `scopeForeign()`.

### 1.3 حقل اختيار الموظف في نماذج Filament
- **دالة الاختيار القياسية:** `CompanyFormService::makeEmployeeSelect()`
  ```php
  use App\Services\CompanyFormService;

  // اختيار موظف عام (جميع الجنسيات):
  CompanyFormService::makeEmployeeSelect(
      name: 'employee_id',
      label: __('tasks.fields.employee'),
      required: true,
  )

  // اختيار موظفين سعوديين فقط:
  CompanyFormService::makeEmployeeSelect(
      name: 'employee_id',
      label: __('tasks.fields.employee'),
      required: true,
      saudiOnly: true, // يفلتر النتائج والبحث التلقائي على السعوديين فقط
  )

  // اختيار موظفين أجانب فقط:
  CompanyFormService::makeEmployeeSelect(
      name: 'employee_id',
      required: true,
      foreignOnly: true, // يفلتر النتائج والبحث التلقائي على الأجانب فقط
  )

  // اختيار موظفين من كافة الشركات (تجاوز نطاق وصلاحيات الشركات والفروع):
  CompanyFormService::makeEmployeeSelect(
      name: 'employee_id',
      saudiOnly: true, // أو foreignOnly: true
      ignoreCompanyScope: true, // يجلب كل الموظفين بغض النظر عن الشركة أو الفرع
  )
  ```

### 1.4 الاسم بالعربي والإنجليزي (Bilingual Employee Names)
- **الاسم بالعربي:** يُحفظ في جدول المستخدمين `users.name` (ويُجلب عبر `$employee->user?->name` أو `$employee->name`).
- **الاسم بالإنجليزي:** يُحفظ داخل حقل الـ JSON المدمج `employees.metadata['name_en']`.
  ```php
  // قراءة الاسم الإنجليزي عبر الـ Accessor:
  $englishName = $employee->name_en; // أو $employee->metadata['name_en'] ?? null

  // تعيين الاسم الإنجليزي:
  $employee->name_en = 'John Doe';
  $employee->save();
  ```
- **البحث ومطابقة الذكاء الاصطناعي (AI & Matching):**
  - دالة `CompanyFormService::employeeSearchResults()` تبحث تلقائياً بالاسم العربي والإنجليزي (`metadata->name_en`).
  - أدوات الـ AI (`CreateSaudiEmployeeTool`, `CreateForeignEmployeeTool`, `CreateEmployeeTool`) تستخرج الاسم الإنجليزي في `name_en` وتحفظه في `metadata['name_en']` تلقائياً مع فصل النصوص المزدوجة (عربي/إنجليزي)، والأسماء الإنجليزية-فقط تُقبل بالاسم اللاتيني كاسم أساسي.
  - خدمة `EmployeeNameMatcher` تدعم البحث والتطابق مع الاسم الإنجليزي المخزن في `metadata['name_en']`.

- **عقد المطابقة الحصري (Exclusive Matching Contract) في `EmployeeNameMatcher` — قاعدة مصيرة للمال:**
  - **الشرط:** أي مطابقة بالاسم (عربي أو لاتيني) تُقبل **فقط** إذا كان المرشّح **حصرياً** (`singleMatch` / `uniqueLatinSubstring`)؛ إذا كان هناك أكثر من موظف مطابق محتمل → لا دمج.
  - **الأسماء الفرعية ممنوعة:** "اقبال حسين" لا تُدمج مع "اقبال حسين مياه" — الـ substring يُقبل فقط عندما تكون الأحرف الزائدة **≤ 2** (اختلاف إملائي مثل ة/ه، وال/الـ). غير ذلك → الموظف غير موجود → `findOrCreate` ينشئ موظف جديد.
  - مسارات الإدخال الرقمية (رقم وظيفي/هوية/إقامة/جوال) — كلها مجمّعة داخل `where()` closure واحدة حتى لا يتجاوز `orWhereHas` پغطية `company_id` (تسريب حصر الشركة).
  - المقارنات التنظيفية (normalized/space-free equality) تعمل على **cache الذاكرة** (`employees()` cached + `compactArabic/compactLatin`) وليس استعلام جدول كامل لكل صف.

- **تدفق الاستيراد الذكي (Smart Import) للقايمات والسداد — الترتيب المصيري:**
  1. حل الشركة أولاً عبر `CompanyFormService::resolveOrCreateCompanyByName()` (**تنشئ الشركة إن لم توجد**) — لا استخدام `LIKE` وحدهما مع fallback صامت.
  2. ثم الموظف: موجود؟ → إرفاق القسط عليه. غير موجود → إنشاءه (وأن يتم ربطه بالشركة المحلولة).
  3. ثم القسط/السداد يُنشأ في شركة الموظف المحلولة. أدوات `CreateInstallmentTool` و `CreateInstallmentPaymentTool` وجبة على هذا الترتيب.
  - **كمية المبلغ صفر** في أي صف = تخطي مع لفة شرح (بدون خطأ).

---

## 2. مكونات النماذج الشائعة (`CompanyFormService`)

جميع المكونات أدناه مهيأة برمجياً لدعم تعدد الشركات (Multi-Tenancy)، الصلاحيات، البحث التلقائي، والتحميل المسبق (`preload`):

| المكون | الدالة | الملاحظات |
| :--- | :--- | :--- |
| **اختيار الشركة** | `CompanyFormService::makeCompanySelect()` | مفلترة حسب صلاحيات المستخدم، تقوم بتصفير الحقول التابعة تلقائياً عند تغييرها. |
| **اختيار الموظف** | `CompanyFormService::makeEmployeeSelect()` | تقبل `companyFieldName`, `saudiOnly`, `foreignOnly`, `ignoreCompanyScope`, `relationshipName`. |
| **اختيار الفرع** | `CompanyFormService::makeBranchSelect()` | مرتبطة بالشركة المحددة وحسابات الفروع المصرح بها للمستخدم. |
| **اختيار القسم** | `CompanyFormService::makeDepartmentSelect()` | تشمل الأقسام العامة (`is_global`) والأقسام التابعة للشركة. |
| **المسمى الوظيفي** | `CompanyFormService::makeJobTitleSelect()` | مرتبطة بالشركة ومرتبة بحسب `sort_order`. |
| **نوع الفاتورة** | `CompanyFormService::makeInvoiceTypeSelect()` | مرتبطة بالشركة والأنواع العامة النشطة. |

---

## 3. توليد الأكواد والحركات التسلسلية (`CodeGeneratorService`)

- **الدالة:** `CodeGeneratorService::generate()`
- **الصيغة المعتمدة:**
  - مع الشركة: `PREFIX-C{company_id}-{seq}` (مثال: `LTR-C53-0001` أو `EMP-C34-0001`).
  - بدون شركة: `PREFIX-{seq}` (مثال: `EMP-0001`).
- **قاعدة الأمان:** فحص عدم التكرار يتم على **مستوى كامل الجدول** لتجنب خطأ `1062 Duplicate entry` في MySQL.
- **تسلسلات مستقلة داخل نفس الجدول:** استخدم المعامل `scopeQuery` لتقييد الصفوف التي يُحسب عليها التسلسل (مثال: سندات الإيداع `DEP-XXXX` وسندات السحب `WDR-XXXX` في جدول `asset_fund_vouchers`).

```php
use App\Services\CodeGeneratorService;

$code = CodeGeneratorService::generate(
    modelClass: Employee::class,
    column: 'employee_number',
    prefix: 'EMP',
    companyId: $companyId,
    padLength: 4,
);

// تسلسل مستقل لنوع معين داخل نفس الجدول
$number = CodeGeneratorService::generate(
    modelClass: AssetFundVoucher::class,
    column: 'voucher_number',
    prefix: 'DEP',
    companyId: null,
    scopeQuery: fn ($query) => $query->where('voucher_number', 'like', 'DEP-%'),
);
```

---

## 4. نظام التقويم المزدوج (هجري / ميلادي)

- **الـ Trait المعتمد في النماذج:** `App\Traits\HasHijriDates`
- **الخدمة المسؤولة:** `App\Services\HijriDateService`
- **طريقة الاستخدام في النموذج:**
  ```php
  use App\Traits\HasHijriDates;

  class Contract extends Model
  {
      use HasHijriDates;

      public function getHijriDateFieldNames(): array
      {
          return [
              'start_date',
              'end_date',
          ];
      }
  }
  ```

---

## 5. تعدد الشركات والصلاحيات (Multi-Company)

- للحصول على معرفات الشركات المصرح بها للمستخدم الحالي:
  ```php
  $companyIds = CompanyFormService::getAuthorizedCompanyIds($user);
  ```
- للحصول على الشركة الافتراضية إذا كان المستخدم مقيداً بشركة واحدة:
  ```php
  $defaultCompanyId = CompanyFormService::getDefaultCompanyId($user);
  ```
- في موارد Filament، استخدم السمة:
  ```php
  use App\Filament\Traits\HasCompanyScope;
  ```
- `HasCompanyScope` يفوض الاستعلام إلى `CompanyAccessScopeService::apply($query)`، ويستخدمها الحضور أيضًا. لقواعد الأدوار، أولوية الفرع، الاستثناءات، وحدود التغطية راجع [دليل نطاق الشركات](modules/COMPANY_ACCESS.md).

---

## 6. الخطابات الرسمية والطباعة (PDF / Letters & Vouchers)

- **محرك الطباعة:** `mPDF`
  - إعدادات RTL واللغة العربية: `autoScriptToLang = true`, `autoArabic = true`, `dir = rtl`.
  - الخط المعتمد: `dejavusans`.
  - إزالة الـ BOM من محتوى الـ HTML دائماً:
    ```php
    $html = preg_replace('/^\xEF\xBB\xBF/', '', $html);
    ```
- **حرية النصوص (No Hardcoded Content):** عدم فرض أي نصوص ثابتة (البسملة، التحيات، الخواتيم) في القالب، بل يتحكم المستخدم بالكامل عبر `RichEditor`.
- **محرك طباعة السندات الموحد (`UniversalPrintService`):**
  - المسار المعتمد: `route('system.print', ['type' => $type, 'id' => $id])`
  - الأنواع المدعومة: `installment-payment` (سندات الأقساط والسلف)، `debt-collection` (سندات تحصيل الديون)، إضافة لسندات الخزينة والعقود والديون عبر مساراتها المخصصة.
  - المعيار القياسي لصفحات السندات: توفير زر "طباعة السند" في الجدول وصفحة التعديل، وزر "حفظ وطباعة السند" (`saveAndPrint` / `createAndPrint`) في صفحات الإنشاء والتعديل.

---

## 7. الخدمات المالية والرواتب (Finance & Payroll)

- **العمليات المعقدة:** لا توضع داخل صفحات أو إجراءات Filament، بل تستدعى من طبقة الخدمات:
  - `PayrollSheetService`: مسيرات الرواتب، الحفظ المجمع، وتحديثات الجداول التفاعلية.
  - `PayrollCalculationService`: البدلات، الخصومات، الغياب، ودالة صرف الراتب `disburseSalary()`.
    - **فصل الديون والأقساط:** أقساط السلف والديون (`Installment`) مفصولة تماماً عن معادلة مسير الرواتب ولا تُخصم آلياً من الراتب الصافي، وتُدار من شاشتها المستقلة `/installments`.
    - **وضع التحصيل (`operation_type = 'collection'`):** نموذج مخصص لتحصيل التزامات أو إيرادات تشغيل من الموظف، يُخفي حقول الرواتب الآلية ويظهر حقلي المبلغ المطلوب تحصيله وبيان التحصيل، وتُسجل كحركة خزينة واردة (`INCOMING`).
    - **استقطاع التحصيل من مسير الراتب:** عند إعداد مسير راتب لموظف لديه سجلات تحصيل سابقة لنفس الشهر، تُخصم مبالغ التحصيل تلقائياً من صافي الراتب ويظهر في الفورم حقل "تحصيل مسبق مخصوم" وبيان التحصيل بوضوح وشفافية.
    - **تجزئة كشف المسير/التحصيل والطباعة (`paymentScope` و `nationalityScope`):**
      - يدعم `PayrollSheetService::buildSheetData` فلتر نطاق الدفع: `'paymentScope' => 'all' | 'paid' | 'unpaid'`.
      - يدعم فلتر نطاق الجنسية الإلزامي: `'nationalityScope' => 'saudi' | 'foreign'` لفصل كشف الموظفين الأجانب كلياً عن السعوديين والملاك.
      - مربع تأشير تفاعلي (Checkbox) أمام اسم الموظف: التأشير ينقل الموظف فورياً لكشف المدفوعين، وإلغاء التأشير يبقيه في كشف غير المدفوعين دون أي إشارة طباعية.
      - أداة الراتب الموحد: إمكانية تطبيق راتب أساسي موحد على كامل الكشف بضغطة زر مع دعم التعديل اليدوي المباشر على أي صف.
      - شريط تمرير علوي متزامن وأزرار تمرير سريعة (يمين/يسار) أعلى الجدول لتسهيل التصفح الأفقي للشاشات المختلفة.
      - زر رجوع موحد على مستوى لوحة التحكم (`PanelsRenderHook::PAGE_HEADER_ACTIONS_BEFORE`) يتيح العودة لصفحة الفهرس أو الصفحة السابقة من أي شاشة إنشاء أو تعديل أو استعراض دون الحاجة لزر المتصفح.
      - يحسب الخدمة تلقائياً أعداد الحالات `counts: ['all', 'paid', 'unpaid']` والمجاميع المالية الخاصة بالنطاق المختار.
      - تتيح شاشة المسير أزرار طباعة سريعة مستقلة لكلا الوضعين (رواتب / تحصيل): "طباعة الكشف كاملاً"، "طباعة من تم الدفع له"، و "طباعة من لم يدفع له"، مع ترويسة تقرير متكيفة رسمياً وحسابات دقيقة للمطابقة المحاسبية.
    - **الأرشيف التاريخي الشجري المنظم للمسيرات والتحصيل (`history-archive`):**
      - تجميع السجلات حسب السنوات المالية عبر `PayrollSheetService::getAvailablePayrollYears()` وملخص الشهور كـ Folder Files عبر `getArchivedMonthsSummary($year, $operationType)`.
      - بطاقات الشهور تظهر كملفات مستقلة (مثل: مسير شهر أكتوبر 2026، تحصيل شهر نوفمبر 2026) تعرض إحصائيات الشهر، الصافي، المدفوع، ونسبة الإنجاز.
      - فتح أي ملف شهر ينقل المستخدم مباشرة إلى الكشف التفاعلي الكامل لذلك الشهر، مع توفير أزرار طباعة سريعة (شامل / مسددين / غير مسددين) وخيار التبديل للجدول المسطح.
  - **الحركات العكسية (Reverse Entries):** عند حذف مسير رواتب أو دفعة تم صرفها بالخزينة، تُنشأ حركة عكسية تلقائياً (`reverseDisbursementPayment` / `reverseDisbursementsForRecord`) لضمان عدم حذف سندات الخزينة التاريخية وتصحيح الرصيد محاسبياً (Audit Trail). راجع [PAYROLL_REVERSE_ENTRY_PLAN.md](file:///d:/company%20projects/orcal%20from%20server/docs/PAYROLL_REVERSE_ENTRY_PLAN.md).
  - **مراقبو دورة حياة المسيرات (Model Observers):** تدار عبر مراقبين رسميين ومسجلين عبر سمة `#[ObservedBy]`:
    - `PayrollRecordObserver`: توليد `record_number`، ضبط الأشهر والتكاليف، وعكس الدفعات عند حذف المسير.
    - `PayrollPaymentObserver`: توليد `payment_number`، مزامنة `paid_amount`، وتنفيذ العكس المالي عند حذف دفعة منفردة.
  - `TreasuryBeneficiaryService`: ربط الحركات النقدية وسندات الصرف مع المستفيدين (موظف سعودي، موظف غير سعودي، مالك، جهة خارجية).
  - **محرك الطباعة الموحد (`UniversalPrintService` + مسار `system.print`):** مسار مركزي `/print/{type}/{id}` يوزع على محرّك الطباعة حسب نوع المستند (`installment-payment`, `debt-collection` عبر `ExportPdfService::exportVoucher`). نمط زر الطباعة المعتمد في السندات: زر "طباعة السند" في الـ Header/الجدول (فتح تاب جديد) + زر "حفظ وطباعة السند" في إنشاء/تعديل (عبر `getRedirectUrl()` في الإنشاء و`$this->js('window.open(...)')` في التعديل). أسماء السننود الطرفية: `treasury.voucher.print`, `contract_vouchers.print`, `exports.debt-voucher`.
  - `InstallmentSummaryService`: تجميع الأقساط والسلف **سطراً واحداً لكل موظف/مستفيد** (داخلي عبر `employee_id`، خارجي عبر رقم الهوية/الاسم). يستخدمها عرض `/installments` الملخص (`InstallmentsSummary`):
    - `getStats()` تحسب 5 بطاقات متكاملة: إجمالي المبلغ (`total`)، إجمالي المسدد (`paid`)، إجمالي المتبقي (`remaining`)، عدد الأقساط (`installments`)، وعدد الموظفين (`beneficiaries`).
    - `getBeneficiaryDetails()` لنافذة تفاصيل الموظف تدمج كشف حساب تفصيلي شامل يجمع كافة حركات الأقساط (`Installment`) وسندات السداد (`InstallmentPayment`) مرتبة زمنياً مع تبويبات سريعة للتصفية داخل المودال (الكل، الأقساط، السدادات). الجدول يحاكي كشف المصدر: `# / التاريخ / نوع الحركة / منفذ العملية / الاقساط / المبلغ المسدد / البيان` + صف إجمالي (`totals.charges` / `totals.paid`)، وسطور الأقساط تعرض «المبلغ المسدد = 0.00» وسطور السداد تعرض «الاقساط = 0.00».
    - `exportRows()` لتصدير Excel. الصفحة المخصصة `InstallmentsSummary` هي مسار `index` للمورد بعنوان «تقرير الأقساط - ملخص الموظفين»، والجدول المسطح القديم متاح على `/installments/detailed`.
  - `TreasurySummaryService`: خدمة لوحة مؤشرات الخزينة وحركات القبض والصرف (`/treasury-transactions`). تستخدمها صفحة `TreasurySummary` (مسار `index` للمورد) لتقديم تجربة سريعة خالية من الزحمة مع الاحتفاظ بالجدول التفصيلي القديم على `/treasury-transactions/detailed`:
    - `getStats()`: تحسب 5 مؤشرات رئيسية (رصيد الخزينة الفعلي الحالي، إجمالي المقبوضات الواردة، إجمالي المنصرفات، صافي حركة الفترة، وعدد العمليات).
    - `transactionsQuery()`: استعلام متقدم يدعم الفلترة السريعة (بحث، مؤسسة، نوع السند، بند المحاسبة، وتاريخ الفترة).
    - نافذة تفاصيل السند (Modal): استعراض سريع لكامل بيانات السند وطباعته برابط مباشر دون إعادة تحميل الصفحة.
    - `exportRows()`: تصدير كشف الحركات المفلترة إلى Excel مع دعم UTF-8 BOM للغة العربية.
  - **قاعدة تخصيص السداد (مهمة):** سطر سداد واحد في الكشف = **سند واحد** بالمبلغ كامل على **قسط واحد** — ممنوع أي waterfall/FIFO يقسّم السند على أكثر من قسط (`CreateInstallmentPaymentTool::resolveTargetInstallment()`: `installment_number` → `installment_date` → أقدم قسط مفتوح). الزيادة عن المتبقي تُسجّل كزيادة موثّقة في `metadata` (`overpaid`, `overpaid_amount`, `remaining_before`) مع تحذير في رسالة الاستيراد.
  - **الدليل التفصيلي الشامل:** راجع وثيقة [PAYROLL_SHEET_GUIDE.md](file:///d:/company%20projects/orcal%20from%20server/docs/PAYROLL_SHEET_GUIDE.md) لمعرفة دورة حياة وحسابات المسير بالكامل.

---

## 11. وحدة صندوق الأصول (Asset Fund)

**المسار:** `app/Filament/Resources/AssetFunds/` و `app/Filament/Resources/AssetFundVouchers/`

### النماذج
- `AssetFund` — الصندوق المستقل (لا `company_id`، كود تلقائي `AF-XXXX`)
- `AssetFundVoucher` — سند إيداع أو سحب (كود تلقائي مستقل لكل نوع: `DEP-XXXX` للإيداع و `WDR-XXXX` للسحب):
  - **سند مدخلات (إيداع)**: المقاول (حر)، المنشأة (ربط بشركة أو حر)، الفاتورة في نظام الزكاة والضريبة، الإجمالي شامل الضريبة، الضريبة، الصافي بعد الضريبة، نسبة ومبلغ الاستقطاع، نسبة ومبلغ نصيب الصندوق، الصافي بعد جميع الخصومات (`total_amount`).
  - **سند سحب (صرف)**: اسم الجهة مصروف لها (حر)، سبب الصرف (حر)، المبلغ المصروف بدون ضرائب، مبلغ الضريبة، المبلغ الإجمالي شامل الضريبة (`total_amount`).
- `AssetFundVoucherItem` — تفاصيل إضافية للسند إن وجدت.

### Enums
- `AssetFundStatus`: `ACTIVE`, `CLOSED`
- `AssetFundVoucherType`: `DEPOSIT`, `WITHDRAWAL`
- `AssetFundSourceType`: `COMPANY` (ربط بمنشأة), `FREE` (إدخال حر)

### الجداول
- `asset_funds` — الصناديق
- `asset_fund_vouchers` — السندات
- `asset_fund_voucher_items` — تفاصيل السندات

### الصفحات
- `/asset-funds` — قائمة وإدارة الصناديق
- `/asset-fund-vouchers` — سندات الإيداع والسحب
- `/asset-fund-statement` — كشف حركة الصندوق (Livewire Page)
- `/asset-fund-statement/pdf` — طباعة كشف PDF (route: `asset-fund.statement.pdf`)

### قواعد مهمة
- تقع الوحدة ضمن مجموعة: **المالية العامة والخدمات** (`public_finance_and_services`).
- الصندوق مستقل تماماً — لا `company_id` إلزامي على الصندوق.
- المدير والمحاسب: يتم اختيارهم عبر `CompanyFormService::makeEmployeeSelect()` من **الموظفين السعوديين فقط** (`saudiOnly: true`, `ignoreCompanyScope: true`) لربط الصلاحيات، مع حفظ أسمائهم تلقائياً في `manager_name` و `accountant_name`.
- الصلاحيات: مدعومة بسياسات `AssetFundPolicy` و `AssetFundVoucherPolicy` مع trait `HasSuperAdminBypass`، وإمكانية وصول المدير والمحاسب لصناديقهم وسنداتها تلقائياً، بالإضافة لصلاحيات Spatie المسجلة في `RoleForm`.
- مصدر السند: إما ربط بمنشأة (`company_id`) أو إدخال حر (`party_name`).
- كشف الحساب والطباعة: مقسمان لجدولين منفصلين (جدول سندات الإيداع 12 عموداً، وجدول سندات الصرف 9 أعمدة) مع صف إجماليات لكل جدول وملخص للرصيد الصافي النهائي، والطباعة بتنسيق أفقي (Landscape A4-L) لمطابقة نموذج الإكسل الرسمي.
- الرصيد يُحسب من: `total_deposits - total_withdrawals` (accessor على Model).
- **نسبة الضريبة الافتراضية:** يدعم الصندوق حقل `default_tax_percentage` (افتراضياً 15%)، وعند اختيار الصندوق في نموذج إنشاء سند إيداع أو صرف، تُسحب هذه النسبة تلقائياً وتُطبق على السند فورياً مع إمكانية تعديلها يدوياً.
- ملفات اللغة: `lang/ar/asset_funds.php` و `lang/en/asset_funds.php`.
- **الدليل التفصيلي للوحدة:** راجع [ASSET_FUND_GUIDE.md](ASSET_FUND_GUIDE.md) لشرح الشاشات ودورة العمل والحسابات والصلاحيات والحدود المعروفة.

---


```
app/Filament/Resources/<Module>/
    ├── <Module>Resource.php
    ├── Schemas/<Module>Form.php      // configure(Schema $schema): Schema
    ├── Tables/<Module>sTable.php     // configure(Table $table): Table
    └── Pages/
        ├── List<Module>s.php
        ├── Create<Module>.php
        └── Edit<Module>.php
```

- **الصفحات الخفيفة (Thin Pages):** لا تحتوي على حسابات مالية أو عمليات حفظ ضخمة.
- **التعريب الإلزامي:** جميع مسميات الحقول والجداول والتنبيهات تقرأ من `lang/ar/<module>.php` و `lang/en/<module>.php` دون أي نصوص إنجليزية أو عربية مكتوبة مباشرة في الكود (`hardcoded`).

---

## 9. إدارة وترتيب القائمة الجانبية (Sidebar Navigation Order)

نظام مرن يتيح للمدير والسوبر أدمن التحكم في ترتيب مجموعات السايدبار والشاشات داخل كل مجموعة بشكل عام على مستوى النظام من واجهة Filament:

- **الخدمة المركزية:** `App\Services\NavigationOrderService`
  - `navigationGroups()`: ترجع مصفوفة `NavigationGroup` بالترتيب المحفوظ في قاعدة البيانات لربطها في `AdminPanelProvider`.
  - `itemOrder()`: خريطة تسلسل الشاشات `[class_or_key => position]`.
  - `save(array $groups, array $items)`: حفظ الترتيب في جدول `settings` بمفتاح `navigation_order` مع تحديث الكاش التلقائي.
  - `reset()`: استعادة الترتيب الافتراضي للنظام.
  - `getCatalog()`: بناء هيكل المجموعات والشاشات لصفحة الإدارة مع دمج أي مجموعات أو شاشات جديدة تلقائياً في النهاية.
  - **قاعدة إلزامية في `getCatalog()`:** تُبنى الملاحة عبر `filament()->getNavigation()` وليس `app(NavigationManager::class)->get()` — لأن تسجيل الصفحات والـ Resources يتم على `$panel->navigationManager`، والاستدعاء المباشر للمدير يرجّع قائمة ناقصة (عناصر السايدبار لا تظهر في صفحة الإدارة).
  - ترتيب العناصر في الكتالوج يُؤخذ كما هو من `OrderedNavigationManager` **بدون أي إعادة فرز** لضمان تطابق صفحة الإدارة مع السايدبار ١٠٠٪.
- **تطبيق ترتيب العناصر:** `App\Filament\Navigation\OrderedNavigationManager`
  - يرث من `Filament\Navigation\NavigationManager` ومربوط في `AppServiceProvider::register()` كـ Scoped Container Override.
  - يعيد فرز العناصر فور توليدها من الـ Resources والـ Pages داخل كل مجموعة وفق الترتيب المحفوظ.
- **شاشة الإدارة:** `App\Filament\Pages\NavigationOrderPage` (`/navigation-order`)
  - تقع تحت مجموعة «الإدارة والصلاحيات» وتتيح سحب وإفلات المجموعات والشاشات (Drag & Drop) مع أزرار الصعود والنزول (Up/Down) للأجهزة اللوحية والموبايل.
  - راجع وثيقة الخطة: [SIDEBAR_NAVIGATION_ORDER_PLAN.md](plans/SIDEBAR_NAVIGATION_ORDER_PLAN.md).


---

## 10. المكونات العامة التفاعلية للوحة التحكم (Universal Panel Enhancements)

- **زر الرجوع الذكي العام (Universal Back Button):**
  - **الملف:** `resources/views/filament/back-button.blade.php`
  - **الربط:** `PanelsRenderHook::PAGE_HEADER_ACTIONS_BEFORE` في `AdminPanelProvider`.
  - **السلوك:** في صفحات الإنشاء والتعديل والعرض يوجه لصفحة الفهرس (Index) للـ Resource، وفي الصفحات الأخرى يعود للخلف عبر تاريخ المتصفح، ويختفي في لوحة المعلومات (Dashboard).

- **شريط التمرير العلوي فائق السلاسة (Universal Top Scrollbar):**
  - **الملف:** `resources/views/filament/global-table-scrollbar.blade.php`
  - **الربط:** `TablesRenderHook::TOOLBAR_AFTER` في `AdminPanelProvider` مع `wire:ignore` و Alpine.js لمنع أي تعارض مع Livewire Morphdom عند تغيير الصفحات (Pagination).
  - **السلوك:** يكتشف تلقائياً أي جدول في النظام (`.fi-ta-content-ctn`) عريض ويحتاج تمرير أفقي، وينشئ شريط تمرير علوي متزامن 1:1 عبر `requestAnimationFrame` مع دعم عجلة الماوس والتوافق الكامل مع RTL والوضع الليلي، ويختفي تلقائياً للجداول القصيرة.

- **الإخفاء التام لأقسام القائمة الجانبية الفارغة/غير المصرح بها (Strict Navigation Permissions Concealment):**
  - **الملفات:** `resources/views/filament/custom-sidebar.blade.php` و `resources/views/filament/sidebar-search.blade.php`
  - **السلوك:** يمنع ظهور أي قسم رئيسي (Navigation Group) كعنوان فارغ أو "جامد" إذا لم يكن للمستخدم صلاحية على أي عنصر داخله، عبر قاعدة CSS الحديثة `.fi-sidebar-group:not(:has(.fi-sidebar-item))` مع فلاتر JS و Alpine.js في شريط بحث القائمة.

---

## 11. تنبيهات استحقاق الديون ومتابعتها (Debts Due Date & Follow-up Alerts)

- صفحة `/debts` تعرض ملخص المدينين عبر `DebtSummaryService`، والسجل التفصيلي في `/debts/detailed`. راجع [دليل الديون](modules/DEBTS.md) لقواعد التجميع والأرصدة والصلاحيات والطباعة.
- **الحقول الأساسية للديون:**
  - `notify_employee_id`: الموظف المحدد لمتابعة الدين وسداده (علاقة `notifyEmployee`).
  - `executed_by`: الموظف الذي سجل المعاملة (علاقة `executor`).
  - `notification_notes`: الملاحظات ونصوص التنبيه المخصصة للمتابعة.
- **الأمر المجدول:** `CheckDebtDueDatesCommand` (`debts:check-due-dates {--days=7}`)
  - يعمل يومياً الساعة 10:00 صباحاً عبر `routes/console.php`.
  - يحدّث الديون المتأخرة إلى حالة `overdue` تلقائياً.
  - يرسل إشعارات قاعدة بيانات Filament Bell للموظف المحدد (`notifyEmployee->user`) مع روابط مباشرة وتفاصيل الدين.
- **خدمة التنبيهات المركزية:** مدمج في `NotificationAlertsService::getActiveAlerts()` ليظهر في تقرير الإشعارات الشامل وبنر لوحة التحكم.
- **الإرسال اليدوي الفوري:** متاح كـ Action في جدول الديون (`DebtsTable`) وصفحة التعديل (`EditDebt`).

---

## 12. معمارية شريط الفلاتر الموحد والمرن (Unified Modular Filter Bar)

- **السيرفس المركزي:** `App\Services\FilterBarService`
  - تطبيق الفلاتر على استعلامات Eloquent في الجداول والصفحات المخصصة:
    `FilterBarService::applyFilters($query, $state, $options)`.
  - الخيارات المدعومة في `$options`:
    - `search_columns`: أعمدة البحث، وتدعم العلاقات المتداخلة مثل `employee.details.national_id`
      و`paymentCollectionItem.sub_item` (تتحول تلقائيًا إلى `whereHas`).
    - `company` + `company_column` (يدعم عمودًا داخل علاقة عند استخدام نقطة).
    - `branch` + `branch_column`.
    - `status` + `status_column` (يتجاهل القيمة `all` ويقبل الـ Enums).
    - `date_column` لتطبيق `date_from` / `date_to`.
    - `where_in`: خريطة `column => value` للفلاتر المتعددة (scalar أو array أو Enum).
    - `extra`: مصفوفة callbacks `fn (Builder $query, array $state)` للفلاتر الخاصة بالصفحة.
  - القيم المتعددة تُطبَّق بـ `whereIn`، والقيمة المفردة تُطبَّق بـ `where`، مع الحفاظ على `0` و`"0"`.
  - **مهم:** يجب تطبيق نطاق الصلاحيات قبل استدعاء `applyFilters` (عبر `HasCompanyScope`
    أو `applyAccessScope()` الخاص بالسيرفس).
- **سمة Livewire للصفحات:** `App\Filament\Traits\HasFilterBarState`
  - توفر الحالة القياسية (`$search`, `$companyId`, `$branchId`, `$dateFrom`, `$dateTo`, `$status`).
  - تُدمج مفاتيحها في الـ QueryString تلقائيًا عبر `queryStringHasFilterBarState()`.
  - `filterBarState(array $additional = [])`: الحالة القياسية + فلاتر الصفحة.
  - `filterBarExtraProperties()`: تُعيد أسماء خصائص الفلاتر الخاصة بالصفحة (تُستخدم في
    `hasActiveFilterBar()` و`resetFilterBar()` و`updated()` تلقائيًا).
  - `hasActiveFilters()` / `resetFilters()` أسماء مختصرة تستخدمها واجهة الفلتر.
  - `getCompaniesProperty()`: شركات المستخدم المصرح بها فقط (تظهر للـ Super Admin كل الشركات).
  - الصفحات التي تفعّل `:show-per-page` تُعرّف خاصية `$perPage` الخاصة بها بقيمتها الافتراضية.
- **مكون الواجهة الموحد (Blade Component):** `<x-filter-bar>` (`resources/views/components/filter-bar.blade.php`)
  - تحكم كامل في إظهار أو إخفاء أي فلتر:
    - `:show-company="true|false"`
    - `:show-branch="true|false"`
    - `:show-dates="true|false"`
    - `:show-status="true|false"`
    - `:show-per-page="true|false"`
    - `:show-search="true|false"`
    - `:multiple-company="true|false"` / `:multiple-branch="true|false"` / `:multiple-status="true|false"`
  - يجب تمرير `:companies` (و`:branches` عند استخدام الفرع) من الـ computed property المصرح بها؛
    لا يقوم المكون بأي استعلام قاعدة بيانات بنفسه.
  - فتحات مرنة (`Slots`) لحقن حقول مخصصة: `prepend`، `append`، و `actions`.
- **مكون الاختيار والبحث المتعدد المخصص:** `<x-filter-select>` (`resources/views/components/filter-select.blade.php`)
  - يدعم البحث السريع الداخلي مع معالجة وتطبيع الحروف العربية (`أإآا`, `ةه`, `يى`).
  - يدعم الاختيار المفرد أو المتعدد (`:multiple="true"`) مع مربعات اختيار (Checkboxes) وأزرار «تحديد الكل» و«إلغاء التحديد».
  - يقبل الخيارات من مصفوفات عادية، Enums، أو مجموعات Eloquent Models مع فك تشفير أي نصوص JSON تلقائياً.

---

## 13. كشف حركة صندوق الأصول وتخصيص الأعمدة والـ PDF (`AssetFundStatementService`)

- **السيرفس المركزي:** `App\Services\AssetFundStatementService`
  - يمثل المرجع الوحيد لتعريفات أعمدة كشف حركة الصندوق (شاشة + PDF)، وبطاقات الإحصائيات، وعمليات حساب الإجماليات.
  - دوال الأعمدة والخيارات:
    - `permanentDepositColumns()` / `permanentWithdrawalColumns()`: الأعمدة الأساسية الثابتة دائماً (المسلسل، التاريخ، المقاول/المنشأة/المستفيد، أرقام السندات والفواتير) - لا تظهر كمربعات اختيار لأنها إلزامية لتوثيق العمليات.
    - `customizableDepositColumns()` / `customizableWithdrawalColumns()`: الأعمدة المالية والضرائب والنسب والملاحظات القابلة للتخصيص.
    - `resolveActiveDepositColumns($selected, $hideTable)` / `resolveActiveWithdrawalColumns($selected, $hideTable)`: دمج الأعمدة الإلزامية مع الأعمدة الاختيارية وفق الترتيب القياسي.
    - `depositColumns()` / `withdrawalColumns()`: كافة الأعمدة المعرفة بالكامل.
    - `summaryCards()`: بطاقات الملخص المالي المتاحة ومسمياتها.
    - `sanitizeColumns($requested, $available)`: تصفية وتطهير الأعمدة الممررة ومنع أي تلاعب.
    - `resolveActiveCards($requested, $filterType)`: تصفية ذكية لبطاقات الملخص حسب نوع العمليات؛ عند اختيار `deposit` (إيداع فقط) تُخفى بطاقة «إجمالي المصروفات (سحب)» لأنها تظهر فقط في الكشف الشامل (`all`)، وعند اختيار `withdrawal` (صرف فقط) يتم إخفاء بطاقة الإيداعات وخانة المصروفات وخانة المبلغ المتاح الإجمالي المكررة، مع الإبقاء على بطاقات الضريبة والنسب والاستقطاع وبطاقة «إجمالي المبلغ المصروف من الصندوق»، وتُعرض كل بطاقات كشف الصرف في سطر واحد.
    - `depositsSummary($deposits)`, `withdrawalsSummary($withdrawals)`, `summary($deposits, $withdrawals)`: الحسابات الموحدة؛ يحسب `total_deposits` المبلغ الإجمالي الكامل الوارد شاملاً الضريبة (`gross_amount`)، ويخصم المصروفات الفعلية دائماً من الأرصدة المتاحة الصافية والإجمالية.
- **تحديد الغرض من الكشف (Statement Purpose & Presets):**
  - كشف شامل (`all`): يعرض كافة الأعمدة والبطاقات.
  - كشف الضريبة فقط (`tax`): أعمدة المبالغ والضرائب وفواتير الزكاة وسندات الصرف والضريبة.
  - كشف النسب والاستقطاعات (`deductions`): نسب الاستقطاع ونسب الصندوق والمبالغ بعد الخصومات.
  - تخصيص يدوي (`custom`): اختيار حر للأعمدة والبطاقات عبر مربعات الاختيار (Checkboxes).
- **بطاقات الأرصدة والملخص المالي في كشف الصندوق:**
  - `total_deposits`: المبلغ الوارد كامل (شامل الضريبة) من واقع إجمالي فواتير وسندات الإيداع.
  - `total_withdrawals`: إجمالي المصروفات (سحب) — يظهر ما صُرف من الصندوق فعلياً، ولا يظهر إلا في الكشف الشامل (`all`).
  - `net_balance`: الرصيد الصافي المتاح (بعد الضريبة والنسب) (الواردات الصافية بعد الخصومات - المصروفات الفعلية)، ويتحول تلقائياً في كشف الصرف إلى «إجمالي المبلغ المصروف من الصندوق» بقيمة موجبة بدون سالب.
  - `gross_balance`: المبلغ المتاح شامل الضرائب والاستقطاعات والنسبة الأخرى (إجمالي كل ما تبقى داخل الصندوق = الرصيد الصافي + مبالغ الضرائب والاستقطاعات ونصيب الصندوق على الإيداعات = إجمالي الواردات كاملة - المصروفات الفعلية)؛ وتُحذف تلقائياً في كشف الصرف لعدم تكرار الأرقام.
  - تظهر بطاقات الأرصدة في صف مستقل بعرض كامل (100%) أسفل كروت الحركات التشغيلية.
- **ربط سندات الصرف بسندات الإيداع (Linked Withdrawals):**
  - الموديل: `AssetFundVoucher` يدعم `disbursement_type` بقيمتين: `general` (صرف حر من كامل الصندوق) أو `linked_deposit` (صرف مخصص مرتبط بسند إيداع محدد عبر `linked_deposit_voucher_id`).
  - العلاقات: `linkedDepositVoucher()` (BelongsTo) و `linkedWithdrawals()` (HasMany).
  - حساب المتاح للصرف: `AssetFundVoucher::getRemainingBalanceForDeposit($depositId, $ignoreWithdrawalId)`.
  - في نموذج سند الصرف: يُعرض رصيد الصندوق المتاح في بطاقة واحدة أعلى النموذج، وقائمة «سند الإيداع المرتبط» تعرض (رقم السند — الجهة — التاريخ) فقط بدون كلمة «متاح» أو تفاصيل إضافية لتجنب تشتيت المستخدم.
  - عمود كشف الصرف: `linked_deposit` يوضح رقم سند الإيداع المرتبط في الشاشة والـ PDF.
- **التوافق التام مع طباعة الـ PDF:**
  - قالب الطباعة `resources/views/pdf/asset-fund-statement.blade.php` ديناميكي 100% ويستهلك نفس تعريفات السيرفس لعرض الأعمدة والبطاقات المحددة بدقة.

---

## 14. مسار المحرك الموحد: تسليم العهدة النقدية والباركود (`Unified Service & Cash Custody`)

منظومة ذكية لربط معاملات الخدمات والمتابعة (`ServiceTransactions`) مع الخزينة (`TreasuryTransactions`):

- **خدمة توليد الباركود النقطي (`BarcodeService`):**
  - **المسار:** `App\Services\BarcodeService`
  - **الدالة:** `BarcodeService::generateSvg(string $code, int $barHeight = 55, bool $showText = true): string`
  - تعتمد داخلياً على معيار Code 128B المدمج في `mpdf` وتولد رسماً متجهياً (SVG) نقي بدون أي مكتبات خارجية إضافية، متوافق 100% مع كافة المتصفحات وقارئات الباركود وكاميرات الجوال.
- **خدمة استلام وتوريد العهدة للخزينة (`TreasuryCustodyService`):**
  - **المسار:** `App\Services\TreasuryCustodyService`
  - `lookupTransaction(string $code, ?int $companyId = null): ?array`: معاينة المعاملة فور كتابة أو مسح كودها (مُقيّدة بمنشآت المستخدم المصرح بها عبر `CompanyFormService::getAuthorizedCompanyIds()`).
  - `receiveCustody(string $code, ?int $receivedBy = null): array`: تنفيذ عملية التوريد داخل `DB::transaction` + `lockForUpdate`:
    1. التحقق من صحة كود المعاملة وعدم توريدها مسبقاً.
    2. **رفض** المعاملات غير النقدية (`custody_status = not_applicable` — دين على العميل/التزام على المكتب) والمعاملات غير المرتبطة بمنشأة.
    3. ربط `company_id` المستخدم المصرح به، وإنشاء/إيجاد بند تحصيل موحّد `PaymentCollectionItem` (تصنيف `SERVICES`) لأن كل حركة خزينة إلزامية تُربط ببند.
    4. إنشاء حركة خزانة واردة `TreasuryTransaction` (`INCOMING`) بالمبلغ المورد.
    5. تحديث حالة المعاملة في `ServiceTransaction` إلى `in_treasury` وتسجيل معرف حركة الخزينة وتوقيت الاستلام.
    6. إسقاط العهدة عن الموظف فورياً.
  - `receiveCustodyHeaderAction(): Action`: enforcement واحد مُستخلَص في الخدمة — يستخدمه الرأس في صفحتي الخزينة معاً (بدلاً من تكرار الكود).
- **قواعد المطابقة والإغلاق (مصدر الحقيقة единي في `ServiceTransaction::saving()`):**
  - المطابقة `matched` لا تتحقق إلا بـ: إيصال سداد + إشعار إيداع + حالة العهدة أُغلقت (`in_treasury` / `settled`) أو غير نقدية (`not_applicable`).
  - إذا الكاش ما زال «عهدة على الموظف»، يبقى الوضع `pending_proofs` حتى لو اكتملت الإثباتات — ولا يمكن الإغلاق قبل التوريد.
- **عناصر الواجهة المدمجة:**
  - زر «رمز QR التسليم» في جدول `ServiceTransactionsTable` يعرض مودال تفاعلي للـ QR وتفاصيل العهدة للموظف (`filament.modals.service-handover-barcode`, نصوص مترجمة بالكامل).
  - زر «استلام عهدة نقدية بالـ QR» كـ Header Action في شاشتي الخزينة (`TreasurySummary` و `ListTreasuryTransactions`) يستدعي `TreasuryCustodyService::receiveCustodyHeaderAction()`.
  - زر «إرفاق الإثباتات والمطابقة» يمنع الإغلاق قبل توريد الكاش ويُظهر تنبيه خطأ، وإلا يغلق المعاملة إلى «مطابقة ومكتملة 100%».
- **اختبارات:** `tests/Feature/TreasuryCustodyTest.php` — 3 اختبارات تغطي: رفض التوريد لمعاملة دين، لزوم توريد الكاش قبل المطابقة، ومنع التوريد المكرر.

---

## 15. مسار المحرك الموحد: الإيداع البنكي المجمع والمبالغ المعلقة والسداد الشخصي (`Unified Batch Deposits & Suspense Engine`)

تطبيق للمرحلة (B) من المخطط الكامل للنظام الموحد لربط دورة السداد والإيداع وحسابات المعاملات:

### 15.1 حالات سير العمل الموحدة (`App\Enums\OperationWorkflowStatus`)
- **الحالات التشغيلية:**
  - `NEW` (`new`): جديدة
  - `IN_PROGRESS` (`in_progress`): قيد التنفيذ
  - `EMPLOYEE_CUSTODY` (`employee_custody`): عهدة موظف
  - `AT_TREASURY` (`at_treasury`): بالخزنة (متبقي سداد/إيداع)
  - `AWAITING_APPROVAL` (`awaiting_approval`): بانتظار اعتماد (سداد شخصي للموظف / طلب إلغاء)
  - `MISMATCHED` (`mismatched`): غير مطابقة (فرق مالي أو نقص)
  - `SUSPENSE_AMOUNT` (`suspense_amount`): مبلغ معلق
  - `ESCALATED` (`escalated`): مصعد للإدارة
  - `RETROACTIVE_SETTLEMENT` (`retroactive_settlement`): تسوية لاحقة
  - `COMPLETED` (`completed`): مكتملة ومطابقة
- تشتق الحالة تلقائياً داخل الموديل `ServiceTransaction::resolveWorkflowStatus()` وفق أولوية رقابية صارمة.

### 15.2 خدمة الإيداع والسداد الموحد (`App\Services\OperationsSettlementService`)
- **الإيداع البنكي المجمّع (`createBatchDeposit`):**
  - ينشئ دفعة إيداع واحدة في `UnifiedBatchDeposit` برقم متسلسل فريد (`BDEP-C{company_id}-{seq}`).
  - ينشئ بنود الربط وتفصيل كل معاملة في `UnifiedBatchDepositItem`.
  - يحدث تلقائياً بيانات الإشعار البنكي وحالة الإيداع في `metadata` لكل معاملة مشمولة في الدفعة، ويعيد تقييم مطابقتها.
  - متاح كـ Bulk Action في `ServiceTransactionsTable` لمعالجة عدة معاملات دفعة واحدة.
- **توثيق السداد الحكومي والسداد الشخصي (`recordGovernmentDisbursement`):**
  - تسجيل الرسوم الحكومية وقناة الدفع (سداد، مدى، قوى، أبشر، بلدي) والمرفقات.
  - دعم خيار **السداد الشخصي للموظف (`is_self_paid = true`)**: يحول المعاملة فوراً إلى `awaiting_approval` ويقيد طلب الاعتماد.
- **اعتماد السداد الشخصي للموظف (`approveSelfPaidDisbursement`):**
  - اعتماد سداد الموظف من ماله الخاص وتوثيق المعتمد، ورفع حالة الانتظار لقيده كمستحق للموظف.
- **المبالغ المعلقة والمجهولة (`recordSuspenseAmount`, `suggestMatchingTransactions`, `allocateSuspenseAmount`):**
  - حجز الحوالات المجهولة في `UnifiedSuspenseAmount` برقم متسلسل (`SUSP-C{company_id}-{seq}`).
  - محرك اقتراح المعاملات المطابقة لمبلغ الحوالة في نفس المنشأة.
  - ترحيل واعتماد ربط الحوالة بالمعاملة الصحيحة وتحديث مدفوعاتها تلقائياً.

### 15.3 النماذج والجداول الجديدة
- `UnifiedBatchDeposit` (`unified_batch_deposits`): دفعات الإيداع المجمع بالبنك مع حصر الشركات (`company_id`).
- `UnifiedBatchDepositItem` (`unified_batch_deposit_items`): بنود الربط والتوزيع لكل معاملة.
- `UnifiedSuspenseAmount` (`unified_suspense_amounts`): سجل المبالغ والحوالات المعلقة.

---

## 16. محرك المطابقة التلقائية وإدارة النواقص والمهام (`Operations Reconciliation & Deficiency Tasks Engine`)

تطبيق للمرحلة (C) من النظام الموحد لضبط ومطابقة المعاملات والعمليات آلياً وإدارة النواقص:

### 16.1 خدمة المطابقة المركزية (`App\Services\OperationsReconciliationService`)
- **التقييم الرباعي للمطابقة (`evaluate`):**
  - فحص أركان المعاملة الـ 4:
    1. **المستفيد والعميل (`missing_counterparty`):** التحقق من تحديد المستفيد/العميل.
    2. **العهدة النقدية (`custody_handover`):** التحقق من توريد الكاش للخزينة إن وجد مبلغ مدفوع، وعدم بقائه بذمة الموظف.
    3. **الإثباتات الرسمية (`missing_gov_receipt`, `missing_bank_deposit`):** التحقق من رفع إيصال السداد الحكومي وإشعار الإيداع البنكي (أو الإيداع المجمع).
    4. **الفروق والاعتمادات (`amount_mismatch`, `self_paid_approval`):** التحقق من عدم وجود فروق غير معتمدة أو سداد شخصي بانتظار الاعتماد.
- **التشغيل والمزامنة التلقائية (`reconcile`):**
  - يقفل المعاملة كمكتملة (`completed`) ومطابقة 100% فور اكتمال الأركان.
  - يزامن المهام آلياً عبر `syncDeficiencyTasks`: ينشئ مهام للنواقص الجديدة، ويغلق تلقائياً (`status = resolved`) أي مهمة استوفيت شروطها دون تدخل يدوي.
- **إغلاق المهام يدوياً (`resolveTask`):**
  - تسجيل إغلاق المهمة والملاحظات وإعادة فحص مطابقة المعاملة فوراً.
- **المطابقة المجمعة للشركات (`runBatchReconciliation`):**
  - فحص ومطابقة جميع معاملات الشركة غير المكتملة دفعة واحدة وحساب عدد المطابق وغير المطابق.

### 16.2 موديل مهام النواقص (`App\Models\UnifiedDeficiencyTask`)
- **الجدول:** `unified_deficiency_tasks`
- **التوليد المتسلسل:** بادئة `TSK-C{company_id}-{seq}` عبر `CodeGeneratorService`.
- **العلاقات:**
  - `company()`: المنشأة التابع لها.
  - `transaction()`: المعاملة المعنية (`service_transaction_id`).
  - `responsibleEmployee()`: الموظف المسؤول عن إكمال النقيصة.
  - `assignedBy()`, `resolvedBy()`: المستخدم المسؤول والمعتمد.
- **النطاقات (`Scopes`):**
  - `scopeOpen()`: تصفية المهام المفتوحة (`status = 'open'`).
  - `scopeResolved()`: تصفية المهام المعالجة والمغلقة (`status = 'resolved'`).

### 16.3 إدماج الواجهة في جدول المعاملات (`ServiceTransactionsTable`)
- **مؤشر النواقص في العمود:** عمود `workflow_status` يعرض تحت الشارة عدد النواقص المفتوحة بلون تنبيهي عند وجودها.
- **إجراء الصف «إعادة فحص المطابقة» (`run_reconciliation`):** فحص فوري للمطابقة وتحديث الحالة وإظهار إشعار فوري.
- **إجراء الصف «النواقص والمهام» (`deficiency_tasks`):**
  - شارة بعدد المهام المفتوحة.
  - نافذة مودال (`filament.modals.deficiency-tasks-list`) تعرض النواقص المفتوحة وتفاصيلها والمسؤول عنها، وتاريخ الاستحقاق، بالإضافة للنواقص المعالجة.
  - نموذج مدمج لاختيار أي مهمة مفتوحة وإغلاقها بتدوين ملاحظات المعالجة.
- **الإجراء المجمع «إعادة فحص ومطابقة المحددة» (`batch_reconcile`):** Bulk Action لفحص مجموعة معاملات دفعة واحدة.

---

## 17. مسار الحوكمة الرقابية: حواجز الإغلاق، التصعيد، وسجل التدقيق الثابت (`Unified Governance, Guards & Audit Trail`)

تطبيق للمرحلتين (D) و (E) من النظام الموحد لفرض الانضباط المالي والرقابي وتتبع الحركات:

### 17.1 حواجز الإغلاق والإلغاء الصارمة (`Closing & Cancellation Guards`)
- مفعّلة على مستوى `ServiceTransactionObserver::updating`:
  1. **حاجز إكمال المعاملة (`Completion Guard`):**
     - عند محاولة تعديل حالة الخدمة إلى مكتملة (`service_status = 'completed'`)، يتحقق النظام آلياً من نتيجة التقييم الرباعي للمطابقة عبر `OperationsReconciliationService::evaluate()`.
     - إن لم تكن المعاملة مطابقة بنسبة 100% (`matched`)، يرمي النظام استثناء تحقق فوري (`ValidationException`) بالرسالة: `services_tracking.settlement.guard_cannot_complete` موضحاً النواقص المانعة للإغلاق.
  2. **حاجز إلغاء المعاملة مع وجود عهدة (`Cancellation Guard`):**
     - يُحظر إلغاء أي معاملة (`status = 'cancelled'`) إذا كانت العهدة النقدية لا تزال بذمة الموظف (`custody_status = 'on_employee'`) ويوجد مبلغ مدفوع، لمنع ضياع الكاش أو إسقاط المسؤولية دون توريد.

### 17.2 محرك التصعيد وفك التصعيد للإدارة (`Escalation Workflow`)
- **إجراء الصف «تصعيد للإدارة» (`escalate`):**
  - متاح للمعاملات غير المكتملة وغير المصعدة.
  - يتيح للموظف رفع عائق تشغيلي (عدم تجاوب العميل، تعطل المنصة الحكومية، اختلاف التسعير، مانع نظامي، غير ذلك) مع تدوين التفاصيل ورفع مرفق الإثبات.
  - يستدعي `OperationsSettlementService::escalateToManagement()` الذي ينقل المعاملة لحالة `ESCALATED` ويوثق الملاحظات والمرفقات والمستخدم ويسجل حركة تدقيق فوري.
- **إجراء الصف «معالجة وفك التصعيد» (`resolve_escalation`):**
  - يظهر فقط للمعاملات المصعدة للإدارة.
  - يتيح للمشرف أو المدير اتخاذ القرار وتدوين التوجيه وفك التصعيد عبر `OperationsSettlementService::resolveEscalation()`.
  - يعيد المعاملة لمسارها التشغيلي ويفحص مطابقتها آلياً فورياً ويسجل حركة تدقيق.

### 17.3 سجل التدقيق الرقابي غير القابل للتعديل (`UnifiedAuditLog & OperationsAuditService`)
- **الجدول:** `unified_audit_logs` (حقول: `company_id`, `service_transaction_id`, `user_id`, `action_type`, `old_status`, `new_status`, `payload`, `ip_address`, `user_agent`, `created_at`).
- **الموديل الصارم:** [`UnifiedAuditLog`](file:///d:/company%20projects/orcal%20from%20server/app/Models/UnifiedAuditLog.php)
  - `UPDATED_AT = null` (سجل تراكمي إلحاقي فقط Append-Only).
  - حماية Eloquent قطعية تمنع التعديل والحذف نهائياً:
    ```php
    static::updating(fn () => false);
    static::deleting(fn () => false);
    ```
- **الخدمة المركزية:** [`OperationsAuditService::record()`](file:///d:/company%20projects/orcal%20from%20server/app/Services/OperationsAuditService.php)
  - موثقة ومدمجة تلقائياً في كل الحركات الجوهرية:
    1. إنشاء المعاملة (`created`).
    2. استلام الكاش بالخزينة وإسقاط العهدة (`custody_received`).
    3. توثيق السداد الحكومي والسداد الشخصي (`disbursement_recorded`).
    4. اعتماد السداد الشخصي للموظف (`self_paid_approved`).
    5. تخصيص دفعة الإيداع البنكي المجمّع (`batch_deposited`).
    6. فحص المطابقة وتحديث الحالة (`reconciled`).
    7. إغلاق مهمة النقيصة يدوياً أو آلياً (`task_resolved`).
    8. تصعيد المعاملة للإدارة (`escalated`).
    9. معالجة وفك تصعيد المعاملة (`escalation_resolved`).
- **واجهة المسار الزمني في Filament:**
  - زر «سجل التدقيق الرقابي» (`audit_trail`) في كل صف في `ServiceTransactionsTable`.
  - يعرض نافذة منبثقة تفاعلية عبر [audit-trail-timeline.blade.php](file:///d:/company%20projects/orcal%20from%20server/resources/views/filament/modals/audit-trail-timeline.blade.php) توضح الخط الزمني الكامل لكل إجراء، المستخدم القائم به، الوقت، الـ IP، وتفاصيل الـ Payload بالأرقام.

### 17.4 المنظومة الموحدة القابلة لإعادة الاستخدام (Polymorphic & Reusable Actions)
- **الـ Trait المشترك للمعاملات المالية:** [`HasFinancialWorkflow`](file:///d:/company%20projects/orcal%20from%20server/app/Traits/HasFinancialWorkflow.php)
  - يتيح لأي نموذج مالي (`PayrollRecord`, `Debt`, `Installment`, `TreasuryTransaction`, `ServiceTransaction`):
    - `auditLogs(): MorphMany` (سجل تدقيق متعدد الأشكال عبر `auditable_type` و `auditable_id`).
    - `deficiencyTasks(): MorphMany` (مهام نواقص مرتبطة عبر `taskable_type` و `taskable_id`).
    - `recordAudit(string $actionType, ...): UnifiedAuditLog`
    - `createDeficiencyTask(string $deficiencyType, ...): UnifiedDeficiencyTask`
- **مكوّن الأكشنز الموحد لجداول Filament:** [`UnifiedOperationsActions`](file:///d:/company%20projects/orcal%20from%20server/app/Filament/Components/UnifiedOperationsActions.php)
  - يقدم أكشنز جاهزة للاستدعاء بسطر واحد في أي جدول:
    - `UnifiedOperationsActions::auditTrail()` — نافذة الخط الزمني للتدقيق.
    - `UnifiedOperationsActions::deficiencyTasks()` — استعراض وحل مهام النواقص مع شارة العدد.
    - `UnifiedOperationsActions::handoverBarcode()` — باركود/QR التسليم والعهدة.
    - `UnifiedOperationsActions::escalate()` و `resolveEscalation()` — التصعيد للإدارة وفك التصعيد.
- **الخدمات المركزية:**
  - `OperationsSettlementService::attachProofs()` و `processBatchDeposit()`
  - `OperationsReconciliationService::reconcileCollection()`


## 18. ملخص الحضور والانصراف

- نقطة الدخول: `AttendanceResource` → `AttendanceSummary`؛ الجدول التاريخي باقٍ في `/attendances/detailed`.
- الخدمة: `AttendanceSummaryService` للتجميع والمؤشرات والتفاصيل والتصدير. `monthStart()` هو مدخل قراءة الشهر لتفادي انزلاق نهاية الشهر.
- نطاق الوصول: `CompanyAccessScopeService::apply()` مستخدم عبر المورد وخدمة الملخص، وفق [دليل نطاق الشركات](modules/COMPANY_ACCESS.md).
- الملخص وكشف الموظف للسعوديين عبر `Employee::saudi()`. لا تفترض أن هذا القيد ينطبق تلقائيًا على كل إجراء قديم.
- [دليل الحضور](modules/ATTENDANCE.md) هو المرجع الأساسي للملفات والمسارات والفلاتر والقيم الافتراضية ووحدات الحساب والطباعة والتحقق والنشر.
- القرارات والحدود المتبقية مسجلة في [المشكلات المفتوحة](KNOWN_ISSUES.md)؛ نتيجة اختبار تاريخية لا تثبت توفر ملفاته أو نشر آخر نسخة.

---

## 19. Notifications: shared queue dispatch


- `NotificationDispatcher::send($record, $notification, $excludeUserIds)` is the common entry point. It resolves recipients, attaches record metadata and the Filament URL, and returns the number queued. `translated(...)` builds a notification from existing translation keys without repeating payload construction in ticket/operations services.
- `NotificationRecipientService` requires the model's update permission and an allowed update policy for ordinary users, plus company/branch scope. Approval, ticket-management, and escalation permissions alone do not replace update permission. Existing super-admin and owner rules still apply within their established scope.
- `BaseSystemNotification` implements `ShouldQueue`, waits for transaction commit, and uses database notifications only. `shouldSend()` checks the recipient and current record again at delivery, so revoked permissions, inactive accounts, and deleted records do not receive queued notifications.
- SMS remains disabled centrally at both channel and service entry points. No outbox, deduplication table, deterministic notification IDs, or new database schema is introduced.
- Event templates, report labels, and manual reminder messages live in `lang/ar/notifications.php` and `lang/en/notifications.php`. Configuration stores translation keys rather than translated text.
- Ticket, operations, debt/violation commands, notification events, manual report reminders, and task-assignment notifications use the shared dispatcher. Temporary UI success/error messages remain local Filament toasts.
- Existing policies remain authoritative: `TicketPolicy::update` currently permits super-admins (and the existing owner bypass). A plain `update_ticket` permission does not override a denied policy.
- Targeted verification: `php vendor/bin/phpunit tests/Feature/NotificationsRefactorTest.php`. Queue workers must consume the configured queue and be restarted after deployment; run `php artisan optimize:clear` after upload according to the deployment guide.

## ملخص الإجازات وقرارات الحالة

- المرجع الأساسي: [دليل الإجازات](modules/LEAVES.md)، آخر تحقق محلي 2026-11-10.
- `LeaveSummaryService::summaryQuery()` و`daysInYear()` يحسبان تقاطع الإجازة مع السنة؛ null/`all` يعني كل السنوات. موقف اليوم مستقل عن السنة ويراعي تاريخ العودة الفعلي.
- `canChangeStatus()` و`changeStatus()` يفرضان `change_leave_status` ونطاق الموظف، ويقصران قرارات الملخص على الطلب المعلق. `override_leave_balance` يتجاوز فحص الرصيد فقط. الرفض يسجل السبب والمستخدم والوقت داخل transaction؛ تفاصيل الاختبارات والحدود في الدليل.

---

## 20. الدورة المالية الموحدة (القبض ← العهدة ← التوريد ← السداد)

- المرجع الأساسي: [دليل الدورة المالية](modules/FINANCIAL_WORKFLOW.md)، آخر تحقق محلي 2026-11-11.
- **التفعيل:** `ServiceFinancialWorkflowService::isManaged($transaction)` يقرأ `metadata.financial_workflow_version = 1`. تُنشأ اللقطة في `ServiceTransactionObserver::created()`، والمعاملات الموسومة `financial_flow = legacy` تبقى على المسار القديم.
- **الحساب:** `FinancialSummaryService::for($transaction)` هو المصدر الوحيد للمقبوض والعهدة والوارد للخزنة والرسوم المسددة والمتبقي ومستحق الموظف والفائض. لا تستخدم `paid_amount` كحقيقة مالية.
- **القبض:** `FinancialReceiptService` (تسجيل/تأكيد/عكس) مع `request_key` فريد وبصمة بيانات. التسجيل يقبل `FinancialPaymentMethod` أو قيمته النصية. القبض النقدي يُحتسب عند التسجيل كعهدة على المحصل ويخفض رصيد العميل. توريده إلى الخزنة يحتاج تأكيد الاستلام. غير النقدي يتطلب وجهة ومرجعًا وإثباتًا عند التأكيد.
- **التوريد:** `CashHandoverService` يحجز المتاح من كل سند تحت قفل، ويؤكد بمبالغ لكل بند ومعرّف `confirmation_key`، وينشئ حركة خزنة واردة واحدة. الزيادة تُسجل مبلغًا معلقًا، والنقص `difference_amount` سالب.
- **السداد:** `FinancialDisbursementService` يفرض قاعدة رسوم معتمدة، ويقيد `treasury_direct` فورًا، ويبقي `employee_personal` بانتظار الاعتماد ثم يعوّضه على دفعات. لا سداد بدون تمويل متاح للمباشر.
- **الخزنة:** `FinancialTreasuryService::post()` هو الكاتب الوحيد للمسار المالي؛ يقفل صف الشركة، ويسلسل `balance_after`، ويرفض الرصيد السالب، و`assertLinkable()` يمنع ربط حركة واحدة بأكثر من مستند.
- **المطابقة:** `OperationsReconciliationService` له فرع `evaluateManaged()` يقرأ الملخص فقط، ومهام النواقص تستخدم `task_key = svc-fin:{id}:{type}` لمنع التكرار.
- **الصلاحيات:** مفاتيح `record_service_receipt` و`confirm_service_receipt` و`request_cash_handover` و`confirm_cash_handover` و`record_service_disbursement` و`approve_self_paid_disbursement` و`pay_employee_reimbursement` و`reverse_financial_transaction` ضمن `OperationsPermissionService`، مع فحص الشركة والفرع داخل الخدمة.
- **النشر:** يلزم migrations جديدة و`php artisan optimize:clear`؛ لا ترحيل تلقائي للبيانات القديمة. تفاصيل الاختبارات والحدود في الدليل.

---

## 21. تنظيم مسارات الروابط ومسارات الاختبار (Routing & Test Routes)

- **مسارات الإنتاج (`routes/web.php`):** مخصصة حصرياً للمسارات الفعلية للإنتاج (طباعة السندات، مستندات الـ PDF، بروتوكولات الأجهزة ZKTeco ADMS). يُمنع وضع أي راوت تجريبي أو seeder فيها.
- **مسارات الاختبار والتهيئة (`routes/test.php`):** أي مسار تجريبي، أو أمر تشغيل Seeder عبر المتصفح، أو مزامنة صلاحيات أو تجارب إشعارات، يُسجل حصرًا داخل `routes/test.php`.
- **آلية التسجيل (Laravel 12):** يتم تسجيل `routes/test.php` عبر الدالة المعيارية `withRouting(then: ...)` داخل `bootstrap/app.php` بمجموعة `web` middleware لضمان بقاء مسارات النظام نظيفة ومعزولة.
