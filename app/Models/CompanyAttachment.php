<?php

namespace App\Models;


use App\Traits\HasHijriDates;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyAttachment extends Model
{
    use HasFactory, HasHijriDates;

    public function getHijriDateFieldNames(): array
    {
        return [
            'start_date',
            'end_date',
        ];
    }


    protected $fillable = [
        'company_id',
        'attachment_type',
        'title',
        'document_number',
        'start_date',
        'end_date',
        'file_path',
        'notes',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'metadata' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (CompanyAttachment $attachment) {
            if ($attachment->file_path === null) {
                $attachment->file_path = '';
            }
        });

        static::updating(function (CompanyAttachment $attachment) {
            if ($attachment->file_path === null) {
                $attachment->file_path = '';
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
