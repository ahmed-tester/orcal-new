<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PosDevice extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'commercial_registration_number',
        'owner_id',
        'owner_identity_number',
        'owner_phone',
        'device_number',
        'card_number',
        'bank_or_wallet_name',
        'account_number',
        'accountant_id',
        'accountant_name',
        'accountant_identity_number',
        'accountant_email',
        'accountant_phone',
        'accountant_password',
        'status',
        'notes',
    ];

    protected $hidden = [
        'accountant_password',
    ];

    protected function casts(): array
    {
        return [
            'accountant_password' => 'encrypted',
            'status'              => \App\Enums\PosDeviceStatus::class,
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (PosDevice $posDevice) {
            if ($posDevice->accountant_id) {
                $employee = $posDevice->accountant ?? Employee::with(['user', 'detail'])->find($posDevice->accountant_id);
                if ($employee) {
                    if (blank($posDevice->accountant_name)) {
                        $posDevice->accountant_name = $employee->user?->name ?? "موظف #{$employee->id}";
                    }
                    if (blank($posDevice->accountant_identity_number)) {
                        $posDevice->accountant_identity_number = $employee->detail?->national_id ?? $employee->detail?->iqama_number ?? $employee->employee_number;
                    }
                    if (blank($posDevice->accountant_phone)) {
                        $posDevice->accountant_phone = $employee->user?->phone;
                    }
                    if (blank($posDevice->accountant_email)) {
                        $posDevice->accountant_email = $employee->user?->email;
                    }
                }
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(Owner::class);
    }

    public function accountant(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'accountant_id');
    }
}
