# خطة: ملخص اليوميات (Daily Journals Summary)

- **التاريخ:** 2026-11-11
- **الحالة:** معتمدة — جاري التنفيذ
- **النمط المرجعي:** ملخص الديون (`DebtsSummary` + `DebtSummaryService` — كومِت `032efa9`)، وهو نفس نمط الأقساط/المكافئات/المخالفات/الخزينة/الحضور/الإجازات.

## الهدف
إضافة صفحة ملخص لوحدة **اليوميات (DailyJournals)** بنفس النمط المعتمد في المشروع:
- صفحة ملخص مجمّعة **حسب الفرع** هي الصفحة الرئيسية (`index`)، والقائمة التفصيلية تتحول إلى `/detailed`.
- بطاقات KPI: عدد القيود، عدد الفروع، إجمالي المبيعات، إجمالي المشتريات، الصافي (مع شارة `net_status` موجودة بالفعل).
- فلتر بار كامل (شركة متعدد + فرع متعدد + تواريخ + بحث + نوع القيد مختصرة/مفصلة).
- نافذة تفاصيل (slide-over) تعرض قيود الفرع يوم بيوم.
- تصدير CSV (BOM + حماية CSV injection).
- طباعة A4 landscape عبر متصفح (بدون mPDF) — ملخص عام أو كشف قيود فرع.

## ملفات جديدة
| الملف | الوصف |
|---|---|
| `app/Services/DailyJournalSummaryService.php` | تجميع `company_id, branch_id` مع `MIN(id) AS row_id, COUNT(*), SUM(sales), SUM(purchases), SUM(net_amount)`، نطاق عبر `CompanyAccessScopeService::apply()` |
| `app/Filament/Resources/DailyJournals/Pages/DailyJournalsSummary.php` | صفحة Filament بنمط `DebtsSummary` (HasFilterBarState + WithPagination + exportCsv) |
| `app/Http/Controllers/DailyJournalSummaryPrintController.php` | Invokable — `{journal?}` اختياري للتفاصيل |
| `resources/views/filament/resources/daily-journals/pages/daily-journals-summary.blade.php` | صفحة الملخص |
| `resources/views/daily-journals/partials/summary-table.blade.php` | جدول الملخص (مشترك بين الصفحة والطباعة) |
| `resources/views/daily-journals/partials/statement.blade.php` | كشف قيود الفرع (مشترك) |
| `resources/views/daily-journals/summary-print.blade.php` | صفحة الطباعة |
| `tests/Feature/DailyJournalSummaryTest.php` | اختبار على نمط `DebtSummaryTest` |

## ملفات معدلة
| الملف | التعديل |
|---|---|
| `app/Filament/Resources/DailyJournals/DailyJournalResource.php` | `getPages()`: `index` = الملخص، `detailed` = `ListDailyJournals` |
| `app/Filament/Resources/DailyJournals/Pages/ListDailyJournals.php` | زر `summary` يعود لصفحة الملخص |
| `routes/web.php` | `GET /daily-journals/summary/print/{journal?}` باسم `daily-journals.summary.print` |
| `lang/ar/finance.php` + `lang/en/finance.php` | `daily_journals.summary.*` |
| `docs/CHANGELOG.md`, `docs/INDEX.md`, `docs/SYSTEM_CHEATSHEET.md` | توثيق |

## إعادة استخدام دون تعديل
`HasFilterBarState` + `FilterBarService` + `CompanyAccessScopeService` + `CompanyFormService` + `<x-filter-bar>`.

## التحقق
`php -l` + `vendor\bin\pint` + `php artisan view:clear` + `php artisan test --filter=DailyJournalSummaryTest` + فتح الصفحة يدويًا. بعد النشر على السيرفر: `php artisan optimize:clear` (ملفات كلاسات جديدة).
