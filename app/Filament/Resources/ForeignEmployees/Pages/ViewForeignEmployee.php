<?php

namespace App\Filament\Resources\ForeignEmployees\Pages;

use App\Filament\Pages\ViewEmployeeProfile;
use App\Filament\Resources\ForeignEmployees\ForeignEmployeeResource;
use App\Services\EmployeePerformanceAccessService;

if (! class_exists(ViewEmployeeProfile::class) || ! class_exists(EmployeePerformanceAccessService::class)) {
    return;
}

class ViewForeignEmployee extends ViewEmployeeProfile
{
    protected static string $resource = ForeignEmployeeResource::class;
}
