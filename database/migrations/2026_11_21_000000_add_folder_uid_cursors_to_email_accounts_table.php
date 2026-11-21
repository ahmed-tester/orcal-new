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
        Schema::table('email_accounts', function (Blueprint $table) {
            $table->unsignedBigInteger('inbox_uid_validity')->nullable()->after('last_message_uid');
            $table->unsignedBigInteger('last_spam_message_uid')->default(0)->after('inbox_uid_validity');
            $table->unsignedBigInteger('spam_uid_validity')->nullable()->after('last_spam_message_uid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('email_accounts', function (Blueprint $table) {
            $table->dropColumn([
                'inbox_uid_validity',
                'last_spam_message_uid',
                'spam_uid_validity',
            ]);
        });
    }
};
