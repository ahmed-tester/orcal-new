<?php

namespace App\Models;

use App\Traits\HasHijriDates;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    protected static function booted(): void
    {
        static::creating(function (OfficialLetter $letter) {
            if (empty($letter->letter_number)) {
                $letter->letter_number = \App\Services\CodeGeneratorService::generate(
                    modelClass: static::class,
                    column:     'letter_number',
                    prefix:     'LTR',
                );
            }

            foreach ($letter->getAttributes() as $key => $value) {
                if ($value === '') {
                    $letter->{$key} = null;
                }
            }
        });

        static::updating(function (OfficialLetter $letter) {
            foreach ($letter->getAttributes() as $key => $value) {
                if ($value === '') {
                    $letter->{$key} = null;
                }
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
