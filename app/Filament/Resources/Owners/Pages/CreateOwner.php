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
        $user = User::create([
            'name' => $data['user_name'] ?? 'Owner',
            'email' => $data['user_email'] ?? ('owner_' . uniqid() . '@company.com'),
            'phone' => PhoneService::normalize($data['user_phone'] ?? null),
            'password' => !empty($data['user_password']) ? Hash::make($data['user_password']) : Hash::make('password'),
            'user_type' => UserType::OWNER,
            'status' => 'active',
        ]);

        $data['user_id'] = $user->id;

        return $data;
    }
}
