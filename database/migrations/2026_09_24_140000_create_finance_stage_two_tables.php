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
        // 1. قيود اليومية للمبيعات والمشتريات (Daily Journals) — [PDF ص 11]
        Schema::create('daily_journals', function (Blueprint $table) {
            $table->id();
            $table->string('entry_number')->unique(); // رقم القيد التلقائي (مثل D-1107)
            $table->date('entry_date'); // تاريخ القيد
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->decimal('purchases', 14, 2)->default(0); // إجمالي المشتريات
            $table->decimal('sales', 14, 2)->default(0); // إجمالي المبيعات
            $table->decimal('net_amount', 14, 2)->default(0); // الصافي (المبيعات - المشتريات)
            $table->foreignId('recorded_by')->nullable()->constrained('employees')->nullOnDelete(); // الموظف/المحاسب المسجل
            $table->text('notes')->nullable(); // ملاحظات أو تفاصيل القيد
            $table->timestamps();

            $table->index(['company_id', 'entry_date']);
        });

        // 2. سجل مكافآت الموظفين بالساعات والدقائق (Employee Rewards) — [PDF ص 18]
        Schema::create('employee_rewards', function (Blueprint $table) {
            $table->id();
            $table->string('reward_number')->unique(); // رقم المكافأة التلقائي (مثل B-0087)
            $table->date('reward_date'); // تاريخ تسجيل المكافأة
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            
            // احتساب الساعات
            $table->decimal('hourly_rate', 10, 2)->default(0); // أجر الساعة
            $table->decimal('hours_count', 8, 2)->default(0);  // عدد الساعات (يقبل كسور الساعات مثل 1.5 س)
            $table->decimal('hours_amount', 12, 2)->default(0); // قيمة الساعات (أجر الساعة × عدد الساعات)
            
            // احتساب الدقائق
            $table->decimal('minute_rate', 10, 4)->default(0); // أجر الدقيقة بدقة 4 خانات
            $table->unsignedInteger('minutes_count')->default(0); // عدد الدقائق
            $table->decimal('minutes_amount', 12, 2)->default(0); // قيمة الدقائق
            
            // الصافي وبيانات المكافأة
            $table->decimal('net_amount', 12, 2)->default(0); // الصافي النهائي (قيمة الساعات + قيمة الدقائق)
            $table->string('reason')->nullable(); // سبب المكافأة (مثال: إنجاز مشروع صيانة طارئ)
            $table->string('location')->nullable(); // موقع العميل / المشروع (مثل الكبرى مول)
            
            // حالة الاعتماد والصرف
            $table->string('status')->default('pending'); // pending (قيد المراجعة), approved (معتمدة), paid (مدفوعة), rejected (مرفوضة)
            $table->date('paid_date')->nullable(); // تاريخ الصرف الفعلي
            $table->foreignId('treasury_transaction_id')->nullable()->constrained('treasury_transactions')->nullOnDelete(); // ربط بسند صرف الخزينة إن صُرفت نقداً
            
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete(); // مدخل المكافأة
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'reward_date']);
            $table->index(['employee_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_rewards');
        Schema::dropIfExists('daily_journals');
    }
};
