<?php

namespace App\Models;


use App\Traits\HasHijriDates;
use App\Enums\WeeklyNoteStatus;
use App\Observers\WeeklyNoteObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ObservedBy([WeeklyNoteObserver::class])]
class WeeklyNote extends Model
{
    use HasFactory, HasHijriDates;

    public function getHijriDateFieldNames(): array
    {
        return [
            'note_date',
        ];
    }


    protected $fillable = [
        'voucher_number',
        'note_date',
        'company_id',
        'title',
        'status',
        'content',
        'assigned_to',
        'created_by',
        'metadata',
    ];

    protected $casts = [
        'note_date' => 'date',
        'status' => WeeklyNoteStatus::class,
        'metadata' => 'array',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function assignedEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'assigned_to');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
