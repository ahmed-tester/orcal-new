<?php

namespace App\Models\Mail;

use App\Enums\Mail\MailConversationStatus;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MailConversation extends Model
{
    use HasFactory;

    protected $table = 'conversations';

    protected $fillable = [
        'company_id',
        'email_account_id',
        'root_message_id',
        'subject',
        'participant_email',
        'status',
        'last_message_at',
    ];

    protected function casts(): array
    {
        return [
            'company_id' => 'integer',
            'email_account_id' => 'integer',
            'status' => MailConversationStatus::class,
            'last_message_at' => 'datetime',
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

    public function messages(): HasMany
    {
        return $this->hasMany(MailMessage::class, 'conversation_id')->orderBy('received_at', 'asc');
    }
}
