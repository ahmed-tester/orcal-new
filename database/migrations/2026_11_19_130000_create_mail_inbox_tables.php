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
        Schema::create('email_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('name');
            $table->string('email')->index();
            $table->string('type')->default('imap');
            $table->string('host')->nullable();
            $table->unsignedSmallInteger('port')->default(993);
            $table->string('encryption')->default('ssl');
            $table->string('username')->nullable();
            $table->text('password')->nullable();
            $table->text('oauth_access_token')->nullable();
            $table->text('oauth_refresh_token')->nullable();
            $table->timestamp('oauth_token_expires_at')->nullable();
            $table->string('status')->default('connected');
            $table->timestamp('last_sync_at')->nullable();
            $table->unsignedBigInteger('last_message_uid')->default(0);
            $table->string('last_history_id')->nullable();
            $table->unsignedInteger('sync_interval_minutes')->default(1);
            $table->boolean('is_active')->default(true);
            $table->text('error_message')->nullable();
            $table->timestamps();
        });

        Schema::create('email_account_employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('email_account_id')->constrained('email_accounts')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['email_account_id', 'employee_id']);
        });

        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('email_account_id')->constrained('email_accounts')->cascadeOnDelete();
            $table->string('root_message_id')->nullable()->index();
            $table->string('subject');
            $table->string('participant_email')->index();
            $table->string('status')->default('new')->index();
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();
        });

        Schema::create('emails', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('email_account_id')->constrained('email_accounts')->cascadeOnDelete();
            $table->foreignId('conversation_id')->nullable()->constrained('conversations')->nullOnDelete();
            $table->string('message_id')->nullable()->index();
            $table->string('in_reply_to')->nullable()->index();
            $table->text('references')->nullable();
            $table->string('sender_email')->index();
            $table->string('sender_name')->nullable();
            $table->string('recipient_email')->index();
            $table->string('subject');
            $table->longText('body_plain')->nullable();
            $table->longText('body_html')->nullable();
            $table->timestamp('received_at')->index();
            $table->string('source_folder')->default('inbox');
            $table->boolean('is_important')->default(false)->index();
            $table->string('category')->default('inquiry');
            $table->unsignedTinyInteger('confidence')->default(0);
            $table->text('summary')->nullable();
            $table->string('priority')->default('medium');
            $table->string('status')->default('new')->index();
            $table->string('classification_reason')->nullable();
            $table->boolean('is_read')->default(false)->index();
            $table->timestamp('replied_at')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('escalated_at')->nullable();
            $table->string('fingerprint')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('replies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('email_id')->constrained('emails')->cascadeOnDelete();
            $table->foreignId('email_account_id')->constrained('email_accounts')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('to_email');
            $table->string('subject');
            $table->longText('body');
            $table->string('sent_message_id')->nullable();
            $table->string('status')->default('sent');
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });

        Schema::create('email_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('email_id')->nullable()->constrained('emails')->cascadeOnDelete();
            $table->foreignId('reply_id')->nullable()->constrained('replies')->cascadeOnDelete();
            $table->string('file_name');
            $table->string('file_path');
            $table->string('content_type')->nullable();
            $table->unsignedBigInteger('file_size')->default(0);
            $table->string('content_id')->nullable()->index();
            $table->boolean('is_inline')->default(false)->index();
            $table->timestamps();
        });

        Schema::create('internal_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('email_id')->constrained('emails')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('content');
            $table->timestamps();
        });

        Schema::create('trusted_emails', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies')->cascadeOnDelete();
            $table->string('email');
            $table->unique(['company_id', 'email']);
            $table->string('organization_name')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('trusted_domains', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies')->cascadeOnDelete();
            $table->string('domain_pattern');
            $table->unique(['company_id', 'domain_pattern']);
            $table->string('organization_name')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('importance_keywords', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies')->cascadeOnDelete();
            $table->string('keyword');
            $table->unique(['company_id', 'keyword']);
            $table->unsignedInteger('weight')->default(10);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('exclusion_keywords', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies')->cascadeOnDelete();
            $table->string('keyword');
            $table->unique(['company_id', 'keyword']);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('sync_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('email_account_id')->constrained('email_accounts')->cascadeOnDelete();
            $table->string('folder')->default('INBOX');
            $table->string('status')->default('success');
            $table->unsignedInteger('messages_found')->default(0);
            $table->unsignedInteger('messages_imported')->default(0);
            $table->unsignedInteger('messages_rejected')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sync_logs');
        Schema::dropIfExists('exclusion_keywords');
        Schema::dropIfExists('importance_keywords');
        Schema::dropIfExists('trusted_domains');
        Schema::dropIfExists('trusted_emails');
        Schema::dropIfExists('internal_notes');
        Schema::dropIfExists('email_attachments');
        Schema::dropIfExists('replies');
        Schema::dropIfExists('emails');
        Schema::dropIfExists('conversations');
        Schema::dropIfExists('email_account_employees');
        Schema::dropIfExists('email_accounts');
    }
};
