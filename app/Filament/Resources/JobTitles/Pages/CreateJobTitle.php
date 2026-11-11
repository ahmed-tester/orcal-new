<?php

namespace App\Filament\Resources\JobTitles\Pages;

use App\Filament\Resources\JobTitles\JobTitleResource;
use App\Services\JobTitleService;
use Filament\Resources\Pages\CreateRecord;

class CreateJobTitle extends CreateRecord
{
    protected static string $resource = JobTitleResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return app(JobTitleService::class)->prepareFormData($data, $this->form->getRawState());
    }
}
