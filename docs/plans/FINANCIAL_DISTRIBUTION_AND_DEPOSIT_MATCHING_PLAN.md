# خطة «التوزيع المالي» ومطابقة الإيداع وعزل الفائض

> **النوع:** وثيقة تحليل وتصميم وخطة تنفيذ — **لم يُعدَّل أي ملف كود في هذه المهمة** (ملف جديد فقط).
> **المرجع الأم:** `docs/plans/UNIFIED_OPERATIONS_FINANCE_PLAN.md` (خاصة البند 5.2/د `financial_allocations` والبند 8.3 أرصدة الخزنة).
> **المرجع التنفيذي الحالي:** `docs/SYSTEM_CHEATSHEET.md` (الأقسام 14–17).
> **تاريخ الفحص:** 2026-11-10.
> **القاعدة الحاكمة:** أي إضافة تكون **إضافية (additive)** فوق الخزينة القائمة، ولا تمس `balance_after` ولا تُعيد بناء وحدة عاملة.

---

## 1. الخلاصة التنفيذية (ثلاث نقاط)

1. **الموجود اليوم** يغطي: العهدة → التوريد للخزينة → حركة خزينة واحدة → الإيداع المجمع → المطابقة الرباعية → النواقص → التدقيق. لكن **حركة التوريد تُقيَّد كرقم واحد (1,500 = 1,500)** ولا تُفرَّز داخلياً إلى «سداد رسوم / مخصص إيداع / فائض». لا يوجد دفتر توزيع.
2. **الفجوة الجوهرية** ثلاث فجوات فقط: (أ) لا دفتر توزيع ولا أوعية أرصدة، (ب) لا «التزامات إيداع» معلنة لمسؤول الإيداع ولا إشعارات له، (ج) لا مطابقة إيداع تُعزل الزائد تلقائياً. المؤشرات لكل جهة/موظف/محصل غير موجودة أيضاً.
3. **الطريق الآمن:** المرحلة (0) تُنجَز **بدون أي تعديل على ملف قائم ولا أي migration** (خدمات قراءة + صفحات Filament جديدة تُكتشف تلقائياً). ثم المرحلة (1) تضيف **دفتر توزيع فرعي** فوق `TreasuryTransaction` (جدول جديد + موديل + خدمة + Observer)، وهذا يحتاج **سطراً واحداً فقط** في ملف قائم (أو صفر تعديل مقابل تشغيل يدوي/مجدول).

---

## 2. ما هو موجود فعلاً في الكود (فحص دقيق)

| # | المكوّن | الملف | ما يفعله فعلياً | الحد / الملاحظة |
| :- | :--- | :--- | :--- | :--- |
| 1 | العملية الموحدة | `app/Models/ServiceTransaction.php` | رقم `SRV-C{n}-{seq}`، مبالغ، `metadata` (cost_bearer, custody_status, receipts, reconciliation…)، `workflow_status` مشتقة في `resolveWorkflowStatus()` | كل التقسيم المالي محفوظ في `metadata` فقط، ولا توجد أعمدة `gov_fee_amount` / `office_fee_amount` حقيقية |
| 2 | توليد الحالة التلقائي | `ServiceTransaction::booted()->saving()` | يضبط `custody_status = on_employee` عند وجود `paid_amount`، ويضبط `reconciliation_status` | لا يوزّع المبلغ على أوعية |
| 3 | Observer العملية | `app/Observers/ServiceTransactionObserver.php` | فاتورة تلقائية + دين على العميل + حواجز الإغلاق/الإلغاء + تسجيل تدقيق `created` | **لا ينسخ `Service.default_gov_fee`** ⇒ لا تُعرف حصة السداد عند الإنشاء |
| 4 | توريد العهدة | `app/Services/TreasuryCustodyService.php::receiveCustody()` | ينشئ **حركة خزينة واردة واحدة** ببند تحصيل عام `SERVICES` («توريد عهدة خدمات العملاء»)، يسقط العهدة، يسجّل تدقيقاً، ثم `reconcile()` | ❗ المبلغ لا يُفرَّز: 1,500 تظهر كـ «توريد عهدة» فقط، لا 1,000 سداد + 500 إيداع |
| 5 | الإيداع المجمع | `OperationsSettlementService::createBatchDeposit()` + `UnifiedBatchDeposit` / `UnifiedBatchDepositItem` | دفعة `BDEP-C{n}-{seq}` + بنود لكل معاملة + تحديث `metadata` وإعادة المطابقة | ❗ مجموع البنود **يُشتق من `paid_amount`** (أو `gov_fee_amount`)، ولا تحقق مقابل «التزام إيداع»، ولا يعزل زائداً |
| 6 | الإيداع المجمع من الجدول | `ServiceTransactionsTable::create_batch_deposit` (Bulk Action) | يختار المستخدم معاملات ويُدخل بيانات البنك | ❗ لا توجد طابور «مطلوب إيداع»، ولا إشعار لمسؤول الإيداع |
| 7 | السداد الحكومي | `OperationsSettlementService::recordGovernmentDisbursement()` | يسجل `gov_fee_amount`, `gov_payment_channel`, إيصال، سداد شخصي بانتظار اعتماد | مبلغ السداد **يُدخل يدوياً** ولا يُقارن بحصة سداد محسوبة |
| 8 | المبالغ المعلقة | `UnifiedSuspenseAmount` + `recordSuspenseAmount()` / `suggestMatchingTransactions()` / `allocateSuspenseAmount()` | جدول `SUSP-C{n}-{seq}` + اقتراح مطابقة + ترحيل | ❗ **لا واجهة ولا شاشة ولا إشعار**، والخِدمة غير مستدعاة من أي مكان (كود نائم) |
| 9 | المطابقة والنواقص | `OperationsReconciliationService` + `UnifiedDeficiencyTask` | تقييم رباعي، مزامنة مهام، حل يدوي/آلي | لا ركن «توزيع» ولا ركن «إيداع مطابق للالتزام» |
| 10 | التدقيق | `OperationsAuditService` + `UnifiedAuditLog` (Append-Only) | 9 أنواع حركة مسجلة | لا يوجد `allocated` / `deposit_matched` / `excess_detected` |
| 11 | الإشعارات | `OperationsNotificationService` | 4 إشعارات: تصعيد، فك تصعيد، استلام عهدة، نواقص | ❗ **لا إشعار لمسؤول الإيداع ولا لمسؤول المبالغ المعلقة** |
| 12 | الخزينة والمؤشرات | `TreasuryTransaction`, `TreasurySummaryService`, `FinancialStatsOverviewWidget` | رصيد تراكمي بـ `balance_after`، مؤشرات الشركة (رصيد/إيراد/رواتب/أقساط) | لا أرصدة «مخصص سداد / مخصص إيداع / فائض»، ولا مؤشرات لكل موظف/محصل |
| 13 | محور التصنيف الموجود | `App\Enums\PaymentItemCategory` (`rent`, `salary_payment`, `salary_collection`, `violations`, `services`, `invoices`…) | كل حركة خزينة مرتبطة إلزامياً ببند تحصيل | محور جاهز لإعادة الاستخدام كـ«وعاء» بدل اختراع محور جديد |
| 14 | الصلاحيات الجاهزة | `RolesAndPermissionsSeeder` | `manage_batch_deposits`, `manage_suspense_amounts`, `resolve_deficiency_task`, `view_operational_dashboard` | يمكن استهداف «مسؤول الإيداع» بها **بدون** إنشاء دور جديد |
| 15 | الجداول بصيغة Morph | `UnifiedBatchDepositItem.depositable` و `UnifiedSuspenseAmount.allocatable` | ربط متعدد الأشكال جاهز | يمكن بناء دفتر التوزيع بنفس النمط |
| 16 | تسجيل الموارد/الصفحات | `AdminPanelProvider` | `discoverResources` + `discoverPages` | ✅ إضافة أي Resource/Page جديد **لا تحتاج تعديل أي مزوّد** |

### 2.1 ملاحظات فنية دقيقة (مهمة للتصميم)
- `financial_split` مفاتيح ترجمة (`gov_fee_amount` / `office_fee_amount`) **موجودة في اللغة وغير مستخدمة في الكود** ⇒ الفكرة معتمدة لغوياً ولم تُنفَّذ برمجياً.
- `Service.default_gov_fee` موجود في الكتالوج وفي نموذج الخدمة، لكن **لا يُنسَخ** إلى العملية عند الإنشاء.
- `UnifiedBatchDepositItem` يسمح بـ`amount` مستقل عن المبلغ الحقيقي ⇒ لا يوجد ضابط «مجموع البنود = المبلغ المودع».
- `TreasuryTransaction` يمنع الحذف نهائياً ⇒ أي تصحيح لاحق **بحركة عكسية** فقط (لا تعديل أرصدة).

---

## 3. الفجوة مقابل طلبك (خريطة 1:1)

| طلبك | الوضع الحالي | الحكم |
| :--- | :--- | :-: |
| 1) توزيع المبلغ تلقائياً في الخزنة حسب نوع الخدمة: رصيد الإيجارات، رصيد السدادات، رصيد الموظفين، مخصص الإيداع… | حركة واحدة ببند عام `SERVICES`، بلا تفريع | ❌ |
| 2) أن يذهب كـ«رصيد دفع» في المؤشرات عند المؤسسة/الموظف/المحصل/الموظف المسلم، كأرقام إجمالية وتكاليف | مؤشرات على مستوى الشركة فقط (4 بطاقات عامة) | ❌ |
| 3) السداد: مبالغ السداد الفعلي لكل خدمة | `metadata.gov_fee_amount` لكل عملية (إدخال يدوي) | ⚠️ جزئي |
| 4) الإيداع: إشعار تلقائي لمسؤول الإيداع بوجود مبالغ مطلوب إيداعها | لا طابور التزامات ولا إشعار | ❌ |
| 5) عند الإيداع: مطابقة المودع حسب كل سداد، والزائد يُفرز لحاله مع «وين راح / ليش / كيف جاء» | لا مطابقة التزام، ولا عزل زائد، وجدول المعلّق بلا واجهة | ❌ |

**ملاحظة حاكمة:** البند (1) متوقف على **مصدر بيانات الحصة** (كم للجهة وكم للمكتب). الحل في المرحلة (2) أدناه، وفي المرحلة (0) نعمل بقواعد استنتاج مرنة.

---

## 4. التصميم المقترح

### 4.1 المبدأ المعماري
> **دفتر توزيع فرعي (Sub-Ledger) فوق الخزينة، لا بديل عنها.**

- `TreasuryTransaction` يبقى **مصدر الحقيقة الوحيد للرصيد النقدي** (`balance_after`) — لا يُلمس.
- التوزيع يُسجَّل في جدول مستقل يوزّع **نفس مبلغ الحركة** على أوعية. `SUM(allocations) = treasury_transaction.amount` (يُتحقق في الخدمة).
- حذف/تعديل أي توزيع بعد التنفيذ ممنوع: التصحيح = توزيع عكسي + تدقيق.
- كل وعاء يمكن ربطه بحركة سداد (`settlements`) أو ببند إيداع (`unified_batch_deposit_items`).

### 4.2 الأوعية (Allocation Buckets)
| الوعاء (Enum) | الاسم العربي | المصدر/المعنى | من ينفّذه |
| :--- | :--- | :--- | :--- |
| `SETTLEMENT` | رصيد السدادات (رسوم جهات) | حصة تُدفع للجهة الحكومية/المورد | مسؤول السداد |
| `BANK_DEPOSIT` | مخصص الإيداع البنكي | حصة تُورَّد للبنك (أتعاب المكتب/صافي الإيراد) | مسؤول الإيداع |
| `RENT` | رصيد الإيجارات | مبالغ إيجارات/عقود تُقبض وتُصرف لجهتها | مسؤول العقود/الخزنة |
| `EMPLOYEES` | رصيد الموظفين | مستحقات موظفين (رواتب/تعويض سداد شخصي/سلف) | الخزنة/الرواتب |
| `OFFICE_FEE` | أتعاب المكتب | صافي إيراد المكتب (بديل/تفصيل للإيداع) | الخزنة |
| `EXCESS` | فائض/زائد | ما زاد عن الالتزام الحقيقي للعملية | مسؤول المبالغ المعلقة |
| `SUSPENSE` | مبلغ معلق مجهول | حوالة غير معروفة المصدر | مسؤول المبالغ المعلقة |

> لتفادي تعارض التسميات: `EXCESS` = زائد **معروف المصدر** (نعرف من أي عملية/دفعة جاء)، و`SUSPENSE` = مجهول **قبل** الربط. عند الربط يتحول `SUSPENSE → EXCESS/RENT/…` أو إلى العملية الصحيحة.

### 4.3 مصدر قواعد الحصة (Rule Resolver) — أولوية صارمة
```
1) metadata.gov_fee_amount / metadata.office_fee_amount   (إدخال يدوي صريح)
2) Service.default_gov_fee (إن > 0)                      (كتالوج الخدمة)
3) metadata.client_share / office_share                  (حالة shared)
4) allocation_rules حسب تصنيف الخدمة (Service.category)   (قاعدة المنشأة)
5) افتراضي: المبلغ بالكامل ⇒ BANK_DEPOSIT مع وسم "split_undetermined"
```
- قاعدة (5) مقصودة: بدل تخمين خاطئ، نضع المبلغ في الإيداع **بوسم أنه غير مفصول**، فيظهر في الطابور بتحذير يحتاج قراراً. هذا يمنع أي ترحيل مالي وهمي.

### 4.4 جدول دفتر التوزيع (مقترح — المرحلة 1)
`financial_allocations`

| العمود | النوع | الوصف |
| :--- | :--- | :--- |
| `id` | bigint | |
| `company_id` | FK companies + index | إلزامي (تعدد المنشآت) |
| `allocation_number` | string(50) unique | `ALC-C{n}-{seq}` عبر `CodeGeneratorService` |
| `allocatable_type`, `allocatable_id` | morphs + index | `ServiceTransaction` / `ContractVoucher` / `DebtCollection` / `PayrollRecord` / `ViolationTransaction` |
| `treasury_transaction_id` | FK treasury_transactions + index | حركة الخزينة المصدر (لا تُحذف) |
| `bucket` | string(30) + index | القيم من `AllocationBucket` |
| `amount` | decimal(14,2) | المبلغ المخصص في هذا الوعاء |
| `settled_amount` | decimal(14,2) default 0 | ما نُفِّذ منه فعلياً |
| `status` | string(20) + index | `pending` / `partial` / `done` / `reversed` |
| `obligation_kind` | string(20) nullable | `settlement` / `deposit` / `none` (الالتزام الذي يتولّد) |
| `responsible_role_permission` | string(60) nullable | مثل `manage_batch_deposits` لاستهداف المستخدمين |
| `source_split` | string(20) nullable | `manual` / `service_default` / `shared` / `category_rule` / `undetermined` |
| `settled_at`, `settled_by` | timestamp/nullable, FK users | إقفال الوعاء |
| `metadata` | json | تفاصيل حرة (لا أرصدة) |
| `timestamps` | | |

**فهارس:** `(company_id, bucket, status)`, `(allocatable_type, allocatable_id)`, `(treasury_transaction_id)`, `(obligation_kind, status)`.

**ضوابط التطبيق (في الخدمة لا في قاعدة البيانات):**
- `SUM(amount)` لكل `treasury_transaction_id` = `TreasuryTransaction.amount` (تسامح 0.01).
- `settled_amount <= amount` دائماً.
- لا حذف؛ `deleting` يعيد `false` كما في `TreasuryTransaction`.

### 4.5 الالتزامات (Obligations) — الجزء الذي يخص «مسؤول الإيداع»
الالتزام ليس جدولاً جديداً: هو **صف توزيع** بحالة `pending` و`obligation_kind` محدد.

- **طابور مطلوب إيداع** = `bucket = BANK_DEPOSIT` و `settled_amount < amount` ⇒ `المتبقي = amount - settled_amount`.
- **طابور مطلوب سداد** = `bucket = SETTLEMENT` و `settled_amount < amount`.
- الطابور يُصفّى بـ`company_id` تلقائياً عبر `CompanyFormService::getAuthorizedCompanyIds()` (نفس نمط النظام).

### 4.6 مطابقة الإيداع وعزل الفائض (السيناريو الحاسم)
عند إيداع بمبلغ `D` مقابل التزامات محددة مجموعها `O`:

| الحالة | السلوك |
| :--- | :--- |
| `D = O` | مطابقة كاملة: كل بند يُسدد بالكامل، الحالة `done`، `deposit_matched` في التدقيق. |
| `D > O` | مطابقة كاملة + **فائض `E = D - O`**: (1) إنشاء/تحديث بند في `UnifiedBatchDepositItem` بمبلغ `O` فقط، (2) إنشاء `UnifiedSuspenseAmount` بمبلغ `E` مع `source = deposit_excess` + `batch_deposit_id` + البنك + رقم الإشعار + تاريخ + المستخدم المودع، (3) إشعار فوري لمن يحمل `manage_suspense_amounts`، (4) الحركة المالية لا تتغير (المال دخل الخزينة بالفعل). |
| `D < O` | مطابقة جزئية: توزيع `D` على البنود بأقدم-أولاً (FIFO) افتراضياً مع إمكان تعديل يدوي للمبالغ، والباقي يظل `pending`/`partial`. لا يُسمح بحفظ `SUM(items) > D` أبداً. |

**المطلوب في شاشة الفائض («وين راح / ليش / كيف جاء») — حقول إلزامية:**
`source_type` (batch_deposit | single_deposit | bank_transfer | manual)، `source_id`، `bank_name`، `reference_number`، `received_date`، `deposited_by_user_id`، `reason_code` (`deposit_excess` | `unidentified_transfer` | `overpayment` | `other`)، `notes`، `status` (`pending`/`allocated`/`refunded`)، وسجل تدقيق كامل.
> الحقول موجودة جزئياً في `unified_suspense_amounts` (المبلغ/التاريخ/البنك/المرجع/المودع/المرفق/الحالة) ويُضاف: `source_type`, `source_id`, `reason_code`, `created_by` + index — عبر **migration إضافية جديدة** (لا تعديل القديمة).

### 4.7 الخدمات الجديدة (كل الحسابات هنا — القاعدة 2.b)
| الخدمة | المسار | أبرز الدوال |
| :--- | :--- | :--- |
| `AllocationRuleResolver` | `app/Services/FinancialDistribution/` | `resolve(ServiceTransaction): array<int, array{bucket, amount, source}>`, `isDetermined()`, `warnings()` |
| `DistributionPlanService` (قراءة فقط) | نفس المجلد | `planForTransaction()`, `planForCompany()`, `assertBalanced()` — يُستخدم في المرحلة 0 |
| `FinancialDistributionService` (كتابة) | نفس المجلد | `distribute(TreasuryTransaction)`, `reverse(allocation, reason)`, `recalculateObligation()` |
| `DepositObligationService` | نفس المجلد | `pendingDeposits(?companyId)`, `summaryByCompany()`, `overdue()` |
| `DepositMatchingService` | نفس المجلد | `preview(items, depositAmount)`, `commit(...)`, `splitExcess()`, `assertSplitValid()` |
| `ExcessAnalysisService` | نفس المجلد | `detectUnmatchedDeposits(companyId)`, `traceability(suspense)`, `linkToTransaction()` |
| `FinancialDistributionMetricsService` | نفس المجلد | `forCompany()`, `forEmployee()`, `forCollector()`, `forCashHandler()`, `treasuryBuckets()`, `indicator()` |
| `FinancialDistributionNotificationService` | نفس المجلد | `notifyDepositObligation()`, `notifyDepositReceived()`, `notifyExcessDetected()`, `notifySettlementDue()` |

**قاعدة الإشعارات (حسب تعليمات المشروع):** نصوص الإشعارات في `lang/ar/financial_distribution.php` + `lang/en/...` فقط، ولا نصوص عربية داخل PHP/Blade.
**قاعدة التوقيت:** كل إشعار داخل `DB::afterCommit()` حتى لا يُرسل إشعار لعملية تراجعت.

### 4.8 المؤشرات المطلوبة (المعادلات)
| الجهة | المؤشر | المعادلة |
| :--- | :--- | :--- |
| المؤسسة | محصّل / بالخزنة / مسدّد / مودع / متبقي سداد / متبقي إيداع / فائض / تكلفة | مجاميع `financial_allocations` + `covered` لكل bucket |
| الموظف المحصّل (`executed_by`، ولاحقاً `collector_employee_id`) | إجمالي محصّل، إجمالي مسلَّم، عهدة حالية، عدد العمليات، متوسط زمن التسليم | من `ServiceTransaction` + `EmployeeCustodyEntries` (لاحقاً) |
| الموظف المسلم للكاش | إجمالي ما سلّمه، ما تبقى عهدة، مبالغ لم تُودع بعد | من `metadata.treasury_received_at` + allocations |
| مسؤول الإيداع | مطلوب إيداع اليوم/المتأخر، ما أُودع، فروق، فائض مكتشف | `DepositObligationService` |
| الخزنة | متاح / مخصص سداد / مخصص إيداع / فائض/معلق | `TreasurySummaryService::calculateCurrentBalance()` + الأوعية |
| المؤشر العام | المتوقع / الفعلي الموثّق / المتبقي / المعلق / المتأخر / الفروق | `FinancialDistributionMetricsService::indicator()` |

> **قاعدة أداء:** لا تُخزَّن المؤشرات كنُسخ من المال؛ تُحسب بـ`aggregate SQL` واحدة لكل بطاقة (كما يفعل `TreasurySummaryService`)، مع كاش 60 ثانية للبطاقات الثقيلة فقط.

### 4.9 الواجهات (Filament — تتبع التخطيط الثابت في 5.3)
1. **صفحة «مطلوب إيداع»** (`app/Filament/Pages/DepositObligations.php`): جدول التزامات + ملخص، وزر «إنشاء إيداع من المحدد» يفتح نموذجاً **مسبق التعبئة** بمجموع المحدد، ثم يستدعي `DepositMatchingService`.
2. **صفحة «المبالغ المعلقة والفائض»** (`SuspenseAmounts` Resource): قائمة + فلترة + شاشة تفاصيل التتبع (مصدر/بنك/مرجع/مودع/وقت/سبب) + ربط معتمد + اقتراح مطابقة ذكي (الخدمة موجودة وجاهزة).
3. **عمود «التوزيع»** في `ServiceTransactionsTable` (لاحقاً): شارات 1,000 سداد / 500 إيداع، وشارة تحذير عند `split_undetermined`.
4. **بطاقات المؤشرات**: تبدأ كـ`header widgets` داخل صفحة جديدة (لا حاجة لتعديل `AdminPanelProvider`).
5. **زر «تشغيل التوزيع»** لمعالجة العمليات القديمة (backfill) لكل معاملة أو دفعة.

---

## 5. خطة التنفيذ المرحلية (كل مرحلة قابلة للتسليم والتراجع)

### المرحلة 0 — أرصدة وطوابير محسوبة **بصفر تعديل** (توصية البدء)
**الهدف:** يرى المستخدم فوراً: توزيع كل عملية، طابور مطلوب إيداع، توزيع الزائد، مؤشرات لكل جهة — **بدون** migration وبدون لمس أي ملف قائم.
- **جديد فقط:**
  - `app/Services/FinancialDistribution/AllocationRuleResolver.php`
  - `app/Services/FinancialDistribution/DistributionPlanService.php`
  - `app/Services/FinancialDistribution/DepositObligationService.php` (استعلامات مشتقة من `ServiceTransaction` + `UnifiedBatchDepositItem`)
  - `app/Services/FinancialDistribution/ExcessAnalysisService.php` (مقارنة المودع مقابل المستحق لكل عملية)
  - `app/Services/FinancialDistribution/FinancialDistributionMetricsService.php`
  - `app/Filament/Pages/DepositObligations.php` + `resources/views/filament/pages/deposit-obligations.blade.php`
  - `app/Filament/Pages/FinancialDistributionOverview.php` + View
  - `app/Filament/Resources/SuspenseAmounts/**` (عرض فقط في هذه المرحلة)
  - `lang/ar/financial_distribution.php` + `lang/en/financial_distribution.php`
- **لماذا آمنة:** لا كتابة، لا تغيير بنية، لا تغيير أرصدة. يمكن حذف الملفات للتراجع.
- **قبول المرحلة:** لعملية 1,500 ⇒ تظهر 1,000 «سداد» + 500 «إيداع» (أو وسم `split_undetermined` إن لم تُعرف الحصة)، وتظهر في طابور مسؤول الإيداع بمبلغ 500، ولا يظهر الفائض عند إيداع 500 بالضبط.

### المرحلة 1 — دفتر التوزيع الدائم (المصدر الرسمي)
- **جديد:** migration `create_financial_allocations_table`، `app/Models/FinancialAllocation.php`، `app/Enums/AllocationBucket.php`، `app/Enums/AllocationStatus.php`، `FinancialDistributionService` (كتابة)، `app/Filament/Resources/FinancialAllocations/**`، `app/Console/Commands/BackfillFinancialAllocations.php` (يدعم `--dry-run`)، اختبار `tests/Feature/FinancialDistributionTest.php`.
- **التماس الوحيد بملف قائم (اختر واحداً):**
  - **(أ) الأقل:** سطر `#[ObservedBy([TreasuryAllocationObserver::class])]` على `TreasuryTransaction` → يوزّع تلقائياً عند أي حركة توريد عهدة.
  - **(ب) الأوضح:** سطر واحد في نهاية `TreasuryCustodyService::receiveCustody()` يستدعي `FinancialDistributionService::distribute($treasuryTx)`.
  - **(ج) صفر تعديل:** لا hook؛ التوزيع يعمل بـ(زر يدوي + أمر مجدول) حتى تُعتمد (أ) أو (ب).
- **قبول المرحلة:** `SUM(allocations) = treasury amount` لكل حركة، ومنع الحذف، وسجل تدقيق `allocated`، والـ backfill لا يغيّر أي مبلغ في الخزينة.

### المرحلة 2 — تحصيل حصة السداد/الإيداع عند الإنشاء (مصدر البيانات)
- migration إضافية: `service_transactions` += `gov_fee_amount decimal(14,2) default 0`, `office_fee_amount decimal(14,2) default 0`, `collector_employee_id` (nullable FK) — **مما يسمح بفصل «المحصل» عن «المنفذ/المسلم»** كما طلبت.
- تعديلات محدودة: `ServiceTransaction` (fillable/casts)، `ServiceTransactionObserver` (نسخ `default_gov_fee`)، `ServiceTransactionForm` (حقلين اختياريين + عرض الحصة المحسوبة)، `AllocationRuleResolver` (أولوية 1 تصبح أعمدة بدل metadata).
- **قبول المرحلة:** كل عملية جديدة تحمل حصتها بوضوح، وتختفي حالات `split_undetermined` تدريجياً؛ العمليات القديمة تُعالَج بقاعدة الكتالوج.

### المرحلة 3 — مطابقة الإيداع + عزل الفائض + الإشعارات
- **جديد:** `DepositMatchingService`, `FinancialDistributionNotificationService`, migration إضافية على `unified_suspense_amounts` (`source_type`, `source_id`, `reason_code`, `created_by`, indexes)، شاشة مطابقة (Modal داخل صفحة مطلوب إيداع) تُنشئ `UnifiedBatchDeposit` عبر الخدمة القائمة **دون تعديلها**، وشاشة/إجراء «ربط الفائض».
- **الإشعارات:** `deposit_obligation_created` (لمن يحمل `manage_batch_deposits` في نفس المنشأة **فوراً** عند استلام العهدة)، `deposit_received`, `deposit_excess_detected` (لمن يحمل `manage_suspense_amounts`)، `settlement_due`.
- **قبول المرحلة:** إيداع 600 مقابل التزام 500 ⇒ 500 مطابقة + 100 فائض بمصدره الكامل + إشعار + إمكانية تتبع «وين راح»؛ وإيداع 400 ⇒ 400 مطابقة + 100 متبقٍ.

### المرحلة 4 — المؤشرات والتقارير والطباعة
- بطاقات المؤشرات (شركة/موظف/محصل/مسلم) + تقرير PDF «كشف توزيع وإيداع» عبر `UniversalPrintService` + تصدير CSV/Excel + بطاقة رقابة على `Dashboard` (عبر `header widgets` لتفادي تعديل المزوّد).

---

## 6. ضوابط الأمان والاتساق (إلزامية في كل مرحلة)
1. **لا مساس بـ`balance_after`** ولا بإعادة حساب الأرصدة التراكمية.
2. **`SUM(allocations) = amount`** لكل حركة، وتُتحقّق قبل الحفظ داخل `DB::transaction` + `lockForUpdate`.
3. **لا حذف** لأي توزيع/معلق/تدقيق؛ التصحيح بحركة عكسية موثقة.
4. **عزل المنشآت** عبر `CompanyFormService::getAuthorizedCompanyIds()` في كل استعلام وكل صفحة.
5. **لا نصوص عربية في PHP/Blade** للإشعارات والنصوص الجديدة (ملفات اللغة فقط).
6. **`DB::afterCommit`** لكل إشعار، وعدم إرسال إشعارات من داخل Pages/Actions.
7. **الصفحات رقيقة:** أي حساب/حفظ مجمع في `app/Services/FinancialDistribution/`.
8. **عدم تعديل migrations منفَّذة**، ولا تعديل أي جدول قائم إلا بإضافة أعمدة nullable بقيم افتراضية.
9. **`assertSplitValid()`** يمنع `SUM(items) > deposit amount` ويشرح الخطأ برسالة من ملف اللغة.
10. **قابلية التراجع:** كل مرحلة تُسلَّم مع `--dry-run` وخطوات رجوع (حذف جدول/إيقاف hook).

---

## 7. التأثير على الملفات الموجودة (شفافية كاملة)
| الملف | المرحلة | نوع التغيير | المخاطرة |
| :--- | :-: | :--- | :-: |
| `app/Models/TreasuryTransaction.php` | 1 | سطر `#[ObservedBy]` واحد (أو صفر مع الخيار ج) | منخفضة جداً |
| `app/Services/TreasuryCustodyService.php` | 1 | سطر استدعاء واحد (بديل للخيار أ) | منخفضة |
| `app/Models/ServiceTransaction.php` | 2 | `fillable` + `casts` + علاقات (`allocations`, `collector`) | منخفضة |
| `app/Observers/ServiceTransactionObserver.php` | 2 | نسخ `default_gov_fee` عند الإنشاء | منخفضة |
| `app/Filament/Resources/ServiceTransactions/Schemas/ServiceTransactionForm.php` | 2 | حقلا الحصة + عرض الحصة | متوسطة (نموذج حسّاس) |
| `database/migrations/*` | 1،2،3 | ملفات جديدة فقط (لا تعديل قديم) | منخفضة |
| `docs/SYSTEM_CHEATSHEET.md` | 1،3،4 | تحديث إلزامي عند التنفيذ (قاعدة 4) | — |

> المرحلة (0) — الموصى بها للبدء — **لا تلمس أي ملف من الجدول أعلاه.**

---

## 8. أسئلة تحتاج قرارك قبل التنفيذ
1. **حصة السداد/الإيداع:** هل نعتمد `Service.default_gov_fee` كمصدر أولي (مع إمكان تعديلها في العملية)، أم نضيف حقل «حصة الجهة» و«حصة المكتب» في نموذج الخدمة نفسها؟
2. **«المحصل» ≠ «الموظف المسلم»؟** هل نضيف `collector_employee_id` (الموظف الذي حصّل من العميل) منفصلاً عن `executed_by` (الذي سلّم للخزنة)؟ أم يكفي حقل واحد حالياً؟
3. **الإيداع إلزامي؟** هل يقفل النظام العملية إذا بقي مخصص إيداع غير مودع، أم تُغلق المعاملة ويبقى الالتزام ظاهراً في طابور مسؤول الإيداع (مع مهمة نقص)؟
4. **الزائد:** عند `D > O` هل نعزل الزائد **تلقائياً** كمبلغ معلق (موصى به)، أم نرفض الإيداع ونطلب تصحيح المبلغ؟
5. **أوعية الإيجارات والموظفين:** هل تُملأ من وحداتها الحالية (`ContractVoucher` للإيجارات، `PayrollRecord` للرواتب) عبر الربط المتعدد الأشكال، أم تبدأ فقط بخدمات `ServiceTransaction`؟
6. **مطابقة الإيداع:** تلقائية (FIFO أقدم-أولاً) أم يدوية باختيار البنود مع اقتراح آلي؟
7. **أولوية البدء:** هل نبدأ بالمرحلة (0) كعرض تجريبي على بياناتكم الحالية، أم نمضي مباشرة للمرحلتين 1+2؟

---

## 9. خطة التحقق لكل مرحلة (وفق 5.6)
1. `php -l` على كل ملف PHP جديد/معدّل.
2. `vendor\bin\pint` على الملفات المتغيرة.
3. `php artisan view:clear` بعد أي Blade، و`php artisan optimize:clear` على السيرفر بعد الرفع (خاصة عند إضافة ملفات كلاسات جديدة: Observer/Service/Page ⇒ خطر `class already in use` — قسم 3.6).
4. اختبارات `tests/Feature/FinancialDistributionTest.php`:
   - توزيع 1,500 ⇒ 1,000 سداد + 500 إيداع، والمجموع = مبلغ الحركة.
   - إيداع 500 ⇒ إغلاق التزام، لا فائض.
   - إيداع 600 ⇒ 500 مطابقة + 100 معلق بمصدره.
   - إيداع 400 ⇒ 400 مطابقة + 100 متبقٍ.
   - منع حذف توزيع، ومنع `SUM > amount`.
   - إشعار يُرسل مرة واحدة فقط لكل التزام.
5. فحص تعدد المنشآت: مستخدم بمنشأة واحدة لا يرى التزامات غيرها.

---

## 10. ملاحظات جانبية (مرصودة، غير متعلقة بالمهمة — تُبلَّغ منفصلة وفق 5.1)
1. `docs/SYSTEM_CHEATSHEET.md` يذكر `tests/Feature/TreasuryCustodyTest.php` (3 اختبارات)، والمجلد الفعلي يحتوي `ExampleTest` و`FilterBarServiceTest` فقط ⇒ توثيق غير مطابق أو ملفات غير مرفوعة.
2. `docs/plans/UNIFIED_OPERATIONS_STATUS_MAP.md` (بتاريخ 2026-11-08) يصف سجل التدقيق والمبالغ المعلقة بأنها «غير موجودة»، وهي منفَّذة فعلياً الآن ⇒ يحتاج تحديثاً.
3. `resources/views/filament/resources/service-transactions/operational-dashboard.blade.php` يستخدم `$record->creator` ولا توجد علاقة `creator()` في `ServiceTransaction` ⇒ يعرض فراغاً دائماً (غير ضار لكنه مضلل).
4. يحتوي نفس الملف على نصوص عربية مباشرة بديلاً عن مفاتيح اللغة (`'ر.س'`) ⇒ يخالف قاعدة الترجمة؛ يُصحَّح في مهمة مستقلة.
