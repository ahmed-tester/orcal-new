<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('mail_categories')) {
            Schema::create('mail_categories', function (Blueprint $table) {
                $table->id();
                $table->string('slug', 64)->unique();
                $table->string('name_ar', 120);
                $table->string('name_en', 120);
                $table->string('color', 32)->default('gray');
                $table->string('chart_color', 16)->default('#64748b');
                $table->boolean('is_excluded')->default(false)->index();
                $table->boolean('is_active')->default(true)->index();
                $table->boolean('is_system')->default(false);
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        $now = now();
        $defaults = [
            [
                'slug' => 'inquiry',
                'name_ar' => 'استفسار',
                'name_en' => 'Inquiry',
                'color' => 'info',
                'chart_color' => '#6366f1',
                'is_excluded' => false,
                'is_active' => true,
                'is_system' => true,
                'sort_order' => 1,
            ],
            [
                'slug' => 'request',
                'name_ar' => 'طلب',
                'name_en' => 'Request',
                'color' => 'primary',
                'chart_color' => '#0ea5e9',
                'is_excluded' => false,
                'is_active' => true,
                'is_system' => true,
                'sort_order' => 2,
            ],
            [
                'slug' => 'complaint',
                'name_ar' => 'شكوى',
                'name_en' => 'Complaint',
                'color' => 'danger',
                'chart_color' => '#ef4444',
                'is_excluded' => false,
                'is_active' => true,
                'is_system' => true,
                'sort_order' => 3,
            ],
            [
                'slug' => 'official',
                'name_ar' => 'رسمي',
                'name_en' => 'Official',
                'color' => 'success',
                'chart_color' => '#10b981',
                'is_excluded' => false,
                'is_active' => true,
                'is_system' => true,
                'sort_order' => 4,
            ],
            [
                'slug' => 'marketing',
                'name_ar' => 'تسويق',
                'name_en' => 'Marketing',
                'color' => 'warning',
                'chart_color' => '#f59e0b',
                'is_excluded' => true,
                'is_active' => true,
                'is_system' => true,
                'sort_order' => 5,
            ],
            [
                'slug' => 'spam',
                'name_ar' => 'سبام',
                'name_en' => 'Spam',
                'color' => 'gray',
                'chart_color' => '#64748b',
                'is_excluded' => true,
                'is_active' => true,
                'is_system' => true,
                'sort_order' => 6,
            ],
            [
                'slug' => 'other',
                'name_ar' => 'أخرى',
                'name_en' => 'Other',
                'color' => 'gray',
                'chart_color' => '#8b5cf6',
                'is_excluded' => false,
                'is_active' => true,
                'is_system' => true,
                'sort_order' => 7,
            ],
        ];

        foreach ($defaults as $row) {
            $exists = DB::table('mail_categories')->where('slug', $row['slug'])->exists();
            if (! $exists) {
                DB::table('mail_categories')->insert(array_merge($row, [
                    'created_at' => $now,
                    'updated_at' => $now,
                ]));
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('mail_categories');
    }
};
