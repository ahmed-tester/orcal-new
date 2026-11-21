<?php

namespace App\Models\Mail;

use App\Enums\Mail\MailSyncStatus;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MailSyncLog extends Model
{
    use HasFactory;

    protected $table = 'sync_logs';

    protected $fillable = [
        'company_id',
        'email_account_id',
        'folder',
        'status',
        'messages_found',
        'messages_imported',
        'messages_rejected',
        'error_message',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'company_id' => 'integer',
            'email_account_id' => 'integer',
            'status' => MailSyncStatus::class,
            'messages_found' => 'integer',
            'messages_imported' => 'integer',
            'messages_rejected' => 'integer',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(MailAccount::class, 'email_account_id');
    }
}
