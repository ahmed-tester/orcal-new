<?php

namespace App\Models\Mail;

use App\Enums\Mail\MailAccountStatus;
use App\Models\Company;
use App\Models\Employee;
use App\Services\Mail\MailNotificationService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MailAccount extends Model
{
    use HasFactory;

    protected $table = 'email_accounts';

    protected $hidden = ['password', 'oauth_access_token', 'oauth_refresh_token'];

    protected $fillable = [
        'company_id',
        'name',
        'email',
        'type',
        'host',
        'port',
        'encryption',
        'username',
        'password',
        'oauth_access_token',
        'oauth_refresh_token',
        'oauth_token_expires_at',
        'status',
        'last_sync_at',
        'last_message_uid',
        'inbox_uid_validity',
        'last_spam_message_uid',
        'spam_uid_validity',
        'last_history_id',
        'sync_interval_minutes',
        'is_active',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'company_id' => 'integer',
            'password' => 'encrypted',
            'oauth_access_token' => 'encrypted',
            'oauth_refresh_token' => 'encrypted',
            'oauth_token_expires_at' => 'datetime',
            'status' => MailAccountStatus::class,
            'last_sync_at' => 'datetime',
            'last_message_uid' => 'integer',
            'inbox_uid_validity' => 'integer',
            'last_spam_message_uid' => 'integer',
            'spam_uid_validity' => 'integer',
            'sync_interval_minutes' => 'integer',
            'is_active' => 'boolean',
            'port' => 'integer',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function employees(): BelongsToMany
    {
        return $this->belongsToMany(
            Employee::class,
            'email_account_employees',
            'email_account_id',
            'employee_id'
        )->withPivot('company_id')->withTimestamps();
    }

    public function messages(): HasMany
    {
        return $this->hasMany(MailMessage::class, 'email_account_id');
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(MailConversation::class, 'email_account_id');
    }

    public function syncLogs(): HasMany
    {
        return $this->hasMany(MailSyncLog::class, 'email_account_id');
    }

    public function isOAuth(): bool
    {
        return in_array($this->type, ['gmail_oauth', 'outlook_oauth'], true);
    }

    protected static function booted(): void
    {
        static::deleting(function (MailAccount $account): void {
            app(MailNotificationService::class)->deleteAccountNotifications($account);
        });
    }
}
