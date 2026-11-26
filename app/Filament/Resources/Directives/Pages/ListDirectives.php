<?php

namespace App\Filament\Resources\Directives\Pages;

use App\Filament\Resources\Directives\DirectiveResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDirectives extends ListRecords
{
    protected static string $resource = DirectiveResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
