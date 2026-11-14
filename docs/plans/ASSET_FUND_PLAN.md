# خطة تنفيذ وحدة «صندوق الأصول»

> الملف المصدر: مذكرة العميل المرفقة (`صندوق الاصول.pdf`).
> تاريخ الإعداد: 2026-11-02.
> الحالة: **مقترح بانتظار موافقة العميل على القرارات المفتوحة (القسم 9).**

---

## 1. فهم الطلب (ملخص المذكرة)

طلب العميل إضافة جديدة في قسم **المالية العامة / المالية** باسم **«صندوق الأصول»**، الفكرة:

- صندوق تُودع فيه مبالغ مستقطعة من أي مؤسسة: فائض مبيعات، مبالغ مخصّصة، أو حتى ودائع.
- المبالغ قد تخص مؤسسة معيّنة أو إدارة معيّنة، وتُحفظ في الصندوق إلى وقت الحاجة.
- الصندوق **خارج التداول العام** (ليس الخزينة العامة)، يُستخدم أيضًا لإيداع مبالغ الزكاة والضريبة عند السداد.
- الإيداع والسحب مسؤولية **مدير الأصول**.
- **لا يُربط الصندوق بأي منشأة ولا بأي موظف**: كل بيانات الصندوق ومديره ومحاسبه **حقول حرة**.

### المتطلبات الوظيفية كما وردت

| # | المتطلب | التفصيل |
|---|---------|---------|
| R1 | إنشاء صندوق حر | اسم الصندوق + تاريخ الإنشاء + نبذة تعريفية + شروط إيداع/سحب الأموال، بلا ربط بأي منشأة أو موظف |
| R2 | مدير الصندوق | حقل حر (اسم) دون ربط بالمنشآت أو الموظفين |
| R3 | محاسب الصندوق | حقل حر (اسم) اختياري، مسؤول عن إدخال سندات الإيداع والسحب |
| R4 | صفحة الصندوق | عند الضغط على اسم الصندوق يظهر إجمالي الأموال الموجودة فيه وكل حركاته |
| R5 | سند إيداع | يزيد رصيد الصندوق، وبه بنود تفصيلية للمبلغ: (نسبة / إجمالي / أخرى / أخرى / المجموع) |
| R6 | خيارات مصدر الإيداع | (١) اختيار المنشأة من قائمة المنشآت، (٢) تسجيل حر + ملاحظات توضّح مصدر المبلغ |
| R7 | سند سحب | ينقص رصيد الصندوق، بنفس بنود سند الإيداع |
| R8 | خيارات جهة السحب | (١) اختيار المنشأة من القائمة (صرف لحسابها)، (٢) تسجيل حر + ملاحظات توضّح إلى أين ذهب المبلغ |
| R9 | كشف حركة الصندوق | كل العمليات (إيداع/سحب) على مدى عمر الصندوق |
| R10 | كشف بتاريخ | تحديد من تاريخ إلى تاريخ، وإخراج كشف مفصّل بالحركة والواردات والمصروفات والكل |
| R11 | حفظ وطباعة | طباعة السند والكشف بصيغة PDF بالعربية (RTL) مع حفظ نسخة |

---

## 2. أين يقع الموديول في النظام

- **المجموعة:** `navigation.public_finance_and_services` (**المالية العامة والخدمات**) — وهي حرفيًا «قسم المالية العامة» المذكور في المذكرة، وتضم حاليًا: الأقساط والسلف، الديون والتحصيل، الخدمات. (المجموعة الأخرى `finance_group` اسمها «المحاسبة» وتضم الخزنة والرواتب؛ لو أراد العميل وضعه بجانب الخزنة ننقله إليها بسطر واحد.)
- **الترتيب:** `navigationSort = 8` (المستخدم حاليًا في هذه المجموعة: 1–7).
- **لا `company_id`:** وفق المذكرة، الصندوق مستقل تمامًا. الاستثناء الوحيد هو `company_id` **اختياري** على مستوى الحركة عندما يختار المستخدم «الجهة من قائمة المنشآت» (R6/R8) — للربط التحليلي فقط، وليس لسكوب الصندوق.
- **تنبيه تسمية:** مورد الخزينة الحالي اسمه «الخزنة وحركة الصندوق»؛ لذلك سنستخدم دائمًا لفظ «صندوق الأصول» كاملًا في كل العناوين لتفادي الالتباس.

---

## 3. تصميم قاعدة البيانات

### 3.1 جدول `asset_funds` (الصناديق)

| العمود | النوع | ملاحظات |
|--------|------|---------|
| `id` | bigint | |
| `fund_number` | string unique | ترقيم تلقائي عبر `CodeGeneratorService` ببادئة `AF` → `AF-0001` |
| `fund_name` | string | مطلوب |
| `established_date` | date | تاريخ الإنشاء (يُدعم بالهجري عبر `HasHijriDates`) |
| `description` | longText null | نبذة تعريفية عن الصندوق ووظيفته |
| `rules_and_conditions` | longText null | شروط إضافة الأموال وسحبها |
| `manager_name` | string | **نص حر** (مدير الصندوق) |
| `manager_phone` | string null | اختياري |
| `accountant_name` | string null | **نص حر** (محاسب الصندوق) |
| `status` | string default `active` | `active` / `suspended` / `closed` |
| `total_deposits` | decimal(14,2) default 0 | رصيد مجمّع مُخزَّن (cache) |
| `total_withdrawals` | decimal(14,2) default 0 | cache |
| `balance` | decimal(14,2) default 0 | **إجمالي الأموال** المعروض (cache) |
| `notes` | text null | |
| `created_by` | FK users nullOnDelete | |
| `metadata` | json null | تواريخ هجرية |
| `timestamps` | | |

فهارس: `fund_number` (unique)، `status`.

### 3.2 جدول `asset_fund_transactions` (سندات الإيداع والسحب)

| العمود | النوع | ملاحظات |
|--------|------|---------|
| `id` | bigint | |
| `asset_fund_id` | FK cascade | |
| `voucher_number` | string unique | `AFD-0001` للإيداع / `AFW-0001` للسحب |
| `type` | string | `deposit` / `withdrawal` (`AssetFundTransactionType`) |
| `transaction_date` | date | مطلوب + هجري |
| `amount` | decimal(14,2) | = مجموع البنود |
| `party_mode` | string | `company` / `free` (`AssetFundPartyMode`) |
| `company_id` | FK nullable nullOnDelete | يُستخدم فقط عند `party_mode = company` |
| `party_name` | string null | اسم الجهة عند التسجيل الحر |
| `purpose` | string null | من أين أتى المبلغ / إلى أين ذهب |
| `balance_after` | decimal(14,2) | رصيد الصندوق بعد الحركة |
| `executed_by` | FK users null | مُنشئ السند (مدير الأصول/المحاسب) |
| `notes` | text null | |
| `metadata` | json null | |
| `timestamps` | | |

فهارس: `(asset_fund_id, transaction_date)`، `type`، `company_id`.

### 3.3 جدول `asset_fund_transaction_items` (بنود المبلغ التفصيلية)

| العمود | النوع | ملاحظات |
|--------|------|---------|
| `id` | bigint | |
| `asset_fund_transaction_id` | FK cascade | |
| `description` | string | تسمية البند: نسبة / إجمالي / أخرى… |
| `percentage` | decimal(5,2) null | النسبة (اختياري) |
| `amount` | decimal(14,2) | |
| `sort_order` | unsigned int default 0 | |
| `timestamps` | | |

> **قاعدة:** `amount` الإجمالي في السند = `SUM(items.amount)`، ويُتحقق منها قبل الحفظ. البند الأول افتراضيًا «المبلغ الإجمالي» بقيمة الإجمالي.

**المخطط:**

```
asset_funds 1 ──── * asset_fund_transactions 1 ──── * asset_fund_transaction_items
      │                        │
      └── manager_name (نص)    └── company_id? (اختياري عند party_mode=company)
```

---

## 4. منطق الأعمال (Services)

### `app/Services/AssetFundService.php`

| الدالة | الوظيفة |
|--------|---------|
| `recordDeposit(AssetFund $fund, array $data): AssetFundTransaction` | ينشئ سند إيداع + بنوده داخل `DB::transaction`، يحسب `balance_after`، ويحدّث أرصدة الصندوق |
| `recordWithdrawal(AssetFund $fund, array $data)` | نفس الشيء للسحب + **منع تجاوز الرصيد** (`ValidationException` برسالة عربية) |
| `recalculateFundBalance(AssetFund $fund): void` | يعيد حساب `total_deposits` / `total_withdrawals` / `balance` من الحركات |
| `recalculateRunningBalances(AssetFund $fund): void` | يعيد تسلسل `balance_after` عند تعديل/حذف حركة قديمة |
| `statement(AssetFund $fund, ?Carbon $from, ?Carbon $to, ?string $type)` | يبني بيانات الكشف مع رصيد افتتاحي وإجمالي وارد/مصروف ورصيد ختامي |
| `canWithdraw(AssetFund $fund, float $amount): bool` | فحص سريع للرصيد |

### `app/Observers/AssetFundTransactionObserver.php`

- يستخدم `#[ObservedBy]` على الموديل (لأن المنطق يتكرر على `creating`/`updating`/`deleted`).
- `creating`: توليد `voucher_number` + `party_mode` → تصفير `company_id` عند `free`.
- `created` / `updated` / `deleted`: إعادة حساب أرصدة الصندوق والتسلسل.
- **منع الحذف** للحركات (مثل `TreasuryTransaction`) لحماية الأرصدة والتسلسل — يُترك خيار «قيد عكسي» لمرحلة لاحقة عند الحاجة.

### `app/Observers/AssetFundObserver.php`

- `creating`: توليد `fund_number` عبر `CodeGeneratorService` (unique على مستوى الجدول، بدون `companyId`).

### الترقيم

| المستند | البادئة | مثال |
|---------|---------|------|
| الصندوق | `AF` | `AF-0001` |
| سند إيداع | `AFD` | `AFD-0001` |
| سند سحب | `AFW` | `AFW-0001` |

---

## 5. واجهة Filament v5

### 5.1 البنية (نفس تخطيط الموارد الحالي)

```
app/Filament/Resources/AssetFunds/
    AssetFundResource.php
    Schemas/AssetFundForm.php
    Schemas/AssetFundTransactionForm.php      ← مشترك بين سند الإيداع والسحب
    Tables/AssetFundsTable.php
    Tables/AssetFundTransactionsTable.php     ← مشترك بين RelationManager والكشف
    RelationManagers/TransactionsRelationManager.php
    Pages/ListAssetFunds.php
    Pages/CreateAssetFund.php
    Pages/EditAssetFund.php
    Pages/ViewAssetFund.php                   ← R4
    Pages/FundStatement.php                   ← R9/R10 (صفحة مخصّصة)
```

### 5.2 شاشة «صناديق الأصول» (List)

- الأعمدة: رقم الصندوق (badge) — اسم الصندوق — مدير الصندوق — تاريخ الإنشاء — **الإجمالي (SAR)** — الحالة — عدد الحركات.
- الإجراءات لكل صف: عرض / تعديل / **سند إيداع** / **سند سحب** / **كشف الحركة**.
- فلاتر: الحالة، تاريخ الإنشاء. بحث: الاسم/الرقم/المدير.
- كارت علوي (Widget): إجمالي أرصدة الصناديق + عدد الصناديق المفتوحة (اختياري، مرحلة 4).

### 5.3 شاشة الإنشاء/التعديل

- قسم «بيانات الصندوق»: الاسم، تاريخ الإنشاء، الحالة، نبذة تعريفية.
- قسم «الإدارة والمسؤولية»: مدير الصندوق (نص حر) + جواله، محاسب الصندوق (نص حر).
- قسم «الشروط والملاحظات»: شروط إيداع/سحب الأموال، ملاحظات.
- كل الحقول **بدون أي Select للمنشآت أو الموظفين** (R1–R3).

### 5.4 شاشة عرض الصندوق (R4)

- Header: اسم الصندوق + رقمه + الحالة.
- ثلاث بطاقات: **إجمالي الإيداعات** / **إجمالي المسحوبات** / **الرصيد الحالي**.
- Infolist: البيانات التعريفية، الشروط، المدير، المحاسب.
- RelationManager «حركات الصندوق»: النوع، رقم السند، التاريخ، الجهة (منشأة/حر)، المبلغ (+/−)، الرصيد بعد، المُنشئ، إجراء طباعة السند؛ مع فلاتر (النوع، التاريخ، المنشأة).
- أزرار في الهيدر: **سند إيداع** / **سند سحب** (Modal بنموذج السند) / **كشف الحركة**.

### 5.5 نموذج السند (R5–R8)

```
قسم «بيانات السند»: النوع (إيداع/سحب، ثابت حسب الزر) — التاريخ — رقم السند (تلقائي) — المنفّذ
قسم «جهة الحركة»:
    party_mode = راديو: [من قائمة المنشآت] [تسجيل حر]
        → company: Select منشأة (بحث + preload)
        → free:    نص «اسم الجهة» + «من أين أتى / إلى أين ذهب»
قسم «تفصيل المبلغ»:
    Repeater للبنود (تسمية + نسبة % + مبلغ) مع «المجموع» محسوب لحظيًا (live)
    + حقل الإجمالي (read-only, dehydrate) = مجموع البنود
قسم «ملاحظات»
```

- في سند السحب: فحص الرصيد لحظيًا وإظهار تحذير إن تجاوز المبلغ الرصيد المتاح (تعطيل زر الحفظ).
- بعد الحفظ: إشعار عربي + خيار «طباعة السند» مباشرة.

### 5.6 كشف الحركة (R9–R11)

صفحة مخصّصة `FundStatement` داخل المورد:

- فلاتر: **من تاريخ** — **إلى تاريخ** — **نوع الحركة** (الكل / واردات / مصروفات) — **(اختياري) منشأة**.
- بطاقات ملخص: رصيد افتتاحي، إجمالي الواردات، إجمالي المصروفات، رصيد ختامي.
- جدول الحركات مع عمود الرصيد المتحرك.
- زر **طباعة / تصدير PDF** يمرّر نفس الفلاتر إلى قالب الطباعة.
- زر **حفظ نسخة** (تنزيل PDF بنفس محرك mPDF).

---

## 6. الطباعة (PDF / mPDF / RTL)

قالبين جديدين بنفس نمط `resources/views/treasury/voucher-print.blade.php`:

| القالب | المحتوى |
|--------|---------|
| `resources/views/asset-funds/voucher-print.blade.php` | ترويسة النظام/الشعار، نوع السند (إيداع/سحب)، رقم السند، التاريخ الميلادي والهجري، بيانات الصندوق، الجهة (منشأة أو حر)، جدول البنود (تسمية/نسبة/مبلغ) + الإجمالي، **تفقيط المبلغ** عبر `TafqeetService`، الملاحظات، توقيعات (مدير الصندوق – المحاسب – المستلم) |
| `resources/views/asset-funds/statement-print.blade.php` | بيانات الصندوق، الفترة، الرصيد الافتتاحي، جدول الحركات بالرصيد المتحرك، إجمالي الوارد/المصروف، الرصيد الختامي، توقيعات |

- يُضاف النوعان إلى `UniversalPrintService::render()`: `asset-fund-voucher` و `asset-fund-statement`، ويستخدمان `ExportPdfService::createMpdf()` (مع `autoArabic = true` و `rtl` وحذف BOM من HTML).

---

## 7. الصلاحيات، القائمة، والترجمة

### الصلاحيات (Spatie + Policies)

- `AssetFundPolicy` و `AssetFundTransactionPolicy` بنفس نمط `RoscaFundPolicy` + `HasSuperAdminBypass`، والحذف ممنوع على الحركات في `before()`.
- إضافة المجموعة إلى `RoleForm::getPermissionGroups()` تحت `public_finance_and_services`:

```
view_any_asset_fund / create_asset_fund / update_asset_fund / delete_asset_fund
view_any_asset_fund_transaction / create_asset_fund_transaction / update_asset_fund_transaction
```

- إضافة مفاتيحها في `lang/ar/permissions.php` و `lang/en/permissions.php`.

### القائمة

- إضافة `navigation.asset_funds` في `lang/ar|en/navigation.php`.
- `getNavigationGroup()` = `navigation.public_finance_and_services`، `navigationSort = 8`، أيقونة `heroicon-o-archive-box`.

### الترجمة

- ملفان جديدان: `lang/ar/asset_funds.php` و `lang/en/asset_funds.php` (نفس المفاتيح حرفيًا): `single/plural/sections/fields/placeholders/helpers/actions/enums/notifications`.

---

## 8. مراحل التنفيذ

### المرحلة 1 — قاعدة البيانات والنماذج (أساس)
- [ ] Migration: `create_asset_funds_tables` (الجدول الثلاثة).
- [ ] `App\Enums\AssetFundTransactionType` (`deposit` / `withdrawal`) و `App\Enums\AssetFundPartyMode` (`company` / `free`).
- [ ] Models: `AssetFund` (casts + `HasHijriDates`)، `AssetFundTransaction`، `AssetFundTransactionItem` + العلاقات.
- [ ] Observers: `AssetFundObserver` (الترقيم)، `AssetFundTransactionObserver` (رقم السند + منع الحذف + إعادة الحساب) مع `#[ObservedBy]`.

### المرحلة 2 — خدمة الصندوق والاختبارات
- [ ] `AssetFundService` بكل الدوال في القسم 4.
- [ ] `tests/Feature/AssetFundTest.php`: إيداع يزيد الرصيد / سحب ينقصه / منع السحب فوق الرصيد / مجموع البنود = الإجمالي / إعادة الحساب بعد التعديل.

### المرحلة 3 — الواجهة (Filament)
- [ ] Resource + Form + Tables + الصفحات الأربع.
- [ ] نموذج السند المشترك + RelationManager الحركات + إجراءي الإيداع/السحب (Modal).
- [ ] صفحة عرض الصندوق ببطاقات الأرصدة.
- [ ] صفحة كشف الحركة بالفلاتر والملخص.

### المرحلة 4 — الطباعة والتقارير
- [ ] قالب سند الإيداع/السحب + زر الطباعة من السند والجدول.
- [ ] قالب كشف الحركة + الطباعة من صفحة الكشف.
- [ ] ربط `UniversalPrintService`.
- [ ] (اختياري) Widget ملخص الصناديق في لوحة التحكم.

### المرحلة 5 — الصلاحيات والتوثيق والتسليم
- [ ] Policies + `RoleForm` + مفاتيح `permissions.php`.
- [ ] `navigation.php` / `asset_funds.php` (ar/en).
- [ ] تحديث `docs/SYSTEM_CHEATSHEET.md` بقسم «صناديق الأصول» (10) وتحديث `AGENTS.md` إن نشأ نمط مشترك جديد.
- [ ] `php -l` على كل ملف، `php artisan view:clear`، `php artisan test --filter=AssetFund`، Pint على الملفات المعدّلة.
- [ ] ملخص تسليم (5.8) يشمل تنبيه `php artisan optimize:clear` بعد الرفع (ملفات Observers/Services/صفحات جديدة).

---

## 9. قرارات تحتاج تأكيد العميل قبل البدء

| # | السؤال | الافتراض المقترح |
|---|--------|------------------|
| Q1 | هل الصندوق مستقل تمامًا بلا أي `company_id`؟ | نعم، مستقل — ويُستخدم `company_id` في الحركة فقط عند اختيار «من قائمة المنشآت» |
| Q2 | هل يُسمح بالسحب الذي يتجاوز الرصيد (رصيد سالب)؟ | **لا** — يُمنع مع رسالة عربية واضحة |
| Q3 | تفصيل المبلغ: بنود متعددة (تسمية + نسبة + مبلغ) أم مبلغ واحد + وصف؟ | **بنود متعددة** كما في المذكرة، والإجمالي = مجموعها |
| Q4 | هل تُولّد حركة في الخزينة العامة مع كل إيداع/سحب؟ | **لا** — الصندوق «خارج التداول العام» حسب المذكرة |
| Q5 | مدير/محاسب الصندوق نص حر فقط أم خيار اختيار مستخدم من النظام أيضًا؟ | **نص حر** (كما طُلب) + حقل جوال اختياري |
| Q6 | هل يُمنع حذف سندات الإيداع/السحب نهائيًا؟ | **نعم** (مثل الخزينة) للحفاظ على الأرصدة |
| Q7 | بادئات الترقيم | `AF` للصندوق، `AFD` للإيداع، `AFW` للسحب |
| Q8 | هل نحتاج ربط السند بمرفق/مستند (إيصال بنكي)؟ | **لا** في المرحلة الأولى (قابل للإضافة لاحقًا) |

---

## 10. خارج النطاق (مؤجل)

- القيود العكسية وتصحيح سندات سابقة بعد اعتمادها.
- ربط الصندوق بالخزينة العامة أو بالتقارير الضريبية.
- تعدد العملات (الافتراضي: ريال سعودي).
- مرفقات السندات ومحاضر الصندوق.
- إشعارات تلقائية عند تجاوز حد معيّن للرصيد.

---

## 11. معايير القبول

1. إنشاء صندوق حر بكل الحقول، بلا أي قائمة منشآت/موظفين.
2. عند فتح الصندوق يظهر إجمالي الأموال + كل الحركات.
3. سند إيداع يزيد الرصيد وسند سحب ينقصه، والإجمالي = مجموع البنود.
4. خياري «من قائمة المنشآت» و«تسجيل حر + ملاحظات» يعملان في الإيداع والسحب.
5. منع السحب فوق الرصيد برسالة عربية.
6. كشف حركة بفترة وبنوع الحركة، مع رصيد افتتاحي/ختامي وإجماليات.
7. طباعة السند والكشف PDF عربي RTL سليم (بلا حروف مقطّعة أو BOM).
8. الصلاحيات تعمل (دور بلا صلاحية لا يرى المورد).
9. `php -l` + `php artisan test` أخضر + Pint نظيف.
