<?php

namespace App\Filament\Pages\Auth;

use App\Models\User;
use App\Services\PhoneService;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Illuminate\Validation\ValidationException;

class Login extends BaseLogin
{
    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('login')
            ->label(__('auth.login_label'))
            ->placeholder(__('auth.login_placeholder'))
            ->required()
            ->autocomplete('username')
            ->autofocus();
    }

    protected function throwFailureValidationException(): never
    {
        throw ValidationException::withMessages([
            'data.login' => __('filament-panels::auth/pages/login.messages.failed'),
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function getCredentialsFromFormData(array $data): array
    {
        $login = trim((string) ($data['login'] ?? $data['email'] ?? ''));
        $password = (string) ($data['password'] ?? '');

        // 1. Check by email
        if (filter_var($login, FILTER_VALIDATE_EMAIL)) {
            $user = User::whereRaw('LOWER(email) = ?', [strtolower($login)])->first();
            if ($user && filled($user->email)) {
                return [
                    'email' => $user->email,
                    'password' => $password,
                ];
            }
        }

        // 2. Check by phone number (support local 05xxxxxxxx, international +966, clean digits)
        $normalizedPhone = PhoneService::normalize($login);
        $cleanedPhone = PhoneService::clean($login);

        $user = User::query()
            ->where('email', $login)
            ->orWhere('phone', $login)
            ->when($normalizedPhone, fn ($q) => $q->orWhere('phone', $normalizedPhone))
            ->when($cleanedPhone, fn ($q) => $q->orWhere('phone', $cleanedPhone))
            ->first();

        if ($user) {
            if (filled($user->email)) {
                return [
                    'email' => $user->email,
                    'password' => $password,
                ];
            }

            return [
                'phone' => $user->phone,
                'password' => $password,
            ];
        }

        // 3. Fallback to standard email credentials
        return [
            'email' => $login,
            'password' => $password,
        ];
    }
}
