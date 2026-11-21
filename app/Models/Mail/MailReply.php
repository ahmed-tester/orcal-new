<?php

namespace App\Models\Mail;

use App\Enums\Mail\MailReplyStatus;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MailReply extends Model
{
    use HasFactory;

    protected $table = 'replies';

    protected $fillable = [
        'company_id',
        'email_id',
        'email_account_id',
        'user_id',
        'to_email',
        'subject',
        'body',
        'sent_message_id',
        'status',
        'error_message',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'company_id' => 'integer',
            'email_id' => 'integer',
            'email_account_id' => 'integer',
            'user_id' => 'integer',
            'status' => MailReplyStatus::class,
            'sent_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(MailMessage::class, 'email_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(MailAccount::class, 'email_account_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(MailAttachment::class, 'reply_id');
    }
}
