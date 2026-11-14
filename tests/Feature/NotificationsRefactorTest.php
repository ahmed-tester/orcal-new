<?php

namespace Tests\Feature;

use App\Models\Debt;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\BaseSystemNotification;
use App\Services\Notifications\NotificationDispatcher;
use App\Services\Notifications\NotificationRecipientService;
use App\Services\Notifications\SmsService;
use App\Services\TicketNotificationService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class NotificationsRefactorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        config(['database.default' => 'sqlite', 'queue.default' => 'database']);
        foreach (['0001_01_01_000000_create_users_table.php', '0001_01_01_000002_create_jobs_table.php', '2026_09_17_123833_create_permission_tables.php', '2026_09_21_110421_create_notifications_table.php'] as $file) {
            (require database_path('migrations/'.$file))->up();
        }
        Schema::create('companies', function (Blueprint $table): void {
            $table->id();
        });
        Schema::create('debts', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
        });
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        DB::table('companies')->insert([['id' => 1], ['id' => 2]]);
        DB::table('debts')->insert(['id' => 1, 'company_id' => 1]);
    }

    private function user(string $permission = 'update_debt', int $company = 1): User
    {
        $user = User::factory()->create(['status' => 'active', 'user_type' => 'admin']);
        DB::table('employees')->insert(['user_id' => $user->id, 'company_id' => $company]);
        $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));

        return $user;
    }

    private function notification(): BaseSystemNotification
    {
        return new BaseSystemNotification('DebtDueAlert', customTitle: 'Test title', customMessage: 'Test body', actionUrl: '/debts/1/edit');
    }

    public function test_only_active_editors_in_the_company_are_queued(): void
    {
        $editor = $this->user();
        $this->user('view_any_debt');
        $this->user('update_debt', 2);
        $inactive = $this->user();
        $inactive->update(['status' => 'inactive']);
        Queue::fake();

        $count = NotificationDispatcher::send(Debt::findOrFail(1), $this->notification());

        $this->assertSame(1, $count);
        Queue::assertPushed(SendQueuedNotifications::class, fn ($job) => $job->notifiables->first()->id === $editor->id);
        Queue::assertPushed(SendQueuedNotifications::class, 1);
    }

    public function test_a_denied_update_policy_cannot_be_bypassed_by_permission(): void
    {
        $user = $this->user();
        Gate::policy(Debt::class, DeniedDebtNotificationPolicy::class);

        $this->assertFalse(NotificationRecipientService::isUserEligibleToReceive($user, Debt::findOrFail(1), ['update_debt'], 1, null));
    }

    public function test_branch_restrictions_and_excluded_authors_are_respected(): void
    {
        $user = $this->user();
        DB::table('employees')->where('user_id', $user->id)->update(['branch_id' => 3]);
        $user->unsetRelation('employee');

        $this->assertFalse(NotificationRecipientService::isUserEligibleToReceive($user, Debt::findOrFail(1), ['update_debt'], 1, 4));
        $this->assertCount(0, NotificationRecipientService::getQualifiedRecipients(Debt::findOrFail(1), excludeUserIds: [$user->id]));
    }

    public function test_queued_delivery_rechecks_revoked_permission(): void
    {
        $user = $this->user();
        Queue::fake();
        NotificationDispatcher::send(Debt::findOrFail(1), $this->notification());
        $job = Queue::pushed(SendQueuedNotifications::class)->first();
        $user->revokePermissionTo('update_debt');

        unserialize(serialize($job))->handle(app(ChannelManager::class));

        $this->assertSame(0, DB::table('notifications')->count());
    }

    public function test_queued_delivery_skips_deactivated_users_and_deleted_records(): void
    {
        $user = $this->user();
        Queue::fake();
        NotificationDispatcher::send(Debt::findOrFail(1), $this->notification());
        $job = Queue::pushed(SendQueuedNotifications::class)->first();
        $user->update(['status' => 'inactive']);
        unserialize(serialize($job))->handle(app(ChannelManager::class));
        $user->update(['status' => 'active']);
        DB::table('debts')->delete();
        unserialize(serialize($job))->handle(app(ChannelManager::class));

        $this->assertSame(0, DB::table('notifications')->count());
    }

    public function test_successful_queue_delivery_stores_a_filament_notification(): void
    {
        $this->user();
        Queue::fake();
        NotificationDispatcher::send(Debt::findOrFail(1), $this->notification());
        $job = Queue::pushed(SendQueuedNotifications::class)->first();

        unserialize(serialize($job))->handle(app(ChannelManager::class));

        $data = json_decode(DB::table('notifications')->sole()->data, true);
        $this->assertSame('filament', $data['format']);
        $this->assertSame('Test title', $data['title']);
        $this->assertSame(Debt::class, $data['record_type']);
        $this->assertSame(1, DB::table('notifications')->count());
    }

    public function test_dispatch_waits_for_commit_and_rollback_does_not_queue(): void
    {
        $this->user();
        DB::beginTransaction();
        NotificationDispatcher::send(Debt::findOrFail(1), $this->notification());
        $this->assertSame(0, DB::table('jobs')->count());
        DB::rollBack();
        $this->assertSame(0, DB::table('jobs')->count());

        DB::beginTransaction();
        NotificationDispatcher::send(Debt::findOrFail(1), $this->notification());
        DB::commit();
        $this->assertSame(1, DB::table('jobs')->count());
    }

    public function test_sms_is_disabled_even_when_called_directly(): void
    {
        config(['notifications.sms.enabled' => false]);
        Http::fake();

        $this->assertFalse(app(SmsService::class)->sendMessage('+966500000000', 'Test'));
        Http::assertNothingSent();
    }

    public function test_ticket_assignment_does_not_bypass_recipient_authorization(): void
    {
        $assigned = $this->user('view_any_debt');
        $manager = $this->user();
        $manager->update(['user_type' => 'super_admin']);
        $ticket = new Ticket(['subject' => 'Test subject', 'company_id' => 1]);
        $ticket->id = 10;
        $ticket->ticket_number = 'TEST-10';
        Queue::fake();

        TicketNotificationService::notifyTicketAssigned($ticket, $assigned);

        Queue::assertPushed(SendQueuedNotifications::class, 1);
        Queue::assertPushed(SendQueuedNotifications::class, fn ($job) => $job->notifiables->first()->id === $manager->id
            && $job->notification->eventKey === 'TicketAssigned');
    }

    public function test_notification_translations_have_matching_keys_and_localized_payloads(): void
    {
        $ar = require lang_path('ar/notifications.php');
        $en = require lang_path('en/notifications.php');
        $arKeys = array_keys(Arr::dot($ar));
        $enKeys = array_keys(Arr::dot($en));
        sort($arKeys);
        sort($enKeys);
        $this->assertSame($arKeys, $enKeys);
        $notification = new BaseSystemNotification('DueDateReminder', ['employee_name' => 'Test', 'code' => 'A1']);
        foreach (['ar' => $ar, 'en' => $en] as $locale => $messages) {
            app()->setLocale($locale);
            $this->assertSame($messages['events']['DueDateReminder']['database']['title'], $notification->toDatabase(new User)['title']);
        }
    }

    public function test_report_blade_compiles_after_localization(): void
    {
        $compiled = Blade::compileString(file_get_contents(resource_path('views/filament/pages/notifications-report.blade.php')));
        $this->assertNotEmpty(\PhpToken::tokenize($compiled, TOKEN_PARSE));
    }
}

class DeniedDebtNotificationPolicy
{
    public function update(User $user, Debt $debt): bool
    {
        return false;
    }
}
