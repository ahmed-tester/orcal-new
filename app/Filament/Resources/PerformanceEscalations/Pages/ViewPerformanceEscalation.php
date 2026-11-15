<?php

namespace App\Filament\Resources\PerformanceEscalations\Pages;

use App\Filament\Actions\PerformanceEscalationActions;
use App\Filament\Resources\PerformanceEscalations\PerformanceEscalationResource;
use Filament\Resources\Pages\ViewRecord;

class ViewPerformanceEscalation extends ViewRecord
{
    protected static string $resource = PerformanceEscalationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            PerformanceEscalationActions::recordManagementAction()
                ->record($this->getRecord()),
        ];
    }
}
