<?php

namespace App\Services\Mail;

use App\Models\Mail\MailAccount;
use App\Models\Mail\MailAttachment;
use App\Models\Mail\MailConversation;
use App\Models\Mail\MailMessage;
use App\Models\User;
use App\Services\CompanyFormService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class MailAccessService
{
    public const PERM_VIEW_INBOX = 'view_mail_inbox';

    public const PERM_REPLY = 'reply_mail';

    public const PERM_MANAGE_RULES = 'manage_mail_rules';

    public const PERM_MANAGE_ACCOUNTS = 'manage_mail_accounts';

    public const PERM_RUN_SYNC = 'run_mail_sync';

    public const PERM_REVIEW = 'review_mail';

    public const PERM_DELETE = 'delete_mail';

    public static function canAdministerAllMail(User $user): bool
    {
        return $user->status === 'active' && $user->isSuperAdmin();
    }

    /**
     * Scope mail accounts to those authorized by company and active employee assignment.
     */
    public static function scopeAccounts(Builder $query, ?User $user): Builder
    {
        if (! $user || $user->status !== 'active') {
            return $query->whereRaw('1 = 0');
        }

        if (static::canAdministerAllMail($user)) {
            return $query;
        }

        $companyIds = array_map('intval', CompanyFormService::getAuthorizedCompanyIds($user));
        if ($companyIds === []) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->can(self::PERM_MANAGE_ACCOUNTS)) {
            return $query->whereIn('email_accounts.company_id', $companyIds);
        }

        return $query
            ->whereIn('email_accounts.company_id', $companyIds)
            ->whereExists(function ($sub) use ($user) {
                $sub->select(DB::raw(1))
                    ->from('email_account_employees')
                    ->join('employees', 'employees.id', '=', 'email_account_employees.employee_id')
                    ->whereColumn('email_account_employees.email_account_id', 'email_accounts.id')
                    ->whereColumn('email_account_employees.company_id', 'email_accounts.company_id')
                    ->whereColumn('employees.company_id', 'email_accounts.company_id')
                    ->where('employees.user_id', $user->id)
                    ->where('employees.status', 'active')
                    ->whereNull('employees.deleted_at');
            });
    }

    /**
     * Scope account-owned records (messages, conversations, sync logs) by company and active assignment.
     */
    public static function scopeRecords(Builder $query, ?User $user, string $table): Builder
    {
        if (! $user || $user->status !== 'active') {
            return $query->whereRaw('1 = 0');
        }

        if (static::canAdministerAllMail($user)) {
            return $query;
        }

        $companyIds = array_map('intval', CompanyFormService::getAuthorizedCompanyIds($user));
        if ($companyIds === []) {
            return $query->whereRaw('1 = 0');
        }

        return $query
            ->whereIn("{$table}.company_id", $companyIds)
            ->whereExists(function ($sub) use ($user, $table) {
                $sub->select(DB::raw(1))
                    ->from('email_account_employees')
                    ->join('employees', 'employees.id', '=', 'email_account_employees.employee_id')
                    ->whereColumn('email_account_employees.email_account_id', "{$table}.email_account_id")
                    ->whereColumn('email_account_employees.company_id', "{$table}.company_id")
                    ->whereColumn('employees.company_id', "{$table}.company_id")
                    ->where('employees.user_id', $user->id)
                    ->where('employees.status', 'active')
                    ->whereNull('employees.deleted_at');
            });
    }

    public static function isAssignedToAccount(User $user, int $accountId, int $companyId): bool
    {
        if ($user->status !== 'active') {
            return false;
        }

        $authorizedCompanyIds = array_map('intval', CompanyFormService::getAuthorizedCompanyIds($user));
        if (! in_array($companyId, $authorizedCompanyIds, true)) {
            return false;
        }

        return DB::table('email_account_employees')
            ->join('employees', 'employees.id', '=', 'email_account_employees.employee_id')
            ->where('email_account_employees.email_account_id', $accountId)
            ->where('email_account_employees.company_id', $companyId)
            ->where('employees.company_id', $companyId)
            ->where('employees.user_id', $user->id)
            ->where('employees.status', 'active')
            ->whereNull('employees.deleted_at')
            ->exists();
    }

    public static function canViewAccount(User $user, MailAccount $account): bool
    {
        if (static::canAdministerAllMail($user)) {
            return true;
        }

        return static::isAssignedToAccount($user, (int) $account->id, (int) $account->company_id);
    }

    public static function canViewMessage(User $user, MailMessage $message): bool
    {
        if (static::canAdministerAllMail($user)) {
            return true;
        }

        if (! $user->can(self::PERM_VIEW_INBOX)) {
            return false;
        }

        return static::isAssignedToAccount($user, (int) $message->email_account_id, (int) $message->company_id);
    }

    public static function canViewConversation(User $user, MailConversation $conversation): bool
    {
        if (static::canAdministerAllMail($user)) {
            return true;
        }

        if (! $user->can(self::PERM_VIEW_INBOX)) {
            return false;
        }

        return static::isAssignedToAccount($user, (int) $conversation->email_account_id, (int) $conversation->company_id);
    }

    public static function canViewAttachment(User $user, MailAttachment $attachment): bool
    {
        if (static::canAdministerAllMail($user)) {
            return true;
        }

        if (! $user->can(self::PERM_VIEW_INBOX)) {
            return false;
        }

        if ($attachment->email_id && $attachment->message) {
            return static::canViewMessage($user, $attachment->message);
        }

        if ($attachment->reply_id && $attachment->reply?->message) {
            return static::canViewMessage($user, $attachment->reply->message);
        }

        return false;
    }

    public static function hasCompanyAccess(User $user, ?int $companyId): bool
    {
        if ($user->status !== 'active') {
            return false;
        }

        if (static::canAdministerAllMail($user)) {
            return true;
        }

        if ($companyId === null) {
            return false;
        }

        $authorizedCompanyIds = array_map('intval', CompanyFormService::getAuthorizedCompanyIds($user));

        return in_array($companyId, $authorizedCompanyIds, true);
    }
}
