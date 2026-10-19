<?php

namespace App\Filament\Resources\Owners\Pages;

use App\Enums\UserType;
use App\Filament\Resources\Owners\OwnerResource;
use App\Models\User;
use App\Services\PhoneService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Hash;

class CreateOwner extends CreateRecord
{
    protected static string $resource = OwnerResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $normalizedPhone = PhoneService::normalize($data['user_phone'] ?? null);
        $email = !empty($data['user_email']) ? trim($data['user_email']) : ('owner_' . uniqid() . '@company.com');

        if (User::where('email', $email)->exists()) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'user_email' => __('employees.fields.errors.email_exists'),
            ]);
        }

        if ($normalizedPhone && User::where('phone', $normalizedPhone)->exists()) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'user_phone' => __('validation.unique', ['attribute' => __('owners.fields.phone')]),
            ]);
        }

        $user = User::create([
            'name' => $data['user_name'] ?? 'Owner',
            'email' => $email,
            'phone' => $normalizedPhone,
            'password' => !empty($data['user_password']) ? Hash::make($data['user_password']) : Hash::make('password'),
            'user_type' => UserType::OWNER,
            'status' => 'active',
        ]);

        $data['user_id'] = $user->id;

        return $data;
    }
}
