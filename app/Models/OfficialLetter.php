<?php

namespace App\Models;

use App\Observers\OfficialLetterObserver;
use App\Traits\HasHijriDates;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ObservedBy([OfficialLetterObserver::class])]
class OfficialLetter extends Model
{
    use HasFactory, HasHijriDates;

    public function getHijriDateFieldNames(): array
    {
        return ['letter_date'];
    }

    protected $fillable = [
        'company_id',
        'letter_number',
        'subject',
        'letter_date',
        'body',
        'status',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'letter_date' => 'date',
            'metadata'    => 'array',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
