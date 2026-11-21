<?php

namespace App\Services\Mail;

use App\Enums\Mail\MailAccountStatus;
use App\Models\Employee;
use App\Models\Mail\MailAccount;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class MailAccountService
{
    public const PROVIDER_GMAIL = 'gmail';

    public const PROVIDER_OUTLOOK = 'outlook';

    public const PROVIDER_YAHOO = 'yahoo';

    public const PROVIDER_CUSTOM = 'custom';

    /**
     * Providers that authenticate with an app password (spaces are not part of the secret).
     *
     * @var array<int, string>
     */
    public const APP_PASSWORD_PROVIDERS = [
        self::PROVIDER_GMAIL,
        self::PROVIDER_OUTLOOK,
        self::PROVIDER_YAHOO,
    ];

    /**
     * @return array<int, string>
     */
    public static function providerOptions(): array
    {
        return [
            self::PROVIDER_GMAIL,
            self::PROVIDER_OUTLOOK,
            self::PROVIDER_YAHOO,
            self::PROVIDER_CUSTOM,
        ];
    }

    public static function inferProvider(?string $email, ?string $host = null): string
    {
        $email = strtolower(trim((string) $email));
        $host = strtolower(trim((string) $host));
        $domain = strtolower(substr(strrchr($email, '@') ?: '', 1));

        if ($host !== '') {
            return match ($host) {
                'imap.gmail.com' => self::PROVIDER_GMAIL,
                'outlook.office365.com', 'imap-mail.outlook.com' => self::PROVIDER_OUTLOOK,
                'imap.mail.yahoo.com' => self::PROVIDER_YAHOO,
                default => self::PROVIDER_CUSTOM,
            };
        }

        if ($domain === 'gmail.com') {
            return self::PROVIDER_GMAIL;
        }

        if (in_array($domain, ['outlook.com', 'hotmail.com', 'live.com'], true)) {
            return self::PROVIDER_OUTLOOK;
        }

        if ($domain === 'yahoo.com' || str_starts_with($domain, 'yahoo.')) {
            return self::PROVIDER_YAHOO;
        }

        return self::PROVIDER_CUSTOM;
    }

    /**
     * Connection defaults per provider. The custom provider derives its host from the email domain.
     *
     * @return array{host: string, port: int, encryption: string, username: string}
     */
    public static function providerDefaults(string $provider, ?string $email): array
    {
        $email = trim((string) $email);
        $domain = substr(strrchr($email, '@') ?: '', 1);

        return match ($provider) {
            self::PROVIDER_GMAIL => [
                'host' => 'imap.gmail.com',
                'port' => 993,
                'encryption' => 'ssl',
                'username' => $email,
            ],
            self::PROVIDER_OUTLOOK => [
                'host' => 'outlook.office365.com',
                'port' => 993,
                'encryption' => 'ssl',
                'username' => $email,
            ],
            self::PROVIDER_YAHOO => [
                'host' => 'imap.mail.yahoo.com',
                'port' => 993,
                'encryption' => 'ssl',
                'username' => $email,
            ],
            default => [
                'host' => $domain !== '' ? 'mail.'.$domain : '',
                'port' => 993,
                'encryption' => 'ssl',
                'username' => $email,
            ],
        };
    }

    /**
     * Remove formatting spaces only for app-password providers; domain passwords stay untouched.
     */
    public static function normalizePassword(?string $password, string $provider): ?string
    {
        if ($password === null || $password === '') {
            return $password;
        }

        if (in_array($provider, self::APP_PASSWORD_PROVIDERS, true)) {
            return str_replace([' ', "\r", "\n", "\t"], '', $password);
        }

        return $password;
    }

    /**
     * Validate company access & employee assignments server-side and save in one transaction.
     *
     * @param  array<string, mixed>  $data
     * @param  array<int|string>  $employeeIds
     */
    public function saveAccount(?MailAccount $account, array $data, array $employeeIds, User $actor): MailAccount
    {
        Gate::forUser($actor)->authorize($account ? 'update' : 'create', $account ?? MailAccount::class);
        $this->logCreationStage('authorization_completed');
        $companyId = (int) ($data['company_id'] ?? $account?->company_id ?? 0);
        $data['company_id'] = $companyId;

        $provider = (string) ($data['provider'] ?? '');
        unset(
            $data['provider'],
            $data['oauth_access_token'],
            $data['oauth_refresh_token'],
            $data['oauth_token_expires_at'],
        );

        $email = (string) ($data['email'] ?? $account?->email ?? '');
        $provider = in_array($provider, self::providerOptions(), true)
            ? $provider
            : self::inferProvider($email, (string) ($data['host'] ?? $account?->host ?? ''));

        $defaults = self::providerDefaults($provider, $email);

        if ($provider === self::PROVIDER_CUSTOM) {
            $data['host'] = filled($data['host'] ?? null) ? $data['host'] : ($account?->host ?: $defaults['host']);
            $data['port'] = filled($data['port'] ?? null) ? (int) $data['port'] : ($account?->port ?: $defaults['port']);
            $data['encryption'] = filled($data['encryption'] ?? null) ? $data['encryption'] : ($account?->encryption ?: $defaults['encryption']);
            $data['username'] = filled($data['username'] ?? null) ? $data['username'] : ($account?->username ?: $defaults['username']);
        } else {
            $data['host'] = $defaults['host'];
            $data['port'] = $defaults['port'];
            $data['encryption'] = $defaults['encryption'];
            $data['username'] = filled($data['username'] ?? null) ? $data['username'] : $defaults['username'];
        }

        if ($provider === self::PROVIDER_OUTLOOK) {
            $data['type'] = 'outlook_oauth';
            $data['password'] = null;

            $emailChanged = $account !== null && strcasecmp(trim((string) $account->email), trim($email)) !== 0;
            $typeChanged = $account !== null && $account->type !== 'outlook_oauth';

            if ($account === null || $emailChanged || $typeChanged) {
                $data['oauth_access_token'] = null;
                $data['oauth_refresh_token'] = null;
                $data['oauth_token_expires_at'] = null;
                $data['status'] = MailAccountStatus::PENDING_AUTH;
                $data['error_message'] = null;
            }
        } else {
            $data['type'] = 'imap';

            if ($account !== null && $account->isOutlookOAuth()) {
                $data['oauth_access_token'] = null;
                $data['oauth_refresh_token'] = null;
                $data['oauth_token_expires_at'] = null;
                $data['status'] = MailAccountStatus::CONNECTED;
                $data['error_message'] = null;
            }

            if (array_key_exists('password', $data)) {
                $data['password'] = self::normalizePassword($data['password'], $provider);
            }
        }

        if ($companyId <= 0 || ! MailAccessService::hasCompanyAccess($actor, $companyId)) {
            throw ValidationException::withMessages([
                'company_id' => __('mail_inbox.validation.company_mismatch'),
            ]);
        }

        if ($account && (int) $account->company_id !== $companyId && $account->messages()->exists()) {
            throw ValidationException::withMessages([
                'company_id' => __('mail_inbox.messages.company_change_blocked'),
            ]);
        }

        $uniqueEmployeeIds = array_values(array_unique(array_map('intval', $employeeIds)));
        if ($uniqueEmployeeIds === []) {
            throw ValidationException::withMessages([
                'employees' => __('validation.required', ['attribute' => __('mail_inbox.fields.employees')]),
            ]);
        }

        $employees = Employee::query()
            ->when(! $actor->canAccessAllBranches() && $actor->branch_id, fn ($query) => $query->where('branch_id', $actor->branch_id))
            ->whereIn('id', $uniqueEmployeeIds)
            ->get(['id', 'company_id', 'status']);

        if ($employees->count() !== count($uniqueEmployeeIds)) {
            throw ValidationException::withMessages([
                'employees' => __('mail_inbox.validation.company_mismatch'),
            ]);
        }

        foreach ($employees as $employee) {
            if ((int) $employee->company_id !== $companyId) {
                throw ValidationException::withMessages([
                    'employees' => __('mail_inbox.validation.company_mismatch'),
                ]);
            }

            $statusValue = $employee->status instanceof \BackedEnum ? $employee->status->value : $employee->status;
            if ($statusValue !== 'active') {
                throw ValidationException::withMessages([
                    'employees' => __('mail_inbox.validation.employee_inactive'),
                ]);
            }
        }

        $this->logCreationStage('assignment_validation_completed');

        return DB::transaction(function () use ($account, $data, $uniqueEmployeeIds, $companyId): MailAccount {
            unset($data['employees']);

            if ($account) {
                if (($data['type'] ?? $account->type) !== 'outlook_oauth' && empty($data['password'])) {
                    unset($data['password']);
                }
                $account->update($data);
            } else {
                $this->logCreationStage('database_insert_started');
                $account = MailAccount::create($data);
                $this->logCreationStage('database_insert_completed');
            }

            $pivotData = [];
            foreach ($uniqueEmployeeIds as $employeeId) {
                $pivotData[$employeeId] = ['company_id' => $companyId];
            }

            $this->logCreationStage('employee_sync_started');
            $account->employees()->sync($pivotData);
            $this->logCreationStage('employee_sync_completed');

            return $account->fresh(['company', 'employees']);
        });
    }

    public function convertLegacyOutlookToOAuth(MailAccount $account, User $actor): MailAccount
    {
        Gate::forUser($actor)->authorize('update', $account);

        if ($account->isOutlookOAuth()) {
            return $account;
        }

        $account->update([
            'type' => 'outlook_oauth',
            'host' => 'outlook.office365.com',
            'port' => 993,
            'encryption' => 'ssl',
            'username' => $account->email,
            'password' => null,
            'oauth_access_token' => null,
            'oauth_refresh_token' => null,
            'oauth_token_expires_at' => null,
            'status' => MailAccountStatus::PENDING_AUTH,
            'error_message' => null,
        ]);

        return $account->fresh(['company', 'employees']);
    }

    public function findPendingMicrosoftAccount(int $companyId, string $email): ?MailAccount
    {
        return MailAccount::query()
            ->where('company_id', $companyId)
            ->whereRaw('LOWER(email) = ?', [strtolower(trim($email))])
            ->where('type', 'outlook_oauth')
            ->where('status', MailAccountStatus::PENDING_AUTH)
            ->whereNull('oauth_refresh_token')
            ->latest('id')
            ->first();
    }

    protected function logCreationStage(string $stage): void
    {
        $traceId = request()->attributes->get('mail_account_creation_trace');
        if (! $traceId) {
            return;
        }

        request()->attributes->set('mail_account_creation_stage', $stage);

        Log::info('Mail account creation service stage', [
            'trace_id' => $traceId,
            'stage' => $stage,
            'elapsed_ms' => (int) round((microtime(true) - request()->attributes->get('mail_account_creation_started', microtime(true))) * 1000),
        ]);
    }
}
