<?php

namespace App\Filament\Resources\Violations\Schemas;

use App\Enums\ViolationPaymentStatus;
use App\Enums\ViolationStage;
use App\Models\Company;
use App\Models\ViolationType;
use App\Services\CompanyFormService;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ViolationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                // 1. Company Information Section
                Section::make(__('violations.violations.sections.company_info'))
                    ->description(__('violations.violations.sections.company_info_desc'))
                    ->columns(4)
                    ->schema([
                        CompanyFormService::makeCompanySelect('company_id', __('violations.violations.fields.company'), true),

                        Placeholder::make('company_cr')
                            ->label(__('violations.violations.fields.commercial_registration'))
                            ->content(function (callable $get) {
                                $companyId = $get('company_id');
                                if (!$companyId) {
                                    return '-';
                                }
                                $company = Company::find($companyId);
                                return $company?->commercial_registration_number ?? '-';
                            }),

                        Placeholder::make('company_phone_display')
                            ->label(__('violations.violations.fields.company_phone'))
                            ->content(function (callable $get) {
                                $companyId = $get('company_id');
                                if (!$companyId) {
                                    return '-';
                                }
                                $company = Company::find($companyId);
                                return $company?->phone ?? '-';
                            }),

                        Placeholder::make('company_address_display')
                            ->label(__('violations.violations.fields.company_address'))
                            ->content(function (callable $get) {
                                $companyId = $get('company_id');
                                if (!$companyId) {
                                    return '-';
                                }
                                $company = Company::find($companyId);
                                return $company?->address ?? '-';
                            }),
                    ]),

                // 2. Violation Details Section
                Section::make(__('violations.violations.sections.violation_info'))
                    ->description(__('violations.violations.sections.violation_info_desc'))
                    ->columns(3)
                    ->schema([
                        Select::make('violation_category_id')
                            ->label(__('violations.violations.fields.category'))
                            ->relationship('category', 'name')
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(fn (callable $set) => $set('violation_type_id', null))
                            ->required(),

                        Select::make('violation_type_id')
                            ->label(__('violations.violations.fields.type'))
                            ->options(function (callable $get) {
                                $categoryId = $get('violation_category_id');
                                if (!$categoryId) {
                                    return [];
                                }
                                return ViolationType::query()
                                    ->where('violation_category_id', $categoryId)
                                    ->where('is_active', true)
                                    ->pluck('name', 'id');
                            })
                            ->searchable()
                            ->preload()
                            ->required(),

                        TextInput::make('external_violation_number')
                            ->label(__('violations.violations.fields.external_violation_number'))
                            ->placeholder(__('violations.violations.placeholders.external_number')),

                        DatePicker::make('violation_date')
                            ->label(__('violations.violations.fields.violation_date'))
                            ->default(now())
                            ->native(false)
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state) {
                                    try {
                                        $date = Carbon::parse($state);
                                        $set('correction_deadline', $date->copy()->addDays(14)->format('Y-m-d'));
                                        $set('objection_deadline', $date->copy()->addDays(60)->format('Y-m-d'));
                                        $set('grace_deadline', $date->copy()->addDays(90)->format('Y-m-d'));
                                        $set('legal_deadline', $date->copy()->addDays(60)->format('Y-m-d'));
                                    } catch (\Throwable $e) {
                                    }
                                }
                            }),

                        TextInput::make('issuing_authority')
                            ->label(__('violations.violations.fields.issuing_authority'))
                            ->placeholder(__('violations.violations.placeholders.issuing_authority'))
                            ->required(),

                        TextInput::make('violation_amount')
                            ->label(__('violations.violations.fields.violation_amount'))
                            ->numeric()
                            ->prefix('SAR')
                            ->default(0)
                            ->dehydrateStateUsing(fn ($state) => (float) ($state ?? 0))
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                $paid = (float) ($get('paid_amount') ?? 0);
                                $total = (float) ($state ?? 0);
                                $set('remaining_amount', max(0, $total - $paid));
                            }),

                        Textarea::make('description')
                            ->label(__('violations.violations.fields.description'))
                            ->placeholder(__('violations.violations.placeholders.description'))
                            ->columnSpanFull()
                            ->rows(3),

                        FileUpload::make('attachment')
                            ->label(__('violations.violations.fields.attachment'))
                            ->disk('public')
                            ->directory('violations')
                            ->columnSpanFull(),
                    ]),

                // 3. Stages and Deadlines Section
                Section::make(__('violations.violations.sections.stages'))
                    ->description(__('violations.violations.sections.stages_desc'))
                    ->columns(3)
                    ->schema([
                        Select::make('stage')
                            ->label(__('violations.violations.fields.stage'))
                            ->options(ViolationStage::class)
                            ->default(ViolationStage::CORRECTION->value)
                            ->required(),

                        DatePicker::make('correction_deadline')
                            ->label(__('violations.violations.fields.correction_deadline'))
                            ->default(fn () => now()->addDays(14)->format('Y-m-d'))
                            ->native(false)
                            ->helperText(__('violations.violations.helpers.correction_deadline')),

                        DatePicker::make('objection_deadline')
                            ->label(__('violations.violations.fields.objection_deadline'))
                            ->default(fn () => now()->addDays(60)->format('Y-m-d'))
                            ->native(false)
                            ->helperText(__('violations.violations.helpers.objection_deadline')),

                        DatePicker::make('grace_deadline')
                            ->label(__('violations.violations.fields.grace_deadline'))
                            ->default(fn () => now()->addDays(90)->format('Y-m-d'))
                            ->native(false)
                            ->helperText(__('violations.violations.helpers.grace_deadline')),

                        DatePicker::make('legal_deadline')
                            ->label(__('violations.violations.fields.legal_deadline'))
                            ->default(fn () => now()->addDays(60)->format('Y-m-d'))
                            ->native(false)
                            ->helperText(__('violations.violations.helpers.legal_deadline')),

                        DatePicker::make('admin_execution_date')
                            ->label(__('violations.violations.fields.admin_execution_date'))
                            ->native(false),
                    ]),

                // 4. Payment Status Section
                Section::make(__('violations.violations.sections.payment'))
                    ->description(__('violations.violations.sections.payment_desc'))
                    ->columns(3)
                    ->schema([
                        Select::make('payment_status')
                            ->label(__('violations.violations.fields.payment_status'))
                            ->options(ViolationPaymentStatus::class)
                            ->default(ViolationPaymentStatus::PENDING->value)
                            ->required(),

                        TextInput::make('paid_amount')
                            ->label(__('violations.violations.fields.paid_amount'))
                            ->numeric()
                            ->prefix('SAR')
                            ->default(0)
                            ->dehydrateStateUsing(fn ($state) => (float) ($state ?? 0))
                            ->live()
                            ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                $total = (float) ($get('violation_amount') ?? 0);
                                $paid = (float) ($state ?? 0);
                                $remaining = max(0, $total - $paid);
                                $set('remaining_amount', $remaining);

                                if ($remaining <= 0 && $total > 0) {
                                    $set('payment_status', ViolationPaymentStatus::PAID->value);
                                    $set('closure_date', now()->format('Y-m-d'));
                                } elseif ($paid > 0) {
                                    $set('payment_status', ViolationPaymentStatus::PARTIAL->value);
                                } else {
                                    $set('payment_status', ViolationPaymentStatus::PENDING->value);
                                }
                            }),

                        TextInput::make('remaining_amount')
                            ->label(__('violations.violations.fields.remaining_amount'))
                            ->numeric()
                            ->prefix('SAR')
                            ->default(0)
                            ->disabled()
                            ->dehydrated()
                            ->dehydrateStateUsing(fn ($state) => (float) ($state ?? 0))
                            ->helperText(__('violations.violations.helpers.remaining_amount')),

                        DatePicker::make('closure_date')
                            ->label(__('violations.violations.fields.closure_date'))
                            ->native(false),

                        Textarea::make('notes')
                            ->label(__('violations.violations.fields.notes'))
                            ->placeholder(__('violations.violations.placeholders.notes'))
                            ->columnSpan(2)
                            ->rows(2),
                    ]),
            ]);
    }
}
