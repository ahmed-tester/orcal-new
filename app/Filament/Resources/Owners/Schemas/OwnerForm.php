<?php

namespace App\Filament\Resources\Owners\Schemas;

use App\Filament\Resources\Owners\Pages\CreateOwner;
use App\Filament\Resources\Owners\Pages\EditOwner;
use App\Services\CompanyFormService;
use App\Services\PhoneService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OwnerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('بيانات المالك الأساسية')
                    ->columns(3)
                    ->schema([
                        TextInput::make('user_name')
                            ->label('اسم المالك')
                            ->required(),

                        TextInput::make('user_email')
                            ->label(__('owners.fields.email'))
                            ->email()
                            ->nullable(),

                        PhoneService::makeFormField('user_phone', __('owners.fields.phone')),

                        TextInput::make('user_password')
                            ->label(__('owners.fields.password'))
                            ->password()
                            ->revealable()
                            ->required(fn ($livewire) => $livewire instanceof CreateOwner)
                            ->helperText(fn ($livewire) => $livewire instanceof EditOwner ? __('owners.fields.password_help') : null),

                        CompanyFormService::makeCompanySelect('company_id', 'المنشأة / الشركة')
                            ->required(),

                        TextInput::make('national_id')
                            ->label('رقم الهوية الوطنية / الإقامة'),

                        TextInput::make('nationality')
                            ->label('الجنسية'),

                        DatePicker::make('birth_date')
                            ->label('تاريخ الميلاد')
                            ->native(false),

                        TextInput::make('ownership_percentage')
                            ->label(__('owners.fields.ownership_percentage'))
                            ->numeric()
                            ->suffix('%')
                            ->default(100),

                        DatePicker::make('employment_date')
                            ->label('تاريخ بداية الملكية / التعيين')
                            ->native(false)
                            ->required(),

                        DatePicker::make('termination_date')
                            ->label('تاريخ الانقطاع / النهاية')
                            ->native(false)
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if (!empty($state)) {
                                    $set('status', 'inactive');
                                }
                            }),

                        Select::make('status')
                            ->label(__('owners.fields.status'))
                            ->options([
                                'active' => 'نشط',
                                'inactive' => 'غير نشط',
                            ])
                            ->default('active'),

                        FileUpload::make('image')
                            ->label('صورة الهوية / المالك')
                            ->directory('owners/images')
                            ->image()
                            ->maxSize(\App\Models\Setting::getMaxUploadSizeKb())
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
