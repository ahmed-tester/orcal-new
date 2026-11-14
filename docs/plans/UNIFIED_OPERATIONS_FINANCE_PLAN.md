# خطة تنفيذ النظام الموحد للخدمات والعمليات المالية

> **المرجع:** `al_nizam_al_mowahad.pdf` — المخطط الكامل للنظام الموحد للخدمات والعمليات المالية (8 صفحات).
> **الغرض:** تحويل المخطط إلى تنفيذ فعلي داخل نظام Orcal الحالي، بدون تكرار مكونات موجودة، وبدون كسر الوحدات العاملة.
> **النوع:** خطة معمارية + خطة تنفيذ مرحلية. لا تتضمن هذه الوثيقة أي تعديل على الكود بعد.
> **اللغة:** العربية (لأنها لغة النظام والتواصل). أسماء الجداول والأعمدة إنجليزية حسب معايير المشروع.

---

## 1. الهدف والنطاق

### 1.1 الهدف
بناء **محرك مالي وتشغيلي واحد** يربط الخدمة بالفاتورة بالتحصيل بالعهدة بالخزنة بالسداد/الإيداع بالمطابقة بالإغلاق، بحيث:

1. تُنشأ الخدمة وماليتها تلقائياً من صاحب الخدمة ونوعها.
2. ينتقل المال داخل دورة موثقة: **عميل → موظف (عهدة) → خزنة → سداد/إيداع → مطابقة → إغلاق**.
3. تُحدد المسؤولية الحالية في كل لحظة، والمتأخر هو **الجزء الناقص فقط** لا من أنهى عمله.
4. كل انتقال أو تصحيح أو اعتماد يُسجَّل في **سجل تدقيق غير قابل للحذف**.
5. تنعكس كل حركة فوراً على **أرصدة العميل والموظف والخزنة والمكتب** وعلى **المؤشر المالي والتقييم**.

### 1.2 داخل النطاق
- العمليات الموحدة (إنشاء / تعديل / عرض / إلغاء / تنازل / تسوية لاحقة).
- التحصيل والعهدة وتسليم الخزنة وتأكيد الاستلام.
- التوزيع المالي والسداد والإيداع (الفردي والمجمع).
- المبالغ المعلقة (المجهولة) وربطها واعتمادها.
- المطابقة الثنائية ومحرك النواقص والفروقات.
- بنك المهام والتنبيهات والتصعيد.
- الأرصدة والمؤشرات المالية ومؤشرات الأداء والتقييم.
- سجل التدقيق.
- الصلاحيات والأدوار والواجهات والتقارير والطباعة.

### 1.3 خارج النطاق (حالياً)
- إعادة بناء مسيرات الرواتب أو صناديق الأصول أو الأقساط (تبقى كما هي، مع ربط اختياري لاحق).
- تطبيق موبايل أو بوابات خارجية.
- الربط المباشر مع البنوك (يُكتفى برفع الإثبات ورقم المرجع البنكي).
- توقيع إلكتروني / OTP حقيقي (يُستخدم باركود + تأكيد مستخدم).

---

## 2. المخطط المستهدف ودليل المنظومة (وفق وثيقة PDF)

### 2.1 المخطط العام لدورة الخدمة
```
[1] اختيار العميل والخدمة
        ↓
[2] إنشاء تلقائي: الخدمة + الفاتورة/الاستحقاق + رقم العملية + الموظف المسؤول + المبلغ المتوقع
        ↓
[3] تحديد من يتحمل التكلفة: عميل دفع | دين على العميل | على المكتب | مشترك
        ↓
[4] تحصيل العميل (كلي/جزئي) ──► يسجل المبلغ المستلم + تأكيد العميل
        ↓
[5] عهدة الموظف ──► المبلغ يصبح عهدة على المستلم + إشعار الخزنة
        ↓
[6] تسليم الخزنة ──► باركود متغير + رقم العملية + تحقق فوري
        ↓
[7] استلام الخزنة ──► انتقال مسؤولية المال من الموظف إلى الخزنة + تحديث الأرصدة
        ↓
[8] التوزيع المالي ──► مخصص للسداد | مخصص للإيداع | زائد يبقى بالخزنة مرتبطاً بالعملية
        ↓
[9] السداد / الإيداع ──► تنفيذ + رفع الإثبات (+ إيداع مجمع لعدة عمليات + سداد شخصي كمستحق)
        ↓
[10] المطابقة الثنائية ──► خدمة ↔ حركة مالية (المبلغ، الجهة، الفاتورة/السند، المستند)
        ↓
[11] معالجة الجزء الناقص ──► مهمة تلقائية لمسؤول الجزء الناقص + إعادة مطابقة
        ↓
[12] الاعتماد أو التصعيد ──► اعتماد إضافي أو رفع للإدارة مع السبب والمستندات
        ↓
[13] تحديث الأرصدة والمؤشرات ──► العميل + الموظف + الخزنة + المكتب + المؤشر المالي
        ↓
[14] الإغلاق ──► لا إغلاق قبل اكتمال المتطلبات والمطابقة والاعتمادات، مع بقاء سجل التدقيق
```

### 2.2 شرح مراحل المخطط الانسيابي التفصيلي (14 مرحلة)
1. **اختيار العميل والخدمة:** يبدأ الموظف باختيار العميل أو المؤسسة ونوع الخدمة وإدخال البيانات الأساسية المطلوبة لبدء العملية وفق المنطق الذكي لمنع تكرار الإدخال.
2. **الإنشاء التلقائي:** ينشئ النظام الخدمة والارتباط المالي المرتبط بها تلقائياً (فاتورة/استحقاق، رقم عملية فريد، مسؤول، مبلغ متوقع).
3. **تحديد من يتحمل التكلفة:** تحديد التغطية المالية للعملية: (دفع من العميل | دين على العميل | على المكتب | توزيع مشترك بين العميل والمكتب).
4. **تحصيل العميل:** عند استلام مبلغ من العميل يسجل كلياً أو جزئياً، ويؤكد العميل التسليم، وتبدأ متابعة المال داخل الدورة المالية فوراً دون تأخير للخدمة.
5. **عهدة الموظف:** يبقى المبلغ المستلم عهدة مباشرة على الموظف الذي استلمه مع إشعار الخزنة آلياً حتى يتم تسليمه وتأكيد استلامه.
6. **تسليم الخزنة:** يتم تسليم المبلغ للخزنة وربطه بالعملية عبر وسيلة تحقق فورية مشفرة (باركود متغير + رقم العملية).
7. **تأكيد استلام الخزنة:** تؤكد الخزنة الاستلام الفعلي؛ عندها فقط تنتقل مسؤولية المال قانونياً ومالياً من عهدة الموظف إلى الخزنة ويتم تحديث الأرصدة فوراً.
8. **التوزيع المالي:** يحدد النظام داخل الخزنة ما يخصص للسداد للجهات، وما يخصص للإيداع البنكي، وما يبقى كرصيد زائد أو معلق مرتبط برقم العملية.
9. **السداد أو الإيداع:** ينفذ المسؤول المختص السداد للجهات أو الإيداع البنكي ويرفع إثبات العملية. في حال سداد الموظف من ماله الشخصي يُسجل كمستحق له بعد الإثبات والاعتماد. كما يمكن إنشاء دفعة إيداع بنكية مجمعة تغطي عدة عمليات أصلية مع بقاء تفصيل كل جزء.
10. **المطابقة الثنائية (Two-Way Reconciliation):** يقارن النظام الخدمة بالحركة المالية المقابلة (المبلغ، الجهة، الفاتورة/السند، الإيصال)، وفي المقابل تبحث الحركة المالية عن خدمتها المقابلة لضمان عدم وجود أطراف يتيمة.
11. **معالجة الجزء الناقص ومبدأ عدم التعطيل:** إذا كان الطرف المقابل أو المستند أو المبلغ غير مكتمل، لا يتوقف العمل في باقي المراحل؛ بل ينشئ النظام مهمة آلية للمسؤول عن الجزء الناقص فقط، ولا يتحمل من أنجز مسؤوليته أي تأخير.
12. **الاعتماد أو التصعيد:** الحالات التي تحتاج صلاحية إضافية تُرسل للاعتماد (مشرف). وإذا وجد عائق خارج صلاحية المسؤول يُسجل السبب وتُرفع الحالة للإدارة لاتخاذ القرار.
13. **تحديث الأرصدة والمؤشرات:** بعد كل حركة تتحدث لحظياً أرصدة العميل والموظف والخزنة والمكتب، وتنعكس مباشرة على المؤشر المالي ومؤشرات تقييم الأداء.
14. **الإغلاق:** لا تغلق العملية ذات الأثر المالي نهائياً إلا بعد اكتمال كافة المتطلبات والمستندات والمطابقة والاعتمادات، مع حفظ سجل التدقيق كاملاً غير قابل للحذف أو التعديل.

### 2.3 دليل الرموز ودلالات الألوان المعتمدة (Color Coding System)
| الرمز / اللون | المعنى في النظام | الاستخدام المعتمد داخل الشاشات والجداول | كود اللون المقترح |
| :--- | :--- | :--- | :--- |
| 🔵 **أزرق** (Blue) | بداية / خدمة / إجراء تشغيلي | اختيار العميل والخدمة، المهام أو الإجراءات الرئيسية | `primary` / `#2563EB` |
| 🟢 **أخضر** (Green) | إنشاء / نجاح / اكتمال | إنشاء تلقائي، اكتمال مرحلة، أرصدة سليمة، نتيجة مطابقة ناجحة | `success` / `#16A34A` |
| 🟠 **برتقالي** (Orange) | استحقاق / قرار مالي | تحديد من يتحمل التكلفة، متبقي، خطوة تحتاج متابعة مالية | `warning` / `#EA580C` |
| 🟣 **بنفسجي** (Purple) | عهدة / مبلغ معلق | عهدة موظف، مبلغ مجهول/معلق، مرحلة تحتاج ربطاً وتسوية | `info` / `#9333EA` |
| 🌐 **سماوي** (Cyan / Sky) | الخزنة / الإيداع | تسليم واستلام الخزنة، الإيداعات، حركة المال إلى البنك | `#0EA5E9` |
| 🔴 **أحمر** (Red) | استثناء / فرق / إلغاء | عدم تطابق، فرق مالي، إلغاء، حالة تحتاج معالجة استثنائية | `danger` / `#DC2626` |
| 🌲 **أخضر داكن** (Dark Green) | إغلاق ومطابقة | انتهاء الدورة بالكامل بعد اكتمال المطابقة المالية والتشغيلية | `#14532D` |
| ⬇️ **سهم** (Arrow) | انتقال للمرحلة التالية | يوضح انتقال مسؤولية العملية أو العهدة من خطوة لأخرى | — |

### 2.4 المنطق الذكي لبداية الخدمة وإنشاء الفاتورة (قاعدة منع تكرار الإدخال)
> **القاعدة الذهبية:** *لا يُطلب من المستخدم إعادة إدخال أي معلومة يستطيع النظام استنتاجها من صاحب الخدمة أو الجهة أو نوع الخدمة، مع منع الاستمرار نهائياً إذا كانت هوية المستفيد غير مكتملة.*

| حالة البداية | آلية العمل والمعالجة البرمجية |
| :--- | :--- |
| **1. البداية من العميل (Customer)** | يختار المستخدم العميل ثم الخدمة؛ يسترجع النظام بيانات العميل والخدمة تلقائياً ويملأ بيانات الفاتورة المعروفة، ولا يطلب إلا البيانات الخاصة الحصرية بالخدمة. |
| **2. البداية من المؤسسة (Company)** | بعد اختيار المؤسسة يلزم تحديد المستفيد قبل الاستمرار: إما المنشأة ككل (إذا كانت الخدمة تخص الكيان التجاري)، أو موظف تابع للمنشأة (إذا كانت خدمة فردية). |
| **3. اختيار موظف من المؤسسة** | إذا كانت الخدمة لموظف، يفلتر النظام ويعرض موظفي تلك المؤسسة المختارة فقط، ويمنع متابعة العملية قبل تحديد الموظف المستفيد بدقة. |
| **4. البداية من الموظف (Employee)** | يتيح النظام البحث عن الموظف مباشرة كنقطة بداية، فيقوم النظام تلقائياً باسترجاع المؤسسة المرتبطة به ثم يتيح الخدمات المناسبة دون إعادة إدخال المنشأة يدوياً. |
| **اختيار الخدمة** | نوع الخدمة يحدد آلياً الحقول المطلوبة ومسار العمل والمسؤولين والارتباط المالي. أي بيانات موجودة في سجل العميل أو المؤسسة أو الموظف أو تعريف الخدمة تُملأ فوراً. |
| **إنشاء الفاتورة والمسار** | بعد اكتمال صاحب الخدمة والخدمة، ينشئ النظام الفاتورة/الاستحقاق تلقائياً ويربطها برقم العملية الموحد وصاحب الخدمة والجهة. |
| **انتقال المسؤولية** | عند إكمال كل مرحلة ينشئ النظام إشعاراً أو مهمة في بنك المهام للمسؤول التالي. وعند ظهور نقص تنتقل المتابعة لمسؤول الجزء الناقص فقط ولا يُحمّل الطرف المنجز أي تأخير. |

### 2.5 نطاق الخدمات الـ 13 المستهدفة بنفس المحرك المالي والتشغيلي
تعتمد جميع الخدمات التالية نفس القواعد الأساسية الموحدة: (إنشاء أو تسجيل ← مستند ← طرف مقابل ← مطابقة ← مسؤول حالي ← اعتماد وإغلاق):
1. **الرواتب والإجازات** (مسيرات الرواتب ومستحقات الإجازات).
2. **الإقامات** (إصدار، تجديد، نقل خدمات).
3. **رخص العمل** (سداد المقابل المالي وإصدار رخص العمل).
4. **التأمين الطبي** (وثائق التأمين وتوزيع التكاليف).
5. **الرخص والسجلات** (السجلات التجارية، الرخص البلدية، اشتراكات الغرف التجارية).
6. **الزكاة والضرائب** (الإقرارات الضريبية، الزكاة، ضريبة القيمة المضافة).
7. **الإيجارات والعقود** (عقود الإيجار، الدفعات الدورية، التزامات المنشأة).
8. **الفواتير والمصروفات** (فواتير الخدمات، المشتريات، المصروفات التشغيلية).
9. **الإيداعات البنكية** (الإيداعات النقدية والشيكات الفردية والمجمعة).
10. **تحصيل العملاء** (الدفعات النقدية والتحويلات ومديونيات العملاء).
11. **العهد المالية** (العهد المستديمة والمؤقتة للموظفين وتصفيتها).
12. **المخالفات** (المخالفات المرورية ومخالفات المنشآت ومتابعة سدادها).
13. **الخدمات العامة** (أي خدمات أو معاملات حكومية أو خاصة إضافية).

---

## 3. تحليل الوضع الحالي (Gap Analysis)

### 3.1 ما هو موجود ويمكن إعادة استخدامه

| المطلوب في المخطط | المكوّن الحالي | القرار |
| :--- | :--- | :--- |
| العميل | `Customer` (فرد/مؤسسة خارجية) + `Company` (منشأة داخلية) | إعادة استخدام + ربط `customer_id` بالعملية |
| الخدمة | `Service` (`target_type`, `category`, `price`, `default_gov_fee`) | إعادة استخدام كما هي |
| رقم العملية | `ServiceTransaction.transaction_number` (بادئة `SRV`) | إعادة استخدام وتوسيع الجدول ليكون العملية الموحدة |
| الفاتورة/الاستحقاق | `Invoice` + `InvoiceItem` | إعادة استخدام + إضافة `operation_id` |
| دين على العميل | `Debt` + `DebtCollection` | إعادة استخدام (إنشاء تلقائي عند `client_debt`) |
| حركة الخزينة | `TreasuryTransaction` (قبض/صرف + `balance_after` + منع الحذف) | إعادة استخدام + إضافة `operation_id` |
| الموظف المسؤول | `executed_by` | لا يكفي: يُضاف `responsible_employee_id` (المسؤول الحالي) |
| الاعتماد | `HasApprovalStatus` (داخل `metadata.status`) | يُبقى للاعتماد، ويُضاف `workflow_status` مستقل |
| توليد الأكواد | `CodeGeneratorService` | إعادة استخدام لكل الأكواد الجديدة |
| التقويم الهجري | `HasHijriDates` | إعادة استخدام في كل التواريخ الجديدة |
| الفلاتر | `FilterBarService` + `HasFilterBarState` + `<x-filter-bar>` | إعادة استخدام إلزامي |
| الطباعة | `UniversalPrintService` + `mPDF` + `TafqeetService` | إعادة استخدام لسندات القبض/السداد/الإيداع |
| التنبيهات | `NotificationAlertsService` + Filament DB Notifications | إعادة استخدام + توسيع |
| الصلاحيات | Spatie + Policies + `HasSuperAdminBypass` | إعادة استخدام |
| تعدد الشركات | `company_id`, `HasCompanyScope`, `CompanyFormService` | إلزامي في كل الجداول الجديدة |

### 3.2 الفجوات الحقيقية (غير موجودة حالياً)

| # | الفجوة | الأثر |
| :-- | :--- | :--- |
| 1 | لا يوجد كيان يربط الخدمة ↔ الفاتورة ↔ التحصيل ↔ الخزنة ↔ السداد ↔ المطابقة | كل وحدة معزولة، لا تتبع كامل للدورة |
| 2 | لا يوجد دفتر **عهدة موظف** | المال المحصّل لا يُتابع كمسؤولية على الموظف |
| 3 | لا يوجد **تسليم خزنة** بتأكيد استلام (باركود/رقم عملية) | لا انتقال موثق للمسؤولية |
| 4 | لا يوجد **توزيع مالي** (سداد/إيداع/زائد/معلق) | الخزنة لا تعرف المخصص لكل غرض |
| 5 | لا يوجد **سداد** و**إيداع** كسجلات مستقلة بإثبات واعتماد | لا توثيق للمنفذ والمستند |
| 6 | لا يوجد **إيداع مجمع** مرتبط بعدة عمليات | كل عملية معزولة |
| 7 | لا يوجد **مبلغ معلق/مجهول** مع اقتراح ربط واعتماد | المبالغ غير المطابقة تُترك بلا معالجة |
| 8 | لا يوجد **محرك مطابقة ثنائية** ولا تحديد الجزء الناقص | لا أحد يعرف من المسؤول عن التأخير |
| 9 | لا يوجد **بنك مهام تشغيلي** (المهام الحالية `Task` هي كتالوج مهام بأوزان للموظفين) | لا متابعة للنواقص والتصعيد |
| 10 | لا يوجد **سجل تدقيق غير قابل للحذف** على مستوى العملية | لا أثر للانتقالات والتصحيحات |
| 11 | لا يوجد **مؤشر مالي موحد** ولا تقييم حسب الجزء المسؤول | لا قياس أداء عادل |
| 12 | لا يوجد **حالة سير عمل موحدة** (الحالة الحالية نصية متفرقة) | لا آلة حالة ولا حماية من الانتقالات غير الصحيحة |
| 13 | لا يوجد **من يتحمل التكلفة** (عميل/مكتب/مشترك) | لا فصل بين إيراد العميل والتزام المكتب |
| 14 | لا يوجد **إلغاء/تنازل** باعتماد مع حفظ التاريخ | الحذف ممنوع لكن لا بديل موثق |
| 15 | `ServiceTransaction` لا يرتبط بـ `Customer` ولا `Invoice` ولا `TreasuryTransaction` | تكرار إدخال البيانات |

### 3.3 تعارضات يجب الانتباه لها
- **`Task` الحالي ≠ بنك المهام:** `Task` كتالوج مهام مرتبط بـ `TaskAssignment` وله `default_weight`؛ لا يُعاد استخدامه لبنك مهام العمليات. يُنشأ جدول `operation_tasks` مستقل، مع إمكانية ربط لاحق اختياري.
- **`status` في `ServiceTransaction`** محفوظ داخل `metadata` عبر `HasApprovalStatus`؛ لذلك الحالة التشغيلية الجديدة يجب أن تكون عموداً مستقلاً باسم `workflow_status` لتجنب أي تعارض.
- **`TreasuryTransaction` يمنع الحذف** (`static::deleting` ترجع `false`) ويستخدم `balance_after` تراكمي؛ لذلك أي تصحيح مالي يجب أن يكون **حركة عكسية** وليس تعديلاً (نفس نمط `PAYROLL_REVERSE_ENTRY_PLAN.md`).
- **الخزينة حالياً على مستوى الشركة** وليس الفرع؛ سيُبنى التوزيع على `company_id` مع إمكانية إضافة `branch_id` لاحقاً في مرحلة مستقلة.
- **`Invoice` مرتبطة إلزامياً بـ `company_id`**؛ لذلك أي عملية خارجية يجب أن تُحل شركتها أولاً (نفس منطق `CompanyFormService::resolveOrCreateCompanyByName()`).

---

## 4. القرارات المعمارية المقترحة

### 4.1 تطوير `service_transactions` ليكون "العملية الموحدة" (القرار الموصى به)
- **لماذا:** الجدول يحمل بالفعل رقم العملية والتاريخ والنطاق وصاحب الخدمة والمستفيد والمبالغ والحالة، وهو الأقرب لمفهوم المخطط. تطويره يمنع وجود كيانين متوازيين ويحافظ على الشاشات والبيانات الحالية.
- **البديل المرفوض:** إنشاء جدول `financial_operations` جديد وترك `service_transactions` — يخلق ازدواجية وتكرار شاشات وترحيل بيانات معقد.
- **الالتزام:** يُعاد تسمية المورد والشاشات إلى «العمليات الموحدة» مع بقاء اسم الجدول `service_transactions` (لا حاجة لإعادة تسمية جدول في الإنتاج).

### 4.2 إعادة استخدام الكيانات المالية القائمة
- الفاتورة: `Invoice` (إضافة `operation_id` nullable + index).
- الدين: `Debt` (إنشاء تلقائي عند تحمّل العميل، مع `metadata.operation_id`).
- الخزينة: `TreasuryTransaction` (إضافة `operation_id` nullable + index).
- الاعتماد: `HasApprovalStatus` كما هو.
- **لا** يُنشأ دفتر مالي عام جديد؛ الأرصدة تُحسب من الحركات الموثقة.

### 4.3 سجل تدقيق مستقل
جدول `operation_events` **إلحاقي فقط** (append-only): لا تحديث ولا حذف على مستوى النموذج، ويُكتب من خدمة واحدة مركزية.

### 4.4 رمز تسليم الخزنة (بدون اعتمادية خارجية)
- المخطط يطلب «باركود متغير + رقم العملية + تحقق فوري» لضمان انتقال موثق للمسؤولية.
- **القرار المعتمد:** لا تُضاف أي حزمة (composer أو npm) في المرحلة الأولى. يُستخدم **رمز تسليم نصي فريد** في `handover_code` (مثال: `HO-C53-000123`) يُطبع على سند التسليم ويُدخل/يُمسح ضوئياً بأي قارئ باركود يعمل كلوحة مفاتيح، مع تحقق فوري في الخادم (وجود الرمز + حالته + المبلغ + المسؤول + العملية).
- **السبب:** تفادي تغيير `composer.json` وتفادي أي اعتمادية إضافية على الاستضافة المشتركة، مع تحقيق نفس الغرض التشغيلي (التحقق اللحظي ومنع التسليم المزدوج).
- **التوسعة المستقبلية:** يمكن لاحقاً توليد صورة Code128 من نفس الرمز عبر مكتبة واجهة محلية، دون أي تغيير في قاعدة البيانات أو منطق التحقق.

### 4.5 آلة الحالة في خدمة واحدة
كل الانتقالات والتحققات داخل `OperationWorkflowService`؛ ممنوع تغيير `workflow_status` من Pages أو Actions مباشرة.

---

## 5. نموذج البيانات المقترح

> جميع الجداول الجديدة تحمل `company_id` (مع index) ما لم يُذكر خلاف ذلك، وتستخدم `timestamps()`، وتُضاف لها ملفات لغة ومورد Filament في نفس المرحلة.

### 5.1 توسعة `service_transactions` (العملية الموحدة)

| العمود | النوع | الوصف |
| :--- | :--- | :--- |
| `customer_id` | FK customers, nullable | العميل/الجهة الخارجية صاحبة الخدمة |
| `beneficiary_type` | string(20) nullable | `company` \| `employee` \| `customer` \| `external` |
| `cost_bearer` | string(20) default `client_pays` | `client_pays` \| `client_debt` \| `office` \| `shared` |
| `client_share_amount` | decimal(14,2) default 0 | نصيب العميل في التكلفة المشتركة |
| `office_share_amount` | decimal(14,2) default 0 | نصيب المكتب |
| `expected_amount` | decimal(14,2) default 0 | المبلغ المتوقع من الخدمة |
| `collected_amount` | decimal(14,2) default 0 | إجمالي المحصّل من العميل |
| `custody_amount` | decimal(14,2) default 0 | المتبقي عهدة على الموظف |
| `treasury_received_amount` | decimal(14,2) default 0 | ما استلمته الخزنة فعلياً |
| `settled_amount` | decimal(14,2) default 0 | ما تم سداده للجهات |
| `deposited_amount` | decimal(14,2) default 0 | ما تم إيداعه بالبنك |
| `pending_amount` | decimal(14,2) default 0 | مبلغ معلق غير مرتبط |
| `excess_amount` | decimal(14,2) default 0 | زائد/رصيد زائد |
| `difference_amount` | decimal(14,2) default 0 | فرق مالي (موجب/سالب) |
| `workflow_status` | string(30) default `new`, index | حالة سير العمل الموحدة |
| `responsible_employee_id` | FK employees nullable | المسؤول الحالي عن الجزء المفتوح |
| `collector_employee_id` | FK employees nullable | الموظف الذي حصّل المبلغ |
| `settlement_officer_id` | FK employees nullable | مسؤول السداد |
| `deposit_officer_id` | FK employees nullable | مسؤول الإيداع |
| `invoice_id` | FK invoices nullable, index | الفاتورة/الاستحقاق المنشأ تلقائياً |
| `debt_id` | FK debts nullable, index | الدين المنشأ عند تحمّل العميل |
| `matching_status` | string(20) default `pending` | `pending` \| `matched` \| `difference` |
| `tracking_code` | string(60) nullable unique | رمز تتبع العملية (للبحث والباركود) |
| `closed_at` | timestamp nullable | تاريخ الإغلاق |
| `cancelled_at` | timestamp nullable | تاريخ الإلغاء/التنازل |
| `cancel_reason` | text nullable | سبب الإلغاء |
| `opened_by` | FK users nullable | منشئ العملية |
| `metadata` | json (موجود) | يُستخدم للأحداث الخفيفة والربط المرن فقط |

> **قاعدة:** لا تُخزَّن الأرصدة المالية الكلية في `metadata`؛ الأعمدة أعلاه هي الملخص الرسمي، وتُحدَّث فقط عبر `OperationBalanceService`.

### 5.2 الجداول الجديدة

#### (أ) `operation_events` — سجل التدقيق
| العمود | النوع |
| :--- | :--- |
| `id`, `operation_id` (FK cascade) | |
| `event_type` | string(40): created, status_changed, approved, rejected, collected, handover_created, handover_confirmed, allocated, settled, deposited, unmatched_held, unmatched_linked, matched, difference_found, task_created, task_closed, escalated, resolved, cancelled, waived, retroactive |
| `from_status`, `to_status` | string(30) nullable |
| `amount` | decimal(14,2) nullable |
| `actor_user_id`, `actor_employee_id` | FK nullable |
| `reason`, `document_path` | text / string nullable |
| `payload` | json nullable (قبل/بعد، معرفات مرتبطة) |
| `occurred_at` | timestamp default now |

- `$timestamps = false`، و`occurred_at` يُضبط تلقائياً.
- يمنع النموذج `updating` و`deleting` صراحةً.
- Indexes: `(operation_id, occurred_at)`, `(event_type)`, `(actor_employee_id)`.

#### (ب) `employee_custody_entries` — دفتر العهدة
| العمود | النوع |
| :--- | :--- |
| `id`, `company_id` (index) | |
| `custody_number` | string(50) unique — بادئة `CUS` |
| `operation_id` | FK nullable |
| `employee_id` | FK employees (صاحب العهدة) |
| `entry_type` | `collection` \| `handover_out` \| `reversal` \| `transfer` |
| `direction` | `in` \| `out` |
| `amount` | decimal(14,2) |
| `balance_after` | decimal(14,2) — رصيد الموظف بعد الحركة |
| `handover_id`, `treasury_transaction_id` | FK nullable |
| `reference` | string(50) nullable |
| `notes`, `metadata` | text / json |
| `created_by`, `timestamps` | |

- Index: `(employee_id, company_id, created_at)`.
- **قاعدة:** لا تُحذف الحركة؛ التصحيح بحركة عكسية فقط.

#### (ج) `custody_handovers` — تسليم الخزنة
| العمود | النوع |
| :--- | :--- |
| `handover_number` | string(50) unique — `HO` |
| `operation_id` | FK |
| `company_id` | FK + index |
| `from_employee_id`, `to_employee_id` | FK employees |
| `amount` | decimal(14,2) |
| `handover_date` | date |
| `handover_code` | string(60) unique (باركود) |
| `barcode_scanned_at` | timestamp nullable |
| `status` | `pending` \| `confirmed` \| `rejected` \| `cancelled` |
| `confirmed_by`, `confirmed_at` | |
| `treasury_transaction_id` | FK nullable |
| `rejection_reason` | text nullable |
| `metadata`, `timestamps` | |

#### (د) `financial_allocations` — التوزيع المالي
| العمود | النوع |
| :--- | :--- |
| `allocation_number` | string(50) unique — `ALC` |
| `operation_id` | FK |
| `company_id` | FK + index |
| `allocation_type` | `settlement` \| `deposit` \| `excess` \| `pending` |
| `amount` | decimal(14,2) |
| `responsible_employee_id` | FK nullable |
| `status` | `pending` \| `assigned` \| `in_progress` \| `executed` \| `failed` \| `cancelled` |
| `settlement_id`, `bank_deposit_id` | FK nullable |
| `executed_at`, `notes`, `metadata`, `timestamps` | |

- Index: `(operation_id)`, `(allocation_type, status)`, `(responsible_employee_id, status)`.
- **قاعدة:** مجموع التوزيعات النشطة = `treasury_received_amount` لكل عملية (يُتحقق في الخدمة).

#### (هـ) `settlements` — السداد
| العمود | النوع |
| :--- | :--- |
| `settlement_number` | unique — `STL` |
| `operation_id`, `allocation_id`, `company_id` | FK |
| `payee_type` | `government` \| `supplier` \| `employee_reimbursement` \| `other` |
| `payee_name` | string |
| `amount` | decimal(14,2) |
| `settlement_date` | date |
| `payment_method` | cash, bank_transfer, cheque, pos |
| `reference_number` | string nullable |
| `proof_path` | string nullable |
| `proof_verified` | boolean default false |
| `status` | `pending` \| `proof_uploaded` \| `approved` \| `rejected` \| `executed` |
| `executed_by`, `approved_by`, `approved_at`, `rejection_reason` | |
| `treasury_transaction_id` | FK nullable |
| `metadata`, `timestamps` | |

- السداد من مال الموظف الشخصي: `payee_type = employee_reimbursement` ويُنشأ مستحق للموظف (يظهر في رصيد الموظف) بعد الإثبات والاعتماد.

#### (و) `bank_deposits` — الإيداع البنكي
| العمود | النوع |
| :--- | :--- |
| `deposit_number` | unique — `DEP` |
| `company_id`, `bank_name`, `bank_account` | |
| `deposit_date` | date |
| `total_amount` | decimal(14,2) |
| `status` | `pending` \| `proof_uploaded` \| `approved` \| `rejected` \| `executed` |
| `proof_path`, `bank_reference` | |
| `executed_by`, `approved_by`, `approved_at`, `rejection_reason` | |
| `treasury_transaction_id` | FK nullable |
| `notes`, `metadata`, `timestamps` | |

#### (ز) `bank_deposit_allocations` — تفصيل الإيداع المجمع
| العمود | النوع |
| :--- | :--- |
| `bank_deposit_id`, `operation_id`, `allocation_id` | FK nullable |
| `original_amount` | decimal(14,2) |
| `amount` | decimal(14,2) settled في هذا الإيداع |
| `notes`, `timestamps` | |

- القاعدة: مجموع `amount` = `bank_deposits.total_amount` (يُتحقق في `BankDepositService`).

#### (ح) `unmatched_receipts` — المبالغ المعلقة/المجهولة
| العمود | النوع |
| :--- | :--- |
| `receipt_number` | unique — `URC` |
| `company_id`, `received_date` | |
| `amount`, `payment_method`, `reference_number`, `bank_reference` | |
| `status` | `held` \| `suggested` \| `approved` \| `rejected` \| `linked` |
| `suggested_operation_id`, `approved_operation_id` | FK nullable |
| `approved_by`, `approved_at`, `rejection_reason` | |
| `proof_path`, `notes`, `metadata`, `timestamps` | |

- **قاعدة:** لا يُرحَّل المبلغ نهائياً قبل اعتماد التصحيح؛ الاقتراح آلي ولا يغيّر أرصدة العملية.

#### (ط) `reconciliation_cases` — المطابقة الثنائية
| العمود | النوع |
| :--- | :--- |
| `case_number` | unique — `REC` |
| `operation_id`, `company_id` | FK |
| `expected_amount`, `actual_amount`, `difference_amount` | decimal(14,2) |
| `missing_part` | `none` \| `service` \| `money` \| `document` \| `invoice` \| `party` \| `amount` |
| `responsible_employee_id` | FK nullable |
| `status` | `open` \| `in_progress` \| `matched` \| `difference` \| `escalated` \| `resolved` |
| `opened_at`, `resolved_at`, `resolution_notes` | |
| `metadata`, `timestamps` | |

- حالة مفتوحة واحدة لكل عملية (يُفرض في الخدمة + index فريد على `(operation_id, is_open)` إن أمكن، أو حقل `is_open` boolean).

#### (ي) `operation_tasks` — بنك المهام
| العمود | النوع |
| :--- | :--- |
| `task_number` | unique — `OPT` |
| `operation_id`, `company_id` | FK nullable (مهمة عامة ممكنة) |
| `task_type` | `missing_document` \| `missing_party` \| `missing_amount` \| `difference` \| `pending_amount` \| `approval` \| `sla` \| `collection_followup` \| `escalation` |
| `title`, `description` | |
| `assigned_employee_id`, `created_by` | FK |
| `priority` | `low` \| `normal` \| `high` \| `critical` |
| `due_date` | date nullable |
| `status` | `open` \| `in_progress` \| `done` \| `cancelled` \| `escalated` |
| `escalated_to_employee_id`, `escalation_reason` | |
| `resolved_at`, `metadata`, `timestamps` | |

- Index: `(assigned_employee_id, status)`, `(status, due_date)`, `(operation_id)`.

#### (ك) `operation_attachments` — مستندات العملية
| العمود | النوع |
| :--- | :--- |
| `operation_id` | FK cascade |
| `attachment_type` | `invoice` \| `receipt` \| `payment_proof` \| `deposit_proof` \| `document` \| `other` |
| `title`, `file_path`, `mime_type`, `size` | |
| `uploaded_by` | FK users nullable |
| `metadata`, `timestamps` | |

- التخزين على قرص خاص (`local`) حسب نمط رفع الملفات القائم في المشروع، مع رابط تحميل محمي.

### 5.3 الـ Enums الجديدة (`app/Enums/`)

| Enum | القيم | ملاحظة |
| :--- | :--- | :--- |
| `OperationWorkflowStatus` | new, in_progress, employee_custody, at_treasury, allocated, awaiting_settlement, awaiting_deposit, partially_settled, pending_amount, unmatched, awaiting_approval, escalated, retroactive_settlement, completed, cancelled, waived | مع `HasLabel/HasColor/HasIcon` و`stage()` |
| `CostBearer` | client_pays, client_debt, office, shared | |
| `BeneficiaryType` | company, employee, customer, external | |
| `CustodyEntryType` | collection, handover_out, reversal, transfer | |
| `HandoverStatus` | pending, confirmed, rejected, cancelled | |
| `AllocationType` | settlement, deposit, excess, pending | |
| `AllocationStatus` | pending, assigned, in_progress, executed, failed, cancelled | |
| `SettlementStatus` | pending, proof_uploaded, approved, rejected, executed | |
| `DepositStatus` | pending, proof_uploaded, approved, rejected, executed | |
| `UnmatchedReceiptStatus` | held, suggested, approved, rejected, linked | |
| `ReconciliationStatus` | open, in_progress, matched, difference, escalated, resolved | |
| `ReconciliationMissingPart` | none, service, money, document, invoice, party, amount | |
| `OperationTaskType` | (كما في الجدول) | |
| `OperationTaskStatus` | open, in_progress, done, cancelled, escalated | |
| `OperationTaskPriority` | low, normal, high, critical | |
| `OperationEventType` | (كما في الجدول) | |

- كل Enum يطبق نفس نمط المشروع: `HasLabel`, `HasColor`, `HasIcon`، ونصوصه في ملفات اللغة.

### 5.4 العلاقات الجديدة
- `ServiceTransaction`: `customer()`, `invoice()`, `debt()`, `custodyEntries()`, `handovers()`, `allocations()`, `settlements()`, `deposits()` (عبر جدول الربط), `reconciliationCase()`, `tasks()`, `events()`, `attachments()`, `responsibleEmployee()`, `collector()`, `settlementOfficer()`, `depositOfficer()`.
- `Invoice`, `Debt`, `TreasuryTransaction`: `operation()` (BelongsTo) بعد إضافة `operation_id`.
- `Employee`: `custodyEntries()`, `custodyBalance()` accessor، `operationTasks()`، `settlements()`, `deposits()`.

---

## 6. آلة الحالة (Workflow) وقواعد الانتقال

### 6.1 الانتقالات المسموحة

| من | إلى | المحفّز | الحارس (Guard) |
| :--- | :--- | :--- | :--- |
| new | in_progress | إنشاء الفاتورة/الاستحقاق | اكتمال هوية المستفيد |
| in_progress | employee_custody | تسجيل تحصيل > 0 | مبلغ ≤ المتبقي، مستلم محدد |
| employee_custody | at_treasury | تأكيد استلام الخزنة | باركود صحيح + مبلغ مطابق للعهدة |
| at_treasury | allocated | تنفيذ التوزيع | مجموع التوزيعات = المستلم |
| allocated | awaiting_settlement | وجود توزيع سداد | مسؤول سداد محدد |
| allocated | awaiting_deposit | وجود توزيع إيداع | مسؤول إيداع محدد |
| allocated | pending_amount | وجود توزيع معلق | لا يزال هناك مبلغ بلا ربط |
| awaiting_settlement | partially_settled | سداد جزئي | — |
| awaiting_settlement / partially_settled | completed | اكتمال السداد + المطابقة | `pending_amount = 0` و`matching_status = matched` |
| awaiting_deposit | completed | إيداع معتمد + مطابقة | نفس الشرط |
| أي حالة (قبل الإغلاق) | unmatched | اكتشاف فرق/نقص | تسجيل الجزء الناقص والمسؤول |
| unmatched | awaiting_approval | إرسال تصحيح | مستند مرفق + سبب |
| awaiting_approval | completed | اعتماد المشرف | صلاحية اعتماد |
| أي حالة (قبل الإغلاق) | escalated | عائق خارج الصلاحية | سبب + مستندات |
| escalated | (الحالة السابقة) | حل العائق | اعتماد الإدارة |
| completed | retroactive_settlement | استكمال متأخر | سبب + اعتماد |
| أي حالة (قبل الإغلاق) | cancelled / waived | إلغاء/تنازل | سبب واضح + اعتماد المشرف |
| — | completed | إغلاق | لا إغلاق مع فرق مفتوح أو مستند ناقص أو اعتماد معلّق |

### 6.2 قواعد إلزامية (من PDF → كود)
1. **لا إعادة إدخال معلومة قابلة للاستنتاج:** أي حقل يمكن استنتاجه من العميل/المنشأة/الموظف/الخدمة يُملأ تلقائياً، ويُقفل التعديل عليه إلا بصلاحية.
2. **لا إغلاق قبل المطابقة:** `OperationWorkflowService::assertCanClose()` يتحقق من `pending_amount`, `difference_amount`, `matching_status`, الاعتمادات، والمستندات.
3. **المسؤولية تتبع الجزء الناقص:** `responsible_employee_id` يُعاد ضبطه عند كل انتقال بناءً على `missing_part`.
4. **كل انتقال = حدث تدقيق + إشعار:** إلزامي عبر `OperationWorkflowService` فقط.
5. **لا حذف للسجل:** `operation_events`, `employee_custody_entries`, `TreasuryTransaction` تمنع الحذف والتعديل المالي المباشر.
6. **الزيادة لا تُفقد:** أي مبلغ زائد عن المتبقي يُسجل `excess_amount` ويبقى مرتبطاً بالعملية/العميل.
7. **التأخير لا يُحمَّل على من أنهى عمله:** يُحسب SLA على `responsible_employee_id` الحالي فقط.
8. **الاعتماد قبل الترحيل:** المبلغ المعلق لا يؤثر على الأرصدة قبل اعتماد الربط.

---

## 7. طبقة الخدمات (`app/Services/Operations/`)

> كل الحسابات والانتقالات والحفظ المجمع تُنفذ هنا فقط. الصفحات والإجراءات تبقى رقيقة (قاعدة 2.b).

| الخدمة | المسؤولية | أبرز الدوال |
| :--- | :--- | :--- |
| `OperationCreationService` | إنشاء العملية وتعبئة البيانات تلقائياً وإنشاء الفاتورة/الدين | `create()`, `resolveBeneficiary()`, `autoFill()`, `assertBeneficiaryComplete()`, `createInvoice()`, `createDebtIfNeeded()` |
| `BeneficiaryResolutionService` | قواعد الانتقال بين عميل/منشأة/موظف | `companyForEmployee()`, `employeesOfCompany()`, `mustSelectEmployee()`, `normalizeTarget()` |
| `OperationWorkflowService` | آلة الحالة والحراس والاعتمادات | `transition()`, `canTransition()`, `assertCanClose()`, `requiredApprovals()`, `nextResponsible()` |
| `CustodyService` | التحصيل والعهدة وتسليم/استلام الخزنة | `recordCollection()`, `createHandover()`, `confirmHandover()`, `rejectHandover()`, `employeeBalance()`, `reverse()` |
| `TreasuryAllocationService` | توزيع المال المستلم | `allocate()`, `reallocate()`, `assignOfficers()`, `assertBalanced()` |
| `SettlementService` | تنفيذ السداد ورفع الإثبات والاعتماد | `create()`, `uploadProof()`, `approve()`, `execute()`, `reimburseEmployee()` |
| `BankDepositService` | الإيداع الفردي والمجمع | `create()`, `attachOperations()`, `validateSplit()`, `approve()`, `execute()` |
| `UnmatchedReceiptService` | المبالغ المعلقة والاقتراح الذكي | `hold()`, `suggest()`, `approveLink()`, `reject()`, `post()` |
| `ReconciliationService` | المطابقة الثنائية والنواقص | `match()`, `detectMissingPart()`, `openCase()`, `rematch()`, `resolve()`, `differenceReport()` |
| `OperationTaskService` | بنك المهام والتصعيد والإغلاق التلقائي | `createTask()`, `closeForOperation()`, `escalate()`, `overdueTasks()`, `syncFromReconciliation()` |
| `OperationBalanceService` | أرصدة العميل/الموظف/الخزنة/المكتب | `clientBalance()`, `employeeBalance()`, `treasuryAllocationSummary()`, `officeLiability()`, `refreshOperationTotals()` |
| `OperationKpiService` | المؤشر المالي ومؤشرات الأداء والتقييم | `financialIndicator()`, `employeeScore()`, `companyScore()`, `clientScore()`, `dashboardStats()` |
| `OperationEventService` | سجل التدقيق الإلحاقي | `log()`, `history()`, `timeline()` |
| `OperationNotificationService` | إشعارات المراحل والتصعيد | `notifyResponsible()`, `notifyTreasury()`, `notifyOfficers()`, `notifyManagement()` |
| `OperationBarcodeService` | توليد/التحقق من باركود التسليم | `generate()`, `renderSvg()`, `verify()` |
| `OperationReportService` | تقارير PDF/Excel | `operationStatement()`, `custodyReport()`, `reconciliationReport()`, `exportExcel()` |

### 7.1 تفصيل المنطق الحرج
- **`OperationCreationService::create()`**:
  1. حل الشركة (من العميل/الموظف/المستخدم) عبر `CompanyFormService::resolveOrCreateCompanyByName()` عند الحاجة.
  2. تحديد `beneficiary_type` والتحقق من اكتمال الهوية (اسم + هوية/سجل + جوال للعميل، أو موظف محدد للمنشأة).
  3. نسخ بيانات الخدمة (`price`, `default_gov_fee`, `category`) و`payment` و`due_date`.
  4. إنشاء `Invoice` + `InvoiceItem` (خدمة واحدة أو أكثر) وربطها بالعملية.
  5. عند `client_debt` → إنشاء `Debt` مربوط بالعملية والعميل.
  6. ضبط `expected_amount` و`responsible_employee_id` (منشئ العملية افتراضياً).
  7. تسجيل حدث `created` + إشعار.
- **`CustodyService::recordCollection()`**: ينشئ `employee_custody_entries` (in) + يحدّث `collected_amount`/`custody_amount` + `paid_amount` في الفاتورة/الدين + `workflow_status` + حدث + إشعار خزنة.
- **`confirmHandover()`**: يتحقق من الباركود والمبلغ والمسؤول، ينشئ `TreasuryTransaction` (INCOMING) مرتبطاً بالعملية، ينشئ حركة عهدة (out)، يحدّث `treasury_received_amount`، ينقل الحالة إلى `at_treasury`، ثم يشغّل التوزيع.
- **`ReconciliationService::match()`**: يقارن `expected_amount` مع مجموع الحركات المالية الموثقة، ويتحقق من الفاتورة والمستند والجهة، ويحدد `missing_part`، ويعيد تعيين `responsible_employee_id`، وينشئ/يغلق مهمة.
- **`OperationTaskService::syncFromReconciliation()`**: مهمة واحدة مفتوحة لكل (عملية + نوع نقص) لتجنب تكرار المهام.

---

## 8. الأرصدة والمؤشرات (المعادلات المعتمدة)

### 8.1 أرصدة العميل
- `due` = مجموع `Invoice.total_amount` + `Debt.total_amount` للعميل.
- `paid` = مجموع التحصيلات الموثقة (`collected_amount` للعمليات المرتبطة + `DebtCollection`).
- `remaining` = `due - paid`.
- `overpaid` = `max(0, paid - due)`.
- `overdue_debts` = ديون `due_date < today` وحالتها غير مسددة.

### 8.2 رصيد الموظف
- `current_custody` = مجموع حركات `employee_custody_entries` (in - out) للحركات غير المسددة.
- `delivered_to_treasury` = مجموع `handover_out` المؤكدة.
- `paid_from_own_money` = مجموع `settlements` بنوع `employee_reimbursement` المعتمدة.
- `due_to_employee` = `paid_from_own_money` - ما تم تعويضه.

### 8.3 رصيد الخزنة
- `available` = رصيد `TreasuryTransaction` التراكمي (`balance_after` لآخر حركة).
- `allocated_for_settlement` = مجموع توزيعات `settlement` غير المنفذة.
- `allocated_for_deposit` = مجموع توزيعات `deposit` غير المنفذة.
- `excess` = مجموع توزيعات `excess` غير المسواة.
- `pending` = مجموع توزيعات `pending` + `unmatched_receipts` بحالة `held/suggested`.

### 8.4 رصيد المكتب (الالتزامات)
- `office_liability` = مجموع `office_share_amount` للعمليات.
- `office_paid` = مجموع السداد المرتبط بها.
- `office_remaining` = الفرق.

### 8.5 المؤشر المالي
- `expected` = مجموع `expected_amount`.
- `documented_paid` = مجموع `settled_amount` + `deposited_amount` الموثقة.
- `remaining` = `expected - documented_paid`.
- `pending` = `pending_amount`.
- `unapproved` = العمليات بحالة `awaiting_approval`.
- `overdue` = مهام SLA متجاوزة + عمليات تجاوزت `due_date`.
- `differences` = مجموع `|difference_amount|` للعمليات غير المغلقة.

### 8.6 التقييم
- تقييم الموظف = (مهام أُنجزت في وقتها) / (إجمالي مهامه) مع استبعاد التأخير الناتج عن طرف آخر.
- تجميع على مستوى القسم/المؤسسة/العميل عبر `OperationKpiService`.
- لا تُخزَّن النتائج إلا في كاش دوري (5–15 دقيقة) لتجنب البطء.

---

## 9. الصلاحيات والأدوار

### 9.1 الأدوار (Spatie) — تُنشأ كأدوار قابلة للتركيب
| الدور | الغرض |
| :--- | :--- |
| `operations_employee` | إنشاء الخدمة، تحصيل مبلغ، تسليم للخزنة، استكمال الجزء المسند |
| `treasury_officer` | التحقق وتأكيد الاستلام ومتابعة المال بالخزنة |
| `settlement_officer` | تنفيذ السداد ورفع الإثبات |
| `deposit_officer` | إدارة دفعات الإيداع ورفع الإثبات |
| `operations_supervisor` | اعتماد الإلغاء والتصحيحات والتسويات |
| `management` | التصعيد والاستثناءات والتقارير |

> تُدار الأدوار من شاشة الأدوار الحالية، ولا يُثبَّت أي اسم دور في الكود؛ الاعتماد على الصلاحيات.

### 9.2 الصلاحيات (Spatie) المقترحة
`view_any_operation`, `view_operation`, `create_operation`, `update_operation`, `delete_operation`,
`approve_operation`, `cancel_operation`, `waive_operation`,
`collect_operation_payment`, `confirm_custody_handover`, `reject_custody_handover`,
`execute_settlement`, `approve_settlement`, `execute_deposit`, `approve_deposit`,
`approve_unmatched_receipt`, `resolve_reconciliation`, `escalate_operation`,
`manage_operation_tasks`, `view_operation_reports`, `view_operation_audit`.

### 9.3 الحماية
- Policy لكل نموذج جديد: `OperationPolicy` (على `ServiceTransaction`)، `CustodyHandoverPolicy`, `SettlementPolicy`, `BankDepositPolicy`, `UnmatchedReceiptPolicy`, `ReconciliationCasePolicy`, `OperationTaskPolicy`, `OperationEventPolicy` (view only).
- إضافة الصلاحيات إلى `RoleForm::getPermissionGroups()` مع ترجمة `ar/en`.
- `HasSuperAdminBypass` في كل السياسات.
- فحص `company_id` في كل استعلام (عبر `HasCompanyScope` أو `applyAccessScope`).

---

## 10. الواجهة (Filament v5)

### 10.1 الموارد
| المورد | المسار | الصفحات |
| :--- | :--- | :--- |
| `Operations` (على `ServiceTransaction`) | `/operations` | List / Create (Wizard) / Edit / View |
| `CustodyHandovers` | `/custody-handovers` | List / Create / View |
| `Settlements` | `/settlements` | List / Create / Edit / View |
| `BankDeposits` | `/bank-deposits` | List / Create / Edit / View |
| `UnmatchedReceipts` | `/unmatched-receipts` | List / Create / Edit |
| `OperationTasks` | `/operation-tasks` | List / View |
| `ReconciliationCases` | `/reconciliation` | List / View (Custom page) |

كل مورد يلتزم بالهيكل الثابت:
```
app/Filament/Resources/<Module>/
    <Module>Resource.php
    Schemas/<Module>Form.php
    Tables/<Module>sTable.php
    Pages/...
```

### 10.2 الشاشات المخصصة
1. **`/operations-board`** — لوحة العمليات: أعمدة حسب `workflow_status` + عدادات + فلتر «مهامي» + متأخرات.
2. **`/operations/financial-indicator`** — المؤشر المالي والأرصدة (Widgets + جداول).
3. **`/custody/scan`** — شاشة مسح/إدخال باركود التسليم والتحقق الفوري.
4. **`/operations/reconciliation`** — طابور المطابقة: كل عملية مفتوحة، الجزء الناقص، المسؤول، زر إعادة مطابقة.

### 10.3 نموذج الإنشاء (Wizard)
- **الخطوة 1:** نقطة البداية: عميل (`Customer`) / منشأة (`Company`) / موظف (`Employee`) + نوع الخدمة.
  - منشأة → إلزام اختيار المستفيد (المنشأة أو موظف تابع لها).
  - موظف → جلب المنشأة تلقائياً.
- **الخطوة 2:** بيانات الخدمة والمستفيد (تُملأ تلقائياً، ويُقفل ما يمكن استنتاجه).
- **الخطوة 3:** التغطية المالية (`cost_bearer`) والنسب عند «مشترك» + تاريخ الاستحقاق.
- **الخطوة 4:** مراجعة: المبلغ المتوقع، الفاتورة، المسؤول، ملخص البيانات المستنتجة → «إنشاء العملية».
- تحقق فوري: منع الاستمرار إذا كانت هوية المستفيد ناقصة، برسالة عربية واضحة.

### 10.4 صفحة العرض (View)
تبويبات:
1. **الملخص:** الحالة، المسؤول الحالي، المبالغ، الأرصدة.
2. **التحصيل والعهدة:** حركات العهدة + التسليمات.
3. **الخزنة والتوزيع:** الحركة المالية + التوزيعات.
4. **السداد والإيداع:** السندات والإثباتات.
5. **المطابقة والمستندات:** المطابقة، الفروقات، المرفقات.
6. **سجل التدقيق:** الخط الزمني غير القابل للتعديل.

الإجراءات في الرأس حسب الحالة والصلاحية:
`تحصيل`, `تسليم للخزنة`, `تأكيد الاستلام`, `توزيع`, `سداد`, `إيداع`, `إعادة مطابقة`, `اعتماد`, `تصعيد`, `إلغاء/تنازل`, `إغلاق`.

### 10.5 الجداول والفلاتر
- استخدام `FilterBarService` + `HasFilterBarState` + `<x-filter-bar>`:
  - البحث: رقم العملية، العميل، المستفيد، الخدمة، رقم الفاتورة.
  - الفلاتر: الشركة، الفرع (عند توفره)، الحالة، نوع التغطية، الفترة، المسؤول الحالي، وجود فرق/نقص.
- أعمدة ملونة حسب دليل الألوان في PDF (أزرق بداية، أخضر نجاح/إكمال، برتقالي استحقاق، بنفسجي عهدة/معلق، سماوي خزنة/إيداع، أحمر استثناء/فرق/إلغاء، أخضر داكن إغلاق ومطابقة).

### 10.6 الطباعة
- سند قبض/تحصيل، سند تسليم خزنة (مع باركود)، سند سداد، سند إيداع، كشف حساب عملية، كشف عهدة موظف، تقرير مطابقة، تقرير مؤشر مالي.
- عبر `UniversalPrintService::route('system.print', ...)` مع إضافة الأنواع الجديدة، واستخدام `TafqeetService` لتفقيط المبالغ.
- إزالة BOM قبل mPDF حسب قاعدة 3.4.

---

## 11. التنبيهات والمهام المجدولة

1. **إشعارات فورية (Filament DB):** تُطلق من `OperationNotificationService` عند كل انتقال.
2. **تكامل `NotificationAlertsService`:** إضافة فئة `operations` تعرض:
   - عمليات متأخرة عن `due_date`.
   - عهد موظفين غير مسلّمة.
   - مبالغ معلقة بلا اعتماد.
   - فروقات مطابقة مفتوحة.
   - مهام بنك المهام المتأخرة.
3. **أمر مجدول `operations:check-sla`** يومياً (مثلاً 08:00):
   - تحديث المهام المتأخرة إلى `escalated` بعد تجاوز SLA.
   - إنشاء مهمة SLA للمسؤول الحالي.
   - إشعار المشرف/الإدارة.
   - تسجيل حدث `sla_breached` في سجل التدقيق.
   - تسجيله في `routes/console.php`.
4. **حدود SLA:** تُعرَّف في جدول `settings` (مفتاح `operations_sla`) لتكون قابلة للتعديل دون نشر كود.

---

## 12. التعريب وملفات اللغة

| الملف | المحتوى |
| :--- | :--- |
| `lang/ar/operations.php` / `lang/en/operations.php` | كل حقول وأقسام وحالات وأزرار العملية |
| `lang/ar/custody.php` / `lang/en/custody.php` | العهدة والتسليم والباركود |
| `lang/ar/settlements.php` / `lang/en/settlements.php` | السداد |
| `lang/ar/bank_deposits.php` / `lang/en/bank_deposits.php` | الإيداع |
| `lang/ar/unmatched_receipts.php` / `lang/en/unmatched_receipts.php` | المبالغ المعلقة |
| `lang/ar/operation_tasks.php` / `lang/en/operation_tasks.php` | بنك المهام |
| `lang/ar/reconciliation.php` / `lang/en/reconciliation.php` | المطابقة والفروقات |
| `lang/ar/navigation.php` | عنوان المجموعة/العناصر إن لزم |

- **ممنوع أي نص عربي/إنجليزي مكتوب داخل الكود** (قاعدة 5.5).
- مفاتيح متطابقة تماماً بين `ar` و`en`.

---

## 13. خطة التنفيذ المرحلية

> كل مرحلة = مهمة مستقلة قابلة للمراجعة والنشر، مع `php -l` و`pint` واختبارات، وتحديث `docs/SYSTEM_CHEATSHEET.md` عند إضافة أي مكوّن أساسي.

### المرحلة 0 — الأساس (1–2 يوم)
**المخرجات:**
- Migration جديدة لتوسعة `service_transactions` (لا تعديل على Migration قديمة).
- Migrations للجداول الأحد عشر الجديدة + indexes + FKs.
- الـ Enums.
- Models + Relations + Casts + `HasHijriDates` + `#[ObservedBy]` عند الحاجة.
- Policies + إضافة الصلاحيات إلى `RoleForm`.
- ملفات اللغة الأساسية.
- `OperationEventService`.
- Seeder اختياري لبيانات تجريبية.

**معيار القبول:** `php artisan migrate` ناجح، `php -l` و`pint` نظيفان، لا شاشات بعد.

### المرحلة 1 — الإنشاء الموحّد والفاتورة التلقائية (3–4 أيام)
**المخرجات:**
- `OperationCreationService` + `BeneficiaryResolutionService`.
- Wizard إنشاء العملية (4 خطوات) + التحقق من الهوية.
- إنشاء `Invoice` + `InvoiceItem` تلقائياً + `Debt` عند الحاجة.
- تعبئة تلقائية كاملة من العميل/المنشأة/الموظف/الخدمة.
- List + View (تبويب الملخص) + فلاتر.
- حدث `created` + إشعار المسؤول.

**معيار القبول:** إنشاء عملية من ثلاث نقاط بداية مختلفة دون إعادة إدخال أي معلومة متاحة، ومنع المتابعة عند نقص هوية المستفيد.

### المرحلة 2 — التحصيل والعهدة وتسليم الخزنة (3–4 أيام)
**المخرجات:**
- `CustodyService` + دفتر العهدة + `CustodyHandovers`.
- إجراء «تحصيل» (كلي/جزئي) مع تحديث الفاتورة/الدين.
- توليد باركود + شاشة مسح/تحقق.
- تأكيد/رفض الاستلام + حركة خزينة واردة + انتقال المسؤولية.
- رصيد الموظف ورصيد الخزنة.
- سندات قبض/تسليم PDF.

**معيار القبول:** مسار كامل: تحصيل → عهدة → تسليم → تأكيد، مع أرصدة صحيحة وحدث تدقيق لكل خطوة، ومنع حذف أي حركة.

### المرحلة 3 — التوزيع والسداد والإيداع والمبالغ المعلقة (3–4 أيام)
**المخرجات:**
- `TreasuryAllocationService` (سداد/إيداع/زائد/معلق).
- `SettlementService` + شاشة السداد + الإثبات + الاعتماد + التعويض للموظف.
- `BankDepositService` + الإيداع المجمع عبر عدة عمليات.
- `UnmatchedReceiptService` + الاقتراح الآلي + الاعتماد قبل الترحيل.
- حركات خزينة صادرة مرتبطة بالعملية.

**معيار القبول:** مجموع التوزيعات = المستلم دائماً؛ مجموع تفصيل الإيداع = إجمالي الإيداع؛ المبلغ المعلق لا يؤثر على الأرصدة قبل الاعتماد.

### المرحلة 4 — المطابقة الثنائية والفروقات (3–5 أيام)
**المخرجات:**
- `ReconciliationService` + `ReconciliationCases`.
- مقارنة (الخدمة/المبلغ/الجهة/الفاتورة/السند/المستند).
- تحديد الجزء الناقص وإعادة تعيين المسؤول.
- إعادة المطابقة الآلية بعد استكمال النقص.
- شاشة/طابور المطابقة + تقرير الفروقات.
- أحداث `matched` / `difference_found`.

**معيار القبول:** كل عملية غير مغلقة لها حالة مطابقة واضحة ومسؤول محدد؛ لا إغلاق مع فرق أو مستند ناقص.

### المرحلة 5 — بنك المهام والتنبيهات والتصعيد (2–3 أيام)
**المخرجات:**
- `OperationTaskService` + `OperationTasks` + صفحة «مهامي».
- توليد تلقائي للمهام من النواقص والفروقات والمبالغ المعلقة.
- التصعيد مع السبب والمستندات.
- `operations:check-sla` + تكامل `NotificationAlertsService`.
- Widgets: مهامي، متأخرات، تصعيدات.

**معيار القبول:** كل نقص يولّد مهمة واحدة فقط لمسؤول الجزء الناقص، وتُغلق تلقائياً عند استكماله.

### المرحلة 6 — الأرصدة والمؤشرات والتقييم (3–4 أيام)
**المخرجات:**
- `OperationBalanceService` + `OperationKpiService`.
- صفحة المؤشر المالي + بطاقات الأرصدة (عميل/موظف/خزنة/مكتب).
- تقييم الموظف/القسم/المؤسسة/العميل.
- تقارير PDF/Excel + كشف حساب العملية + كشف العهدة.

**معيار القبول:** مطابقة أرقام الأرصدة مع حركات الخزينة والتحصيلات بنسبة 100% على بيانات تجريبية، وتقرير فروقات يطابق الفروقات المسجلة.

### المرحلة 7 — سجل التدقيق والإلغاء/التنازل والتسويات اللاحقة (2–3 أيام)
**المخرجات:**
- صفحة الخط الزمني الكامل (قبل/بعد، المستخدم، الوقت، المبلغ، السبب، المستند).
- إجراء إلغاء/تنازل باعتماد المشرف وعدم حذف التاريخ.
- حالة `retroactive_settlement` واستكمال النواقص للخدمات المنفذة مسبقاً.
- تقرير تدقيق قابل للتصدير.

**معيار القبول:** أي انتقال/تصحيح/اعتماد يظهر في السجل، ويستحيل حذفه من الواجهة أو من الكود.

### المرحلة 8 — التثبيت والتوثيق (2–3 أيام)
**المخرجات:**
- Feature Tests للخدمات المالية الحساسة (العهدة، التوزيع، المطابقة، الأكواد).
- مراجعة أداء الاستعلامات (indexes + eager loading + كاش المؤشرات).
- تحديث `docs/SYSTEM_CHEATSHEET.md` بقسم كامل عن المحرك الموحد.
- دليل مستخدم مختصر داخل `docs/` (دورة العمل + الصلاحيات + الحالات).
- `php artisan optimize:clear` بعد النشر + تنبيه النشر في ملخص المهمة (قاعدة 3.6).

---

## 14. الاختبارات والتحقق

### 14.1 اختبارات آلية (`tests/Feature`)
1. `OperationCreationTest`: النقاط الثلاث، التعبئة التلقائية، منع نقص الهوية، إنشاء الفاتورة/الدين.
2. `CustodyFlowTest`: تحصيل جزئي/كلي، تسليم، تأكيد، رفض، أرصدة، منع الحذف.
3. `AllocationTest`: مجموع التوزيعات = المستلم، الزائد، المعلق.
4. `SettlementDepositTest`: سداد بإثبات، تعويض موظف، إيداع مجمع وتفصيله.
5. `UnmatchedReceiptTest`: الاحتجاز، الاقتراح، الاعتماد، عدم التأثير قبل الاعتماد.
6. `ReconciliationTest`: مطابقة ناجحة، فرق مبلغ، مستند ناقص، إعادة مطابقة، المسؤول الصحيح.
7. `WorkflowGuardTest`: منع الإغلاق مع فرق/نقص/اعتماد معلّق.
8. `AuditTrailTest`: تسجيل كل حدث، منع التحديث/الحذف.
9. `CodeGenerationTest`: تفرد الأكواد على مستوى الجدول (قاعدة `CodeGeneratorService`).

### 14.2 تحقق يدوي قبل التسليم
1. `php -l` على كل ملف معدّل.
2. `vendor\bin\pint` على الملفات المعدّلة.
3. `php artisan view:clear` بعد أي تعديل Blade.
4. `php artisan test` للاختبارات المضافة.
5. تجربة سيناريو كامل في المتصفح: عميل → خدمة → فاتورة → تحصيل → تسليم → خزنة → توزيع → سداد/إيداع → مطابقة → إغلاق.

---

## 15. المخاطر والقيود

| الخطر | الأثر | التخفيف |
| :--- | :--- | :--- |
| تعديل جدول `service_transactions` المستخدم في الإنتاج | كسر الشاشات الحالية | Migration إضافية فقط (أعمدة nullable)، وتحديث الشاشات بنفس المهمة، وعدم حذف أي عمود |
| ازدواجية مع `Debt`/`Invoice`/`TreasuryTransaction` | بيانات متعارضة | إعادة استخدامها مع `operation_id`، ومصدر واحد للحقيقة لكل رقم |
| `TreasuryTransaction` يمنع الحذف | تصحيح خاطئ | كل تصحيح بحركة عكسية موثقة، لا تعديل مباشر |
| بطء المؤشرات على بيانات كبيرة | تجربة سيئة | كاش 5–15 دقيقة + indexes + تجميع في الخدمة |
| صلاحيات غير مضبوطة | تسريب مالي بين الشركات | `company_id` + Policies + اختبارات عزل |
| إضافة اعتمادية الباركود | تغيير `composer.json` | موافقة صريحة + بديل نصي إن رُفضت |
| بيانات قديمة بلا ربط | تقارير ناقصة | سكربت ترحيل اختياري + شاشة «ربط العمليات القديمة» |

### 15.1 ترحيل البيانات الحالية
- بعد إضافة الأعمدة: تعبئة `workflow_status` للعمليات القديمة وفق القاعدة:
  - `completed` إذا `service_status = completed` و`payment_status = paid`.
  - `in_progress` إذا لا يوجد تحصيل.
  - `employee_custody` إذا `paid_amount > 0` ولا توجد حركة خزينة مرتبطة.
  - `at_treasury` إذا توجد حركة خزينة واردة مرتبطة.
- إنشاء حدث `imported` لكل عملية قديمة في سجل التدقيق.
- عدم ربط الفواتير القديمة تلقائياً إلا بمراجعة يدوية (لتجنب ربط خاطئ).

---

## 16. القرارات المطلوبة قبل بدء التنفيذ

1. **الكيان الأساسي:** تطوير `service_transactions` (الموصى به) أم إنشاء جدول `financial_operations` مستقل؟
2. **العميل:** هل `Customer` هو العميل الرسمي في كل العمليات، أم يبقى `Company` هو الأساس مع `Customer` اختياري؟
3. **الباركود:** الموافقة على إضافة `picqer/php-barcode-generator` أم الاعتماد على رمز نصي فقط في المرحلة الأولى؟
4. **الخزنة:** هل تبقى على مستوى الشركة أم نضيف مستوى الفرع في هذه الخطة؟
5. **الأدوار:** إنشاء أدوار Spatie جاهزة (6 أدوار) أم صلاحيات فقط تُركَّب يدوياً؟
6. **الأولوية:** هل نبدأ بالمرحلة 0+1 فوراً أم تسبقها مراجعة بصرية للواجهات (Mockups)؟
7. **الربط مع الأقساط/الديون:** هل تُدرج الأقساط داخل نفس المحرك أم تبقى وحدة مستقلة مع ربط تقارير فقط؟

---

## 17. المخرجات النهائية المتوقعة

1. محرك عمليات موحد يعمل من الإنشاء حتى الإغلاق.
2. أحد عشر جدولاً جديداً + توسعة `service_transactions` + ثلاثة روابط (`invoices`, `debts`, `treasury_transactions`).
3. 16 Enum و16 خدمة (أو أقل بعد الدمج) و8 سياسات.
4. 7 موارد Filament + 4 شاشات مخصصة + Widgets.
5. سجل تدقيق إلحاقي كامل.
6. بنك مهام وتنبيهات وتصعيد مع SLA.
7. أرصدة ومؤشرات مالية وتقييم أداء.
8. تقارير PDF/Excel وسندات مطبوعة.
9. اختبارات Feature للعمليات المالية الحساسة.
10. تحديث `docs/SYSTEM_CHEATSHEET.md` ودليل مستخدم مختصر.
