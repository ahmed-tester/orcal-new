<?php

namespace App\Filament\Resources\Settings\Pages;

use App\Filament\Resources\Settings\SettingResource;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Concerns\RedirectsToIndex;

class EditSetting extends EditRecord
{
    use RedirectsToIndex;

    protected static string $resource = SettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // طھظ… ط¥ظ„ط؛ط§ط، ط²ط± ط§ظ„ط­ط°ظپ ظ„ط­ظ…ط§ظٹط© ط¥ط¹ط¯ط§ط¯ط§طھ ظˆظ…ظپط§طھظٹط­ ط§ظ„ظ†ط¸ط§ظ…
        ];
    }
}
