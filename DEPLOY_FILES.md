# 📁 قائمة الرفع الذكية (مجلدات كاملة + ملفات فردية)

> 💡 **فكرة هذه القائمة:**  
> بدلاً من رفع 46 ملفاً واحداً تلو الآخر، تم تجميع التعديلات في **8 مجلدات رئيسية** يمكنك رفعها أو سحبها بالكامل، بالإضافة إلى **7 ملفات فردية** فقط!
> 
> *(في VS Code، اضغط `Ctrl + Click` على أي مجلد أو ملف لفتحه مباشرة)*

---

## أولاً: المجلدات الكاملة (ارفع المجلد كاملاً دفعة واحدة) 📦

1. 📂 **الموظفون المؤقتون (مورد جديد كامل بكل صفحاته ونماذجه):**
   - 👉 [app/Filament/Resources/TemporaryEmployees/](app/Filament/Resources/TemporaryEmployees/)

2. 📂 **المُلّاك (يشمل الفورم، الجدول، صفحات الإضافة والتعديل والعرض):**
   - 👉 [app/Filament/Resources/Owners/](app/Filament/Resources/Owners/)

3. 📂 **الموظفون السعوديون (يشمل الفورم، الجدول، وكشف الطباعة، والصفة):**
   - 👉 [app/Filament/Resources/SaudiEmployees/](app/Filament/Resources/SaudiEmployees/)

4. 📂 **الموظفون غير السعوديين (يشمل مدة الخدمة، المهنة، المسمى الوظيفي):**
   - 👉 [app/Filament/Resources/ForeignEmployees/](app/Filament/Resources/ForeignEmployees/)

5. 📂 **المسميات الوظيفية (يشمل الفورم والجدول والنطاق العام):**
   - 👉 [app/Filament/Resources/JobTitles/](app/Filament/Resources/JobTitles/)

6. 📂 **مجلد الخدمات (يشمل كافة كلاسات الخدمات الجديدة والمعدلة):**
   - 👉 [app/Services/](app/Services/)
   *(يحتوي على: PersonCapacityService, OwnerFormService, JobTitleService, EmployeeServiceDurationService, CompanyFormService)*

7. 📂 **مجلدات الترجمة الكاملة:**
   - 👉 [lang/ar/](lang/ar/) *(الترجمة العربية بالكامل)*
   - 👉 [lang/en/](lang/en/) *(الترجمة الإنجليزية بالكامل)*

---

## ثانياً: الملفات الفردية المستقلة (فقط 7 ملفات) 📄

- 📄 [app/Models/Employee.php](app/Models/Employee.php) *(تحديث الموديل وحساب مدة الخدمة والميتاداتا)*
- 📄 [app/Models/TemporaryEmployee.php](app/Models/TemporaryEmployee.php) *(موديل الموظف المؤقت الجديد)*
- 📄 [app/Models/Owner.php](app/Models/Owner.php) *(حقول الميتاداتا المالية والاسم بالإنجليزية)*
- 📄 [app/Models/JobTitle.php](app/Models/JobTitle.php) *(النطاق العام للمسمى)*
- 📄 [app/Enums/JobTitleScope.php](app/Enums/JobTitleScope.php) *(Enum نطاق المسمى الجديد)*
- 📄 [app/Policies/TemporaryEmployeePolicy.php](app/Policies/TemporaryEmployeePolicy.php) *(صلاحيات الموظف المؤقت)*
- 📄 [app/Providers/AppServiceProvider.php](app/Providers/AppServiceProvider.php) *(تسجيل السياسات)*
- 📄 [app/Http/Controllers/ExportPdfController.php](app/Http/Controllers/ExportPdfController.php) *(كنترولر تصدير وطباعة الكشوفات)*
- 📄 [routes/web.php](routes/web.php) *(مسار طباعة الكشف)*
- 📄 [resources/views/exports/employees-statement-print.blade.php](resources/views/exports/employees-statement-print.blade.php) *(قالب طباعة الكشف الجديد)*
- 📄 [database/migrations/2026_11_10_170000_make_job_titles_company_id_nullable_and_add_is_global.php](database/migrations/2026_11_10_170000_make_job_titles_company_id_nullable_and_add_is_global.php) *(مايجريشن المسميات العامة)*

---

### ⚙️ الأوامر بعد الانتهاء من الرفع:
```bash
php artisan migrate
php artisan optimize:clear
```
