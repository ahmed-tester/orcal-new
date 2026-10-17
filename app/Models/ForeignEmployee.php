<?php

namespace App\Models;

use App\Enums\UserType;
use Illuminate\Database\Eloquent\Builder;

class ForeignEmployee extends Employee
{
    protected $table = 'employees';

    protected static function booted(): void
    {
        parent::booted();

        static::addGlobalScope('foreign', function (Builder $builder) {
            $builder->whereHas('user', fn ($query) => $query->where('user_type', UserType::FOREIGN_EMPLOYEE));
        });
    }
}
