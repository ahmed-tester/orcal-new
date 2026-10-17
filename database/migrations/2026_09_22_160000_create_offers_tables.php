<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offers', function (Blueprint $table) {
            $table->id();

            // ─── نوع النموذج ورقم المستند ──────────────────────────────────────
            $table->string('offer_type')->default('quotation'); // quotation, supply_offer, unified_contract, agreement_contract
            $table->string('offer_number')->nullable()->unique();

            // ─── الأطراف ─────────────────────────────────────────────────────
            $table->foreignId('company_id')
                  ->constrained('companies')
                  ->cascadeOnDelete();

            $table->foreignId('customer_id')
                  ->nullable()
                  ->constrained('customers')
                  ->nullOnDelete();

            // ─── بيانات وموضوع العرض ─────────────────────────────────────────
            $table->string('subject');                          // موضوع / عنوان العرض
            $table->longText('description')->nullable();        // نص المقدمة / التوضيح

            // ─── نطاق العمل والتوريد ─────────────────────────────────────────
            $table->string('work_scope')->default('labor_only'); // materials_and_labor, labor_with_some_materials, labor_only
            $table->text('supplied_materials')->nullable();        // المواد التي يتم توريدها
            $table->longText('scope_description')->nullable();     // وصف الأعمال المناطة وموقع العمل

            // ─── التسعير والضريبة والمدة ─────────────────────────────────────
            $table->string('pricing_type')->default('lump_sum');   // lump_sum, per_meter, per_unit
            $table->boolean('is_tax_inclusive')->default(false);   // شامل الضريبة
            $table->decimal('tax_rate', 5, 2)->default(15.00);     // نسبة الضريبة %
            $table->string('execution_duration')->nullable();      // مدة تنفيذ الأعمال

            // ─── الشروط والتزامات الطرفين ────────────────────────────────────
            $table->longText('terms_and_conditions')->nullable();  // الشروط والأحكام والدفعات

            // ─── التاريخ والصلاحية والحالة ────────────────────────────────────
            $table->date('offer_date');                         // تاريخ إصدار العرض
            $table->date('valid_until')->nullable();            // تاريخ انتهاء الصلاحية
            $table->string('status')->default('draft');         // draft, sent, accepted, rejected, cancelled

            // ─── المسؤولون والتواقيع والختم ──────────────────────────────────
            $table->foreignId('manager_id')
                  ->nullable()
                  ->constrained('employees')
                  ->nullOnDelete();         // المدير التنفيذي / صاحب التوقيع

            $table->foreignId('supervisor_id')
                  ->nullable()
                  ->constrained('employees')
                  ->nullOnDelete();         // مشرف المواقع

            $table->boolean('show_stamp')->default(true);       // إظهار الختم عند الطباعة

            // ─── Meta ─────────────────────────────────────────────────────────
            $table->foreignId('created_by')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            $table->text('notes')->nullable();

            $table->timestamps();

            // ─── Indexes ─────────────────────────────────────────────────────
            $table->index(['company_id', 'offer_type']);
            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'offer_date']);
            $table->index('customer_id');
        });

        Schema::create('offer_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('offer_id')
                  ->constrained('offers')
                  ->cascadeOnDelete();

            // ─── ترتيب البنود ─────────────────────────────────────────────────
            $table->integer('sort_order')->default(0);

            // ─── بيانات البند ────────────────────────────────────────────────
            $table->string('item_name');                        // اسم البند
            $table->text('specifications')->nullable();         // المواصفات التفصيلية
            $table->decimal('quantity', 10, 2)->default(1);     // الكمية
            $table->string('unit')->nullable();                 // الوحدة (م2، م.ط، حبة، مقطوع)
            $table->decimal('unit_price', 12, 2)->default(0);   // سعر الوحدة
            $table->decimal('price', 12, 2)->default(0);        // السعر الإجمالي للبند

            $table->timestamps();

            $table->index(['offer_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offer_items');
        Schema::dropIfExists('offers');
    }
};
