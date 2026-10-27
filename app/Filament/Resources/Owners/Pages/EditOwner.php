<?php

namespace App\Filament\Resources\Owners\Pages;

use App\Enums\UserType;
use App\Filament\Resources\Owners\OwnerResource;
use App\Models\User;
use App\Services\PhoneService;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class EditOwner extends EditRecord
{
    protected static string $resource = OwnerResource::class;

    protected string $view = 'filament.resources.owners.pages.edit-owner';

    protected array $pendingUserData = [];

    public function getTitle(): string
    {
        return __('owners.edit_title');
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()->label(__('employees.actions.delete')),
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

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $owner = $this->record;
        $userId = $owner->user_id;

        if (! empty($data['user_email'])) {
            $email = trim($data['user_email']);
            if (User::where('email', $email)->where('id', '!=', $userId)->exists()) {
                throw ValidationException::withMessages([
                    'user_email' => __('employees.fields.errors.email_exists'),
                ]);
            }
        }

        if (! empty($data['user_phone'])) {
            $normalizedPhone = PhoneService::normalize($data['user_phone']);
            if ($normalizedPhone && User::where('phone', $normalizedPhone)->where('id', '!=', $userId)->exists()) {
                throw ValidationException::withMessages([
                    'user_phone' => __('validation.unique', ['attribute' => __('owners.fields.phone')]),
                ]);
            }
        }

        $this->pendingUserData = [
            'name' => $data['user_name'] ?? null,
            'email' => $data['user_email'] ?? null,
            'phone' => $data['user_phone'] ?? null,
            'password' => $data['user_password'] ?? null,
        ];

        return $data;
    }

    protected function afterSave(): void
    {
        $owner = $this->record->fresh();
        $pending = $this->pendingUserData;

        $directUser = $owner->user_id ? User::find($owner->user_id) : null;
        $user = $owner->user ?? $directUser;

        if (! empty($pending)) {
            if (! $user) {
                $email = ! empty($pending['email']) ? trim($pending['email']) : ('owner_'.uniqid().'@company.com');
                $user = User::create([
                    'name' => $pending['name'] ?: 'Owner',
                    'email' => $email,
                    'phone' => PhoneService::normalize($pending['phone'] ?? null),
                    'password' => ! empty($pending['password']) ? Hash::make($pending['password']) : Hash::make('password'),
                    'user_type' => UserType::OWNER,
                    'status' => 'active',
                ]);

                $owner->user_id = $user->id;
                $owner->saveQuietly();
            } else {
                $userUpdate = [
                    'name' => $pending['name'] ?? $user->name,
                    'email' => ! empty($pending['email']) ? $pending['email'] : $user->email,
                    'phone' => PhoneService::normalize($pending['phone'] ?? $user->phone),
                ];

                if (! empty($pending['password'])) {
                    $userUpdate['password'] = Hash::make($pending['password']);
                }

                $user->update($userUpdate);
            }
        }

        $this->pendingUserData = [];
    }

    public function deleteRecord(): void
    {
        $this->record->delete();

        Notification::make()
            ->title(__('owners.deleted_successfully'))
            ->success()
            ->send();

        $this->redirect(OwnerResource::getUrl('index'));
    }
}
