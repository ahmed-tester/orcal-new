<?php

namespace App\Models;

use App\Enums\UserType;
use Illuminate\Database\Eloquent\Builder;

class SaudiEmployee extends Employee
{
    protected $table = 'employees';

    protected static function booted(): void
    {
        parent::booted();

        static::addGlobalScope('saudi', function (Builder $builder) {
            $builder->whereHas('user', fn ($query) => $query->whereIn('user_type', [
                UserType::SAUDI_EMPLOYEE,
                UserType::OWNER,
            ]));
        });
    }
}
