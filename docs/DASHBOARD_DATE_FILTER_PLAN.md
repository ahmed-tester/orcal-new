# خطة تنفيذ فلتر التاريخ في لوحة التحكم (Filament Dashboard)

## 1. الهدف
إضافة فلتر زمني (تاريخ البداية وتاريخ النهاية) في أعلى صفحة لوحة التحكم الرئيسية (`/`) لتمكين المستخدم من تصفية المؤشرات والكروت المالية بناءً على أي فترة زمنية يحددها بدقة وتحديث النتائج فورياً.

---

## 2. الخطوات التنفيذية

### 2.1 إنشاء صفحة لوحة تحكم مخصصة (`App\Filament\Pages\Dashboard`)
- إنشاء الملف `app/Filament/Pages/Dashboard.php` يرث من `Filament\Pages\Dashboard`.
- استخدام `Filament\Pages\Dashboard\Concerns\HasFiltersForm` لتمكين نموذج الفلترة أعلى الصفحة.
- بناء حقول الفلترة داخل `filtersForm(Schema $schema)`:
  - `startDate` (من تاريخ)
  - `endDate` (إلى تاريخ)
  - تفعيل `live()` والتنسيق في قسم أنيق `Section` بعمودين وأيقونة تقويم.

### 2.2 تحديث موفر الخدمة (`AdminPanelProvider`)
- تعديل `app/Providers/Filament/AdminPanelProvider.php` لاستخدام `App\Filament\Pages\Dashboard::class` بدلاً من صفحة Filament الافتراضية.

### 2.3 تحديث ويدجت الإحصائيات المالية (`FinancialStatsOverviewWidget`)
- تضمين `Filament\Widgets\Concerns\InteractsWithPageFilters`.
- قراءة الفلاتر:
  ```php
  $startDate = $this->pageFilters['startDate'] ?? null;
  $endDate = $this->pageFilters['endDate'] ?? null;
  ```
- تصفية الحسابات:
  1. **الإيرادات والمبيعات**: تقييد الفواتير ومبيعات القيود اليومية بنطاق التاريخ.
  2. **مسيرات الرواتب**: تقييد `payroll_month` بنطاق التاريخ، مع البقاء على الشهر الحالي كافتراضي عند عدم التحديد.
  3. **الخزينة النقدية**: حساب الرصيد وتدفقات الفترة المحددة.
  4. **محفظة الأقساط والسلف**: تقييد الأقساط بتاريخ الاستحقاق/الإنشاء خلال الفترة المحددة.

### 2.4 ملفات الترجمة (`lang/ar/dashboard.php` و `lang/en/dashboard.php`)
- إضافة مفاتيح:
  - `filters.date_filter`
  - `filters.start_date`
  - `filters.end_date`
  - `stats.payroll_period`
  - `stats.period_flow`

---

## 3. التحقق والاختبار
1. `php -l` على كافة الملفات الجديدة والمعدلة.
2. `php artisan view:clear`
3. تنسيق الكود بواسطة `pint`.

