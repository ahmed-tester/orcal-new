<?php

namespace App\Filament\Resources\JobTitles\Pages;

use App\Filament\Resources\JobTitles\JobTitleResource;
use App\Services\JobTitleService;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditJobTitle extends EditRecord
{
    protected static string $resource = JobTitleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return app(JobTitleService::class)->prepareFormData($data, $this->form->getRawState());
    }
}
