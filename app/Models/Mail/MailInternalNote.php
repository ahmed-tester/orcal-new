<?php

namespace App\Models\Mail;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MailInternalNote extends Model
{
    use HasFactory;

    protected $table = 'internal_notes';

    protected $fillable = [
        'company_id',
        'email_id',
        'user_id',
        'content',
    ];

    protected function casts(): array
    {
        return [
            'company_id' => 'integer',
            'email_id' => 'integer',
            'user_id' => 'integer',
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
