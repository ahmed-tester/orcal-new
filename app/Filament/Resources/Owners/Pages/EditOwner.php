<?php

namespace App\Filament\Resources\Owners\Pages;

use App\Filament\Resources\Owners\OwnerResource;
use App\Services\PhoneService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Hash;

class EditOwner extends EditRecord
{
    protected static string $resource = OwnerResource::class;

    protected string $view = 'filament.resources.owners.pages.edit-owner';

    public function getTitle(): string
    {
        return 'تعديل بيانات المالك';
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\DeleteAction::make()->label(__('employees.actions.delete')),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $owner = $this->record;
        $user = $owner->user;

        if ($user) {
            $data['user_name'] = $user->name;
            $data['user_email'] = $user->email;
            $data['user_phone'] = $user->phone;
        }

        return $data;
    }

    protected function afterSave(): void
    {
        $data = $this->form->getRawState();
        $owner = $this->record;

        if ($owner->user) {
            $userUpdate = [
                'name' => $data['user_name'] ?? $owner->user->name,
                'email' => !empty($data['user_email']) ? $data['user_email'] : $owner->user->email,
                'phone' => PhoneService::normalize($data['user_phone'] ?? $owner->user->phone),
            ];

            if (!empty($data['user_password'])) {
                $userUpdate['password'] = Hash::make($data['user_password']);
            }

            $owner->user->update($userUpdate);
        }
    }

    public function deleteRecord(): void
    {
        $this->record->delete();

        Notification::make()
            ->title('تم حذف المالك بنجاح')
            ->success()
            ->send();

        $this->redirect(OwnerResource::getUrl('index'));
    }
}

