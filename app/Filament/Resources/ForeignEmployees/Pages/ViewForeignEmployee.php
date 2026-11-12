<?php

namespace App\Filament\Resources\ForeignEmployees\Pages;

use App\Filament\Pages\ViewEmployeeProfile;
use App\Filament\Resources\ForeignEmployees\ForeignEmployeeResource;

class ViewForeignEmployee extends ViewEmployeeProfile
{
    protected static string $resource = ForeignEmployeeResource::class;
}
