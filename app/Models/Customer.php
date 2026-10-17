<?php

namespace App\Models;

use App\Enums\CustomerType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_type',
        'name',
        'national_id',
        'commercial_registration_number',
        'tax_number',
        'phone',
        'email',
        'address',
        'city',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'customer_type' => CustomerType::class,
            'status' => \App\Enums\GeneralStatus::class,
        ];
    }

    public function invoices(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function offers(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Offer::class);
    }
}
