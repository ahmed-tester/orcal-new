<?php

namespace App\Filament\Resources\PerformanceEscalations\Pages;

use App\Filament\Resources\PerformanceEscalations\PerformanceEscalationResource;
use Filament\Resources\Pages\ListRecords;

class ListPerformanceEscalations extends ListRecords
{
    protected static string $resource = PerformanceEscalationResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
