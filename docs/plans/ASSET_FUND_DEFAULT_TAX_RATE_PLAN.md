# خطة إضافة نسبة الضريبة الافتراضية للصناديق والسندات (Asset Funds Default Tax Rate)

## 1. الهدف من المهمة
تمكين المستخدم من تحديد **«نسبة الضريبة الافتراضية (%)»** على مستوى الصندوق (`AssetFund`)، بحيث يتم تعبئة نسبة الضريبة تلقائياً عند إنشاء أي سند إيداع أو صرف (`AssetFundVoucher`) فور اختيار الصندوق، مع إمكانية تعديلها يدوياً إذا لزم الأمر.

---

## 2. خطوات التنفيذ التفصيلية

### الخطوة 1: الهجرة وقاعدة البيانات (Migration)
- إنشاء ملف هجرة جديد: `add_default_tax_percentage_to_asset_funds_table.php`.
- إضافة عمود `default_tax_percentage` من نوع `decimal(5, 2)->default(15.00)->nullable()` في جدول `asset_funds`.

### الخطوة 2: تحديث الموديل (`AssetFund.php`)
- إضافة `default_tax_percentage` إلى مصفوفة `$fillable`.
- إضافة `default_tax_percentage` إلى دالة `casts()` كـ `float` أو `decimal:2`.

### الخطوة 3: تحديث نموذج بيانات الصندوق (`AssetFundForm.php`)
- إضافة حقل `TextInput::make('default_tax_percentage')` داخل قسم المعلومات الأساسية (`basic_info`).
- الخصائص:
  - `label`: `__('asset_funds.asset_funds.fields.default_tax_percentage')`
  - `numeric()`
  - `suffix('%')`
  - `default(15)`
  - `minValue(0)`
  - `maxValue(100)`
  - `helperText`: `__('asset_funds.asset_funds.helpers.default_tax_percentage')`

### الخطوة 4: تحديث نموذج السندات (`AssetFundVoucherForm.php`)
- عند تغيير أو اختيار الصندوق `asset_fund_id`:
  - جلب قيمة `default_tax_percentage` من الصندوق المختار.
  - تعيين `tax_percentage` تلقائياً بالقيمة المسحوبة إذا كان السند جديداً.
  - إعادة احتساب المبالغ تلقائياً (`recalculateDepositFromTaxPercentage` أو `recalculateWithdrawal`).
- في دالة `deriveTaxPercentage` أو عند تهيئة النموذج (Create Voucher):
  - إذا كان النموذج في وضع الإنشاء، تعيين القيمة الافتراضية لحقل `tax_percentage` من الصندوق المختار أو `15%`.

### الخطوة 5: ملفات الترجمة (Localization)
- تحديث `lang/ar/asset_funds.php`:
  - إضافة مفاتيح: `default_tax_percentage` في `fields` و `helpers`.
- تحديث `lang/en/asset_funds.php`:
  - إضافة المفاتيح المطابقة باللغة الإنجليزية.

### الخطوة 6: الفحص والاختبار
- فحص بناء الجملة لجميع الملفات المعدلة عبر `php -l`.
- تشغيل مسح الكاش `php artisan view:clear` و `php artisan optimize:clear`.
- فحص الأكواد عبر `vendor\bin\pint`.

