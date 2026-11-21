<?php

namespace App\Filament\Resources\Mail\MailAccounts\Pages;

use App\Filament\Resources\Mail\MailAccounts\MailAccountResource;
use App\Jobs\Mail\SyncEmailAccountJob;
use App\Services\Mail\MailAccountService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class CreateMailAccount extends CreateRecord
{
    protected static string $resource = MailAccountResource::class;

    public function create(bool $another = false): void
    {
        request()->attributes->set('mail_account_creation_trace', (string) Str::uuid());
        request()->attributes->set('mail_account_creation_started', microtime(true));
        $this->logCreationStage('request_started');

        try {
            parent::create($another);
            $this->logCreationStage('request_completed');
        } catch (Throwable $exception) {
            Log::warning('Mail account creation failed', [
                'trace_id' => request()->attributes->get('mail_account_creation_trace'),
                'stage' => request()->attributes->get('mail_account_creation_stage'),
                'elapsed_ms' => $this->creationElapsedMilliseconds(),
                'exception_class' => $exception::class,
                'exception_file' => basename($exception->getFile()),
                'exception_line' => $exception->getLine(),
                'validation_fields' => $exception instanceof ValidationException ? array_keys($exception->errors()) : [],
            ]);

            throw $exception;
        } finally {
            request()->attributes->remove('mail_account_creation_trace');
            request()->attributes->remove('mail_account_creation_started');
            request()->attributes->remove('mail_account_creation_stage');
        }
    }

    protected function beforeValidate(): void
    {
        $this->logCreationStage('validation_started');
    }

    protected function afterValidate(): void
    {
        $this->logCreationStage('validation_completed');
    }

    protected function handleRecordCreation(array $data): Model
    {
        $this->logCreationStage('account_save_started');
        $employeeIds = (array) ($this->data['employees'] ?? []);

        $record = app(MailAccountService::class)->saveAccount(
            null,
            $data,
            $employeeIds,
            auth()->user()
        );

        $this->logCreationStage('account_save_completed');

        return $record;
    }

    protected function afterCreate(): void
    {
        $this->logCreationStage('form_relationships_saved');
        if ($this->record->is_active) {
            $this->logCreationStage('sync_dispatch_started');
            SyncEmailAccountJob::dispatch($this->record)->afterCommit();
            $this->logCreationStage('sync_dispatch_returned');
            Notification::make()->title(__('mail_inbox.messages.sync_queued'))->success()->send();
        }

        $this->logCreationStage('after_create_completed');
    }

    protected function logCreationStage(string $stage): void
    {
        request()->attributes->set('mail_account_creation_stage', $stage);

        Log::info('Mail account creation stage', [
            'trace_id' => request()->attributes->get('mail_account_creation_trace'),
            'stage' => $stage,
            'elapsed_ms' => $this->creationElapsedMilliseconds(),
            'account_id' => $this->record?->getKey(),
            'queue_connection' => config('queue.default'),
            'queue_driver' => config('queue.connections.'.config('queue.default').'.driver'),
        ]);
    }

    protected function creationElapsedMilliseconds(): int
    {
        return (int) round((microtime(true) - request()->attributes->get('mail_account_creation_started', microtime(true))) * 1000);
    }
}
