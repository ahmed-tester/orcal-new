# خطة التطوير: إسناد المهام لأكثر من موظف (Task Assignments Multi-Employee)

## 1. مراجعة الكود الحالي للراوت (`/task-assignments/create`)

### 1.1 المكونات المسؤولة عن الراوت
- **الراوت في Filament:** `CreateTaskAssignment::route('/create')` المعرّف داخل `TaskAssignmentResource`.
- **صفحة الإنشاء:** `App\Filament\Resources\TaskAssignments\Pages\CreateTaskAssignment` (صفحة قياسية ترث من `CreateRecord`).
- **مخطط النموذج (Form Schema):** `App\Filament\Resources\TaskAssignments\Schemas\TaskAssignmentForm`.
- **النماذج المرتبطة:**
  - `App\Models\TaskAssignment`: يمثل ترويسة التكليف (رقم الحركة `transaction_number`، تاريخ التكليف، معرف الموظف `employee_id`، الأوزان، نسبة التقييم الإجمالية، حالة الإنجاز).
  - `App\Models\TaskAssignmentItem`: يمثل تفاصيل وبنود المهام المسندة داخل التكليف (`task_id`، الوزن `weight`، الدرجة `score`، تاريخ الاستحقاق، الإنجاز).
- **المراقبون (Observers):**
  - `TaskAssignmentObserver`: يولد رقم الحركة `transaction_number` تلقائياً، ويرسل إشعاراً داخلياً (Database Notification) للموظف المسند إليه فور الإنشاء.
  - `TaskAssignmentItemObserver`: يحسب نسبة درجات البند ويعيد احتساب أوزان ونسب التكليف ككل عبر `recalculateTotals()`.

### 1.2 الوضع الحالي والقصور
- حقل الموظف في `TaskAssignmentForm` مبني باستخدام `CompanyFormService::makeEmployeeSelect(...)`، وهو حقل مفرد يطلب `employee_id` واحد فقط.
- جدول `task_assignments` في قاعدة البيانات يحتوي على عمود مفرد:
  ```php
  $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
  ```
- التقييمات والأوزان (`total_score`, `overall_percentage`, `is_completed`) مرتبطة مباشرة برأس التكليف الخاص بهذا الموظف.

---

## 2. دراسة الخيارات المعمارية لإسناد المهمة لأكثر من موظف

| المعيار | الخيار 1: الإنشاء المجمّع لتكليفات مستقلة (موصى به) | الخيار 2: تحويل العلاقة إلى متعدد لمتعدد (Pivot N:M) | الخيار 3: إسناد الموظف على مستوى بنود المهام (Items) |
| :--- | :--- | :--- | :--- |
| **طريقة العمل** | يختار المستخدم عدة موظفين في شاشة الإنشاء، ويقوم النظام تلقائياً بإنشاء تكليف منفصل لكل موظف بنفس المهام والأوزان. | إنشاء جدول وسيط `task_assignment_employee` وإلغاء `employee_id` من الترويسة. | جعل رأس التكليف مشتركاً، وتحديد الموظف في كل سطر من سطور الـ Repeater. |
| **استقلالية التقييم والمتابعة** | **ممتاز جداً:** كل موظف له درجاته ونسبة إنجازه وحالته المستقلة دون تضارب. | **صعب ومعقد:** كيف يتم تقييم 3 موظفين على نفس التكليف؟ هل إنجاز أحدهم يعني إنجاز البقية؟ | **مربك:** كل موظف سينفذ بنداً وستختلط نسب الأوزان الإجمالية. |
| **توافق التقارير والإشعارات** | **100% متوافق فوراً:** تقارير `TaskAssignmentsReport` وإشعارات الـ Observer تعمل تلقائياً لكل موظف. | **كسر للتوافق:** يتطلب إعادة كتابة التقارير والفلاتر ونظام الإشعارات بالكامل. | **تعديل جوهري:** يتطلب تعديل تقارير إنجاز الموظف وحساب النسب. |
| **التأثير على قاعدة البيانات** | **صفر مخاطر:** لا يتطلب أي Migration مدمر، ولا يكسر أي بيانات تاريخية (مطابق لقاعدة 5.4). | **مخاطر عالية:** تعديل جداول قائمة وتفريغ بيانات سابقة. | **مخاطر متوسطة:** تعديل جدول `task_assignment_items`. |

---

## 3. الخيار الموصى به: الإنشاء المجمّع الذكي (Batch Assignment Service)

### الفكرة الجوهرية:
1. في شاشة الإضافة (`CreateTaskAssignment`):
   - يتم توفير خيار للمستخدم: إما تكليف موظف واحد، أو اختيار مجموعة موظفين دفعة واحدة (`employee_ids` Multi-select).
2. عند الحفظ:
   - يتم ترحيل عملية الحفظ إلى طبقة الخدمات `app/Services/TaskAssignmentService.php` (التزاماً بالقاعدة المعمارية 2.b و 5.3 بعدم كتابة منطق الحفظ والتكرار داخل الـ Pages).
   - تقوم الخدمة بإنشاء سجل `TaskAssignment` مستقل لكل موظف مع نسخ بنود المهام (`items`) الخاصة به داخل `DB::transaction`.
   - يتم توليد رقم حركة فريد لكل موظف، ويصل إشعار فوري في لوحة التحكم لكل موظف بمفرده.
   - يمكن حفظ معرف دفعة موحد (`batch_uuid`) داخل حقل `metadata` للرجوع للتكليفات الصادرة سوياً.
3. في شاشة التعديل (`EditTaskAssignment`):
   - تظل كما هي تعرض تكليف الموظف المحدد لمتابعة درجاته وحالة إنجازه بكل دقة واستقلالية.

---

## 4. مراجعة القواعد والسياسات المعمارية في المشروع (`AGENTS.md`)

1. **Services Layer (القاعدة 2.b):**
   - نقل منطق التكرار وإنشاء التكليفات للخدمة `TaskAssignmentService`، وإبقاء صفحة `CreateTaskAssignment` خفيفة (Thin).
2. **Sequential Code Generation (القاعدة 2.c):**
   - استخدام `CodeGeneratorService` لضمان عدم حدوث تضارب في أرقام الحركات `transaction_number` عند توليد أكثر من تكليف بالتزامن.
3. **Filament v5 Conventions (القاعدة 5.3):**
   - الحفاظ على الفصل المعماري: `Schemas/TaskAssignmentForm.php` و `Pages/CreateTaskAssignment.php`.
4. **No Hardcoded Content & Localization (القاعدة 5.5):**
   - تعريف كافة المسميات والنصوص في ملفات الترجمة: `lang/ar/tasks.php` و `lang/en/tasks.php`.
5. **Database Safety (القاعدة 5.4):**
   - عدم المساس بالجداول التاريخية أو كتابة Migrations تعدل جداول تعمل بالفعل.

---

## 5. خطوات التنفيذ التفصيلية (Action Plan)

### الخطوة 1: تحديث ملفات الترجمة (Localization)
- إضافة مفاتيح الترجمة في `lang/ar/tasks.php` و `lang/en/tasks.php`:
  - نمط الإسناد (فردي / متعدد).
  - رسالة نجاح الإسناد المجمّع (`تم إسناد المهام بنجاح لعدد :count موظف`).
  - نص تلميحي للمستخدم يوضح أن اختيار عدة موظفين سيقوم بإنشاء تكليف وتقييم مستقل لكل موظف.

### الخطوة 2: إنشاء خدمة المهام `TaskAssignmentService`
- إنشاء الملف: `app/Services/TaskAssignmentService.php`
- يحتوي على:
  - `createAssignments(array $data): TaskAssignment`
  - معالجة الحفظ المجمع للموظفين داخل `DB::transaction`.
  - ربط البنود `items` والتحقق من حساب الأوزان.

### الخطوة 3: تعديل نموذج الإدخال `TaskAssignmentForm`
- في `app/Filament/Resources/TaskAssignments/Schemas/TaskAssignmentForm.php`:
  - جعل حقل الموظف في صفحة الإنشاء يقبل الاختيار المتعدد (`multiple()`) مع إمكانية الفلترة والبحث بحسب الشركة/الفرع المصرح للمستخدم.
  - الحفاظ على حقل الموظف الفردي في صفحة التعديل (`Edit`).

### الخطوة 4: تحديث صفحة `CreateTaskAssignment`
- في `app/Filament/Resources/TaskAssignments/Pages/CreateTaskAssignment.php`:
  - تجاوز دالة `handleRecordCreation(array $data): Model`.
  - استدعاء `TaskAssignmentService` للحفظ المجمع وإظهار إشعار Filament بنجاح العملية لجميع الموظفين المحددين.

### الخطوة 5: التحقق والاختبار (Verification Checklist)
- فحص الأخطاء النحوية: `php -l` على كافة الملفات المعدلة.
- مسح الكاش: `php artisan view:clear`.
- فحص التنسيق الكودي: تشغيل `vendor/bin/pint` على الملفات المعدلة.
- تجربة إنشاء مهمة لموظف واحد، وتجربة إسناد مهمة لـ 3 موظفين والتأكد من توليد الحركات والإشعارات بنجاح.
