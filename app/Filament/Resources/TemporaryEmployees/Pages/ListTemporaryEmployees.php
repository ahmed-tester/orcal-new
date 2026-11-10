<?php

namespace App\Filament\Resources\TemporaryEmployees\Pages;

use App\Filament\Resources\TemporaryEmployees\TemporaryEmployeeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTemporaryEmployees extends ListRecords
{
    protected static string $resource = TemporaryEmployeeResource::class;

    public function getTitle(): string
    {
        return __('navigation.temporary_employees');
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('إضافة موظف مؤقت')
                ->icon('heroicon-o-user-plus')
                ->color('success'),
        ];
    }
}

