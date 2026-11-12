# خطة: التحكم في ترتيب القائمة الجانبية (Sidebar) من داخل لوحة الأدمن

> التاريخ: 2026-10-31 — مبنية على قراءة كود المشروع الفعلي (Filament v5.8) وليس على افتراضات.
> **الحالة: تم اعتماد النطاق من المستخدم — الترتيب عام على مستوى النظام، ويشمل ترتيب المجموعات وترتيب العناصر داخل مجموعتها الأصلية فقط (دون نقل العناصر بين المجموعات).**

---

## 1) الوضع الحالي (تحليل فعلي)

| العنصر | الواقع في المشروع | الملف |
| :--- | :--- | :--- |
| ترتيب **المجموعات** | ثابت ومكتوب يدويًا بترتيب `->navigationGroups([...])` (13 مجموعة) | `app/Providers/Filament/AdminPanelProvider.php:51` |
| ترتيب **العناصر داخل المجموعة** | `protected static ?int $navigationSort` داخل كل من 53 Resource + 9 Pages | `app/Filament/Resources/**` |
| اسم المجموعة | نص مترجم `__('navigation.xxx')` (لا يوجد Enum) | `lang/ar/navigation.php` |
| الشكل (CSS) | السايدبار هو سايدبار Filament القياسي + تنسيق ألوان مخصص فقط | `resources/views/filament/custom-sidebar.blade.php` |
| عنصر إضافي | عنصر «سجل الأخطاء» مسجّل inline في الـ Panel بدون `->key()` | `AdminPanelProvider.php:80` |

**خلاصة:** أي تعديل في الترتيب اليوم يحتاج تعديل كود + رفع + `optimize:clear`. المطلوب تحويله لإعداد محفوظ في قاعدة البيانات يعدله الأدمن من الواجهة.

### كيف يحسب Filament الترتيب فعليًا (من كود الـ vendor)
- `Filament\Navigation\NavigationManager::get()`:
  - العناصر تُرتَّب بـ `sortBy($item->getSort())` ثم تُجمَّع حسب المجموعة.
  - المجموعات تُرتَّب حسب **ترتيب تسجيلها في `->navigationGroups()`** (`array_search` على أسماء المجموعات).
- كل عنصر (Resource/Page) يُسجَّل بـ `->key(static::class)` → **مفتاح ثابت ومستقر** يمكن الاعتماد عليه في حفظ الترتيب.
- `NavigationManager` مربوط في الـ container كـ `scoped` → يمكن استبداله بكلاس خاص بنا (نقطة الاعتراض الأنظف).

---

## 2) الهدف

1. الأدمن يفتح صفحة «ترتيب القائمة الجانبية» ويسحب المجموعات والعناصر ويحفظ.
2. الترتيب يُطبَّق فورًا على السايدبار لكل المستخدمين (تغيير عام على مستوى النظام).
3. أي تعديل جديد مستقبلًا (Resource/Page جديدة) يظهر تلقائيًا في آخر مجموعته **بدون** أي إعداد يدوي.
4. زرار «استعادة الترتيب الافتراضي» يرجّع كل شيء لترتيب الكود الحالي.
5. لا تعديل على أي من الـ 53 Resource أو الـ 9 Pages.

---

## 3) القرار المعماري

### 3.1 تخزين الترتيب
استخدام جدول `settings` الموجود (`App\Models\Setting`) بمفتاح واحد:

```
key   = navigation_order
group = navigation
value = {"groups":["system_group","hr_group",...],"items":{"App\\Filament\\Resources\\Users\\UserResource":3,...}}
```

**لماذا:**
- صفر migrations، والـ `Setting` عنده cache جاهز (`rememberForever` + `forget` تلقائي عند الحفظ).
- قابل للنسخ الاحتياطي مع باقي الإعدادات.
- لا يستهلك جدولًا جديدًا لبيانات صغيرة.

### 3.2 ترتيب المجموعات
`AdminPanelProvider` لا يكتب القائمة يدويًا بعد الآن، بل:

```php
->navigationGroups(\App\Services\NavigationOrderService::navigationGroups())
```

الدالة ترجع مصفوفة `NavigationGroup` بترتيب محفوظ (أو الافتراضي) من مفاتيح الترجمة → يبقى التنسيق (label/icon) كما هو، ويصبح الترتيب من البيانات.

### 3.3 ترتيب العناصر داخل المجموعات
استبدال `NavigationManager` بكلاس وارث يطبّق الترتيب بعد بناء الملاحة الأصلية:

```php
// app/Filament/Navigation/OrderedNavigationManager.php
class OrderedNavigationManager extends NavigationManager
{
    public function get(): array
    {
        $groups = parent::get();
        $order  = NavigationOrderService::itemOrder(); // [class => position]

        foreach ($groups as $group) {
            $items = collect($group->getItems())->values();
            $group->items($items->sortBy(function ($item, $index) use ($order) {
                return [$order[$item->getKey()] ?? PHP_INT_MAX, $item->getSort(), $index];
            })->values()->all());
        }

        return $groups;
    }
}
```

التسجيل في `AppServiceProvider::register()`:

```php
$this->app->scoped(
    \Filament\Navigation\NavigationManager::class,
    fn () => new \App\Filament\Navigation\OrderedNavigationManager(),
);
```

**لماذا هذا الحل وليس غيره:**

| البديل | التقييم |
| :--- | :--- |
| تعديل `getNavigationSort()` في كل Resource عبر كلاس أساس جديد | يعمل، لكنه يفرض تعديل 53 ملفًا + مخاطرة نسيان أي Resource جديد |
| `->navigation(fn (NavigationBuilder $b) => ...)` | يفرض تعداد كل العناصر يدويًا داخل الـ Provider → تكرار وصيانة سيئة |
| Override لـ `NavigationManager` | ملف واحد + سطر ربط، ويغطي Resources و Pages و NavigationItems المخصصة تلقائيًا ✅ |

> ملاحظة: `HasRoutes.php:223` ينشئ `new NavigationManager` مباشرة (لا من الـ container) لمعرفة «أول عنصر متاح» عند فتح الجذر — لا يؤثر على السايدبار، وسيُذكر في التحقق.

### 3.4 قاعدة الدمج (Merge) — الأهم للاستقرار
- **المجموعات:** نحتفظ من المحفوظ فقط بالمفاتيح التي ما زالت موجودة في القائمة الافتراضية، ثم نضيف أي مجموعة جديدة في نهايتها بترتيبها الافتراضي.
- **العناصر:** أي عنصر غير مذكور في المحفوظ (جديد أو أُضيف حديثًا) يُرتَّب بعد العناصر المحددة تصاعديًا بـ `getNavigationSort()` الأصلي.
- لو المفتاح `navigation_order` غير موجود أو JSON تالف → النظام يرجع للترتيب الافتراضي بهدوء (لا Exception على السايدبار أبدًا).

---

## 4) الملفات المطلوبة

### ملفات جديدة
| # | الملف | الدور |
| :--- | :--- | :--- |
| 1 | `app/Services/NavigationOrderService.php` | مصدر الحقيقة الوحيد: القائمة الافتراضية، الدمج، الحفظ، الاستعادة، بناء كتالوج الواجهة |
| 2 | `app/Filament/Navigation/OrderedNavigationManager.php` | تطبيق ترتيب العناصر عند رسم السايدبار |
| 3 | `app/Filament/Pages/NavigationOrderPage.php` | صفحة الأدمن (Filament Page بصلاحية) |
| 4 | `resources/views/filament/pages/navigation-order.blade.php` | واجهة السحب والإفلات + أزرار الحفظ/الاستعادة |
| 5 | `lang/ar/navigation_order.php` | كل النصوص العربية |
| 6 | `lang/en/navigation_order.php` | نفس المفاتيح بالإنجليزي |
| 7 | `tests/Feature/NavigationOrderTest.php` | اختبار الخدمة (دمج/حفظ/استعادة/عنصر جديد) |

### ملفات تُعدَّل (تعديلات صغيرة)
| # | الملف | التعديل |
| :--- | :--- | :--- |
| 1 | `app/Providers/Filament/AdminPanelProvider.php` | `->navigationGroups(NavigationOrderService::navigationGroups())` + إضافة `->key('log_viewer_logs')` لعنصر سجل الأخطاء (استقرار المفتاح) |
| 2 | `app/Providers/AppServiceProvider.php` | ربط `NavigationManager` بالكلاس الجديد |
| 3 | `docs/SYSTEM_CHEATSHEET.md` | قسم جديد: «ترتيب القائمة الجانبية» + وصف الخدمة والربط |

**لا تعديل على أي Resource / Page.**

---

## 5) تصميم صفحة الأدمن

- **المسار:** `/navigation-order` — **المجموعة:** «الإدارة والصلاحيات» — **الأيقونة:** `heroicon-o-bars-3` — **Sort:** بعد «إعدادات النظام».
- **الصلاحية:** المالك / السوبر أدمن، أو من يملك `manage_settings` (نفس منطق `ManageSettings`).
- **الشاشة:**
  - كل مجموعة = كارت قابل للطي، فيه عدد عناصرها.
  - كل صف = أيقونة العنصر + اسمه المترجم + مقبض سحب.
  - سحب المجموعات لإعادة ترتيبها، وسحب العناصر داخل مجموعتها فقط (نقل عنصر لمجموعة أخرى = مرحلة لاحقة).
  - أزرار أعلى/أسفل بجانب مقبض السحب (للموبايل وإمكانية الوصول).
  - زر «حفظ الترتيب» (primary) + زر «استعادة الافتراضي» (danger + تأكيد).
  - بعد الحفظ: `redirect` للصفحة نفسها لتُعاد تسمية السايدبار فورًا + إشعار عربي.
- **التنفيذ:** Alpine.js (موجود مع Filament) + HTML5 drag events أصلية، **بدون أي مكتبة خارجية جديدة** وبدون تعديل `package.json`.
- **الكتالوج:** يُبنى من الـ Panel نفسه:
  ```php
  foreach (Filament::getPanel('admin')->getResources() as $resource) {
      $resource::getNavigationItems(); // ترجع [] للعناصر غير المسجّلة/غير المصرح لها
  }
  ```
  → الأدمن يرى فقط ما يراه فعلًا، والاسم والأيقونة مطابقان للسايدبار تمامًا.

---

## 6) واجهة الخدمة (API مختصرة)

```php
NavigationOrderService::navigationGroups(): array          // NavigationGroup[] مرتبة (تُستخدم في الـ Provider)
NavigationOrderService::itemOrder(): array                 // [class => position]
NavigationOrderService::catalog(): array                   // [ ['key','label','icon','items'=>[...]], ... ] للواجهة
NavigationOrderService::save(array $groups, array $items): void
NavigationOrderService::reset(): void
NavigationOrderService::DEFAULT_GROUP_KEYS                 // مصدر الحقيقة الوحيد للترتيب الافتراضي
```

الترتيب الافتراضي الحالي (كما في الـ Provider):

```
system_group, org_group, hr_group, finance_group,
public_finance_and_services, tasks_group, contracts_group, violations_group,
funds_group, letters_group, reports_group, faq_group, support_group
```

> سيُلاحظ وجود مفاتيح قديمة غير مستخدمة في `lang/ar/navigation.php` (`organization_group`, `invoices_group`) — لن تُضاف للقائمة لأنها ليست ضمن `DEFAULT_GROUP_KEYS`.

---

## 7) مراحل التنفيذ

| المرحلة | المحتوى | الناتج |
| :--- | :--- | :--- |
| **1** | `NavigationOrderService` + الحفظ/الاستعادة/الدمج + اختبار Feature | منطق جاهز ومختبر بدون واجهة |
| **2** | `OrderedNavigationManager` + الربط في `AppServiceProvider` + تعديل `navigationGroups()` في الـ Provider | الترتيب يُطبَّق فعليًا على السايدبار |
| **3** | صفحة `NavigationOrderPage` + الـ Blade + ملفات الترجمة | الأدمن يعدّل من الواجهة |
| **4** | تحقق يدوي (سحب/حفظ/استعادة/تغيير لغة) + تحديث `SYSTEM_CHEATSHEET` + التشييك النهائي | جاهز للرفع |

---

## 8) التحقق والاختبار

1. `php -l` على كل ملف PHP جديد/معدّل.
2. `vendor\bin\pint` على الملفات المتغيرة.
3. `php artisan view:clear` بعد ملف الـ Blade.
4. **Feature test:** يحفظ ترتيبًا → يتأكد أن `itemOrder()` يرجعه، وأن عنصرًا غير مذكور يُضاف في النهاية، وأن `reset()` يرجّع الافتراضي، وأن JSON تالف لا يكسر شيئًا.
5. **يدويًا:** فتح السايدبار قبل/بعد الحفظ، تغيير اللغة عربي/إنجليزي (الترتيب يجب أن يبقى لأنه مربوط بمفاتيح ترجمة وأسماء كلاسات لا بالنصوص)، تسجيل خروج ودخول، وتجربة بحساب غير مصرح له (لا يرى الصفحة).
6. **بعد الرفع للسيرفر:** `php artisan optimize:clear` إلزامي — المهمة تضيف كلاسات جديدة (Service + Manager + Page) وهي من أعلى الحالات خطرًا لخطأ «Cannot declare class» على الاستضافة المشتركة.

---

## 9) المخاطر والحلول

| الخطر | الأثر | الحل |
| :--- | :--- | :--- |
| `NavigationManager` مربوط `scoped` في vendor | لو تغيّر الـ binding في تحديث Filament، يبطل الترتيب بهدوء | الربط في `AppServiceProvider` + اختبار Feature يغطي، والبديل الجاهز (كلاس أساس للـ Resources) موثّق |
| تغيير `->navigationGroups()` لترجع مصفوفة ديناميكية | لو فشل تحميل الإعداد يتوقف السايدبار | `rescue()` داخل الخدمة + fallback للافتراضي (نفس نمط `brandName` الموجود) |
| حفظ ترتيب لعناصر محذوفة من الكود | بيانات ميتة في JSON | التقليم (prune) عند كل حفظ + تجاهل غير الموجود عند القراءة |
| سحب عنصر بين مجموعتين | يغيّر مجموعة العنصر (سلوك مختلف) | **خارج النطاق** في هذه المرحلة — الواجهة تمنع النقل بين المجموعات |
| تعدد اللغات | الترتيب مربوط بالترجمة فينكسر عند تبديل اللغة | الترتيب مخزّن بمفاتيح `lang` (مجموعات) وأسماء كلاسات (عناصر) — مستقل عن اللغة ✅ |

---

## 10) خارج النطاق (يمكن طلبه لاحقًا)

1. **ترتيب خاص لكل مستخدم** بدل الترتيب العام (يحتاج جدول `user_navigation_orders` + fallback للعام).
2. **إخفاء/إظهار** عناصر من السايدبار لبعض الأدوار (الأنسب حاليًا عبر صلاحيات Spatie الموجودة).
3. **تغيير اسم المجموعة أو أيقونتها** أو إنشاء مجموعات مخصصة وسحب العناصر بينها.
4. **إعادة تسمية عناصر السايدبار** (Override للـ label فقط).

---

## 11) تقدير الحجم

7 ملفات جديدة + 3 تعديلات صغيرة + تجاوز واحد لـ vendor class — **حوالي نصف يوم إلى يوم عمل** بما فيه الاختبار اليدوي.

---

## 12) قرار مُعتمد

✅ **الترتيب عام لكل المستخدمين** (الأدمن يرتب مرة واحدة للنظام كله) — تم اعتماده من صاحب الطلب بتاريخ 2026-10-31.

- لا جدول جديد: التخزين في `settings` بمفتاح `navigation_order`.
- الترتيب الشخصي لكل مستخدم يبقى بندًا مؤجلًا في القسم 10.

> الخطة في انتظار مراجعة صاحب الطلب قبل كتابة أي كود.
