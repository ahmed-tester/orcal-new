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
        Schema::table('attendance_devices', function (Blueprint $table) {
            if (!Schema::hasColumn('attendance_devices', 'serial_number')) {
                $table->string('serial_number')->nullable()->after('code')->index();
            }
            if (!Schema::hasColumn('attendance_devices', 'model')) {
                $table->string('model')->nullable()->after('serial_number');
            }
            if (!Schema::hasColumn('attendance_devices', 'push_version')) {
                $table->string('push_version')->nullable()->after('model');
            }
            if (!Schema::hasColumn('attendance_devices', 'timezone')) {
                $table->string('timezone')->default('Asia/Riyadh')->after('push_version');
            }
        });

        Schema::create('biometric_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->nullable()->constrained('attendance_devices')->nullOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->string('device_sn')->nullable()->index();
            $table->string('device_user_id'); // PIN / Employee ID on Device
            $table->dateTime('punched_at')->index();
            $table->string('punch_type')->default('check_in'); // check_in, check_out, break_out, break_in, other
            $table->string('raw_status')->nullable(); // 0, 1, 2, 3, etc.
            $table->string('verify_mode')->nullable(); // 1 (fingerprint), 2 (face), 3 (pwd), 4 (card), etc.
            $table->string('work_code')->nullable();
            $table->text('raw_payload')->nullable();
            $table->boolean('is_processed')->default(false)->index();
            $table->timestamp('processed_at')->nullable();
            $table->string('unique_key')->unique();
            $table->timestamps();

            $table->index(['employee_id', 'punched_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('biometric_logs');

        Schema::table('attendance_devices', function (Blueprint $table) {
            $table->dropColumn(['serial_number', 'model', 'push_version', 'timezone']);
        });
    }
};
