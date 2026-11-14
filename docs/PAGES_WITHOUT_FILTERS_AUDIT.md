# جرد الشاشات التي لا تحتوي على فلاتر (تصفية)

**تاريخ الجرد:** 2026-11-04
**النطاق:** كل موارد Filament داخل `app/Filament/Resources` (55 مورد) + الصفحات المخصصة داخل `app/Filament/Pages` + الصفحات المخصصة داخل الموارد (`TreasurySummary`, `InstallmentsSummary`, `AssetFundStatement`).

**المرجع البصري:** الشاشة المطلوبة هي لوحة «خيارات التصفية والبحث» (مثل صفحة `GeneralReports` / `NotificationsReport`) التي تحتوي على: نوع التقرير، الفرع، الحالة، عدد السجلات، من تاريخ، إلى تاريخ، وبحث.

---

## 1) الخلاصة

- **8 شاشات** لا تحتوي على أي فلاتر على الإطلاق.
- بقية الشاشات (47) تحتوي فعليًا على فلاتر `->filters([...])` (بعضها فلتران فقط مثل الشركة/الفرع، وهذا يُعد موجودًا).
- الشاشتان `Faqs` و `ContractCategories` خارج النمط المعماري المتفق عليه في `AGENTS.md` (5.3): لا يوجد `Tables/` منفصل، والجدول مكتوب داخل الـ Resource أو بصفحة Blade مخصصة — يُفضّل تصحيحهما عند إضافة الفلاتر.

| # | الشاشة | المسار | الملف المسؤول | الوضع الحالي | الفلاتر المقترحة |
|---|--------|--------|----------------|--------------|------------------|
| 1 | صناديق الأصول | `/asset-funds` | `app/Filament/Resources/AssetFunds/Tables/AssetFundsTable.php` | لا يوجد `->filters()` نهائيًا | الحالة، من/إلى تاريخ التأسيس، نطاق الرصيد |
| 2 | صناديق سلفني | `/loan-funds` | `app/Filament/Resources/LoanFunds/Tables/LoanFundsTable.php` | لا يوجد `->filters()` نهائيًا | الحالة، من/إلى تاريخ الإنشاء |
| 3 | صناديق الجمعية | `/rosca-funds` | `app/Filament/Resources/RoscaFunds/Tables/RoscaFundsTable.php` | لا يوجد `->filters()` نهائيًا | الحالة، طريقة التوزيع، من/إلى تاريخ البداية |
| 4 | سداد أقساط السلف | `/loan-fund-loan-payments` | `app/Filament/Resources/LoanFundLoanPayments/Tables/LoanFundLoanPaymentsTable.php` | لا يوجد `->filters()` نهائيًا | العضو، السلفة، مفتوح/مغلق، من/إلى تاريخ القسط |
| 5 | المستخدمون | `/users` | `app/Filament/Resources/Users/Tables/UsersTable.php` | يوجد `->filters([ // ])` **فارغ** | الحالة (نشط/معطل)، الفرع، الأدوار |
| 6 | الأدوار والصلاحيات | `/roles` | `app/Filament/Resources/Roles/Tables/RolesTable.php` | يوجد `->filters([ // ])` **فارغ** | من/إلى تاريخ الإنشاء، الأدوار التي لديها مستخدمون، عدد الصلاحيات |
| 7 | تصنيفات عروض الأسعار والعقود | `/contract-categories` | `app/Filament/Resources/ContractCategories/ContractCategoryResource.php` (الجدول مكتوب داخل الـ Resource) | لا يوجد `->filters()` | الحالة (مفعّل/معطّل)، ترتيب العرض |
| 8 | الأسئلة الشائعة | `/faqs` | `app/Filament/Resources/Faqs/Pages/ListFaqs.php` + `resources/views/filament/resources/faqs/list-faqs.blade.php` | صفحة Blade مخصصة فيها **بحث نصي فقط** بدون فلاتر | الحالة (مفعّل/معطّل) + إبقاء البحث النصي |

---

## 2) تفاصيل كل شاشة

### 1. صناديق الأصول — `/asset-funds`
- **الملف:** `app/Filament/Resources/AssetFunds/Tables/AssetFundsTable.php`
- **الأعمدة:** `fund_number`, `fund_name`, `established_date`, `manager_name`, `balance`, `vouchers_count`, `status`
- **الملاحظة:** الجدول يحتوي على بحث (3 أعمدة `searchable`) لكن لا توجد أي فلاتر.
- **المقترح:**
  - `SelectFilter::make('status')` بخيارات `asset_funds.enums.status.active` / `asset_funds.enums.status.closed` (نفس نمط `TextColumn::make('status')` الموجود).
  - `Filter::make('established_date')->form([DatePicker::make('from'), DatePicker::make('until')])->query(...)`.
  - فلتر نطاق الرصيد (اختياري) `balance` من/إلى.

### 2. صناديق سلفني — `/loan-funds`
- **الملف:** `app/Filament/Resources/LoanFunds/Tables/LoanFundsTable.php`
- **الأعمدة:** `fund_number`, `fund_name`, `target_amount`, `max_loan_limit`, `members_count`, `status`
- **الملاحظة:** لا فلاتر نهائيًا. لا يوجد `company_id` فعّال على الموديل (`LoanFund` الحقول المتعلقة بالشركة معلّقة)، لذا لا تُضاف فلاتر شركة/فرع.
- **المقترح:** `SelectFilter` للحالة (`FundGeneralStatus` عبر `funds.enums.general_status.*`)، وفلتر تاريخ إنشاء `created_at`.

### 3. صناديق الجمعية — `/rosca-funds`
- **الملف:** `app/Filament/Resources/RoscaFunds/Tables/RoscaFundsTable.php`
- **الأعمدة:** `fund_number`, `fund_name`, `members_count`, `monthly_installment`, `total_pool_amount`, `allocation_method`, `start_date`, `status`
- **الملاحظة:** لا فلاتر نهائيًا (نفس ملاحظة الشركة أعلاه: `company_id` معلّق في `RoscaFund`).
- **المقترح:** `SelectFilter` للحالة، `SelectFilter` لطريقة التوزيع، وفلتر نطاق تاريخ البداية `start_date`.

### 4. سداد أقساط السلف — `/loan-fund-loan-payments`
- **الملف:** `app/Filament/Resources/LoanFundLoanPayments/Tables/LoanFundLoanPaymentsTable.php`
- **الأعمدة:** `voucher_number`, `member.full_name`, `installment_date`, `installment_order`, `collected_amount`, `remaining_balance`, `is_closed`
- **الملاحظة:** لا فلاتر نهائيًا، رغم أن الشاشة الأكثر احتياجًا للتصفية (تاريخ/عضو/حالة السداد).
- **المقترح:**
  - `TernaryFilter::make('is_closed')` (مفتوح / مغلق).
  - `SelectFilter::make('member_id')->relationship('member', 'full_name')->searchable()`.
  - `SelectFilter::make('loan_id')->relationship('loan', 'id')->searchable()` (أو رقم السلفة).
  - فلتر نطاق `installment_date`.

### 5. المستخدمون — `/users`
- **الملف:** `app/Filament/Resources/Users/Tables/UsersTable.php` (سطر 66: كتلة فلاتر فارغة `//`)
- **الأعمدة:** `avatar_url`, `name`, `email`, `phone`, `employee.branch.name`, `roles.name`, `status`
- **المقترح:**
  - `SelectFilter::make('status')` (نشط/معطل).
  - `SelectFilter::make('roles')->relationship('roles', 'name')->multiple()`.
  - `SelectFilter::make('branch')` عبر علاقة الموظف: `->options(fn () => Branch::pluck('name', 'id'))->query(fn ($q, $state) => $q->whereHas('employee', fn ($e) => $e->where('branch_id', $state)))`.

### 6. الأدوار والصلاحيات — `/roles`
- **الملف:** `app/Filament/Resources/Roles/Tables/RolesTable.php` (سطر 42: كتلة فلاتر فارغة `//`)
- **الأعمدة:** `name`, `permissions_count`, `users_count`, `created_at`
- **المقترح:** فلتر تاريخ الإنشاء، وفلتر «أدوار لها مستخدمون / بدون مستخدمين»، وفلتر عدد الصلاحيات (أقل من / أكبر من).

### 7. تصنيفات عروض الأسعار والعقود — `/contract-categories`
- **الملف:** `app/Filament/Resources/ContractCategories/ContractCategoryResource.php` (الجدول داخل `table()` في الـ Resource وليس في `Tables/ContractCategoriesTable.php`)
- **الأعمدة:** `name`, `is_active`, `sort_order`
- **المقترح:** `TernaryFilter::make('is_active')` + إبقاء البحث على `name`.
- **ملاحظة معمارية:** عند التنفيذ يُنقل الجدول إلى `Tables/ContractCategoriesTable.php` ويُنقل النموذج إلى `Schemas/ContractCategoryForm.php` تطبيقًا لـ `AGENTS.md` (5.3).

### 8. الأسئلة الشائعة — `/faqs`
- **الملفات:** `app/Filament/Resources/Faqs/Pages/ListFaqs.php` + `resources/views/filament/resources/faqs/list-faqs.blade.php`
- **الوضع:** صفحة `Page` مخصصة (وليست `ListRecords`) مع خاصية `$search` و`getFaqs()`، والـ Blade يحتوي شريط بحث فقط.
- **المقترح:** إضافة فلتر `is_active` (مفعّل/معطّل) و`sort_order`، أو تحويل الصفحة إلى `ListRecords` + `Tables/FaqsTable.php` لتوحيد النمط والحصول على لوحة الفلاتر القياسية مجانًا.

---

## 3) شاشات تم فحصها ولديها فلاتر بالفعل (للتأكيد فقط)

- **صفحات التقارير المخصصة:** `GeneralReports`، `NotificationsReport`، `AssetFundStatement` (فيها `fund_id` / `date_from` / `date_to` / `filter_type`).
- **صفحات الملخصات:** `TreasurySummary` (شركة، نوع المعاملة، بند الدفع، من/إلى تاريخ، بحث)، `InstallmentsSummary` (شركة، فرع، نوع المعاملة، نوع المستفيد، الحالة، بحث).
- **إدارة الترجمة:** `ManageTranslations` (بحث + فلتر ملف).
- **كل جداول الموارد الأخرى:** تحتوي `->filters()` فعّالة، وبعضها يعتمد مساعد `App\Filament\Components\OrgSelect::tableFilterCompany()` / `tableFilterBranch()` (مثل `DailyJournals`, `TreasuryTransactions`).
- **استثناءات ليست شاشات قوائم:** `Dashboard`، `ManageSettings`، `NavigationOrderPage`، `SystemDocumentationPage`، `SmartFileImport`، `LoanFundPolicyPage`، `RoscaFundPolicyPage` (ليست جداول سجلات، فلا حاجة لفلاتر فيها).

---

## 4) هل الشاشات دي فعلًا محتاجة فلاتر؟ ولأي شيء؟

الفرق بين «الصفحة لا تحتوي فلاتر» و«الصفحة محتاجة فلاتر» هو **حجم البيانات وطبيعة البحث اليومي**. التقييم التالي مبني على حقول كل جدول فعليًا:

### أ) يحتاج فلاتر فعلًا — أولوية عالية

| الشاشة | لماذا | الفلاتر المحددة (بأسماء الحقول) |
|--------|-------|--------------------------------|
| **سداد أقساط السلف** `/loan-fund-loan-payments` | الجدول فيه سجل لكل قسط لكل عضو لكل سلفة، فالعدد يكبر بسرعة، والاستخدام اليومي هو «مين عليه قسط متأخر» و«أقساط الشهر ده» | 1) `TernaryFilter::make('is_closed')` (مفتوح / مغلق) — أهم فلتر.<br>2) `SelectFilter::make('loan_fund_member_id')->relationship('member', 'full_name')->searchable()`.<br>3) `SelectFilter::make('loan_fund_loan_id')->relationship('loan', 'id')` (رقم السلفة).<br>4) `Filter` نطاق على `installment_date` (من/إلى).<br>5) فلتر جاهز «المتأخرات» = `installment_date < today AND is_closed = false`. |

### ب) مفيد — أولوية متوسطة

| الشاشة | لماذا | الفلاتر المحددة |
|--------|-------|------------------|
| **المستخدمون** `/users` | العدد يكبر مع كل موظف له حساب، والإدارة تحتاج «مين معطّل» و«مين في فرع كذا» و«مين عنده دور كذا» | 1) `SelectFilter::make('status')` بخيارات `active` / `inactive` (القيم الفعلية في `users.status`).<br>2) `SelectFilter::make('roles')->relationship('roles', 'name')->multiple()`.<br>3) `SelectFilter` على الفرع عبر `employee.branch_id` (`Branch::pluck('name','id')` + `whereHas('employee', ...)`). |
| **تصنيفات عروض الأسعار والعقود** `/contract-categories` | الجدول متعدد الشركات فعليًا (`company_id` موجود في الموديل و`HasCompanyScope` مفعّل)، فمستخدم متعدد الشركات يحتاج تصفية | 1) `OrgSelect::tableFilterCompany()` (موجود وجاهز).<br>2) `TernaryFilter::make('is_active')`. |

### ج) قيمة منخفضة — اختياري (فلتر واحد للتجميل فقط)

| الشاشة | لماذا القيمة منخفضة | الفلتر الممكن |
|--------|----------------------|----------------|
| **صناديق الأصول** `/asset-funds` | عدد الصناديق عادة قليل (1–10)، والبحث بالاسم/الرقم كافٍ | `SelectFilter::make('status')` (`AssetFundStatus`: نشط/مغلق) + نطاق `established_date` |
| **صناديق سلفني** `/loan-funds` | نفس السبب | `SelectFilter::make('status')` (`FundGeneralStatus`: active / completed / suspended) |
| **صناديق الجمعية** `/rosca-funds` | نفس السبب | `SelectFilter::make('status')` + `SelectFilter::make('allocation_method')` (`RoscaAllocationMethod`: lottery / consensus / prearranged) |

### د) غير ضروري — لا يُنصح بإضافة فلاتر

| الشاشة | السبب |
|--------|-------|
| **الأدوار والصلاحيات** `/roles` | عدد الأدوار محدود جدًا (أقل من 10 عادة) والبحث بالاسم يكفي؛ أي فلتر هنا سيكون حشوًا بصريًا |
| **الأسئلة الشائعة** `/faqs` | عدد محدود + شريط بحث موجود بالفعل؛ فلتر `is_active` ممكن لكنه غير مؤثر |

### ملاحظات مهمة قبل التنفيذ

1. **لو الهدف هو «توحيد الشكل» فقط** (ظهور لوحة «خيارات التصفية والبحث» في كل صفحة): أضف **فلتر حالة واحد** لكل شاشة صغيرة، وده أرخص حل وأقل تشويشًا من 4–5 فلاتر.
2. **لو الهدف هو «الفائدة الفعلية»**: ابدأ بـ `سداد أقساط السلف` ثم `المستخدمون` فقط، واترك الباقي كما هو.
3. **عدم توحيد قيم الحالة بين الصناديق:** `AssetFundStatus` = (active/closed)، `LoanFund` = `FundGeneralStatus` (active/completed/suspended)، `RoscaFund` = نص عادي (active/completed/closed). أي فلتر حالة يجب أن يستخدم قيم كل موديل كما هي، وتوحيدها **مهمة منفصلة** (حسب قاعدة: مهمة واحدة = غرض واحد).
4. **الصناديق لا تحتوي `company_id` فعّالًا** (`LoanFund` و`RoscaFund` حقولهما معلّقة، و`AssetFund` بلا عمود شركة) → **لا** تُضاف فلاتر شركة/فرع لها؛ سترجع نتائج فارغة أو مضللة.
5. **`UsersTable` و`RolesTable` فيهما كتلة `->filters([ // ])` فارغة** — الحذف أو التعبئة مطلوب، لأن الكتلة الفارغة تُنتج لوحة فلاتر فارغة عند الفتح.

---

## 5) خطة التنفيذ المقترحة (مهمة واحدة = غرض واحد)

1. **مهمة أ (أعلى أولوية):** فلاتر سداد أقساط السلف (العضو/السلفة/مفتوح-مغلق/التاريخ/المتأخرات).
2. **مهمة ب:** فلاتر المستخدمين (الحالة/الأدوار/الفرع) + تنظيف كتلة فلاتر الأدوار الفارغة.
3. **مهمة ج:** فلتر شركة + حالة لتصنيفات العقود (مع نقل الجدول إلى `Tables/`).
4. **مهمة د (اختيارية):** فلتر حالة واحد لكل من صناديق الأصول/سلفني/الجمعية.

**لكل مهمة:**
- استخدام `SelectFilter` / `TernaryFilter` / `Filter` بنمط `DebtsTable` و`AttendanceTable`.
- كل التسميات من `lang/ar/<module>.php` و`lang/en/<module>.php` (لا نصوص مكتوبة يدويًا).
- `php -l` على كل ملف معدّل + `vendor\bin\pint` + فتح الشاشة للتأكد من ظهور لوحة «خيارات التصفية والبحث».
- تحديث `docs/SYSTEM_CHEATSHEET.md` إذا أُضيف نمط فلتر مشترك جديد.
