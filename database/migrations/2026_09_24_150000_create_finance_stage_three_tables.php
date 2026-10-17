<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // ==========================================
        // 1. نظام الرواتب (مسيرات الرواتب وعمليات الصرف)
        // ==========================================

        // أ) مسير استحقاق الرواتب للشهر (Payroll Records / Monthly Slips) — [PDF ص 16, 17, 31]
        Schema::create('payroll_records', function (Blueprint $table) {
            $table->id();
            $table->string('record_number')->nullable()->unique(); // رقم الوصل / المسير (مثل P-2026-09-001)
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('operation_type')->default('payroll'); // 'payroll' (رواتب), 'collection' (تحصيل)
            $table->date('payroll_month'); // شهر الاستحقاق (YYYY-MM-01)
            $table->date('record_date');   // تاريخ إعداد المسير
            
            // الراتب الأساسي وتفاصيل الإضافات
            $table->decimal('basic_salary', 14, 2)->default(0);
            $table->decimal('overtime_amount', 14, 2)->default(0); // إضافي / ساعات
            $table->decimal('rewards_amount', 14, 2)->default(0);  // مكافآت معتمدة
            $table->decimal('additional_amount', 14, 2)->default(0); // إجمالي الإضافات (= overtime + rewards + أخرى)
            
            // تفاصيل الخصومات والاستقطاعات
            $table->decimal('advance_deduction', 14, 2)->default(0); // استقطاع أقساط وسلف
            $table->decimal('absence_deduction', 14, 2)->default(0); // خصم غياب وتأخير
            $table->decimal('deduction_amount', 14, 2)->default(0);  // إجمالي الخصومات (= advance + absence + أخرى)
            
            // الصافي والمتبقي وحالة السداد
            $table->decimal('net_amount', 14, 2)->default(0);       // صافي المستحق (= basic + additional - deduction)
            $table->decimal('paid_amount', 14, 2)->default(0);      // إجمالي المدفوع حتى الآن
            $table->decimal('remaining_amount', 14, 2)->default(0); // المتبقي للصرف
            
            $table->string('payment_status')->default('unpaid'); // unpaid (لم يسدد), partial (جزئي), paid (كامل)
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'employee_id', 'payroll_month', 'operation_type'], 'payroll_records_comp_emp_month_op_unique');
            $table->index(['company_id', 'payroll_month']);
            $table->index(['employee_id', 'payment_status']);
        });

        // ب) سجل دفعات وصرف الرواتب الفعلية (Payroll Payments) — [PDF ص 16]
        Schema::create('payroll_payments', function (Blueprint $table) {
            $table->id();
            $table->string('payment_number')->nullable()->unique(); // رقم سند/إيصال الصرف
            $table->foreignId('payroll_record_id')->constrained('payroll_records')->cascadeOnDelete();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->date('payment_date'); // تاريخ الصرف الفعلي
            $table->decimal('amount', 14, 2); // المبلغ المصروف في هذه الدفعة
            $table->string('payment_method')->default('payroll'); // payroll (بند الرواتب), treasury (الخزينة مباشرة), bank_transfer
            $table->foreignId('treasury_transaction_id')->nullable()->constrained('treasury_transactions')->nullOnDelete(); // ربط بحركة الخزينة إن وجدت
            $table->foreignId('executed_by')->nullable()->constrained('employees')->nullOnDelete(); // منفذ العملية
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['payroll_record_id', 'payment_date']);
            $table->index(['company_id', 'payment_date']);
            $table->index(['employee_id', 'payment_date']);
        });

        // ==========================================
        // 2. نظام الأقساط والسلف والديون
        // ==========================================

        // أ) أنواع وتصنيفات الأقساط (Installment Types) — [PDF ص 19, 21]
        Schema::create('installment_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete(); // اختياري لو لكل شركة أنواع خاصة أو عامة
            $table->string('category')->default('advances'); // advances (سلف), debts (ديون), others (أخرى)
            $table->string('name'); // قسط سيارة, قرض شخصي, سلفة, قسط جهاز, مديونية مشتريات...
            $table->string('status')->default('active'); // active, inactive
            $table->timestamps();

            $table->index('category');
        });

        // ب) أصل القسط / السلفة (Installments & Loans) — [PDF ص 19, 21, 22]
        Schema::create('installments', function (Blueprint $table) {
            $table->id();
            $table->string('installment_number')->unique(); // رقم القسط / السلفة (مثل A-0032 أو INS-0001)
            $table->date('installment_date'); // تاريخ التحرير / البداية
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('installment_type_id')->constrained('installment_types')->restrictOnDelete();
            
            // الحسابات المالية
            $table->decimal('total_amount', 14, 2)->default(0); // إجمالي قيمة القسط/السلفة الأصلي
            $table->decimal('down_payment', 14, 2)->default(0);  // الدفعة المقدمة/الإضافية
            $table->decimal('monthly_amount', 14, 2)->default(0); // القسط الشهري
            $table->decimal('paid_amount', 14, 2)->default(0);   // إجمالي ما تم سداده
            $table->decimal('remaining_amount', 14, 2)->default(0); // المتبقي (= total - down_payment - paid)
            
            // المدة والاستحقاقات والتنبيهات (ص 22)
            $table->unsignedInteger('months_count')->default(1); // عدد الأشهر / الأقساط
            $table->date('start_due_date')->nullable(); // تاريخ أول قسط
            $table->date('final_due_date')->nullable(); // تاريخ الاستحقاق النهائي
            $table->foreignId('notify_employee_id')->nullable()->constrained('employees')->nullOnDelete(); // يصل التنبيه إلى
            $table->text('notification_notes')->nullable(); // نص التنبيه
            
            $table->text('description')->nullable(); // البيان / التفاصيل
            $table->foreignId('executed_by')->nullable()->constrained('employees')->nullOnDelete(); // منفذ العملية
            $table->string('status')->default('open'); // open (نشط), completed (مكتمل), overdue (متأخر), cancelled (ملغي)
            $table->timestamps();

            $table->index(['company_id', 'employee_id']);
            $table->index(['employee_id', 'installment_date']);
            $table->index('status');
        });

        // ج) جدول الأقساط الشهرية المجدولة (Installment Schedules)
        Schema::create('installment_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('installment_id')->constrained('installments')->cascadeOnDelete();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->unsignedSmallInteger('installment_order')->default(1); // رقم القسط (1 من 12)
            $table->date('due_date'); // تاريخ استحقاق هذا القسط
            $table->decimal('amount', 14, 2); // مبلغ هذا القسط
            $table->decimal('paid_amount', 14, 2)->default(0); // المسدد من هذا القسط
            $table->decimal('remaining_amount', 14, 2)->default(0); // المتبقي
            $table->string('status')->default('pending'); // pending (مستحق), partial (سداد جزئي), paid (مسدد), overdue (متأخر)
            $table->timestamps();

            $table->index(['installment_id', 'due_date']);
            $table->index(['employee_id', 'due_date']);
            $table->index('status');
        });

        // د) سجل الدفعات وسندات تسديد الأقساط (Installment Payments & Vouchers) — [PDF ص 20]
        Schema::create('installment_payments', function (Blueprint $table) {
            $table->id();
            $table->string('voucher_number')->nullable()->index(); // رقم فاتورة/سند التسديد المجمع (مثل P-0092)
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('installment_id')->constrained('installments')->cascadeOnDelete();
            $table->foreignId('installment_schedule_id')->nullable()->constrained('installment_schedules')->nullOnDelete(); // ربط بالاستحقاق الشهري إن وجد
            $table->date('payment_date'); // تاريخ السداد
            $table->decimal('amount', 14, 2); // المبلغ المسدد
            $table->string('payment_source')->default('cash'); // cash (نقدي/خزينة), payroll_deduction (خصم مسير), bank (تحويل)
            $table->foreignId('treasury_transaction_id')->nullable()->constrained('treasury_transactions')->nullOnDelete(); // سند القبض بالخزينة إن كان نقدياً
            $table->foreignId('payroll_record_id')->nullable()->constrained('payroll_records')->nullOnDelete(); // مسير الراتب إن كان خصماً
            $table->text('description')->nullable(); // البيان
            $table->foreignId('executed_by')->nullable()->constrained('employees')->nullOnDelete(); // منفذ العملية
            $table->timestamps();

            $table->index(['installment_id', 'payment_date']);
            $table->index(['company_id', 'payment_date']);
            $table->index(['employee_id', 'payment_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('installment_payments');
        Schema::dropIfExists('installment_schedules');
        Schema::dropIfExists('installments');
        Schema::dropIfExists('installment_types');
        Schema::dropIfExists('payroll_payments');
        Schema::dropIfExists('payroll_records');
    }
};
