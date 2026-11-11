<?php

namespace App\Models;

use App\Enums\EmploymentType;
use Illuminate\Database\Eloquent\Builder;

class TemporaryEmployee extends Employee
{
    protected $table = 'employees';

    protected static function booted(): void
    {
        parent::booted();

        static::addGlobalScope('temporary', function (Builder $builder) {
            $builder->where('employment_type', EmploymentType::TEMPORARY->value);
        });
    }
}

