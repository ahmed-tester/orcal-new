<?php

namespace Tests\Feature\Mail;

use App\Enums\Mail\MailAccountStatus;
use App\Enums\Mail\MailMessageStatus;
use App\Enums\Mail\MailPriority;
use App\Enums\Mail\MailSourceFolder;
use App\Enums\Mail\MailSyncStatus;
use App\Enums\UserType;
use App\Models\Mail\MailAccount;
use App\Models\Mail\MailAttachment;
use App\Models\Mail\MailExclusionKeyword;
use App\Models\Mail\MailMessage;
use App\Models\Mail\MailSyncLog;
use App\Models\Mail\MailTrustedEmail;
use App\Models\User;
use App\Services\Mail\EmailClassifierService;
use App\Services\Mail\MailAccessService;
use App\Services\Mail\MailNotificationService;
use App\Services\Mail\MailSyncService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;
use Webklex\PHPIMAP\Message;

class MailSyncAndAttachmentTest extends TestCase
{
    protected MailSyncService $service;

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

        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('status')->default('active');
            $table->string('user_type')->default(UserType::SAUDI_EMPLOYEE->value);
            $table->foreignId('company_id')->nullable();
            $table->foreignId('branch_id')->nullable();
            $table->timestamps();
        });

        Schema::create('owners', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable();
            $table->foreignId('company_id')->nullable();
            $table->timestamps();
        });

        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable();
            $table->foreignId('department_id')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
        });

        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
        });

        Schema::create('model_has_permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('permission_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
        });

        Schema::create('model_has_roles', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
        });

        Schema::create('role_has_permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('permission_id');
            $table->unsignedBigInteger('role_id');
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('group')->nullable();
            $table->string('type')->nullable();
            $table->string('label')->nullable();
            $table->boolean('is_encrypted')->default(false);
            $table->timestamps();
        });

        $migration = require base_path('database/migrations/2026_11_19_130000_create_mail_inbox_tables.php');
        $migration->up();

        $cursorMigration = require base_path('database/migrations/2026_11_21_000000_add_folder_uid_cursors_to_email_accounts_table.php');
        $cursorMigration->up();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Permission::create(['name' => MailAccessService::PERM_VIEW_INBOX, 'guard_name' => 'web']);
        Role::create(['name' => 'Super Admin', 'guard_name' => 'web']);

        Notification::fake();
        Storage::fake(MailSyncService::ATTACHMENT_DISK);
        Storage::fake('public');

        $this->service = app(MailSyncService::class);
    }

    public function test_sync_error_notifies_once_per_outage_and_again_after_recovery(): void
    {
        $companyId = DB::table('companies')->insertGetId(['trade_name' => 'Orcal']);
        $account = MailAccount::create([
            'company_id' => $companyId,
            'name' => 'Support',
            'email' => 'support@orcal.test',
            'host' => 'imap.orcal.test',
            'status' => MailAccountStatus::CONNECTED,
            'is_active' => true,
        ]);
        $staleAccount = $account->fresh();

        $notifications = \Mockery::mock(MailNotificationService::class);
        $notifications->shouldReceive('notifySyncError')->times(3)->andReturn(1);
        $this->app->instance(MailNotificationService::class, $notifications);

        $client = new class
        {
            public ?string $error = 'AUTHENTICATIONFAILED';

            public function connect(): void
            {
                if ($this->error !== null) {
                    throw new \RuntimeException($this->error);
                }
            }

            public function disconnect(): void {}

            public function getFolder(string $name): ?object
            {
                return null;
            }
        };

        $service = new class(app(EmailClassifierService::class), $client) extends MailSyncService
        {
            public function __construct(EmailClassifierService $classifier, private object $client)
            {
                parent::__construct($classifier);
            }

            public function createImapClient(MailAccount $account): object
            {
                return $this->client;
            }
        };

        $service->syncAccount($account);
        $notifications->shouldHaveReceived('notifySyncError')->once();
        $this->assertSame(MailAccountStatus::CONNECTION_ERROR, $account->fresh()->status);

        // A stale queued model and a changed error must not create a second alert.
        $client->error = 'AUTHENTICATIONFAILED changed details';
        $service->syncAccount($staleAccount);
        $notifications->shouldHaveReceived('notifySyncError')->once();
        $this->assertSame(2, MailSyncLog::where('email_account_id', $account->id)->count());
        $this->assertNotNull($account->fresh()->error_message);

        // Each account has its own outage, independent of other accounts.
        $other = $account->replicate();
        $other->email = 'other@orcal.test';
        $other->status = MailAccountStatus::CONNECTED;
        $other->error_message = null;
        $other->save();
        $service->syncAccount($other);
        $notifications->shouldHaveReceived('notifySyncError')->twice();

        $client->error = null;
        $service->syncAccount($account->fresh());
        $this->assertSame(MailAccountStatus::CONNECTED, $account->fresh()->status);
        $this->assertNull($account->fresh()->error_message);
        $notifications->shouldHaveReceived('notifySyncError')->twice();

        $client->error = 'AUTHENTICATIONFAILED';
        $service->syncAccount($account->fresh());
        $this->assertSame(MailAccountStatus::CONNECTION_ERROR, $account->fresh()->status);
    }

    public function test_existing_sync_error_does_not_notify_again(): void
    {
        $companyId = DB::table('companies')->insertGetId(['trade_name' => 'Orcal']);
        $this->app->instance(MailNotificationService::class, \Mockery::mock(MailNotificationService::class)
            ->shouldNotReceive('notifySyncError')->getMock());

        $service = new class(app(EmailClassifierService::class)) extends MailSyncService
        {
            public function createImapClient(MailAccount $account): object
            {
                throw new \RuntimeException('AUTHENTICATIONFAILED');
            }
        };

        foreach ([MailAccountStatus::CONNECTION_ERROR, MailAccountStatus::REAUTH_REQUIRED] as $status) {
            $account = MailAccount::create([
                'company_id' => $companyId,
                'name' => 'Support',
                'email' => $status->value.'@orcal.test',
                'host' => 'imap.orcal.test',
                'status' => $status,
                'is_active' => true,
            ]);

            $service->syncAccount($account);
            $this->assertSame(MailAccountStatus::CONNECTION_ERROR, $account->fresh()->status);
            $this->assertNotNull($account->fresh()->error_message);
            $this->assertSame(1, MailSyncLog::where('email_account_id', $account->id)->count());
        }
    }

    public function test_import_deduplicates_threads_and_protects_reply_in_conversation(): void
    {
        $companyId = DB::table('companies')->insertGetId(['trade_name' => 'Orcal', 'created_at' => now(), 'updated_at' => now()]);
        $account = MailAccount::create([
            'company_id' => $companyId,
            'name' => 'Support',
            'email' => 'support@orcal.test',
            'is_active' => true,
        ]);

        MailTrustedEmail::create([
            'company_id' => $companyId,
            'email' => 'client@vip.test',
            'is_active' => true,
        ]);
        MailExclusionKeyword::create([
            'company_id' => $companyId,
            'keyword' => 'unsubscribe',
            'is_active' => true,
        ]);

        // Phantom empty message is ignored
        $this->assertNull($this->service->importSingleMessage($account, [
            'sender_email' => 'client@vip.test',
            'subject' => '',
            'body_plain' => '',
        ]));

        // First message creates conversation
        $first = $this->service->importSingleMessage($account, [
            'message_id' => '<root-1@vip.test>',
            'sender_email' => 'client@vip.test',
            'subject' => 'Project Quote',
            'body_plain' => 'Please send the quote.',
            'received_at' => '2026-11-19 10:00:00',
        ], MailSourceFolder::INBOX);
        $this->assertTrue($first);

        // Duplicate message_id is skipped
        $duplicate = $this->service->importSingleMessage($account, [
            'message_id' => '<root-1@vip.test>',
            'sender_email' => 'client@vip.test',
            'subject' => 'Project Quote',
            'body_plain' => 'Please send the quote.',
            'received_at' => '2026-11-19 10:00:00',
        ], MailSourceFolder::INBOX);
        $this->assertNull($duplicate);

        // Reply from a non-whitelisted address containing an exclusion keyword
        // must NOT be rejected because it references the existing conversation via In-Reply-To
        $replyResult = $this->service->importSingleMessage($account, [
            'message_id' => '<reply-2@other.test>',
            'in_reply_to' => '<root-1@vip.test>',
            'sender_email' => 'colleague@other.test',
            'subject' => 'Re: Project Quote',
            'body_plain' => 'Thanks, click unsubscribe in footer.',
            'received_at' => '2026-11-19 10:05:00',
        ], MailSourceFolder::SPAM_JUNK);

        $this->assertTrue($replyResult);
        $messages = MailMessage::orderBy('id')->get();
        $this->assertCount(2, $messages);
        $this->assertSame($messages[0]->conversation_id, $messages[1]->conversation_id);
        $this->assertSame(MailMessageStatus::NEW, $messages[1]->status);
        $this->assertSame('inquiry', $messages[1]->category);
        $this->assertSame(MailPriority::MEDIUM, $messages[1]->priority);
        $this->assertTrue($messages[1]->is_important);
        $this->assertSame(MailSourceFolder::SPAM_JUNK, $messages[1]->source_folder);
    }

    public function test_attachments_use_private_disk_resolve_cid_and_enforce_policy_on_routes(): void
    {
        $companyId = DB::table('companies')->insertGetId(['trade_name' => 'Orcal', 'created_at' => now(), 'updated_at' => now()]);

        $assignedUser = User::create([
            'name' => 'Assigned',
            'email' => 'assigned@orcal.test',
            'password' => bcrypt('secret'),
            'status' => 'active',
            'company_id' => $companyId,
        ]);
        $assignedUser->givePermissionTo(MailAccessService::PERM_VIEW_INBOX);

        $unassignedUser = User::create([
            'name' => 'Unassigned',
            'email' => 'unassigned@orcal.test',
            'password' => bcrypt('secret'),
            'status' => 'active',
            'company_id' => $companyId,
        ]);
        $unassignedUser->givePermissionTo(MailAccessService::PERM_VIEW_INBOX);

        $employeeId = DB::table('employees')->insertGetId([
            'company_id' => $companyId,
            'user_id' => $assignedUser->id,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $account = MailAccount::create([
            'company_id' => $companyId,
            'name' => 'Support',
            'email' => 'support@orcal.test',
            'is_active' => true,
        ]);
        $account->employees()->attach($employeeId, ['company_id' => $companyId]);

        MailTrustedEmail::create(['company_id' => $companyId, 'email' => 'vip@gov.sa', 'is_active' => true]);

        $inlineAtt = new class
        {
            public function getName(): string
            {
                return 'logo.png';
            }

            public function getContent(): string
            {
                return 'PNG_DATA';
            }

            public function getMimeType(): string
            {
                return 'image/png';
            }

            public function getSize(): int
            {
                return 8;
            }

            public function getId(): string
            {
                return '<logo123@mail>';
            }
        };

        $fakeMessage = new class($inlineAtt)
        {
            public function __construct(private object $att) {}

            public function getUid(): int
            {
                return 10;
            }

            public function getMessageId(): string
            {
                return '<att-msg@gov.sa>';
            }

            public function getSubject(): string
            {
                return 'With Inline Logo';
            }

            public function getFrom(): array
            {
                return [(object) ['mail' => 'vip@gov.sa', 'personal' => 'VIP']];
            }

            public function getTextBody(): string
            {
                return 'Plain';
            }

            public function getHTMLBody(): string
            {
                return '<p>Hello <img src="cid:logo123@mail"/></p>';
            }

            public function getDate(): string
            {
                return '2026-11-19 11:00:00';
            }

            public function getAttachments(): array
            {
                return [$this->att];
            }
        };

        $this->assertTrue($this->service->importSingleMessage($account, $fakeMessage, MailSourceFolder::INBOX));

        $email = MailMessage::firstOrFail();
        $attachment = MailAttachment::firstOrFail();

        $this->assertTrue($attachment->is_inline);
        Storage::disk(MailSyncService::ATTACHMENT_DISK)->assertExists($attachment->file_path);
        Storage::disk('public')->assertMissing($attachment->file_path);

        $expectedUrl = route('mail-attachments.view', $attachment, false);
        $this->assertStringContainsString($expectedUrl, (string) $email->body_html);
        $this->assertStringNotContainsString('cid:logo123@mail', (string) $email->body_html);

        // Guest cannot access
        $this->getJson(route('mail-attachments.view', $attachment))->assertUnauthorized();

        // Unassigned user gets 403
        $this->actingAs($unassignedUser)
            ->get(route('mail-attachments.view', $attachment))
            ->assertForbidden();

        // Assigned user gets 200
        $this->actingAs($assignedUser)
            ->get(route('mail-attachments.view', $attachment))
            ->assertOk();

        $this->actingAs($assignedUser)
            ->get(route('mail-attachments.download', $attachment))
            ->assertOk();

        foreach (['text/html' => '<script>alert(1)</script>', 'image/svg+xml' => '<svg xmlns="http://www.w3.org/2000/svg" onload="alert(1)"></svg>'] as $type => $payload) {
            $path = 'mail-attachments/active-'.md5($type);
            Storage::disk(MailSyncService::ATTACHMENT_DISK)->put($path, $payload);
            $active = MailAttachment::create(['company_id' => $companyId, 'email_id' => $email->id, 'file_name' => 'active-file', 'file_path' => $path, 'content_type' => $type]);
            $response = $this->get(route('mail-attachments.view', $active))->assertOk();
            $this->assertStringContainsString('attachment', $response->headers->get('Content-Disposition'));
        }
    }

    public function test_sync_account_advances_inbox_uid_and_logs_sync(): void
    {
        $companyId = DB::table('companies')->insertGetId(['trade_name' => 'Orcal', 'created_at' => now(), 'updated_at' => now()]);
        $account = MailAccount::create([
            'company_id' => $companyId,
            'name' => 'Support',
            'email' => 'support@orcal.test',
            'host' => 'imap.orcal.test',
            'last_message_uid' => 100,
            'is_active' => true,
        ]);

        MailTrustedEmail::create(['company_id' => $companyId, 'email' => 'vip@gov.sa', 'is_active' => true]);
        MailExclusionKeyword::create(['company_id' => $companyId, 'keyword' => 'promo', 'is_active' => true]);

        $msg1 = new class
        {
            public function getUid(): int
            {
                return 101;
            }

            public function getMessageId(): string
            {
                return '<u101@gov.sa>';
            }

            public function getSubject(): string
            {
                return 'Official';
            }

            public function getFrom(): array
            {
                return [(object) ['mail' => 'vip@gov.sa', 'personal' => 'VIP']];
            }

            public function getTextBody(): string
            {
                return 'Important body';
            }

            public function getHTMLBody(): string
            {
                return '';
            }

            public function getDate(): string
            {
                return '2026-11-19 12:00:00';
            }
        };

        $msg2 = new class
        {
            public function getUid(): int
            {
                return 105;
            }

            public function getMessageId(): string
            {
                return '<u105@ads.test>';
            }

            public function getSubject(): string
            {
                return 'Big promo';
            }

            public function getFrom(): array
            {
                return [(object) ['mail' => 'ads@ads.test', 'personal' => 'Ads']];
            }

            public function getTextBody(): string
            {
                return 'promo deal';
            }

            public function getHTMLBody(): string
            {
                return '';
            }

            public function getDate(): string
            {
                return '2026-11-19 12:05:00';
            }
        };

        $inboxFolder = new class([$msg1, $msg2])
        {
            public function __construct(private array $msgs) {}

            public function query(): object
            {
                $msgs = $this->msgs;

                return new class($msgs)
                {
                    public function __construct(private array $msgs) {}

                    public function leaveUnread(): self
                    {
                        return $this;
                    }

                    public function limit(int $limit): self
                    {
                        return $this;
                    }

                    public function getByUidGreater(int $uid): array
                    {
                        return $this->msgs;
                    }
                };
            }
        };

        $fakeClient = new class($inboxFolder)
        {
            public function __construct(private object $inbox) {}

            public function connect(): void {}

            public function disconnect(): void {}

            public function getFolder(string $name): ?object
            {
                return $name === 'INBOX' ? $this->inbox : null;
            }
        };

        $syncService = new class($this->app->make(EmailClassifierService::class), $fakeClient) extends MailSyncService
        {
            public function __construct(
                EmailClassifierService $classifier,
                private object $fakeClient
            ) {
                parent::__construct($classifier);
            }

            public function createImapClient(MailAccount $account): object
            {
                return $this->fakeClient;
            }
        };

        $result = $syncService->syncAccount($account);

        $this->assertSame(['found' => 2, 'imported' => 1, 'rejected' => 1, 'failed' => 0], $result);
        $this->assertSame(105, $account->fresh()->last_message_uid);
        $this->assertSame(1, MailSyncLog::where('email_account_id', $account->id)->where('folder', 'INBOX')->count());
    }

    public function test_real_webklex_message_preserves_headers_date_and_cid(): void
    {
        $companyId = DB::table('companies')->insertGetId(['trade_name' => 'A']);
        $account = MailAccount::create(['company_id' => $companyId, 'name' => 'Support', 'email' => 'support@test.test']);
        MailTrustedEmail::create(['company_id' => $companyId, 'email' => 'vip@gov.sa', 'is_active' => true]);
        $raw = "From: VIP <vip@gov.sa>\r\nTo: support@test.test\r\nSubject: Real message\r\nDate: Thu, 19 Nov 2026 12:00:00 +0000\r\nMessage-ID: <real@gov.sa>\r\nIn-Reply-To: <parent@gov.sa>\r\nReferences: <root@gov.sa> <parent@gov.sa>\r\nContent-Type: text/plain; charset=UTF-8\r\n\r\nOfficial message body.";
        $message = Message::fromString($raw);
        $message->setUid(170);
        $this->assertTrue($this->service->importSingleMessage($account, $message));
        $saved = MailMessage::firstOrFail();
        $this->assertStringContainsString('real@gov.sa', $saved->message_id);
        $this->assertStringContainsString('parent@gov.sa', $saved->in_reply_to);
        $this->assertStringContainsString('root@gov.sa', $saved->references);
        $this->assertSame('2026-11-19 12:00:00', $saved->received_at->format('Y-m-d H:i:s'));
        $this->assertSame('vip@gov.sa', $saved->sender_email);
        $this->assertSame(170, $message->getUid());
    }

    public function test_inbox_cursor_does_not_skip_failed_uid_and_resumes_on_next_sync(): void
    {
        $companyId = DB::table('companies')->insertGetId(['trade_name' => 'A']);
        $account = MailAccount::create([
            'company_id' => $companyId,
            'name' => 'Support',
            'email' => 'support@test.test',
            'host' => 'mail.test.test',
            'last_message_uid' => 100,
        ]);

        MailTrustedEmail::create(['company_id' => $companyId, 'email' => 'vip@gov.sa', 'is_active' => true]);

        $makeFakeMessage = function (int $uid, string $msgId, string $subject): object {
            return new class($uid, $msgId, $subject)
            {
                public function __construct(private int $uid, private string $msgId, private string $subject) {}

                public function getUid(): int
                {
                    return $this->uid;
                }

                public function getMessageId(): string
                {
                    return $this->msgId;
                }

                public function getSubject(): string
                {
                    return $this->subject;
                }

                public function getFrom(): array
                {
                    return [(object) ['mail' => 'vip@gov.sa', 'personal' => 'VIP']];
                }

                public function getTextBody(): string
                {
                    return 'Body '.$this->uid;
                }

                public function getHTMLBody(): string
                {
                    return '';
                }

                public function getDate(): string
                {
                    return '2026-11-21 10:00:0'.($this->uid % 10);
                }
            };
        };

        $messages = [
            $makeFakeMessage(101, '<u101@gov.sa>', 'First'),
            $makeFakeMessage(102, '<u102@gov.sa>', 'Second (fails first)'),
            $makeFakeMessage(103, '<u103@gov.sa>', 'Third'),
        ];

        $shouldFail102 = true;

        $inboxFolder = new class($messages)
        {
            public function __construct(private array $msgs) {}

            public function query(): object
            {
                $msgs = $this->msgs;

                return new class($msgs)
                {
                    public function __construct(private array $msgs) {}

                    public function leaveUnread(): self
                    {
                        return $this;
                    }

                    public function limit(int $limit): self
                    {
                        return $this;
                    }

                    public function getByUidGreater(int $uid): array
                    {
                        return array_values(array_filter($this->msgs, fn ($m) => $m->getUid() > $uid));
                    }
                };
            }
        };

        $fakeClient = new class($inboxFolder)
        {
            public function __construct(private object $inbox) {}

            public function connect(): void {}

            public function disconnect(): void {}

            public function getFolder(string $name): ?object
            {
                return $name === 'INBOX' ? $this->inbox : null;
            }
        };

        $syncService = new class($this->app->make(EmailClassifierService::class), $fakeClient, $shouldFail102) extends MailSyncService
        {
            public function __construct(
                EmailClassifierService $classifier,
                private object $fakeClient,
                public bool &$shouldFail102
            ) {
                parent::__construct($classifier);
            }

            public function createImapClient(MailAccount $account): object
            {
                return $this->fakeClient;
            }

            protected function extractMessageData(mixed $message): array
            {
                if ($this->shouldFail102 && is_object($message) && $message->getUid() === 102) {
                    throw new \RuntimeException('Transient parse failure on UID 102');
                }

                return parent::extractMessageData($message);
            }
        };

        $firstRun = $syncService->syncAccount($account);
        $this->assertSame(['found' => 3, 'imported' => 2, 'rejected' => 0, 'failed' => 1], $firstRun);
        // Cursor must stop at 101 (before failed 102) and NOT advance to 103
        $this->assertSame(101, $account->fresh()->last_message_uid);
        $this->assertSame(MailSyncStatus::PARTIAL, MailSyncLog::where('email_account_id', $account->id)->latest('id')->first()->status);

        // Next sync: 102 recovers, 103 is deduplicated, cursor advances to 103
        $shouldFail102 = false;
        $secondRun = $syncService->syncAccount($account->fresh());
        $this->assertSame(['found' => 2, 'imported' => 1, 'rejected' => 0, 'failed' => 0], $secondRun);
        $this->assertSame(103, $account->fresh()->last_message_uid);
        $this->assertSame(3, MailMessage::where('email_account_id', $account->id)->count());
    }

    public function test_spam_folder_uses_independent_uid_cursor_and_uidvalidity(): void
    {
        $companyId = DB::table('companies')->insertGetId(['trade_name' => 'A']);
        $account = MailAccount::create([
            'company_id' => $companyId,
            'name' => 'Support',
            'email' => 'support@test.test',
            'host' => 'mail.test.test',
            'last_message_uid' => 500,
        ]);

        MailTrustedEmail::create(['company_id' => $companyId, 'email' => 'vip@gov.sa', 'is_active' => true]);

        $spamMessages = [];
        for ($uid = 1; $uid <= 25; $uid++) {
            $spamMessages[] = new class($uid)
            {
                public function __construct(private int $uid) {}

                public function getUid(): int
                {
                    return $this->uid;
                }

                public function getMessageId(): string
                {
                    return "<spam-{$this->uid}@test.test>";
                }

                public function getSubject(): string
                {
                    return "Spam {$this->uid}";
                }

                public function getFrom(): array
                {
                    return [(object) ['mail' => 'vip@gov.sa', 'personal' => 'VIP']];
                }

                public function getTextBody(): string
                {
                    return "Body {$this->uid}";
                }

                public function getHTMLBody(): string
                {
                    return '';
                }

                public function getDate(): string
                {
                    return '2026-11-21 11:00:00';
                }
            };
        }

        $uidValidity = 111;

        $spamFolder = new class($spamMessages, $uidValidity)
        {
            public function __construct(private array $msgs, public int &$uidValidity) {}

            public function status(): array
            {
                return ['uidvalidity' => $this->uidValidity];
            }

            public function query(): object
            {
                $msgs = $this->msgs;

                return new class($msgs)
                {
                    private ?int $limitValue = null;

                    public function __construct(private array $msgs) {}

                    public function all(): self
                    {
                        return $this;
                    }

                    public function leaveUnread(): self
                    {
                        return $this;
                    }

                    public function limit(int $limit): self
                    {
                        $this->limitValue = $limit;

                        return $this;
                    }

                    public function get(): array
                    {
                        return $this->limitValue ? array_slice($this->msgs, 0, $this->limitValue) : $this->msgs;
                    }

                    public function getByUidGreater(int $uid): array
                    {
                        $filtered = array_values(array_filter($this->msgs, fn ($m) => $m->getUid() > $uid));

                        return $this->limitValue ? array_slice($filtered, 0, $this->limitValue) : $filtered;
                    }
                };
            }
        };

        $fakeClient = new class($spamFolder)
        {
            public function __construct(private object $spam) {}

            public function connect(): void {}

            public function disconnect(): void {}

            public function getFolder(string $name): ?object
            {
                return $name === 'Junk' ? $this->spam : null;
            }
        };

        $syncService = new class($this->app->make(EmailClassifierService::class), $fakeClient) extends MailSyncService
        {
            public function __construct(EmailClassifierService $classifier, private object $fakeClient)
            {
                parent::__construct($classifier);
            }

            public function createImapClient(MailAccount $account): object
            {
                return $this->fakeClient;
            }
        };

        // First sync imports first 20 spam messages
        $first = $syncService->syncAccount($account);
        $this->assertSame(['found' => 20, 'imported' => 20, 'rejected' => 0, 'failed' => 0], $first);
        $this->assertSame(20, $account->fresh()->last_spam_message_uid);
        $this->assertSame(111, $account->fresh()->spam_uid_validity);
        $this->assertSame(500, $account->fresh()->last_message_uid);

        // Second sync reaches messages 21..25
        $second = $syncService->syncAccount($account->fresh());
        $this->assertSame(['found' => 5, 'imported' => 5, 'rejected' => 0, 'failed' => 0], $second);
        $this->assertSame(25, $account->fresh()->last_spam_message_uid);
        $this->assertSame(25, MailMessage::where('email_account_id', $account->id)->count());

        // If UIDVALIDITY changes, cursor resets without skipping
        $uidValidity = 222;
        $syncService->syncAccount($account->fresh());
        $this->assertSame(222, $account->fresh()->spam_uid_validity);
        $this->assertSame(20, $account->fresh()->last_spam_message_uid);
    }

    public function test_imap_config_enforces_cert_validation_and_preserves_custom_password(): void
    {
        $companyId = DB::table('companies')->insertGetId(['trade_name' => 'A']);

        $customAccount = MailAccount::create([
            'company_id' => $companyId,
            'name' => 'Custom',
            'email' => 'info@company.sa',
            'host' => 'mail.company.sa',
            'password' => 'abc def ',
        ]);

        $gmailAccount = MailAccount::create([
            'company_id' => $companyId,
            'name' => 'Gmail',
            'email' => 'team@gmail.com',
            'host' => 'imap.gmail.com',
            'password' => 'abcd efgh ijkl mnop',
        ]);

        $customConfig = $this->service->buildImapConfig($customAccount);
        $gmailConfig = $this->service->buildImapConfig($gmailAccount);

        $this->assertTrue($customConfig['validate_cert']);
        $this->assertSame('abc def ', $customConfig['password']);
        $this->assertTrue($gmailConfig['validate_cert']);
        $this->assertSame('abcdefghijklmnop', $gmailConfig['password']);
    }

    public function test_attachment_failure_rolls_back_message_cleans_files_and_marks_sync_log_partial(): void
    {
        $companyId = DB::table('companies')->insertGetId(['trade_name' => 'A']);
        $account = MailAccount::create([
            'company_id' => $companyId,
            'name' => 'Support',
            'email' => 'support@test.test',
            'host' => 'mail.test.test',
            'last_message_uid' => 10,
        ]);

        MailTrustedEmail::create(['company_id' => $companyId, 'email' => 'vip@gov.sa', 'is_active' => true]);

        $shouldFailAttachment = true;

        $msg11 = new class
        {
            public function getUid(): int
            {
                return 11;
            }

            public function getMessageId(): string
            {
                return '<u11@gov.sa>';
            }

            public function getSubject(): string
            {
                return 'OK Message';
            }

            public function getFrom(): array
            {
                return [(object) ['mail' => 'vip@gov.sa', 'personal' => 'VIP']];
            }

            public function getTextBody(): string
            {
                return 'Body 11';
            }

            public function getHTMLBody(): string
            {
                return '';
            }

            public function getDate(): string
            {
                return '2026-11-21 12:00:00';
            }
        };

        $msg12 = new class($shouldFailAttachment)
        {
            public function __construct(public bool &$shouldFailAttachment) {}

            public function getUid(): int
            {
                return 12;
            }

            public function getMessageId(): string
            {
                return '<u12@gov.sa>';
            }

            public function getSubject(): string
            {
                return 'Attachment Message';
            }

            public function getFrom(): array
            {
                return [(object) ['mail' => 'vip@gov.sa', 'personal' => 'VIP']];
            }

            public function getTextBody(): string
            {
                return 'Body 12';
            }

            public function getHTMLBody(): string
            {
                return '';
            }

            public function getDate(): string
            {
                return '2026-11-21 12:05:00';
            }

            public function getAttachments(): array
            {
                $att1 = new class
                {
                    public function getName(): string
                    {
                        return 'first.pdf';
                    }

                    public function getContent(): string
                    {
                        return 'PDF1';
                    }

                    public function getMimeType(): string
                    {
                        return 'application/pdf';
                    }

                    public function getSize(): int
                    {
                        return 4;
                    }
                };

                $failFlag = &$this->shouldFailAttachment;
                $att2 = new class($failFlag)
                {
                    public function __construct(private bool &$failFlag) {}

                    public function getName(): string
                    {
                        return 'second.pdf';
                    }

                    public function getContent(): string
                    {
                        if ($this->failFlag) {
                            throw new \RuntimeException('Attachment stream failed');
                        }

                        return 'PDF2';
                    }

                    public function getMimeType(): string
                    {
                        return 'application/pdf';
                    }

                    public function getSize(): int
                    {
                        return 4;
                    }
                };

                return [$att1, $att2];
            }
        };

        $inboxFolder = new class([$msg11, $msg12])
        {
            public function __construct(private array $msgs) {}

            public function query(): object
            {
                $msgs = $this->msgs;

                return new class($msgs)
                {
                    public function __construct(private array $msgs) {}

                    public function leaveUnread(): self
                    {
                        return $this;
                    }

                    public function limit(int $limit): self
                    {
                        return $this;
                    }

                    public function getByUidGreater(int $uid): array
                    {
                        return array_values(array_filter($this->msgs, fn ($m) => $m->getUid() > $uid));
                    }
                };
            }
        };

        $fakeClient = new class($inboxFolder)
        {
            public function __construct(private object $inbox) {}

            public function connect(): void {}

            public function disconnect(): void {}

            public function getFolder(string $name): ?object
            {
                return $name === 'INBOX' ? $this->inbox : null;
            }
        };

        $syncService = new class($this->app->make(EmailClassifierService::class), $fakeClient) extends MailSyncService
        {
            public function __construct(EmailClassifierService $classifier, private object $fakeClient)
            {
                parent::__construct($classifier);
            }

            public function createImapClient(MailAccount $account): object
            {
                return $this->fakeClient;
            }
        };

        // First run: msg11 succeeds, msg12 fails on second attachment -> rolled back completely
        $first = $syncService->syncAccount($account);
        $this->assertSame(['found' => 2, 'imported' => 1, 'rejected' => 0, 'failed' => 1], $first);
        $this->assertSame(11, $account->fresh()->last_message_uid);
        $this->assertSame(1, MailMessage::count());
        $this->assertSame(0, MailAttachment::count());
        $this->assertSame([], Storage::disk(MailSyncService::ATTACHMENT_DISK)->allFiles('mail-attachments'));

        $firstLog = MailSyncLog::where('email_account_id', $account->id)->latest('id')->first();
        $this->assertSame(MailSyncStatus::PARTIAL, $firstLog->status);
        $this->assertNotNull($firstLog->error_message);

        // Second run: attachment succeeds -> msg12 and both attachments are saved, log is SUCCESS
        $shouldFailAttachment = false;
        $second = $syncService->syncAccount($account->fresh());
        $this->assertSame(['found' => 1, 'imported' => 1, 'rejected' => 0, 'failed' => 0], $second);
        $this->assertSame(12, $account->fresh()->last_message_uid);
        $this->assertSame(2, MailMessage::count());
        $this->assertSame(2, MailAttachment::count());
        $this->assertCount(2, Storage::disk(MailSyncService::ATTACHMENT_DISK)->allFiles('mail-attachments'));

        $secondLog = MailSyncLog::where('email_account_id', $account->id)->latest('id')->first();
        $this->assertSame(MailSyncStatus::SUCCESS, $secondLog->status);
        $this->assertNull($secondLog->error_message);
    }
}
