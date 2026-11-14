<?php

namespace App\Filament\Resources\SaudiEmployees\Pages;

use App\Filament\Pages\ViewEmployeeProfile;
use App\Filament\Resources\SaudiEmployees\SaudiEmployeeResource;
use App\Services\EmployeePerformanceAccessService;

if (! class_exists(ViewEmployeeProfile::class) || ! class_exists(EmployeePerformanceAccessService::class)) {
    return;
}

class ViewSaudiEmployee extends ViewEmployeeProfile
{
    protected static string $resource = SaudiEmployeeResource::class;
}
