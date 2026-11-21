<?php

namespace Tests\Feature\Mail;

use App\Enums\Mail\MailCategory;
use App\Enums\Mail\MailMessageStatus;
use App\Enums\Mail\MailPriority;
use App\Enums\Mail\MailSourceFolder;
use App\Enums\UserType;
use App\Filament\Pages\ManageSettings;
use App\Filament\Resources\Mail\MailAccounts\Pages\CreateMailAccount;
use App\Filament\Resources\Mail\MailAccounts\Pages\EditMailAccount;
use App\Filament\Resources\Mail\MailAccounts\Pages\ListMailAccounts;
use App\Filament\Resources\Mail\MailMessages\MailMessageResource;
use App\Filament\Resources\Mail\MailMessages\Pages\ViewMailMessage;
use App\Filament\Resources\Mail\MailMessages\Widgets\LatestImportantMail;
use App\Filament\Resources\Mail\MailMessages\Widgets\MailCategoriesChart;
use App\Filament\Resources\Mail\MailMessages\Widgets\MailOverview;
use App\Jobs\Mail\SyncEmailAccountJob;
use App\Models\Mail\MailAccount;
use App\Models\Mail\MailAttachment;
use App\Models\Mail\MailConversation;
use App\Models\Mail\MailMessage;
use App\Models\Mail\MailReply;
use App\Models\Setting;
use App\Models\User;
use App\Services\Mail\EmailClassifierService;
use App\Services\Mail\MailAccessService;
use App\Services\Mail\MailAccountService;
use Filament\Facades\Filament;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class MailAccountUiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();

        config(['database.default' => 'sqlite']);
        config(['database.connections.sqlite.database' => ':memory:']);
        DB::purge();
        DB::reconnect('sqlite');
        DB::statement('PRAGMA foreign_keys = ON;');

        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('trade_name')->nullable();
            $table->string('trade_name_en')->nullable();
            $table->string('company_number')->nullable();
            $table->json('metadata')->nullable();
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
            $table->string('employee_number')->nullable();
            $table->json('metadata')->nullable();
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
        Permission::create(['name' => MailAccessService::PERM_MANAGE_ACCOUNTS, 'guard_name' => 'web']);
        Permission::create(['name' => MailAccessService::PERM_VIEW_INBOX, 'guard_name' => 'web']);
        Role::create(['name' => 'Super Admin', 'guard_name' => 'web']);

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_mail_account_form_company_and_multi_employee_workflow(): void
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

        $u1 = User::create(['name' => 'Emp One', 'email' => 'e1@orcal.test', 'password' => bcrypt('secret'), 'status' => 'active']);
        $u2 = User::create(['name' => 'Emp Two', 'email' => 'e2@orcal.test', 'password' => bcrypt('secret'), 'status' => 'active']);
        $u3 = User::create(['name' => 'Emp Other', 'email' => 'e3@orcal.test', 'password' => bcrypt('secret'), 'status' => 'active']);

        $empA1 = DB::table('employees')->insertGetId(['company_id' => $companyA, 'user_id' => $u1->id, 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
        $empA2 = DB::table('employees')->insertGetId(['company_id' => $companyA, 'user_id' => $u2->id, 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
        $empB1 = DB::table('employees')->insertGetId(['company_id' => $companyB, 'user_id' => $u3->id, 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);

        $this->actingAs($superAdmin);

        // Create account via Livewire: set company, select two employees, save
        Livewire::test(CreateMailAccount::class)
            ->assertFormFieldDisabled('employees')
            ->fillForm([
                'company_id' => $companyA,
                'employees' => [$empA1, $empA2],
                'name' => 'Support A',
                'email' => 'support@companya.test',
                'host' => 'mail.companya.test',
                'port' => 993,
                'encryption' => 'ssl',
                'password' => 'app-secret',
                'sync_interval_minutes' => 1,
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $account = MailAccount::firstOrFail();
        $this->assertSame($companyA, $account->company_id);
        $this->assertEqualsCanonicalizing([$empA1, $empA2], $account->employees()->pluck('employees.id')->all());
        Queue::assertPushed(SyncEmailAccountJob::class, fn ($job) => $job->account->id === $account->id);

        // Switching company clears employees in form state
        Livewire::test(CreateMailAccount::class)
            ->fillForm([
                'company_id' => $companyA,
                'employees' => [$empA1],
            ])
            ->fillForm([
                'company_id' => $companyB,
            ])
            ->assertFormSet([
                'employees' => [],
            ]);

        // Reopening EditMailAccount hydrates both employees
        Livewire::test(EditMailAccount::class, ['record' => $account->getRouteKey()])
            ->assertFormSet(fn (array $state) => count($state['employees'] ?? []) === 2);

        MailMessage::create([
            'company_id' => $companyA, 'email_account_id' => $account->id,
            'sender_email' => 'client@outside.test', 'recipient_email' => $account->email,
            'subject' => 'Existing message', 'received_at' => now(),
        ]);
        Livewire::test(EditMailAccount::class, ['record' => $account->getRouteKey()])
            ->fillForm(['name' => 'Updated support'])
            ->call('save')
            ->assertHasNoFormErrors();
        $this->assertSame('Updated support', $account->fresh()->name);
        $this->assertSame($companyA, $account->fresh()->company_id);

        // Server-side validation rejects cross-company employee injection
        $this->expectException(ValidationException::class);
        app(MailAccountService::class)->saveAccount(null, [
            'company_id' => $companyA,
            'name' => 'Tampered',
            'email' => 'tampered@companya.test',
        ], [$empB1], $superAdmin);
    }

    public function test_navigation_badge_counts_only_unread_non_rejected_accessible_messages(): void
    {
        $companyA = DB::table('companies')->insertGetId(['trade_name' => 'Company A', 'created_at' => now(), 'updated_at' => now()]);

        $u1 = User::create(['name' => 'Emp One', 'email' => 'e1@orcal.test', 'password' => bcrypt('secret'), 'status' => 'active', 'company_id' => $companyA]);
        $u1->givePermissionTo(MailAccessService::PERM_VIEW_INBOX);

        $u2 = User::create(['name' => 'Emp Two', 'email' => 'e2@orcal.test', 'password' => bcrypt('secret'), 'status' => 'active', 'company_id' => $companyA]);
        $u2->givePermissionTo(MailAccessService::PERM_VIEW_INBOX);

        $emp1 = DB::table('employees')->insertGetId(['company_id' => $companyA, 'user_id' => $u1->id, 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);

        $account = MailAccount::create(['company_id' => $companyA, 'name' => 'Support', 'email' => 's@a.test', 'is_active' => true]);
        $account->employees()->attach($emp1, ['company_id' => $companyA]);

        // 1 unread new + 1 read new + 1 unread rejected => badge should be "1" for u1, null for u2
        MailMessage::create([
            'company_id' => $companyA,
            'email_account_id' => $account->id,
            'sender_email' => 'c1@ext.test',
            'recipient_email' => 's@a.test',
            'subject' => 'Unread New',
            'received_at' => now(),
            'source_folder' => MailSourceFolder::INBOX,
            'category' => MailCategory::INQUIRY,
            'priority' => MailPriority::MEDIUM,
            'status' => MailMessageStatus::NEW,
            'is_read' => false,
        ]);

        MailMessage::create([
            'company_id' => $companyA,
            'email_account_id' => $account->id,
            'sender_email' => 'c2@ext.test',
            'recipient_email' => 's@a.test',
            'subject' => 'Read New',
            'received_at' => now(),
            'source_folder' => MailSourceFolder::INBOX,
            'category' => MailCategory::INQUIRY,
            'priority' => MailPriority::MEDIUM,
            'status' => MailMessageStatus::NEW,
            'is_read' => true,
        ]);

        MailMessage::create([
            'company_id' => $companyA,
            'email_account_id' => $account->id,
            'sender_email' => 'c3@ext.test',
            'recipient_email' => 's@a.test',
            'subject' => 'Unread Rejected',
            'received_at' => now(),
            'source_folder' => MailSourceFolder::INBOX,
            'category' => MailCategory::MARKETING,
            'priority' => MailPriority::LOW,
            'status' => MailMessageStatus::REJECTED,
            'is_read' => false,
        ]);

        $this->actingAs($u1);
        $this->assertSame('1', MailMessageResource::getNavigationBadge());
        $importantMessage = MailMessage::where('subject', 'Unread New')->firstOrFail();
        $importantMessage->update(['is_important' => true]);
        Livewire::test(MailOverview::class)
            ->assertSee(__('mail_inbox.stats.total'));
        Livewire::test(MailCategoriesChart::class)
            ->assertSee(__('mail_inbox.stats.categories'));
        Livewire::test(LatestImportantMail::class)
            ->assertCanSeeTableRecords([$importantMessage]);

        $this->actingAs($u2);
        $this->assertNull(MailMessageResource::getNavigationBadge());
        Livewire::test(LatestImportantMail::class)
            ->assertCanNotSeeTableRecords([$importantMessage]);
    }

    public function test_conversation_displays_other_messages_and_reply_attachments(): void
    {
        $companyId = DB::table('companies')->insertGetId(['trade_name' => 'A']);
        $admin = User::create(['name' => 'Admin', 'email' => 'admin@test.test', 'password' => bcrypt('secret'), 'status' => 'active', 'user_type' => UserType::SUPER_ADMIN]);
        $account = MailAccount::create(['company_id' => $companyId, 'name' => 'Support', 'email' => 'support@test.test']);
        $conversation = MailConversation::create(['company_id' => $companyId, 'email_account_id' => $account->id, 'subject' => 'Thread', 'participant_email' => 'client@ext.test']);
        $messages = [];
        foreach (['First thread message', 'Second thread message'] as $subject) {
            $messages[] = MailMessage::create(['company_id' => $companyId, 'email_account_id' => $account->id, 'conversation_id' => $conversation->id, 'sender_email' => 'client@ext.test', 'recipient_email' => $account->email, 'subject' => $subject, 'body_plain' => $subject, 'received_at' => now()]);
        }
        $reply = MailReply::create(['company_id' => $companyId, 'email_account_id' => $account->id, 'email_id' => $messages[1]->id, 'user_id' => $admin->id, 'to_email' => 'client@ext.test', 'subject' => 'Reply', 'body' => 'Reply in the thread']);
        MailAttachment::create(['company_id' => $companyId, 'email_id' => $messages[1]->id, 'reply_id' => $reply->id, 'file_name' => 'reply-contract.pdf', 'file_path' => 'reply-contract.pdf']);
        $this->actingAs($admin);
        Livewire::test(ViewMailMessage::class, ['record' => $messages[0]->getRouteKey()])
            ->assertSee('First thread message')->assertSee('Second thread message')
            ->assertSee('Reply in the thread')->assertSee('reply-contract.pdf');
    }

    public function test_mail_settings_are_saved_from_system_settings_without_an_accounts_modal(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('group')->nullable();
            $table->string('label')->nullable();
            $table->string('input_type')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        $admin = User::create(['name' => 'Admin', 'email' => 'admin@test.test', 'password' => bcrypt('secret'), 'status' => 'active', 'user_type' => UserType::SUPER_ADMIN]);
        $this->actingAs($admin);
        Livewire::test(ListMailAccounts::class)
            ->assertActionDoesNotExist('mail_settings');
        Livewire::test(ManageSettings::class)
            ->fillForm([
                'site_name' => 'Orcal', 'allowed_upload_extensions' => 'pdf,jpg', 'max_upload_size_kb' => 5120,
                'gemini_model' => 'gemini-3.5-flash', 'mail_gemini_model' => 'gemini-flash-lite-latest',
                'mail_gemini_daily_limit' => 25, 'mail_escalation_hours' => 8, 'mail_enable_email_notifications' => false,
            ])
            ->call('saveSettings')
            ->assertHasNoFormErrors();
        $this->assertSame('25', Setting::get('mail_gemini_daily_limit'));
        $this->assertSame('8', Setting::get('mail_escalation_hours'));

        Setting::set('gemini_api_key', 'fake-review-key');
        Http::fake(['*' => Http::response(['candidates' => []])]);
        $result = app(EmailClassifierService::class)->testConnection('draft-model');
        $this->assertTrue($result['success']);
        Http::assertSent(fn ($request): bool => str_contains($request->url(), '/models/draft-model:generateContent'));
        $this->assertSame('gemini-flash-lite-latest', Setting::get('mail_gemini_model'));

        Permission::firstOrCreate(['name' => 'manage_settings', 'guard_name' => 'web']);
        $staff = User::create(['name' => 'Settings Manager', 'email' => 'settings-manager@test.test', 'password' => bcrypt('secret'), 'status' => 'active']);
        $staff->givePermissionTo('manage_settings');
        $this->actingAs($staff);
        Livewire::test(ManageSettings::class)
            ->assertFormFieldHidden('mail_gemini_model')
            ->fillForm(['site_name' => 'Updated Orcal'])
            ->call('saveSettings')
            ->assertHasNoFormErrors();
        $this->assertSame('Updated Orcal', Setting::get('site_name'));
        $this->assertSame('25', Setting::get('mail_gemini_daily_limit'));
    }

    public function test_provider_sections_follow_email_detection_and_explicit_selection(): void
    {
        $this->actingAs($this->providerFormAdmin());

        $form = Livewire::test(CreateMailAccount::class)
            ->fillForm(['email' => 'support@gmail.com'])
            ->assertFormSet(['provider' => 'gmail', 'host' => 'imap.gmail.com', 'username' => 'support@gmail.com'])
            ->assertFormFieldHidden('host')
            ->assertFormFieldHidden('port')
            ->assertFormFieldHidden('encryption')
            ->assertFormFieldHidden('username')
            ->assertSee(__('mail_inbox.provider_setup.gmail.title'))
            ->assertSee('https://myaccount.google.com/apppasswords', false)
            ->fillForm(['email' => 'support@yahoo.com'])
            ->assertFormSet(['provider' => 'yahoo', 'host' => 'imap.mail.yahoo.com'])
            ->assertSee(__('mail_inbox.provider_setup.yahoo.title'))
            ->assertDontSee(__('mail_inbox.provider_setup.gmail.instructions'))
            ->fillForm(['email' => 'support@example.test'])
            ->assertFormSet(['provider' => 'custom', 'host' => 'mail.example.test'])
            ->assertFormFieldVisible('host')
            ->assertFormFieldVisible('port')
            ->assertFormFieldVisible('username');

        $form->fillForm(['provider' => 'gmail'])
            ->fillForm(['email' => 'workspace@example.test'])
            ->assertFormSet(['provider' => 'gmail', 'host' => 'imap.gmail.com', 'username' => 'workspace@example.test']);

        $form->fillForm(['provider' => 'custom', 'host' => 'imap.company.test', 'username' => 'custom-login'])
            ->fillForm(['email' => 'custom@gmail.com'])
            ->assertFormSet(['provider' => 'custom', 'host' => 'imap.company.test', 'username' => 'custom-login']);
    }

    public function test_all_provider_instructions_render_in_both_languages(): void
    {
        $this->actingAs($this->providerFormAdmin());

        foreach (['ar', 'en'] as $locale) {
            app()->setLocale($locale);
            $form = Livewire::test(CreateMailAccount::class);
            foreach (MailAccountService::providerOptions() as $provider) {
                $form->fillForm(['provider' => $provider])
                    ->assertSee(__('mail_inbox.provider_setup.'.$provider.'.title'))
                    ->assertSee(__('mail_inbox.provider_setup.'.$provider.'.heading'))
                    ->assertFormFieldVisible('password')
                    ->assertFormFieldVisible('sync_interval_minutes');
            }
        }
    }

    public function test_provider_credentials_are_saved_with_server_defaults_and_password_formatting(): void
    {
        $admin = $this->providerFormAdmin();
        $this->actingAs($admin);
        $companyId = DB::table('companies')->insertGetId(['trade_name' => 'Provider Company']);
        $employeeId = DB::table('employees')->insertGetId(['company_id' => $companyId, 'user_id' => $admin->id, 'status' => 'active']);

        foreach (['gmail', 'yahoo', 'custom'] as $provider) {
            $form = Livewire::test(CreateMailAccount::class)
                ->fillForm(['company_id' => $companyId, 'employees' => [$employeeId], 'provider' => $provider])
                ->fillForm([
                    'name' => $provider,
                    'email' => $provider.'@example.test',
                    'password' => ' abcd efgh ijkl mnop ',
                    'is_active' => false,
                ]);
            if ($provider === 'custom') {
                $form->fillForm(['host' => 'imap.company.test', 'username' => 'custom-login', 'port' => 143, 'encryption' => 'tls']);
            }
            $form->call('create')->assertHasNoFormErrors();
            $account = MailAccount::where('name', $provider)->firstOrFail();
            $this->assertSame($provider === 'custom' ? ' abcd efgh ijkl mnop ' : 'abcdefghijklmnop', $account->password);
            $this->assertSame($provider === 'custom' ? 'imap.company.test' : MailAccountService::providerDefaults($provider, $account->email)['host'], $account->host);
            $this->assertSame($provider === 'custom' ? 143 : 993, (int) $account->port);
        }
    }

    public function test_editing_custom_account_preserves_its_connection_and_blank_password(): void
    {
        $admin = $this->providerFormAdmin();
        $this->actingAs($admin);
        $companyId = DB::table('companies')->insertGetId(['trade_name' => 'Custom Company']);
        $employeeId = DB::table('employees')->insertGetId(['company_id' => $companyId, 'user_id' => $admin->id, 'status' => 'active']);
        $account = MailAccount::create([
            'company_id' => $companyId, 'name' => 'Custom', 'email' => 'account@gmail.com',
            'host' => 'imap.gmail.company.test', 'port' => 143, 'encryption' => 'tls',
            'username' => 'custom-login', 'password' => ' original secret ', 'is_active' => false,
        ]);
        $account->employees()->attach($employeeId, ['company_id' => $companyId]);

        Livewire::test(EditMailAccount::class, ['record' => $account->getRouteKey()])
            ->assertFormSet(['provider' => 'custom', 'host' => 'imap.gmail.company.test'])
            ->fillForm(['name' => 'Renamed', 'email' => 'changed@yahoo.com', 'password' => ''])
            ->call('save')
            ->assertHasNoFormErrors();

        $account->refresh();
        $this->assertSame('imap.gmail.company.test', $account->host);
        $this->assertSame('custom-login', $account->username);
        $this->assertSame(143, (int) $account->port);
        $this->assertSame(' original secret ', $account->password);
    }

    private function providerFormAdmin(): User
    {
        return User::create([
            'name' => 'Provider Admin', 'email' => 'provider-admin@test.test', 'password' => bcrypt('secret'),
            'status' => 'active', 'user_type' => UserType::SUPER_ADMIN,
        ]);
    }
}
