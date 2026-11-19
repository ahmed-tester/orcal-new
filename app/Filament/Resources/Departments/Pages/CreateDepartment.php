<?php

namespace App\Filament\Resources\Departments\Pages;

use App\Filament\Resources\Departments\DepartmentResource;
use App\Filament\Resources\Departments\Traits\HandlesDepartmentScope;
use Filament\Resources\Pages\CreateRecord;

class CreateDepartment extends CreateRecord
{
    use HandlesDepartmentScope;

    protected static string $resource = DepartmentResource::class;
}
