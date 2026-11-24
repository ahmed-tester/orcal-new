<?php

namespace App\Filament\Resources\Settings\Pages;

use App\Filament\Resources\Settings\SettingResource;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Concerns\RedirectsToIndex;

class CreateSetting extends CreateRecord
{
    use RedirectsToIndex;

    protected static string $resource = SettingResource::class;
}
