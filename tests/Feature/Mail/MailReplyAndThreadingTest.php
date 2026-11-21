<?php

namespace Tests\Feature\Mail;

use App\Enums\Mail\MailCategory;
use App\Enums\Mail\MailConversationStatus;
use App\Enums\Mail\MailMessageStatus;
use App\Enums\Mail\MailPriority;
use App\Enums\Mail\MailReplyStatus;
use App\Enums\Mail\MailSourceFolder;
use App\Enums\UserType;
use App\Mail\AccountReplyMail;
use App\Models\Mail\MailAccount;
use App\Models\Mail\MailAttachment;
use App\Models\Mail\MailConversation;
use App\Models\Mail\MailMessage;
use App\Models\Mail\MailReply;
use App\Models\User;
use App\Services\Mail\EmailReplyService;
use App\Services\Mail\MailSyncService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class MailReplyAndThreadingTest extends TestCase
{
    protected EmailReplyService $service;

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
            $table->string('user_type')->default('super_admin');
            $table->timestamps();
        });

        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        $migration = require base_path('database/migrations/2026_11_19_130000_create_mail_inbox_tables.php');
        $migration->up();

        $cursorMigration = require base_path('database/migrations/2026_11_21_000000_add_folder_uid_cursors_to_email_accounts_table.php');
        $cursorMigration->up();

        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
        });
        Schema::create('model_has_roles', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
        });
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
        });
        Schema::create('model_has_permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('permission_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
        });
        Schema::create('role_has_permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('permission_id');
            $table->unsignedBigInteger('role_id');
        });
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Storage::fake(MailSyncService::ATTACHMENT_DISK);
        $this->service = app(EmailReplyService::class);
    }

    public function test_reply_sends_from_receiving_account_preserves_headers_and_updates_statuses(): void
    {
        Mail::fake();

        $companyId = DB::table('companies')->insertGetId(['trade_name' => 'Orcal', 'created_at' => now(), 'updated_at' => now()]);
        $user = User::create([
            'name' => 'Agent',
            'email' => 'agent@orcal.test',
            'password' => bcrypt('secret'),
            'status' => 'active', 'user_type' => UserType::SUPER_ADMIN,
        ]);

        $account = MailAccount::create([
            'company_id' => $companyId,
            'name' => 'Support Dept',
            'email' => 'support@orcal.test',
            'host' => 'mail.orcal.test',
            'is_active' => true,
        ]);

        $conversation = MailConversation::create([
            'company_id' => $companyId,
            'email_account_id' => $account->id,
            'root_message_id' => '<msg-1@client.test>',
            'subject' => 'Quote Request',
            'participant_email' => 'client@client.test',
            'status' => MailConversationStatus::NEW,
        ]);

        $message = MailMessage::create([
            'company_id' => $companyId,
            'email_account_id' => $account->id,
            'conversation_id' => $conversation->id,
            'message_id' => '<msg-1@client.test>',
            'references' => '<prev-0@client.test>',
            'sender_email' => 'client@client.test',
            'sender_name' => 'Client',
            'recipient_email' => 'support@orcal.test',
            'subject' => 'Quote Request',
            'received_at' => now(),
            'source_folder' => MailSourceFolder::INBOX,
            'category' => MailCategory::REQUEST,
            'priority' => MailPriority::HIGH,
            'status' => MailMessageStatus::NEW,
        ]);

        $attachmentPath = EmailReplyService::attachmentDirectory($message, $user).'/quote.pdf';
        Storage::disk(MailSyncService::ATTACHMENT_DISK)->put($attachmentPath, 'PDF_CONTENT');

        $result = $this->service->sendReply($message, 'Please find attached.', $user, [$attachmentPath]);

        $this->assertTrue($result['success']);
        $this->assertInstanceOf(MailReply::class, $result['reply']);
        $this->assertSame(MailReplyStatus::SENT, $result['reply']->status);
        $this->assertSame('client@client.test', $result['reply']->to_email);
        $this->assertSame(MailMessageStatus::REPLIED, $message->fresh()->status);
        $this->assertNotNull($message->fresh()->replied_at);
        $this->assertSame(MailConversationStatus::REPLIED, $conversation->fresh()->status);

        $replyAttachment = MailAttachment::where('reply_id', $result['reply']->id)->first();
        $this->assertNotNull($replyAttachment);
        $this->assertSame('quote.pdf', $replyAttachment->file_name);

        Mail::assertSent(AccountReplyMail::class, function (AccountReplyMail $mail) {
            return $mail->fromEmail === 'support@orcal.test'
                && $mail->inReplyTo === '<msg-1@client.test>'
                && str_contains((string) $mail->references, '<prev-0@client.test>')
                && str_contains((string) $mail->references, '<msg-1@client.test>')
                && $mail->hasTo('client@client.test');
        });
    }

    public function test_failed_delivery_records_failed_reply_and_keeps_message_unreplied(): void
    {
        $companyId = DB::table('companies')->insertGetId(['trade_name' => 'Orcal', 'created_at' => now(), 'updated_at' => now()]);
        $account = MailAccount::create([
            'company_id' => $companyId,
            'name' => 'Support Dept',
            'email' => 'support@orcal.test',
            'is_active' => true,
        ]);

        $message = MailMessage::create([
            'company_id' => $companyId,
            'email_account_id' => $account->id,
            'sender_email' => 'client@client.test',
            'recipient_email' => 'support@orcal.test',
            'subject' => 'Need help',
            'received_at' => now(),
            'source_folder' => MailSourceFolder::INBOX,
            'category' => MailCategory::INQUIRY,
            'priority' => MailPriority::MEDIUM,
            'status' => MailMessageStatus::NEW,
        ]);

        $failingService = new class extends EmailReplyService
        {
            protected function deliverMail(AccountReplyMail $mailable, MailMessage $email): void
            {
                throw new \RuntimeException('SMTP connection refused');
            }
        };

        $actor = User::create(['name' => 'Sender', 'email' => 'sender@orcal.test', 'password' => bcrypt('secret'), 'status' => 'active', 'user_type' => UserType::SUPER_ADMIN]);
        $result = $failingService->sendReply($message, 'Trying to reply', $actor);

        $this->assertFalse($result['success']);
        $this->assertSame(MailReplyStatus::FAILED, $result['reply']->status);
        $this->assertSame('SMTP connection refused', $result['reply']->error_message);
        $this->assertSame(MailMessageStatus::NEW, $message->fresh()->status);
        $this->assertNull($message->fresh()->replied_at);
    }

    public function test_mime_headers_are_built_by_real_array_transport(): void
    {
        $mail = new AccountReplyMail('support@orcal.test', 'Support', 'Re: Contract', 'Received', 'reply@orcal.test', '<parent@client.test>', '<root@client.test> <parent@client.test>');
        $sent = Mail::mailer('array')->to('client@client.test')->send($mail);
        $headers = $sent->getSymfonySentMessage()->getOriginalMessage()->getHeaders();
        $this->assertStringContainsString('reply@orcal.test', $headers->get('Message-ID')->getBodyAsString());
        $this->assertStringContainsString('parent@client.test', $headers->get('In-Reply-To')->getBodyAsString());
        $this->assertStringContainsString('root@client.test', $headers->get('References')->getBodyAsString());
    }

    public function test_reply_rejects_paths_outside_the_current_users_upload_directory(): void
    {
        $companyId = DB::table('companies')->insertGetId(['trade_name' => 'A']);
        $account = MailAccount::create(['company_id' => $companyId, 'name' => 'A', 'email' => 'support@a.test']);
        $message = MailMessage::create(['company_id' => $companyId, 'email_account_id' => $account->id, 'sender_email' => 'client@ext.test', 'recipient_email' => $account->email, 'subject' => 'Test', 'received_at' => now()]);
        $actor = User::create(['name' => 'Sender', 'email' => 'sender@orcal.test', 'password' => bcrypt('secret'), 'status' => 'active', 'user_type' => UserType::SUPER_ADMIN]);
        Storage::disk(MailSyncService::ATTACHMENT_DISK)->put('other-company/private.pdf', 'private');
        Mail::fake();
        try {
            $this->service->sendReply($message, 'Body', $actor, ['other-company/private.pdf']);
            $this->fail('A private file outside the reply directory must be rejected.');
        } catch (ValidationException) {
            Mail::assertNothingSent();
        }
    }

    public function test_smtp_config_enforces_peer_verification_and_preserves_custom_password(): void
    {
        $companyId = DB::table('companies')->insertGetId(['trade_name' => 'A']);

        $customAccount = MailAccount::create([
            'company_id' => $companyId,
            'name' => 'Custom',
            'email' => 'info@company.sa',
            'host' => 'mail.company.sa',
            'password' => 'my pass word ',
        ]);

        $gmailAccount = MailAccount::create([
            'company_id' => $companyId,
            'name' => 'Gmail',
            'email' => 'team@gmail.com',
            'host' => 'imap.gmail.com',
            'password' => 'abcd efgh ijkl mnop',
        ]);

        $customSmtp = $this->service->buildSmtpConfig($customAccount);
        $gmailSmtp = $this->service->buildSmtpConfig($gmailAccount);

        $this->assertTrue($customSmtp['verify_peer']);
        $this->assertSame('my pass word ', $customSmtp['password']);
        $this->assertTrue($gmailSmtp['verify_peer']);
        $this->assertSame('abcdefghijklmnop', $gmailSmtp['password']);
    }
}
