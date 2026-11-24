<?php

namespace Tests\Feature\Mail;

use App\Enums\Mail\MailAccountStatus;
use App\Enums\Mail\MailCategory;
use App\Enums\Mail\MailConversationStatus;
use App\Enums\Mail\MailMessageStatus;
use App\Enums\Mail\MailPriority;
use App\Enums\Mail\MailReplyStatus;
use App\Enums\Mail\MailSourceFolder;
use App\Enums\Mail\MailSyncStatus;
use App\Models\Mail\MailAccount;
use App\Models\Mail\MailAttachment;
use App\Models\Mail\MailConversation;
use App\Models\Mail\MailExclusionKeyword;
use App\Models\Mail\MailImportanceKeyword;
use App\Models\Mail\MailInternalNote;
use App\Models\Mail\MailMessage;
use App\Models\Mail\MailReply;
use App\Models\Mail\MailSyncLog;
use App\Models\Mail\MailTrustedDomain;
use App\Models\Mail\MailTrustedEmail;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MailInboxSchemaTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['database.default' => 'sqlite']);
        config(['database.connections.sqlite.database' => ':memory:']);
        DB::purge();
        DB::reconnect('sqlite');
        DB::statement('PRAGMA foreign_keys = ON;');

        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('trade_name')->nullable();
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('active');
            $table->timestamps();
        });

        $migration = require base_path('database/migrations/2026_11_19_130000_create_mail_inbox_tables.php');
        $migration->up();
    }

    public function test_mail_inbox_tables_and_relationships_work(): void
    {
        $companyId = DB::table('companies')->insertGetId([
            'trade_name' => 'Orcal Co',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $userId = DB::table('users')->insertGetId([
            'name' => 'Agent One',
            'email' => 'agent@orcal.test',
            'password' => bcrypt('secret'),
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $employeeId = DB::table('employees')->insertGetId([
            'company_id' => $companyId,
            'user_id' => $userId,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $account = MailAccount::create([
            'company_id' => $companyId,
            'name' => 'Support Inbox',
            'email' => 'support@orcal.test',
            'type' => 'imap',
            'host' => 'mail.orcal.test',
            'port' => 993,
            'encryption' => 'ssl',
            'username' => 'support@orcal.test',
            'password' => 'secret-pass',
            'status' => MailAccountStatus::CONNECTED,
            'last_message_uid' => 42,
            'is_active' => true,
        ]);

        $account->employees()->attach($employeeId, ['company_id' => $companyId]);

        $this->assertSame($companyId, $account->company->id);
        $this->assertCount(1, $account->employees);
        $this->assertSame($employeeId, $account->employees->first()->id);
        $this->assertSame('secret-pass', $account->fresh()->password);
        $this->assertNotSame('secret-pass', DB::table('email_accounts')->where('id', $account->id)->value('password'));
        $this->assertArrayNotHasKey('password', $account->fresh()->toArray());
        $this->assertArrayNotHasKey('oauth_access_token', $account->fresh()->toArray());

        $conversation = MailConversation::create([
            'company_id' => $companyId,
            'email_account_id' => $account->id,
            'root_message_id' => '<root@example.com>',
            'subject' => 'Official Inquiry',
            'participant_email' => 'client@example.com',
            'status' => MailConversationStatus::NEW,
            'last_message_at' => now(),
        ]);

        $message = MailMessage::create([
            'company_id' => $companyId,
            'email_account_id' => $account->id,
            'conversation_id' => $conversation->id,
            'message_id' => '<msg1@example.com>',
            'sender_email' => 'client@example.com',
            'recipient_email' => 'support@orcal.test',
            'subject' => 'Official Inquiry',
            'body_html' => '<div dir="rtl" style="color:red"><p>Hello</p><img src="cid:img1@mail"/><script>alert(1)</script><a href="https://example.com" onclick="evil()">Link</a></div>',
            'received_at' => now(),
            'source_folder' => MailSourceFolder::INBOX,
            'is_important' => true,
            'category' => MailCategory::OFFICIAL,
            'confidence' => 100,
            'priority' => MailPriority::HIGH,
            'status' => MailMessageStatus::NEW,
        ]);

        $attachment = MailAttachment::create([
            'company_id' => $companyId,
            'email_id' => $message->id,
            'file_name' => 'contract.pdf',
            'file_path' => 'mail-attachments/1/contract.pdf',
            'content_type' => 'application/pdf',
            'file_size' => 1024,
            'is_inline' => false,
        ]);

        $reply = MailReply::create([
            'company_id' => $companyId,
            'email_id' => $message->id,
            'email_account_id' => $account->id,
            'user_id' => $userId,
            'to_email' => 'client@example.com',
            'subject' => 'Re: Official Inquiry',
            'body' => 'Received with thanks.',
            'status' => MailReplyStatus::SENT,
            'sent_at' => now(),
        ]);

        $note = MailInternalNote::create([
            'company_id' => $companyId,
            'email_id' => $message->id,
            'user_id' => $userId,
            'content' => 'Reviewed by supervisor.',
        ]);

        $syncLog = MailSyncLog::create([
            'company_id' => $companyId,
            'email_account_id' => $account->id,
            'folder' => 'INBOX',
            'status' => MailSyncStatus::SUCCESS,
            'messages_found' => 5,
            'messages_imported' => 4,
            'messages_rejected' => 1,
        ]);

        MailTrustedEmail::create(['email' => 'vip@gov.sa', 'is_active' => true]);
        MailTrustedDomain::create(['domain_pattern' => '*.gov.sa', 'is_active' => true]);
        MailImportanceKeyword::create(['keyword' => 'urgent', 'weight' => 20, 'is_active' => true]);
        MailExclusionKeyword::create(['keyword' => 'unsubscribe', 'is_active' => true]);

        $this->assertSame(MailMessageStatus::NEW, $message->fresh()->status);
        $this->assertSame('official', $message->fresh()->category);
        $this->assertSame(MailPriority::HIGH, $message->fresh()->priority);
        $this->assertSame($attachment->id, $message->regularAttachments()->first()->id);
        $this->assertSame($reply->id, $message->replies()->first()->id);
        $this->assertSame($note->id, $message->internalNotes()->first()->id);
        $this->assertSame($syncLog->id, $account->syncLogs()->first()->id);

        $sanitized = $message->sanitized_html;
        $this->assertStringNotContainsString('<script>', $sanitized);
        $this->assertStringNotContainsString('onclick', $sanitized);
        $this->assertStringContainsString('dir="rtl"', $sanitized);
        $this->assertStringContainsString('cid:img1', $sanitized);
        $this->assertStringContainsString('target="_blank"', $sanitized);
    }

    public function test_duplicate_account_employee_assignment_is_rejected(): void
    {
        $companyId = DB::table('companies')->insertGetId([
            'trade_name' => 'Orcal Co',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $employeeId = DB::table('employees')->insertGetId([
            'company_id' => $companyId,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $account = MailAccount::create([
            'company_id' => $companyId,
            'name' => 'Sales Inbox',
            'email' => 'sales@orcal.test',
        ]);

        $account->employees()->attach($employeeId, ['company_id' => $companyId]);

        $this->expectException(QueryException::class);
        $account->employees()->attach($employeeId, ['company_id' => $companyId]);
    }

    public function test_arabic_labels_map_to_category_and_priority_enums(): void
    {
        $arCategories = __('mail_inbox.categories', [], 'ar');
        $arPriorities = __('mail_inbox.priorities', [], 'ar');

        $this->assertSame(MailCategory::COMPLAINT, MailCategory::fromLabel($arCategories['complaint']));
        $this->assertSame(MailCategory::OFFICIAL, MailCategory::fromLabel($arCategories['official']));
        $this->assertSame(MailCategory::OTHER, MailCategory::fromLabel('unknown-value'));

        $this->assertSame(MailPriority::HIGH, MailPriority::fromLabel($arPriorities['high']));
        $this->assertSame(MailPriority::MEDIUM, MailPriority::fromLabel(null));
    }

    public function test_existing_mail_rule_constraints_upgrade_without_losing_records(): void
    {
        Schema::table('trusted_emails', function (Blueprint $table) {
            $table->dropUnique(['company_id', 'email']);
            $table->unique('email');
        });
        Schema::table('trusted_emails', fn (Blueprint $table) => $table->dropForeign(['company_id']));
        Schema::table('trusted_emails', fn (Blueprint $table) => $table->foreign('company_id')->references('id')->on('companies')->nullOnDelete());
        $companyA = DB::table('companies')->insertGetId(['trade_name' => 'A']);
        $companyB = DB::table('companies')->insertGetId(['trade_name' => 'B']);
        $first = MailTrustedEmail::create(['company_id' => $companyA, 'email' => 'shared@gov.sa', 'is_active' => true]);
        $migration = require base_path('database/migrations/2026_11_19_140000_scope_existing_mail_rules_to_companies.php');
        $migration->up();
        $migration->up();
        $second = MailTrustedEmail::create(['company_id' => $companyB, 'email' => 'shared@gov.sa', 'is_active' => true]);
        $this->assertNotNull($first->fresh());
        $this->assertNotNull($second->fresh());
        DB::table('companies')->where('id', $companyA)->delete();
        $this->assertNull($first->fresh());
        $this->assertSame($companyB, $second->fresh()->company_id);
    }
}
