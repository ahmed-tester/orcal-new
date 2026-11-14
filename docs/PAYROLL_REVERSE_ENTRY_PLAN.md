# خطة تنفيذ آلية الحركة العكسية (Reverse Entry) للخزينة عند حذف مسيرات الرواتب

## 1. الهدف والمبرر المحاسبي
تحقيق التوافق المالي والمحاسبي الكامل (Audit Trail) عند حذف أو إلغاء مسير راتب أو دفعة راتب سبق صرفها:
- في حال صُرف للموظف مبلغ (مثلاً 6,000 ر.س من راتب 10,000 ر.س)، تم تسجيل حركة صرف سابقة بالخزينة.
- عند حذف المسير، بدلاً من حذف سند الخزينة التاريخي، يتم توليد **حركة عكسية (سند إيداع/وارد)** بنفس القيمة بعنوان واضح: `استرداد مسير رواتب ملغى: [اسم الموظف] ([الشهر]) [رقم المسير]`.
- يعود رصيد الخزينة إلى وضعه الدقيق، وتعود مستحقات الموظف في مسير الرواتب غير مسددة بالكامل، ويبقى السجل المالي موثقاً وشفافاً.

---

## 2. مصفوفة الحركات العكسية
| نوع العملية الأصلية | الحركة الأصلية بالخزينة | الحركة العكسية عند الحذف | التصنيف / البند المالي |
| :--- | :--- | :--- | :--- |
| **مسير رواتب (Payroll)** | `OUTGOING` (صرف نقدي) | `INCOMING` (إيداع / استرداد) | `SALARY_PAYMENT` / استرداد مسير رواتب ملغى |
| **مسير تحصيل (Collection)** | `INCOMING` (تحصيل وارد) | `OUTGOING` (صرف / استرداد) | `SALARY_COLLECTION` / استرداد مسير تحصيل ملغى |

---

## 3. المعمارية ومكونات التنفيذ

### 3.1 طبقة الخدمات (`app/Services/PayrollCalculationService.php`)
إضافة دالتين مخصصتين للعمليات العكسية:
1. `reverseDisbursementPayment(PayrollPayment $payment, ?string $reason = null): ?TreasuryTransaction`:
   - التحقق من وجود حركة خزينة مرتبطة بالدفعة (`$payment->treasury_transaction_id`).
   - تحديد اتجاه الحركة العكسية (إذا كانت الأصلية `OUTGOING` تصبح `INCOMING` والعكس).
   - إنشاء حركة خزينة جديدة مع بيان استرداد رسمي، وتحديث الرصيد التراكمي للخزينة تلقائياً.
   - تسجيل الحدث في قناة اللوج `Log::channel('payroll')`.
2. `reverseDisbursementsForRecord(PayrollRecord $record, ?string $reason = null): int`:
   - تكرار على كافة الدفعات المرتبطة بالمسير وعكسها بالتتابع داخل `DB::transaction`.

### 3.2 خطافات دورة حياة الموديل (Model Lifecycle Hooks)
1. **`PayrollRecord`**:
   - في حدث `deleting`، استدعاء `PayrollCalculationService::reverseDisbursementsForRecord($record)`.
2. **`PayrollPayment`**:
   - في حدث `deleting`، استدعاء `PayrollCalculationService::reverseDisbursementPayment($payment)`.
3. **`PayrollSheetService::deleteEmployeeRecord`**:
   - ضمان استدعاء العكس المالي قبل إزالة السجلات من قاعدة البيانات.

### 3.3 ملفات الترجمة (Localization)
إضافة مفاتيح النصوص باللغتين العربية والإنجليزية في:
- `lang/ar/finance.php`
- `lang/en/finance.php`

---

## 4. خطوات التحقق والاختبار (Verification Checklist)
1. فحص الصياغة `php -l` لكافة الملفات المعدلة.
2. تشغيل `vendor/bin/pint` لضمان معايير الكود.
3. اختبار عملي لسيناريو الحذف.
