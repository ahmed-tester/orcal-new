<?php

namespace Tests\Feature\Mail;

use App\Enums\Mail\MailAccountStatus;
use App\Enums\UserType;
use App\Filament\Resources\Mail\MailAccounts\Pages\CreateMailAccount;
use App\Jobs\Mail\SyncEmailAccountJob;
use App\Models\Mail\MailAccount;
use App\Models\User;
use App\Services\Mail\EmailReplyService;
use App\Services\Mail\MailAccessService;
use App\Services\Mail\MailAccountService;
use App\Services\Mail\MailSyncService;
use App\Services\Mail\MicrosoftMailOAuthService;
use Filament\Facades\Filament;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class MicrosoftMailOAuthTest extends TestCase
{
    protected int $companyId;

    protected int $otherCompanyId;

    protected int $employeeId;

    protected User $adminUser;

    protected User $otherCompanyUser;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        Cache::flush();

        config(['database.default' => 'sqlite']);
        config(['database.connections.sqlite.database' => ':memory:']);
        DB::purge();
        DB::reconnect('sqlite');
        DB::statement('PRAGMA foreign_keys = ON;');

        config([
            'services.microsoft_mail.client_id' => 'test-client-id',
            'services.microsoft_mail.client_secret' => 'test-client-secret',
            'services.microsoft_mail.redirect_uri' => 'https://example.test/mail/microsoft/callback',
            'services.microsoft_mail.tenant' => 'common',
        ]);

        $this->createTestSchema();
        $this->seedPermissionsAndActors();
    }

    public function test_create_form_saves_pending_microsoft_account_once_and_redirects(): void
    {
        $this->actingAs($this->adminUser);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::test(CreateMailAccount::class)
            ->fillForm([
                'company_id' => $this->companyId,
                'employees' => [$this->employeeId],
                'provider' => MailAccountService::PROVIDER_OUTLOOK,
                'email' => 'Support@Hotmail.com',
                'name' => 'Hotmail Support',
                'is_active' => true,
            ])
            ->call('createAndConnectMicrosoft')
            ->assertRedirect();

        $account = MailAccount::where('email', 'Support@Hotmail.com')->first();
        $this->assertNotNull($account);
        $this->assertSame('outlook_oauth', $account->type);
        $this->assertSame(MailAccountStatus::PENDING_AUTH, $account->status);
        $this->assertNull($account->password);
        Queue::assertNothingPushed();

        // Retrying before authorization completes reuses the same pending account
        Livewire::test(CreateMailAccount::class)
            ->fillForm([
                'company_id' => $this->companyId,
                'employees' => [$this->employeeId],
                'provider' => MailAccountService::PROVIDER_OUTLOOK,
                'email' => 'support@hotmail.com',
                'name' => 'Hotmail Support Updated',
                'is_active' => true,
            ])
            ->call('createAndConnectMicrosoft')
            ->assertRedirect();

        $this->assertSame(1, MailAccount::count());
        $this->assertSame('Hotmail Support Updated', MailAccount::first()->name);
    }

    public function test_mail_account_service_strips_injected_oauth_tokens(): void
    {
        $service = app(MailAccountService::class);
        $account = $service->saveAccount(null, [
            'company_id' => $this->companyId,
            'provider' => MailAccountService::PROVIDER_OUTLOOK,
            'email' => 'team@hotmail.com',
            'name' => 'Team Hotmail',
            'is_active' => true,
            'oauth_access_token' => 'injected-access',
            'oauth_refresh_token' => 'injected-refresh',
            'oauth_token_expires_at' => now()->addYear(),
        ], [$this->employeeId], $this->adminUser);

        $this->assertNull($account->oauth_access_token);
        $this->assertNull($account->oauth_refresh_token);
        $this->assertNull($account->oauth_token_expires_at);
        $this->assertSame('outlook_oauth', $account->type);
        $this->assertSame(MailAccountStatus::PENDING_AUTH, $account->status);
    }

    public function test_successful_oauth_callback_verifies_oidc_saves_tokens_and_queues_sync(): void
    {
        $account = $this->createPendingAccount('sales@hotmail.com');
        $this->actingAs($this->adminUser);

        $redirectResponse = $this->post(route('mail.microsoft.redirect', $account));
        $redirectResponse->assertRedirect();
        parse_str((string) parse_url((string) $redirectResponse->headers->get('Location'), PHP_URL_QUERY), $query);

        [$idToken, $jwks] = $this->makeSignedIdToken(
            nonce: (string) $query['nonce'],
            email: 'sales@hotmail.com',
        );

        Http::fake([
            'https://login.microsoftonline.com/common/oauth2/v2.0/token' => Http::response([
                'access_token' => 'ms-access-token-1',
                'refresh_token' => 'ms-refresh-token-1',
                'expires_in' => 3600,
                'id_token' => $idToken,
            ], 200),
            'https://login.microsoftonline.com/common/discovery/v2.0/keys' => Http::response($jwks, 200),
        ]);

        $response = $this->get(route('mail.microsoft.callback', [
            'state' => $query['state'],
            'code' => 'valid-auth-code',
        ]));

        $response->assertRedirect();
        $account->refresh();
        $this->assertSame('ms-access-token-1', $account->oauth_access_token);
        $this->assertSame('ms-refresh-token-1', $account->oauth_refresh_token);
        $this->assertSame(MailAccountStatus::CONNECTED, $account->status);
        $this->assertTrue(MicrosoftMailOAuthService::isVerifying($account->id));
        Queue::assertPushed(SyncEmailAccountJob::class);

        // Replay of the same state must fail
        $replay = $this->get(route('mail.microsoft.callback', [
            'state' => $query['state'],
            'code' => 'valid-auth-code',
        ]));
        $replay->assertRedirect();
    }

    public function test_callback_rejects_mismatched_email_and_user_cancellation(): void
    {
        $account = $this->createPendingAccount('sales@hotmail.com');
        $this->actingAs($this->adminUser);

        $redirectResponse = $this->post(route('mail.microsoft.redirect', $account));
        $redirectResponse->assertRedirect();
        parse_str((string) parse_url((string) $redirectResponse->headers->get('Location'), PHP_URL_QUERY), $query);

        [$idToken, $jwks] = $this->makeSignedIdToken(
            nonce: (string) $query['nonce'],
            email: 'other-person@hotmail.com',
        );

        Http::fake([
            'https://login.microsoftonline.com/common/oauth2/v2.0/token' => Http::response([
                'access_token' => 'ms-access-token-1',
                'refresh_token' => 'ms-refresh-token-1',
                'expires_in' => 3600,
                'id_token' => $idToken,
            ], 200),
            'https://login.microsoftonline.com/common/discovery/v2.0/keys' => Http::response($jwks, 200),
        ]);

        $this->get(route('mail.microsoft.callback', [
            'state' => $query['state'],
            'code' => 'valid-auth-code',
        ]))->assertRedirect();

        $account->refresh();
        $this->assertNull($account->oauth_access_token);
        $this->assertSame(MailAccountStatus::PENDING_AUTH, $account->status);
        $this->assertStringContainsString('sales@hotmail.com', (string) $account->error_message);
        Queue::assertNothingPushed();

        // User cancellation
        $redirect2 = $this->post(route('mail.microsoft.redirect', $account));
        $redirect2->assertRedirect();
        parse_str((string) parse_url((string) $redirect2->headers->get('Location'), PHP_URL_QUERY), $query2);

        $this->get(route('mail.microsoft.callback', [
            'state' => $query2['state'],
            'error' => 'access_denied',
        ]))->assertRedirect();

        $account->refresh();
        $this->assertNull($account->oauth_access_token);
        $this->assertSame(MailAccountStatus::PENDING_AUTH, $account->status);
    }

    public function test_token_refresh_rotation_transient_failure_and_invalid_grant(): void
    {
        $account = $this->createPendingAccount('ops@hotmail.com', [
            'oauth_access_token' => 'expired-token',
            'oauth_refresh_token' => 'refresh-v1',
            'oauth_token_expires_at' => now()->subMinute(),
            'status' => MailAccountStatus::CONNECTED,
        ]);

        Http::fake([
            'https://login.microsoftonline.com/common/oauth2/v2.0/token' => Http::sequence()
                ->push(['error' => 'temporarily_unavailable'], 503)
                ->push([
                    'access_token' => 'access-v2',
                    'refresh_token' => 'refresh-v2',
                    'expires_in' => 3600,
                ], 200)
                ->push(['error' => 'invalid_grant'], 400),
        ]);

        $oauthService = app(MicrosoftMailOAuthService::class);

        // 1. Transient failure preserves tokens
        try {
            $oauthService->ensureValidAccessToken($account);
            $this->fail('Expected transient failure exception');
        } catch (\RuntimeException) {
            $account->refresh();
            $this->assertSame('refresh-v1', $account->oauth_refresh_token);
            $this->assertSame(MailAccountStatus::CONNECTED, $account->status);
        }

        // 2. Successful refresh rotates refresh token
        $token = $oauthService->ensureValidAccessToken($account);
        $this->assertSame('access-v2', $token);
        $account->refresh();
        $this->assertSame('refresh-v2', $account->oauth_refresh_token);

        // 3. Force expiry & hit invalid_grant -> clears tokens and marks REAUTH_REQUIRED
        $account->update(['oauth_token_expires_at' => now()->subMinute()]);
        try {
            $oauthService->ensureValidAccessToken($account);
            $this->fail('Expected invalid_grant exception');
        } catch (\RuntimeException) {
            $account->refresh();
            $this->assertNull($account->oauth_access_token);
            $this->assertNull($account->oauth_refresh_token);
            $this->assertSame(MailAccountStatus::REAUTH_REQUIRED, $account->status);
        }
    }

    public function test_imap_and_smtp_configs_for_outlook_oauth_and_sync_readiness(): void
    {
        $pending = $this->createPendingAccount('pending@hotmail.com');
        $syncService = app(MailSyncService::class);
        $this->assertFalse($pending->isReadyForSync());
        $this->assertFalse($syncService->testConnection($pending)['success']);

        $connected = $this->createPendingAccount('agent@hotmail.com', [
            'oauth_access_token' => 'live-access-token',
            'oauth_refresh_token' => 'live-refresh-token',
            'oauth_token_expires_at' => now()->addHour(),
            'status' => MailAccountStatus::CONNECTED,
        ]);

        $imapConfig = $syncService->buildImapConfig($connected);
        $this->assertSame('oauth', $imapConfig['authentication']);
        $this->assertSame('live-access-token', $imapConfig['password']);
        $this->assertSame('outlook.office365.com', $imapConfig['host']);

        $replyService = app(EmailReplyService::class);
        $smtpConfig = $replyService->buildSmtpConfig($connected);
        $this->assertSame('smtp-mail.outlook.com', $smtpConfig['host']);
        $this->assertSame(587, $smtpConfig['port']);
        $this->assertSame('tls', $smtpConfig['encryption']);
        $this->assertSame('live-access-token', $smtpConfig['password']);

        $m365Account = $this->createPendingAccount('info@contoso.com', [
            'oauth_access_token' => 'm365-token',
            'oauth_refresh_token' => 'm365-refresh',
            'oauth_token_expires_at' => now()->addHour(),
            'status' => MailAccountStatus::CONNECTED,
        ]);
        $this->assertSame('smtp.office365.com', $replyService->buildSmtpConfig($m365Account)['host']);
    }

    public function test_unauthorized_user_cannot_begin_authorization(): void
    {
        $account = $this->createPendingAccount('locked@hotmail.com');
        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);

        app(MicrosoftMailOAuthService::class)->beginAuthorization($account, $this->otherCompanyUser);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    protected function createPendingAccount(string $email, array $overrides = []): MailAccount
    {
        return MailAccount::create(array_merge([
            'company_id' => $this->companyId,
            'name' => 'Test Outlook',
            'email' => $email,
            'type' => 'outlook_oauth',
            'host' => 'outlook.office365.com',
            'port' => 993,
            'encryption' => 'ssl',
            'username' => $email,
            'status' => MailAccountStatus::PENDING_AUTH,
            'sync_interval_minutes' => 1,
            'is_active' => true,
        ], $overrides));
    }

    /**
     * @return array{0: string, 1: array{keys: array<int, array<string, mixed>>}}
     */
    protected function makeSignedIdToken(string $nonce, string $email): array
    {
        $cnfPath = sys_get_temp_dir().DIRECTORY_SEPARATOR.'orcal_test_openssl.cnf';
        if (! file_exists($cnfPath)) {
            file_put_contents($cnfPath, "[req]\ndistinguished_name = req_distinguished_name\n[req_distinguished_name]\n");
        }

        $privateKey = openssl_pkey_new([
            'config' => $cnfPath,
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);
        $details = openssl_pkey_get_details($privateKey);

        $header = $this->b64Url(json_encode(['alg' => 'RS256', 'typ' => 'JWT', 'kid' => 'test-kid-1']));
        $payload = $this->b64Url(json_encode([
            'aud' => 'test-client-id',
            'iss' => 'https://login.microsoftonline.com/9188040d-6c67-4c5b-b112-36a304b66dad/v2.0',
            'tid' => '9188040d-6c67-4c5b-b112-36a304b66dad',
            'exp' => time() + 3600,
            'nonce' => $nonce,
            'email' => $email,
        ]));

        openssl_sign($header.'.'.$payload, $signature, $privateKey, OPENSSL_ALGO_SHA256);

        return [
            $header.'.'.$payload.'.'.$this->b64Url($signature),
            ['keys' => [[
                'kid' => 'test-kid-1',
                'kty' => 'RSA',
                'n' => $this->b64Url($details['rsa']['n']),
                'e' => $this->b64Url($details['rsa']['e']),
            ]]],
        ];
    }

    protected function b64Url(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    protected function createTestSchema(): void
    {
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
            $table->foreignId('user_id')->nullable();
            $table->string('name');
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
            $table->primary(['permission_id', 'model_id', 'model_type']);
        });

        Schema::create('model_has_roles', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->primary(['role_id', 'model_id', 'model_type']);
        });

        Schema::create('role_has_permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('permission_id');
            $table->unsignedBigInteger('role_id');
            $table->primary(['permission_id', 'role_id']);
        });

        Schema::create('email_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('name');
            $table->string('email');
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
            $table->unsignedBigInteger('inbox_uid_validity')->nullable();
            $table->unsignedBigInteger('last_spam_message_uid')->default(0);
            $table->unsignedBigInteger('spam_uid_validity')->nullable();
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
        });

        Schema::create('emails', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('email_account_id')->constrained('email_accounts')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    protected function seedPermissionsAndActors(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ([
            MailAccessService::PERM_VIEW_INBOX,
            MailAccessService::PERM_MANAGE_ACCOUNTS,
            MailAccessService::PERM_RUN_SYNC,
        ] as $perm) {
            Permission::create(['name' => $perm, 'guard_name' => 'web']);
        }

        $superRole = Role::create(['name' => 'super_admin', 'guard_name' => 'web']);

        $this->companyId = (int) DB::table('companies')->insertGetId(['trade_name' => 'Company A']);
        $this->otherCompanyId = (int) DB::table('companies')->insertGetId(['trade_name' => 'Company B']);

        $this->adminUser = User::create([
            'name' => 'Super Admin',
            'email' => 'admin@example.test',
            'password' => bcrypt('password'),
            'status' => 'active',
            'user_type' => UserType::SUPER_ADMIN->value,
            'company_id' => $this->companyId,
        ]);
        $this->adminUser->assignRole($superRole);
        $this->adminUser->givePermissionTo([
            MailAccessService::PERM_VIEW_INBOX,
            MailAccessService::PERM_MANAGE_ACCOUNTS,
            MailAccessService::PERM_RUN_SYNC,
        ]);

        $this->otherCompanyUser = User::create([
            'name' => 'Other Manager',
            'email' => 'other@example.test',
            'password' => bcrypt('password'),
            'status' => 'active',
            'company_id' => $this->otherCompanyId,
        ]);
        $this->otherCompanyUser->givePermissionTo(MailAccessService::PERM_MANAGE_ACCOUNTS);

        $this->employeeId = (int) DB::table('employees')->insertGetId([
            'company_id' => $this->companyId,
            'user_id' => $this->adminUser->id,
            'name' => 'Support Agent',
            'status' => 'active',
        ]);
    }
}
