<?php

namespace App\Models;

use App\Traits\HasHijriDates;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeDetail extends Model
{
    use HasFactory, HasHijriDates;

    public function getHijriDateFieldNames(): array
    {
        return [
            'iqama_expiry',
            'insurance_expiry',
            'work_permit_expiry',
            'last_leave_return_date',
            'passport_expiry',
            'birth_date',
        ];
    }

    protected $fillable = [
        'employee_id',
        'nationality',
        'national_id',
        'birth_date',
        'gender',
        'marital_status',
        'address',
        'city',
        'country',
        'emergency_contact_name',
        'emergency_contact_phone',
        'iqama_number',
        'iqama_expiry',
        'insurance_expiry',
        'work_permit_expiry',
        'work_permit_number',
        'sponsor_name',
        'guarantor_id',
        'last_leave_return_date',
        'passport_number',
        'passport_expiry',
        'iqama_file',
        'passport_file',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'iqama_expiry' => 'date',
            'insurance_expiry' => 'date',
            'work_permit_expiry' => 'date',
            'last_leave_return_date' => 'date',
            'passport_expiry' => 'date',
            'gender' => \App\Enums\Gender::class,
            'marital_status' => \App\Enums\MaritalStatus::class,
            'metadata' => 'array',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function guarantor(): BelongsTo
    {
        return $this->belongsTo(Guarantor::class);
    }
}
