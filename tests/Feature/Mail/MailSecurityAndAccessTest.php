<?php

namespace Tests\Feature\Mail;

use App\Enums\Mail\MailCategory;
use App\Enums\Mail\MailMessageStatus;
use App\Enums\Mail\MailPriority;
use App\Enums\Mail\MailSourceFolder;
use App\Enums\UserType;
use App\Filament\Resources\Roles\Schemas\RoleForm;
use App\Jobs\Mail\SendReplyJob;
use App\Models\Mail\MailAccount;
use App\Models\Mail\MailAttachment;
use App\Models\Mail\MailConversation;
use App\Models\Mail\MailMessage;
use App\Models\Mail\MailTrustedEmail;
use App\Models\User;
use App\Notifications\BaseSystemNotification;
use App\Services\Mail\EmailReplyService;
use App\Services\Mail\MailAccessService;
use App\Services\Notifications\NotificationRecipientService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class MailSecurityAndAccessTest extends TestCase
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

        $migration = require base_path('database/migrations/2026_11_19_130000_create_mail_inbox_tables.php');
        $migration->up();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ([
            MailAccessService::PERM_VIEW_INBOX,
            MailAccessService::PERM_REPLY,
            MailAccessService::PERM_MANAGE_RULES,
            MailAccessService::PERM_MANAGE_ACCOUNTS,
            MailAccessService::PERM_RUN_SYNC,
            MailAccessService::PERM_REVIEW,
            MailAccessService::PERM_DELETE,
        ] as $permissionName) {
            Permission::create(['name' => $permissionName, 'guard_name' => 'web']);
        }

        Role::create(['name' => 'Super Admin', 'guard_name' => 'web']);
    }

    public function test_assigned_employee_can_view_message_attachment_and_receive_notifications_while_unassigned_is_blocked(): void
    {
        $companyA = DB::table('companies')->insertGetId(['trade_name' => 'Company A', 'created_at' => now(), 'updated_at' => now()]);
        $companyB = DB::table('companies')->insertGetId(['trade_name' => 'Company B', 'created_at' => now(), 'updated_at' => now()]);

        $superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'super@orcal.test',
            'password' => bcrypt('secret'),
            'status' => 'active',
            'user_type' => UserType::SUPER_ADMIN,
        ]);
        $superAdmin->assignRole('Super Admin');

        $assignedUser = User::create([
            'name' => 'Assigned User',
            'email' => 'assigned@orcal.test',
            'password' => bcrypt('secret'),
            'status' => 'active',
            'user_type' => UserType::SAUDI_EMPLOYEE,
            'company_id' => $companyA,
        ]);
        $assignedUser->givePermissionTo(MailAccessService::PERM_VIEW_INBOX);

        $unassignedUser = User::create([
            'name' => 'Unassigned User',
            'email' => 'unassigned@orcal.test',
            'password' => bcrypt('secret'),
            'status' => 'active',
            'user_type' => UserType::SAUDI_EMPLOYEE,
            'company_id' => $companyA,
        ]);
        $unassignedUser->givePermissionTo([MailAccessService::PERM_VIEW_INBOX, MailAccessService::PERM_REPLY]);

        $otherCompanyUser = User::create([
            'name' => 'Other Company User',
            'email' => 'other@orcal.test',
            'password' => bcrypt('secret'),
            'status' => 'active',
            'user_type' => UserType::SAUDI_EMPLOYEE,
            'company_id' => $companyB,
        ]);
        $otherCompanyUser->givePermissionTo(MailAccessService::PERM_VIEW_INBOX);

        $assignedEmployeeId = DB::table('employees')->insertGetId([
            'company_id' => $companyA,
            'user_id' => $assignedUser->id,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $account = MailAccount::create([
            'company_id' => $companyA,
            'name' => 'Support A',
            'email' => 'support@companya.test',
            'is_active' => true,
        ]);
        $account->employees()->attach($assignedEmployeeId, ['company_id' => $companyA]);

        $conversation = MailConversation::create([
            'company_id' => $companyA,
            'email_account_id' => $account->id,
            'subject' => 'Contract',
            'participant_email' => 'client@ext.test',
        ]);

        $message = MailMessage::create([
            'company_id' => $companyA,
            'email_account_id' => $account->id,
            'conversation_id' => $conversation->id,
            'sender_email' => 'client@ext.test',
            'recipient_email' => 'support@companya.test',
            'subject' => 'Contract',
            'received_at' => now(),
            'source_folder' => MailSourceFolder::INBOX,
            'category' => MailCategory::OFFICIAL,
            'priority' => MailPriority::HIGH,
            'status' => MailMessageStatus::NEW,
        ]);

        $attachment = MailAttachment::create([
            'company_id' => $companyA,
            'email_id' => $message->id,
            'file_name' => 'file.pdf',
            'file_path' => 'mail-attachments/1/file.pdf',
            'file_size' => 100,
        ]);

        // Super admin can view & reply
        $this->assertTrue(Gate::forUser($superAdmin)->allows('view', $message));
        $this->assertTrue(Gate::forUser($superAdmin)->allows('reply', $message));
        $this->assertTrue(Gate::forUser($superAdmin)->allows('view', $attachment));

        // Assigned read-only user can view message, conversation, attachment, but cannot reply or delete
        $this->assertTrue(Gate::forUser($assignedUser)->allows('view', $message));
        $this->assertTrue(Gate::forUser($assignedUser)->allows('view', $conversation));
        $this->assertTrue(Gate::forUser($assignedUser)->allows('view', $attachment));
        $this->assertFalse(Gate::forUser($assignedUser)->allows('reply', $message));
        $this->assertFalse(Gate::forUser($assignedUser)->allows('delete', $message));

        // Unassigned user in same company & user in another company are blocked
        $this->assertFalse(Gate::forUser($unassignedUser)->allows('view', $message));
        $this->assertFalse(Gate::forUser($unassignedUser)->allows('reply', $message));
        $this->assertFalse(Gate::forUser($unassignedUser)->allows('view', $attachment));
        $this->assertFalse(Gate::forUser($otherCompanyUser)->allows('view', $message));

        // Query scoping matches policy
        $this->assertSame([$message->id], MailAccessService::scopeRecords(MailMessage::query(), $assignedUser, 'emails')->pluck('id')->all());
        $this->assertSame([], MailAccessService::scopeRecords(MailMessage::query(), $unassignedUser, 'emails')->pluck('id')->all());

        // Notification recipients include super admin & read-only assigned user, exclude unassigned/other company
        $recipients = NotificationRecipientService::getQualifiedRecipients($message)->pluck('id')->all();
        $this->assertContains($superAdmin->id, $recipients);
        $this->assertContains($assignedUser->id, $recipients);
        $this->assertNotContains($unassignedUser->id, $recipients);
        $this->assertNotContains($otherCompanyUser->id, $recipients);

        // Delivery-time check in BaseSystemNotification re-verifies assignment
        $notification = new BaseSystemNotification(
            eventKey: 'general.info',
            recordType: MailMessage::class,
            recordId: $message->id,
        );
        $this->assertTrue($notification->shouldSend($assignedUser, 'database'));

        DB::table('employees')->where('id', $assignedEmployeeId)->update(['status' => 'inactive']);
        $this->assertFalse($notification->shouldSend($assignedUser, 'database'));
        DB::table('employees')->where('id', $assignedEmployeeId)->update(['status' => 'active', 'company_id' => $companyB]);
        $this->assertFalse($notification->shouldSend($assignedUser, 'database'));
        DB::table('employees')->where('id', $assignedEmployeeId)->update(['company_id' => $companyA]);
        $assignedUser->revokePermissionTo(MailAccessService::PERM_VIEW_INBOX);
        $this->assertFalse($notification->shouldSend($assignedUser, 'database'));
        $assignedUser->givePermissionTo(MailAccessService::PERM_VIEW_INBOX);
        $this->assertTrue($notification->shouldSend($assignedUser, 'database'));

        $colleague = User::create(['name' => 'Second assignee', 'email' => 'second@test.test', 'password' => bcrypt('secret'), 'status' => 'active']);
        $colleague->givePermissionTo(MailAccessService::PERM_VIEW_INBOX);
        $secondEmployeeId = DB::table('employees')->insertGetId(['company_id' => $companyA, 'user_id' => $colleague->id, 'status' => 'active']);
        $account->employees()->attach($secondEmployeeId, ['company_id' => $companyA]);
        $this->assertTrue($notification->shouldSend($colleague, 'database'));
        $assignedUser->givePermissionTo(MailAccessService::PERM_REPLY);

        // Remove assignment -> delivery-time check must block
        $account->employees()->detach($assignedEmployeeId);
        $this->assertFalse($notification->shouldSend($assignedUser, 'database'));
        $this->expectException(AuthorizationException::class);
        (new SendReplyJob($message, 'Revoked assignment', $assignedUser))->handle(app(EmailReplyService::class));
    }

    public function test_owner_cannot_bypass_mail_policies_and_roles_expose_mail_permissions(): void
    {
        $companyA = DB::table('companies')->insertGetId(['trade_name' => 'A']);
        $companyB = DB::table('companies')->insertGetId(['trade_name' => 'B']);
        $owner = User::create(['name' => 'Owner', 'email' => 'owner@test.test', 'password' => bcrypt('secret'), 'status' => 'active', 'user_type' => UserType::OWNER]);
        DB::table('owners')->insert(['user_id' => $owner->id, 'company_id' => $companyA]);
        $owner->givePermissionTo(MailAccessService::PERM_VIEW_INBOX);
        $account = MailAccount::create(['company_id' => $companyB, 'name' => 'B', 'email' => 'b@test.test']);
        $message = MailMessage::create(['company_id' => $companyB, 'email_account_id' => $account->id, 'sender_email' => 'sender@ext.test', 'recipient_email' => $account->email, 'subject' => 'Private', 'received_at' => now()]);
        $attachment = MailAttachment::create(['company_id' => $companyB, 'email_id' => $message->id, 'file_name' => 'private.pdf', 'file_path' => 'private.pdf']);
        $this->assertFalse(Gate::forUser($owner)->allows('view', $message));
        $this->assertFalse(Gate::forUser($owner)->allows('reply', $message));
        $this->assertFalse(Gate::forUser($owner)->allows('view', $attachment));
        $groups = RoleForm::getPermissionGroups();
        $this->assertCount(7, $groups['permissions_mail']['permissions']);
    }

    public function test_account_manager_scope_matches_policy_and_global_rules_are_protected(): void
    {
        $companyId = DB::table('companies')->insertGetId(['trade_name' => 'A']);
        $manager = User::create(['name' => 'Manager', 'email' => 'manager@test.test', 'password' => bcrypt('secret'), 'status' => 'active']);
        $manager->givePermissionTo([MailAccessService::PERM_MANAGE_ACCOUNTS, MailAccessService::PERM_MANAGE_RULES]);
        DB::table('employees')->insert(['company_id' => $companyId, 'user_id' => $manager->id, 'status' => 'active']);
        $account = MailAccount::create(['company_id' => $companyId, 'name' => 'A', 'email' => 'a@test.test']);
        $this->assertTrue(Gate::forUser($manager)->allows('update', $account));
        $this->assertSame([$account->id], MailAccessService::scopeAccounts(MailAccount::query(), $manager)->pluck('id')->all());
        $global = MailTrustedEmail::create(['email' => 'global@gov.sa', 'is_active' => true]);
        $this->assertFalse(Gate::forUser($manager)->allows('update', $global));
        $this->assertFalse(Gate::forUser($manager)->allows('delete', $global));
        $this->actingAs($manager);
        $this->expectException(HttpException::class);
        MailTrustedEmail::create(['email' => 'unauthorized@gov.sa', 'is_active' => true]);
    }

    public function test_rule_policy_requires_manage_mail_rules_and_company_scope(): void
    {
        $companyA = DB::table('companies')->insertGetId(['trade_name' => 'Company A', 'created_at' => now(), 'updated_at' => now()]);
        $companyB = DB::table('companies')->insertGetId(['trade_name' => 'Company B', 'created_at' => now(), 'updated_at' => now()]);

        $ruleManager = User::create([
            'name' => 'Rule Manager',
            'email' => 'rules@orcal.test',
            'password' => bcrypt('secret'),
            'status' => 'active',
            'user_type' => UserType::SAUDI_EMPLOYEE,
            'company_id' => $companyA,
        ]);
        $ruleManager->givePermissionTo(MailAccessService::PERM_MANAGE_RULES);

        DB::table('employees')->insert([
            'company_id' => $companyA,
            'user_id' => $ruleManager->id,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $ruleA = MailTrustedEmail::create(['company_id' => $companyA, 'email' => 'a@gov.sa', 'is_active' => true]);
        $ruleB = MailTrustedEmail::create(['company_id' => $companyB, 'email' => 'b@gov.sa', 'is_active' => true]);

        $this->assertTrue(Gate::forUser($ruleManager)->allows('update', $ruleA));
        $this->assertFalse(Gate::forUser($ruleManager)->allows('update', $ruleB));
    }
}
