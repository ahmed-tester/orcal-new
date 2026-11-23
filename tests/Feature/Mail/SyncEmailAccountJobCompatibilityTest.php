<?php

namespace Tests\Feature\Mail;

use App\Jobs\Mail\SyncEmailAccountJob;
use App\Models\Mail\MailAccount;
use App\Services\Mail\MailSyncService;
use Mockery;
use ReflectionClass;
use Tests\TestCase;

class SyncEmailAccountJobCompatibilityTest extends TestCase
{
    public function test_old_payload_without_verify_flag_runs_normal_sync(): void
    {
        $account = Mockery::mock(MailAccount::class)->makePartial();
        $account->id = 123;
        $account->shouldReceive('fresh')->once()->andReturnSelf();
        $account->shouldReceive('isReadyForSync')->once()->andReturnTrue();
        $syncService = Mockery::mock(MailSyncService::class);
        $syncService->shouldReceive('syncAccount')->once()->with($account)->andReturn([]);

        $job = (new ReflectionClass(SyncEmailAccountJob::class))->newInstanceWithoutConstructor();
        $job->__unserialize(['account' => $account]);

        $this->assertFalse($job->verifyOnly);
        $job->handle($syncService);
    }

    public function test_restored_verification_job_keeps_verification_mode(): void
    {
        $account = Mockery::mock(MailAccount::class)->makePartial();
        $account->id = 124;
        $account->shouldReceive('fresh')->once()->andReturnSelf();
        $account->shouldReceive('hasOAuthTokens')->once()->andReturnTrue();
        $account->shouldNotReceive('isReadyForSync');
        $syncService = Mockery::mock(MailSyncService::class);
        $syncService->shouldReceive('testConnection')->once()->with($account)->andReturn(['success' => true]);
        $syncService->shouldNotReceive('syncAccount');

        $job = (new ReflectionClass(SyncEmailAccountJob::class))->newInstanceWithoutConstructor();
        $job->__unserialize(['account' => $account, 'verifyOnly' => true]);

        $this->assertTrue($job->verifyOnly);
        $job->handle($syncService);
    }

    public function test_constructor_keeps_default_and_explicit_modes(): void
    {
        $account = new MailAccount;

        $this->assertFalse((new SyncEmailAccountJob($account))->verifyOnly);
        $this->assertFalse((new SyncEmailAccountJob($account, verifyOnly: false))->verifyOnly);
        $this->assertTrue((new SyncEmailAccountJob($account, verifyOnly: true))->verifyOnly);
    }
}
