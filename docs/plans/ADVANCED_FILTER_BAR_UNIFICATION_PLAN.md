# خطة توحيد شريط الفلاتر المتقدم والتحكم المعياري المرن لكل صفحة
**Unified & Modular Advanced Filter Bar Architecture Plan**

---

## 1. الهدف الأساسي ومتطلبات التحكم المرن

تحتاج كل صفحة في النظام إلى **تشكيلة فلاتر خاصة بها ومناسبة لطبيعة بياناتها**؛ فلا يمكن فرض نفس الفلاتر على الجميع:
- **صفحات تتطلب المؤسسة (Company):** مثل الفواتير، الحسابات، الخزينة، الفروع، والموظفين.
- **صفحات عامة أو مستقلة لا تحتاج للمؤسسة إطلاقاً:** مثل الأدوار والصلاحيات (`Roles`)، إعدادات النظام (`Settings`)، تصنيفات العقود (`ContractCategories`)، أو الأسئلة الشائعة (`Faqs`).
- **صفحات تتطلب نطاق تواريخ:** مثل الحركات والعمليات المالية والمهام والإجازات.
- **صفحات ثابتة لا تحتاج لتواريخ:** مثل دليل الخدمات، المسميات الوظيفية، أو أجهزة البصمة.
- **صفحات تحتاج فلاتر خاصة جداً بها:** مثل (نوع التقرير، طريقة السداد، اسم الضامن، نوع القسط).

> [!IMPORTANT]
> **القاعدة المعمارية الذهبية:** الكومبوننت والسيرفس والـ Trait يجب أن تكون **قابلة للتركيب والتخصيص التام (Fully Composable & Modular)**؛ بحيث يحدد المبرمج في كل صفحة بدقة متناهية: ما هي الفلاتر المعروضة؟ وما هي الفلاتر المستبعدة؟ وأي فلاتر مخصصة يرغب في حقنها بسهولة.

---

## 2. معمارية التحكم المرن ثلاثية الطبقات

```
┌────────────────────────────────────────────────────────────────────────┐
│                        معمارية الفلاتر الموحدة والمرنة                  │
├────────────────────────────────────────────────────────────────────────┤
│ 1. طبقة الواجهة (Blade Component):                                     │
│    resources/views/components/filter-bar.blade.php                     │
│    -> مفاتيح تشغيل وإطفاء مرنة (showCompany, showBranch, showDates...) │
│    -> فتحات (Slots) لحقن أي فلاتر مخصصة في أي موضع (prepend / append) │
├────────────────────────────────────────────────────────────────────────┤
│ 2. طبقة الـ Livewire (PHP Trait):                                      │
│    app/Filament/Traits/HasFilterBarState.php                           │
│    -> خفة ومرونة: لا تستعلم إلا عن الفلاتر المفعّلة فعلياً للصفحة     │
├────────────────────────────────────────────────────────────────────────┤
│ 3. طبقة السيرفس (Service Layer - نمط Factory مثل CompanyFormService):   │
│    app/Services/FilterBarService.php                                   │
│    -> ميثودات مستقلة لكل فلتر على حدة يمكن استدعاؤها فرادى أو مجمعة  │
│    -> FilterBarService::make(['company' => false, 'date' => '...'])    │
└────────────────────────────────────────────────────────────────────────┘
```

---

## 3. تفاصيل آليات التحكم الموديلار (Modular Control)

### أ) في طبقة السيرفس: `FilterBarService.php`

توفر السيرفس طريقتين للتحكم بحسب رغبة المطور:

#### الطريقة الأولى: استدعاء الفلاتر فرادى (مثل `OrgSelect` و `CompanyFormService`):
```php
// إذا كانت الصفحة تحتاج فقط: تاريخ + حالة (بدون مؤسسة وبدون فرع):
$table->filters([
    FilterBarService::makeDateRangeFilter('created_at'),
    FilterBarService::makeStatusFilter(TicketStatus::class),
], layout: \Filament\Tables\Enums\FiltersLayout::AboveContent);
```

#### الطريقة الثانية: مصفوفة إعدادات مرنة بسطر واحد (`FilterBarService::make([...])`):
```php
// صفحة مالية تحتاج المؤسسة والفرع والتاريخ:
$table->filters(
    FilterBarService::make([
        'company' => true,
        'branch'  => true,
        'date'    => 'invoice_date',
        'status'  => InvoicePaymentStatus::class,
    ]),
    layout: \Filament\Tables\Enums\FiltersLayout::AboveContent
);

// صفحة عامة مستقلة لا تريد المؤسسة ولا الفرع:
$table->filters(
    FilterBarService::make([
        'company' => false, // مستبعد تماماً
        'branch'  => false, // مستبعد تماماً
        'date'    => 'created_at',
        'status'  => ['active' => 'نشط', 'inactive' => 'معطل'],
    ]),
    layout: \Filament\Tables\Enums\FiltersLayout::AboveContent
);
```

---

### ب) في طبقة واجهة Blade: `<x-filter-bar>`

كل عنصر عبارة عن `Boolean Flag` افتراضه معطل أو قابل للتفعيل حسب رغبة الصفحة، مع فتحات (Slots) لإضافة فلاتر خاصة:

```blade
{{-- مثال 1: صفحة تحتاج المؤسسة والتواريخ والبحث --}}
<x-filter-bar 
    :show-company="true"
    :companies="$this->companies"
    :show-dates="true"
    :show-search="true"
    :has-active="$this->hasActiveFilterBar()"
/>

{{-- مثال 2: صفحة عامة (مثل الأدوار والصلاحيات أو الإعدادات) لا تريد المؤسسة ولا التواريخ --}}
<x-filter-bar 
    :show-company="false"
    :show-branch="false"
    :show-dates="false"
    :show-status="true"
    :status-options="['active' => 'نشط', 'inactive' => 'معطل']"
    :show-search="true"
    search-placeholder="ابحث بالاسم أو الصلاحية..."
    :has-active="$this->hasActiveFilterBar()"
/>

{{-- مثال 3: صفحة التقارير العامة (تحتاج حقل نوع التقرير أولاً وحقل الفرع بدون المؤسسة) --}}
<x-filter-bar 
    :show-company="false"
    :show-branch="true"
    :branches="$this->branches"
    :show-dates="true"
    :show-status="true"
    :status-options="$this->statusOptions"
    :has-active="$this->hasActiveFilterBar()"
>
    {{-- حقن حقل نوع التقرير في بداية الشريط --}}
    <x-slot name="prepend">
        <div class="filter-item">
            <label>{{ __('reports.filters.report_type') }}</label>
            <select wire:model.live="reportType" class="filter-input">
                @foreach($this->availableReports as $key => $r)
                    <option value="{{ $key }}">{{ $r['title'] }}</option>
                @endforeach
            </select>
        </div>
    </x-slot>

    {{-- حقن حقل الضامن في نهاية الشريط عند اختيار تقرير الضامنين فقط --}}
    <x-slot name="append">
        @if($reportType === 'guarantors')
            <div class="filter-item">
                <label>{{ __('guarantors.single') }}</label>
                <select wire:model.live="guarantorId" class="filter-input">
                    <option value="">جميع الضامنين</option>
                    @foreach($this->guarantors as $g)
                        <option value="{{ $g->id }}">{{ $g->name }}</option>
                    @endforeach
                </select>
            </div>
        @endif
    </x-slot>
</x-filter-bar>
```

---

### ج) في معالجة الاستعلامات (Query Filtering):

في ميثود `FilterBarService::applyFilters($query, $state, $options)`:
الاستعلام ذكي ولا يطبق أي شرط إلا إذا كان الفلتر مطلوباً وموجوداً في خيارات الصفحة:
```php
public static function applyFilters(Builder $query, array $state, array $options = []): Builder
{
    // شرط المؤسسة: لا يتم تطبيقه إلا إذا كان مفعلاً للصفحة وموجوداً في الـ state
    if (!empty($options['company']) && !empty($state['company_id'])) {
        $query->where($options['company_column'] ?? 'company_id', $state['company_id']);
    }

    // شرط الفرع: لا يتم تطبيقه إلا إذا كان مفعلاً للصفحة
    if (!empty($options['branch']) && !empty($state['branch_id'])) {
        $query->where($options['branch_column'] ?? 'branch_id', $state['branch_id']);
    }

    // شرط التاريخ: لا يتم تطبيقه إلا إذا تم تحديد اسم عمود التاريخ
    if (!empty($options['date_column'])) {
        $col = $options['date_column'];
        $query->when(!empty($state['date_from']), fn($q) => $q->whereDate($col, '>=', $state['date_from']))
              ->when(!empty($state['date_to']), fn($q) => $q->whereDate($col, '<=', $state['date_to']));
    }

    // شرط الحالة
    if (!empty($options['status']) && isset($state['status']) && $state['status'] !== 'all') {
        $query->where($options['status_column'] ?? 'status', $state['status']);
    }

    // شرط البحث
    if (!empty($state['search']) && !empty($options['search_columns'])) {
        $query->where(function ($sub) use ($options, $state) {
            foreach ($options['search_columns'] as $col) {
                $sub->orWhere($col, 'like', "%{$state['search']}%");
            }
        });
    }

    return $query;
}
```

---

## 4. جدول أمثلة على تنوع صفحات النظام وكيفية تغطيتها

| نوع الصفحة | مثال عليها | المؤسسة (`company`) | الفرع (`branch`) | التاريخ (`dates`) | الحالة (`status`) | فلاتر إضافية مخصصة |
| :--- | :--- | :---: | :---: | :---: | :---: | :--- |
| **مالية / تشغيلية** | الخزينة (`TreasurySummary`) | ✅ نعم | ❌ لا | ✅ نعم | ❌ لا | نوع الحركة (قبض/صرف)، بند التحصيل |
| **تقارير عامة شاملة** | التقارير العامة (`GeneralReports`) | ❌ من خلال الفرع | ✅ نعم | ✅ نعم | ✅ نعم | نوع التقرير المنسدل، الضامن |
| **أقساط وعقود** | ملخص الأقساط (`InstallmentsSummary`) | ✅ نعم | ❌ لا | ✅ نعم | ✅ نعم | نوع المعاملة، الفئة، نوع المستفيد |
| **صناديق تكافل** | كشف الأصول (`AssetFundStatement`) | ❌ لا | ❌ لا | ✅ نعم | ❌ لا | اختيار الصندوق، نوع السند |
| **إدارية عامة** | الأدوار والصلاحيات (`Roles`) | ❌ **مستبعدة** | ❌ **مستبعدة** | ❌ **مستبعدة** | ❌ **مستبعدة** | بحث بالاسم والصلاحيات فقط |
| **تنظيمية داخلية** | المسميات الوظيفية (`JobTitles`) | ✅ نعم | ❌ لا | ❌ **مستبعدة** | ✅ نعم | القسم (`department_id`) |

---

## 5. خطة التطبيق والاعتماد

1. **الخطوة 1:** بناء كلاس السيرفس `app/Services/FilterBarService.php` متضمناً دوال التوليد المستقلة والدالة المجمعة القابلة للإطفاء والتشغيل.
2. **الخطوة 2:** بناء سمة `app/Filament/Traits/HasFilterBarState.php` مع مراعاة دعم الحقول المخصصة.
3. **الخطوة 3:** بناء مكون الـ Blade `resources/views/components/filter-bar.blade.php` بجميع الـ Flags والـ Slots الموضحة أعلاه.
4. **الخطوة 4:** البدء بتطبيقه على صفحات المستوى الثاني الـ 6، بحيث نرى كيف تأخذ صفحة الخزينة فلاترها الخاصة، وتأخذ صفحة التقارير فلاترها الخاصة، بدون أي تعارض أو إجبار لأي حقل.
