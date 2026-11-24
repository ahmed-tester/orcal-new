<?php

namespace Tests\Feature\Mail;

use App\Enums\Mail\MailMessageStatus;
use App\Enums\Mail\MailPriority;
use App\Enums\Mail\MailSourceFolder;
use App\Enums\UserType;
use App\Models\Mail\MailAccount;
use App\Models\Mail\MailCategory;
use App\Models\Mail\MailMessage;
use App\Models\User;
use App\Services\Mail\EmailClassifierService;
use App\Services\Mail\MailAccessService;
use App\Services\Mail\MailCategoryService;
use App\Services\Mail\MailMessageService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class MailCategoryAndMoveTest extends TestCase
{
    protected MailCategoryService $categoryService;

    protected MailMessageService $messageService;

    protected User $superAdmin;

    protected User $reviewer;

    protected User $plainUser;

    protected int $companyId;

    protected MailAccount $account;

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
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
        });
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
        });
        Schema::create('model_has_roles', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
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

        (require base_path('database/migrations/2026_11_19_130000_create_mail_inbox_tables.php'))->up();
        (require base_path('database/migrations/2026_11_21_000000_add_folder_uid_cursors_to_email_accounts_table.php'))->up();
        (require base_path('database/migrations/2026_11_22_140000_create_mail_categories_table.php'))->up();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        MailCategoryService::clearCache();

        foreach ([MailAccessService::PERM_VIEW_INBOX, MailAccessService::PERM_REVIEW] as $perm) {
            Permission::create(['name' => $perm, 'guard_name' => 'web']);
        }

        $this->companyId = DB::table('companies')->insertGetId(['trade_name' => 'Orcal Co']);

        $this->superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'status' => 'active',
            'user_type' => UserType::SUPER_ADMIN->value,
        ]);

        $this->reviewer = User::create([
            'name' => 'Reviewer',
            'email' => 'reviewer@example.com',
            'password' => bcrypt('password'),
            'status' => 'active',
            'user_type' => UserType::SAUDI_EMPLOYEE->value,
            'company_id' => $this->companyId,
        ]);
        $this->reviewer->givePermissionTo([MailAccessService::PERM_VIEW_INBOX, MailAccessService::PERM_REVIEW]);

        $this->plainUser = User::create([
            'name' => 'Reader',
            'email' => 'reader@example.com',
            'password' => bcrypt('password'),
            'status' => 'active',
            'user_type' => UserType::SAUDI_EMPLOYEE->value,
            'company_id' => $this->companyId,
        ]);
        $this->plainUser->givePermissionTo([MailAccessService::PERM_VIEW_INBOX]);

        $reviewerEmpId = DB::table('employees')->insertGetId([
            'company_id' => $this->companyId,
            'user_id' => $this->reviewer->id,
            'status' => 'active',
        ]);
        $readerEmpId = DB::table('employees')->insertGetId([
            'company_id' => $this->companyId,
            'user_id' => $this->plainUser->id,
            'status' => 'active',
        ]);

        $this->account = MailAccount::create([
            'company_id' => $this->companyId,
            'name' => 'Support',
            'email' => 'support@example.com',
            'account_type' => 'imap',
            'imap_host' => 'imap.example.com',
            'imap_port' => 993,
            'imap_encryption' => 'ssl',
            'imap_username' => 'support@example.com',
            'imap_password' => 'secret',
            'is_active' => true,
        ]);

        DB::table('email_account_employees')->insert([
            ['company_id' => $this->companyId, 'email_account_id' => $this->account->id, 'employee_id' => $reviewerEmpId],
            ['company_id' => $this->companyId, 'email_account_id' => $this->account->id, 'employee_id' => $readerEmpId],
        ]);

        $this->categoryService = app(MailCategoryService::class);
        $this->messageService = app(MailMessageService::class);
    }

    protected function makeMessage(array $overrides = []): MailMessage
    {
        return MailMessage::create(array_merge([
            'company_id' => $this->companyId,
            'email_account_id' => $this->account->id,
            'sender_email' => 'client@example.com',
            'recipient_email' => 'support@example.com',
            'subject' => 'Test Subject',
            'received_at' => now(),
            'source_folder' => MailSourceFolder::INBOX,
            'is_important' => false,
            'category' => 'inquiry',
            'priority' => MailPriority::MEDIUM,
            'status' => MailMessageStatus::NEW,
        ], $overrides));
    }

    public function test_category_policy_and_protections(): void
    {
        $this->assertTrue(Gate::forUser($this->superAdmin)->allows('viewAny', MailCategory::class));
        $this->assertFalse(Gate::forUser($this->reviewer)->allows('viewAny', MailCategory::class));

        $custom = MailCategory::create([
            'slug' => 'billing',
            'name_ar' => 'فواتير',
            'name_en' => 'Billing',
            'color' => 'info',
            'chart_color' => '#3b82f6',
        ]);

        $custom->update(['slug' => 'changed_slug', 'name_ar' => 'الفواتير']);
        $this->assertSame('billing', $custom->fresh()->slug);

        $other = MailCategory::where('slug', 'other')->firstOrFail();
        $this->expectException(ValidationException::class);
        $other->update(['is_active' => false]);
    }

    public function test_inactive_category_is_preserved_on_existing_message_but_forbidden_for_new_assignment(): void
    {
        $billing = MailCategory::create([
            'slug' => 'billing',
            'name_ar' => 'فواتير',
            'name_en' => 'Billing',
            'color' => 'info',
            'chart_color' => '#3b82f6',
            'is_active' => true,
        ]);

        $msg = $this->makeMessage(['category' => 'billing']);
        $billing->update(['is_active' => false]);

        $this->messageService->updateReview($msg, [
            'status' => MailMessageStatus::FOLLOWING_UP->value,
            'priority' => MailPriority::HIGH->value,
            'category' => 'billing',
            'is_important' => true,
            'assigned_to' => null,
        ], $this->reviewer);

        $this->assertSame('billing', $msg->fresh()->category);
        $this->assertSame(MailPriority::HIGH, $msg->fresh()->priority);

        $otherMsg = $this->makeMessage(['category' => 'inquiry']);
        $this->expectException(ValidationException::class);
        $this->messageService->updateReview($otherMsg, [
            'status' => MailMessageStatus::NEW->value,
            'priority' => MailPriority::MEDIUM->value,
            'category' => 'billing',
            'is_important' => false,
            'assigned_to' => null,
        ], $this->reviewer);
    }

    public function test_tabs_are_mutually_exclusive_and_all_includes_everything(): void
    {
        $important = $this->makeMessage(['is_important' => true, 'status' => MailMessageStatus::NEW, 'category' => 'inquiry']);
        $review = $this->makeMessage(['is_important' => false, 'status' => MailMessageStatus::UNDER_REVIEW, 'category' => 'request']);
        $spamReview = $this->makeMessage(['is_important' => true, 'status' => MailMessageStatus::UNDER_REVIEW, 'category' => 'spam']);
        $normal = $this->makeMessage(['is_important' => false, 'status' => MailMessageStatus::NEW, 'category' => 'official']);

        $importantIds = $this->messageService->applyTabScope(MailMessage::query(), MailMessageService::TAB_IMPORTANT)->pluck('id')->all();
        $reviewIds = $this->messageService->applyTabScope(MailMessage::query(), MailMessageService::TAB_UNDER_REVIEW)->pluck('id')->all();
        $excludedIds = $this->messageService->applyTabScope(MailMessage::query(), MailMessageService::TAB_SPAM_EXCLUDED)->pluck('id')->all();
        $allIds = $this->messageService->applyTabScope(MailMessage::query(), 'all')->pluck('id')->all();

        $this->assertSame([$important->id], $importantIds);
        $this->assertSame([$review->id], $reviewIds);
        $this->assertSame([$spamReview->id], $excludedIds);
        $this->assertCount(4, $allIds);
        $this->assertContains($normal->id, $allIds);
    }

    public function test_move_message_transitions_and_preserves_replied_status(): void
    {
        $spam = $this->makeMessage(['status' => MailMessageStatus::REJECTED, 'category' => 'spam', 'is_important' => false]);

        // Moving spam to important without replacement category must fail
        try {
            $this->messageService->moveMessage($spam, MailMessageService::TAB_IMPORTANT, $this->reviewer);
            $this->fail('Expected ValidationException when moving excluded category without replacement');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('category', $e->errors());
        }

        // With replacement category, moves cleanly to important
        $this->messageService->moveMessage($spam, MailMessageService::TAB_IMPORTANT, $this->reviewer, 'official');
        $spam->refresh();
        $this->assertTrue($spam->is_important);
        $this->assertSame(MailMessageStatus::NEW, $spam->status);
        $this->assertSame('official', $spam->category);

        // Moving replied message to important/normal preserves REPLIED status
        $replied = $this->makeMessage([
            'status' => MailMessageStatus::REPLIED,
            'is_important' => false,
            'replied_at' => now(),
        ]);
        $this->messageService->moveMessage($replied, MailMessageService::TAB_IMPORTANT, $this->reviewer);
        $this->assertSame(MailMessageStatus::REPLIED, $replied->fresh()->status);
        $this->assertTrue($replied->fresh()->is_important);

        // Changing category of under_review message to excluded moves it to rejected
        $review = $this->makeMessage(['status' => MailMessageStatus::UNDER_REVIEW, 'category' => 'inquiry']);
        $this->messageService->moveMessage($review, MailMessageService::ACTION_CHANGE_CATEGORY, $this->reviewer, 'marketing');
        $this->assertSame(MailMessageStatus::REJECTED, $review->fresh()->status);
        $this->assertFalse($review->fresh()->is_important);
    }

    public function test_bulk_move_is_atomic_and_enforces_permissions(): void
    {
        $m1 = $this->makeMessage(['category' => 'inquiry', 'status' => MailMessageStatus::NEW]);
        $m2 = $this->makeMessage(['category' => 'spam', 'status' => MailMessageStatus::REJECTED]);

        // Plain user without review permission is rejected before any update
        try {
            $this->messageService->moveMessagesBulk(collect([$m1, $m2]), MailMessageService::TAB_IMPORTANT, $this->plainUser, 'official');
            $this->fail('Expected AuthorizationException');
        } catch (AuthorizationException) {
            $this->assertFalse($m1->fresh()->is_important);
        }

        // Reviewer moving mixed group without replacement category fails atomically
        try {
            $this->messageService->moveMessagesBulk(collect([$m1, $m2]), MailMessageService::TAB_IMPORTANT, $this->reviewer);
            $this->fail('Expected ValidationException');
        } catch (ValidationException) {
            $this->assertFalse($m1->fresh()->is_important);
        }

        // With replacement category, both succeed; m1 keeps its original category, m2 gets official
        $count = $this->messageService->moveMessagesBulk(collect([$m1, $m2]), MailMessageService::TAB_IMPORTANT, $this->reviewer, 'official');
        $this->assertSame(2, $count);
        $this->assertTrue($m1->fresh()->is_important);
        $this->assertSame('inquiry', $m1->fresh()->category);
        $this->assertTrue($m2->fresh()->is_important);
        $this->assertSame('official', $m2->fresh()->category);
    }

    public function test_approve_review_on_excluded_category_requires_and_applies_replacement(): void
    {
        $msg = $this->makeMessage([
            'status' => MailMessageStatus::UNDER_REVIEW,
            'category' => 'spam',
            'is_important' => false,
        ]);

        try {
            $this->messageService->approveReview($msg, $this->reviewer);
            $this->fail('Expected ValidationException when approving excluded category without replacement');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('category', $e->errors());
        }

        $this->messageService->approveReview($msg, $this->reviewer, 'official');
        $msg->refresh();
        $this->assertSame(MailMessageStatus::NEW, $msg->status);
        $this->assertTrue($msg->is_important);
        $this->assertSame(MailPriority::HIGH, $msg->priority);
        $this->assertSame('official', $msg->category);
    }

    public function test_update_review_detects_concurrent_modification(): void
    {
        $msg = $this->makeMessage();
        $staleToken = MailMessageService::concurrencyToken($msg);

        $msg->update(['priority' => MailPriority::HIGH]);

        $this->expectException(ValidationException::class);
        $this->messageService->updateReview($msg, [
            'status' => MailMessageStatus::NEW->value,
            'priority' => MailPriority::LOW->value,
            'category' => 'inquiry',
            'is_important' => false,
            'assigned_to' => null,
        ], $this->reviewer, $staleToken);
    }

    public function test_gemini_classifier_uses_dynamic_categories_and_invalidates_cache_on_change(): void
    {
        DB::table('settings')->insert(['key' => 'gemini_api_key', 'value' => 'test-key']);
        Cache::flush();

        MailCategory::create([
            'slug' => 'vip_support',
            'name_ar' => 'دعم خاص',
            'name_en' => 'VIP Support',
            'color' => 'success',
            'chart_color' => '#22c55e',
            'is_active' => true,
        ]);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::sequence()
                ->push([
                    'candidates' => [[
                        'content' => [
                            'parts' => [[
                                'text' => json_encode([
                                    'is_important' => true,
                                    'category' => 'دعم خاص',
                                    'confidence' => 92,
                                    'summary' => 'طلب دعم خاص',
                                    'suggested_priority' => 'عالية',
                                ]),
                            ]],
                        ],
                    ]],
                ], 200)
                ->push([
                    'candidates' => [[
                        'content' => [
                            'parts' => [[
                                'text' => json_encode([
                                    'is_important' => true,
                                    'category' => 'استفسار',
                                    'confidence' => 90,
                                    'summary' => 'استفسار عام',
                                    'suggested_priority' => 'متوسطة',
                                ]),
                            ]],
                        ],
                    ]],
                ], 200),
        ]);

        $classifier = app(EmailClassifierService::class);
        $v1 = $this->categoryService->geminiVersion();

        $result = $classifier->classify('vip@client.com', 'VIP issue', 'Need help');
        $this->assertSame('vip_support', $result['category']);
        $this->assertSame(MailMessageStatus::NEW, $result['status']);

        MailCategory::where('slug', 'vip_support')->firstOrFail()->update(['is_active' => false]);
        $v2 = $this->categoryService->geminiVersion();
        $this->assertNotSame($v1, $v2);

        $secondResult = $classifier->classify('vip@client.com', 'VIP issue', 'Need help');
        $this->assertSame('inquiry', $secondResult['category']);
        Http::assertSentCount(2);
    }

    public function test_disabled_category_routes_classifier_rules_to_other_and_under_review(): void
    {
        DB::table('exclusion_keywords')->insert([
            'company_id' => null,
            'keyword' => 'unsubscribe',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        MailCategory::where('slug', 'marketing')->firstOrFail()->update(['is_active' => false]);

        $result = app(EmailClassifierService::class)->classify('promo@example.com', 'Weekly news', 'Click unsubscribe');
        $this->assertSame('other', $result['category']);
        $this->assertSame(MailMessageStatus::UNDER_REVIEW, $result['status']);
        $this->assertFalse($result['is_important']);
    }
}
