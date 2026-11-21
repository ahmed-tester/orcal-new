<?php

namespace App\Models\Mail;

use App\Models\Company;
use App\Observers\MailRuleObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ObservedBy([MailRuleObserver::class])]
class MailExclusionKeyword extends Model
{
    use HasFactory;

    protected $table = 'exclusion_keywords';

    protected $fillable = [
        'company_id',
        'keyword',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'company_id' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
