<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Filament\Resources\Users\Pages\CreateUser;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Grid::make(['default' => 1, 'lg' => 2])
                    ->schema([
                        // 1. معلومات الحساب الأساسية
                        Section::make(__('admin.account_information'))
                            ->schema([
                                TextInput::make('name')
                                    ->label(__('admin.name'))
                                    ->required()
                                    ->maxLength(255),

                                TextInput::make('email')
                                    ->label(__('admin.email'))
                                    ->email()
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->maxLength(255),

                                TextInput::make('phone')
                                    ->label(__('admin.phone'))
                                    ->tel()
                                    ->maxLength(50),

                                FileUpload::make('avatar_url')
                                    ->label(__('admin.avatar'))
                                    ->image()
                                    ->avatar()
                                    ->directory('avatars')
                                    ->maxSize(\App\Models\Setting::getMaxUploadSizeKb())
                                    ->alignCenter(),

                                Toggle::make('status')
                                    ->label(__('admin.status'))
                                    ->onColor('success')
                                    ->offColor('danger')
                                    ->formatStateUsing(fn ($state) => $state === 'active' || $state === true || $state === 1 || $state === null)
                                    ->dehydrateStateUsing(fn ($state) => ($state === true || $state === 'active' || $state === 1) ? 'active' : 'inactive')
                                    ->default(true),
                            ]),

                        // 2. الأمان وكلمة المرور
                        Section::make(__('admin.security_and_password'))
                            ->schema([
                                TextInput::make('password')
                                    ->label(__('admin.password'))
                                    ->password()
                                    ->revealable()
                                    ->required(fn ($livewire) => $livewire instanceof CreateUser)
                                    ->dehydrated(fn ($state) => filled($state))
                                    ->same('password_confirmation')
                                    ->maxLength(255),

                                TextInput::make('password_confirmation')
                                    ->label(__('admin.password_confirmation'))
                                    ->password()
                                    ->revealable()
                                    ->required(fn ($livewire) => $livewire instanceof CreateUser)
                                    ->dehydrated(false)
                                    ->maxLength(255),
                            ]),
                    ]),

                // 3. تعيين الأدوار والصلاحيات
                Section::make(__('admin.roles_and_permissions_assignment'))
                    ->schema([
                        Select::make('roles')
                            ->label(__('admin.roles_and_permissions'))
                            ->relationship('roles', 'name', fn ($query) => $query->where('name', '!=', 'Super Admin'))
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->placeholder(__('admin.roles_placeholder')),
                    ]),
            ]);
    }
}
