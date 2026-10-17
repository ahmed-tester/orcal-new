<?php

namespace App\Filament\Resources\EmployeeRewards\Pages;

use App\Filament\Resources\EmployeeRewards\EmployeeRewardResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditEmployeeReward extends EditRecord
{
    protected static string $resource = EmployeeRewardResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $hourly = (float)($data['hourly_rate'] ?? 0);
        $hours = (int)($data['hours_count'] ?? 0);
        $minutes = (int)($data['minutes_count'] ?? 0);

        $data['hours_amount'] = round($hourly * $hours, 2);
        $data['minute_rate'] = round($hourly / 60, 4);
        $data['minutes_amount'] = round($data['minute_rate'] * $minutes, 2);
        $data['net_amount'] = round($data['hours_amount'] + $data['minutes_amount'], 2);

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
