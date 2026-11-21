<?php

namespace Tests\Feature\Mail;

use App\Enums\Mail\MailCategory;
use App\Enums\Mail\MailMessageStatus;
use App\Enums\Mail\MailPriority;
use App\Enums\Mail\MailSourceFolder;
use App\Enums\UserType;
use App\Models\Mail\MailAccount;
use App\Models\Mail\MailMessage;
use App\Models\User;
use App\Notifications\BaseSystemNotification;
use App\Services\Mail\MailAccessService;
use App\Services\Mail\MailNotificationService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class MailEscalationAndNotificationTest extends TestCase
{
    protected MailNotificationService $service;

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

        $migration = require base_path('database/migrations/2026_11_19_130000_create_mail_inbox_tables.php');
        $migration->up();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Permission::create(['name' => MailAccessService::PERM_VIEW_INBOX, 'guard_name' => 'web']);
        Permission::create(['name' => MailAccessService::PERM_MANAGE_ACCOUNTS, 'guard_name' => 'web']);
        Role::create(['name' => 'Super Admin', 'guard_name' => 'web']);

        $this->service = app(MailNotificationService::class);
    }

    public function test_new_email_notifies_assigned_employees_and_super_admin_only(): void
    {
        Notification::fake();

        $companyId = DB::table('companies')->insertGetId(['trade_name' => 'Orcal', 'created_at' => now(), 'updated_at' => now()]);

        $superAdmin = User::create([
            'name' => 'Super',
            'email' => 'super@orcal.test',
            'password' => bcrypt('secret'),
            'status' => 'active',
            'user_type' => UserType::SUPER_ADMIN,
        ]);
        $superAdmin->assignRole('Super Admin');

        $assigned = User::create([
            'name' => 'Assigned',
            'email' => 'assigned@orcal.test',
            'password' => bcrypt('secret'),
            'status' => 'active',
            'company_id' => $companyId,
        ]);
        $assigned->givePermissionTo(MailAccessService::PERM_VIEW_INBOX);

        $unassigned = User::create([
            'name' => 'Unassigned',
            'email' => 'unassigned@orcal.test',
            'password' => bcrypt('secret'),
            'status' => 'active',
            'company_id' => $companyId,
        ]);
        $unassigned->givePermissionTo(MailAccessService::PERM_VIEW_INBOX);

        $empId = DB::table('employees')->insertGetId([
            'company_id' => $companyId,
            'user_id' => $assigned->id,
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
        $account->employees()->attach($empId, ['company_id' => $companyId]);

        $message = MailMessage::create([
            'company_id' => $companyId,
            'email_account_id' => $account->id,
            'sender_email' => 'vip@gov.sa',
            'recipient_email' => 'support@orcal.test',
            'subject' => 'Urgent Notice',
            'received_at' => now(),
            'source_folder' => MailSourceFolder::INBOX,
            'is_important' => true,
            'category' => MailCategory::OFFICIAL,
            'priority' => MailPriority::HIGH,
            'status' => MailMessageStatus::NEW,
        ]);

        $count = $this->service->sendNewEmailNotification($message);

        $this->assertSame(2, $count);
        Notification::assertSentTo($superAdmin, BaseSystemNotification::class);
        Notification::assertSentTo($assigned, BaseSystemNotification::class);
        Notification::assertNotSentTo($unassigned, BaseSystemNotification::class);
    }

    public function test_escalation_targets_overdue_high_priority_important_emails_once_and_notifies_super_admin(): void
    {
        Notification::fake();

        $companyId = DB::table('companies')->insertGetId(['trade_name' => 'Orcal', 'created_at' => now(), 'updated_at' => now()]);

        $superAdmin = User::create([
            'name' => 'Super',
            'email' => 'super@orcal.test',
            'password' => bcrypt('secret'),
            'status' => 'active',
            'user_type' => UserType::SUPER_ADMIN,
        ]);
        $superAdmin->assignRole('Super Admin');

        $regularAgent = User::create([
            'name' => 'Agent',
            'email' => 'agent@orcal.test',
            'password' => bcrypt('secret'),
            'status' => 'active',
            'company_id' => $companyId,
        ]);
        $regularAgent->givePermissionTo(MailAccessService::PERM_VIEW_INBOX);

        $empId = DB::table('employees')->insertGetId([
            'company_id' => $companyId,
            'user_id' => $regularAgent->id,
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
        $account->employees()->attach($empId, ['company_id' => $companyId]);

        // Eligible: high priority, important, unreplied, older than 4 hours
        $overdue = MailMessage::create([
            'company_id' => $companyId,
            'email_account_id' => $account->id,
            'sender_email' => 'vip@gov.sa',
            'recipient_email' => 'support@orcal.test',
            'subject' => 'Overdue High Priority',
            'received_at' => now()->subHours(5),
            'source_folder' => MailSourceFolder::INBOX,
            'is_important' => true,
            'category' => MailCategory::OFFICIAL,
            'priority' => MailPriority::HIGH,
            'status' => MailMessageStatus::NEW,
        ]);

        // Not eligible: recent (< 4 hours)
        MailMessage::create([
            'company_id' => $companyId,
            'email_account_id' => $account->id,
            'sender_email' => 'vip@gov.sa',
            'recipient_email' => 'support@orcal.test',
            'subject' => 'Recent High Priority',
            'received_at' => now()->subHour(),
            'source_folder' => MailSourceFolder::INBOX,
            'is_important' => true,
            'category' => MailCategory::OFFICIAL,
            'priority' => MailPriority::HIGH,
            'status' => MailMessageStatus::NEW,
        ]);

        // Not eligible: already replied
        MailMessage::create([
            'company_id' => $companyId,
            'email_account_id' => $account->id,
            'sender_email' => 'vip@gov.sa',
            'recipient_email' => 'support@orcal.test',
            'subject' => 'Already Replied',
            'received_at' => now()->subHours(6),
            'replied_at' => now()->subHours(5),
            'source_folder' => MailSourceFolder::INBOX,
            'is_important' => true,
            'category' => MailCategory::OFFICIAL,
            'priority' => MailPriority::HIGH,
            'status' => MailMessageStatus::REPLIED,
        ]);

        $escalatedCount = $this->service->checkEscalations();

        $this->assertSame(1, $escalatedCount);
        $this->assertNotNull($overdue->fresh()->escalated_at);
        Notification::assertSentTo($superAdmin, BaseSystemNotification::class);
        Notification::assertNotSentTo($regularAgent, BaseSystemNotification::class);

        // Second run must exclude already escalated email
        $this->assertSame(0, $this->service->checkEscalations());
    }

    public function test_sync_error_notifies_super_admin_only(): void
    {
        Notification::fake();

        $companyId = DB::table('companies')->insertGetId(['trade_name' => 'Orcal', 'created_at' => now(), 'updated_at' => now()]);

        $superAdmin = User::create([
            'name' => 'Super',
            'email' => 'super@orcal.test',
            'password' => bcrypt('secret'),
            'status' => 'active',
            'user_type' => UserType::SUPER_ADMIN,
        ]);
        $superAdmin->assignRole('Super Admin');

        $regularAgent = User::create([
            'name' => 'Agent',
            'email' => 'agent@orcal.test',
            'password' => bcrypt('secret'),
            'status' => 'active',
            'company_id' => $companyId,
        ]);
        $regularAgent->givePermissionTo(MailAccessService::PERM_MANAGE_ACCOUNTS);

        $account = MailAccount::create([
            'company_id' => $companyId,
            'name' => 'Support',
            'email' => 'support@orcal.test',
            'is_active' => true,
        ]);

        $count = $this->service->notifySyncError($account, 'AUTHENTICATIONFAILED');

        $this->assertSame(1, $count);
        Notification::assertSentTo($superAdmin, BaseSystemNotification::class);
        Notification::assertNotSentTo($regularAgent, BaseSystemNotification::class);
    }
}
