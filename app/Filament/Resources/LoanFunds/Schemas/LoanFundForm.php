<?php

namespace App\Filament\Resources\LoanFunds\Schemas;

use App\Enums\FundGeneralStatus;
use App\Enums\UserType;
// use App\Filament\Components\OrgSelect;
use App\Models\Employee;
use App\Services\CompanyFormService;
// use App\Services\FundFormService;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class LoanFundForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make(__('funds.loan_funds.sections.basic_info'))
                    ->description(__('funds.loan_funds.sections.basic_info_desc'))
                    ->icon('heroicon-o-banknotes')
                    ->columns(3)
                    ->schema([
                        TextInput::make('fund_number')
                            ->label(__('funds.loan_funds.fields.fund_number'))
                            ->disabled()
                            ->dehydrated(false)
                            ->visible(fn ($record) => $record !== null),

                        TextInput::make('fund_name')
                            ->label(__('funds.loan_funds.fields.fund_name'))
                            ->required(),

                        // OrgSelect::company('company_id', __('funds.loan_funds.fields.company')),

                        TextInput::make('target_amount')
                            ->label(__('funds.loan_funds.fields.target_amount'))
                            ->numeric()
                            ->prefix('SAR')
                            ->default(0)
                            ->dehydrateStateUsing(fn ($state) => (float) ($state ?? 0))
                            ->required(),

                        Select::make('installment_period')
                            ->label(__('funds.loan_funds.fields.installment_period'))
                            ->options([
                                'monthly' => __('funds.enums.installment_period.monthly'),
                                'yearly' => __('funds.enums.installment_period.yearly'),
                            ])
                            ->default('monthly')
                            ->required(),

                        TextInput::make('default_installments_count')
                            ->label(__('funds.loan_funds.fields.default_installments_count'))
                            ->numeric()
                            ->default(12)
                            ->minValue(1)
                            ->dehydrateStateUsing(fn ($state) => max(1, (int) ($state ?: 12)))
                            ->required(),

                        TextInput::make('max_loan_limit')
                            ->label(__('funds.loan_funds.fields.max_loan_limit'))
                            ->numeric()
                            ->prefix('SAR')
                            ->default(0)
                            ->dehydrateStateUsing(fn ($state) => (float) ($state ?? 0))
                            ->required(),

                        TextInput::make('max_repayment_months')
                            ->label(__('funds.loan_funds.fields.max_repayment_months'))
                            ->numeric()
                            ->default(12)
                            ->minValue(1)
                            ->dehydrateStateUsing(fn ($state) => max(1, (int) ($state ?: 12)))
                            ->required(),

                        Select::make('status')
                            ->label(__('funds.loan_funds.fields.status'))
                            ->options(FundGeneralStatus::class)
                            ->default(FundGeneralStatus::ACTIVE->value)
                            ->required(),

                        CompanyFormService::makeEmployeeSelect(
                            name: 'trustee_employee_id',
                            label: __('funds.loan_funds.fields.trustee_employee'),
                            required: false,
                            relationshipName: 'trusteeEmployee',
                            saudiOnly: true,
                            ignoreCompanyScope: true,
                        ),

                        // FundFormService::makeSaudiEmployeeSelect(
                        //     name: 'trustee_employee_id',
                        //     label: __('funds.loan_funds.fields.trustee_employee'),
                        //     required: false,
                        //     relationshipName: 'trusteeEmployee',
                        // ),

                        // Select::make('trustee_employee_id')
                        //     ->label(__('funds.loan_funds.fields.trustee_employee'))
                        //     ->relationship(
                        //         name: 'trusteeEmployee',
                        //         modifyQueryUsing: fn (Builder $query) => $query
                        //             ->withoutGlobalScopes()
                        //             ->with(['user', 'details'])
                        //             ->where('status', 'active')
                        //             ->saudi()
                        //     )
                        //     ->getOptionLabelFromRecordUsing(
                        //         fn ($record) => ($record->user?->name ?? "موظف #{$record->id}").($record->employee_number ? " ({$record->employee_number})" : '')
                        //     )
                        //     ->searchable()
                        //     ->preload()
                        //     ->nullable(),

                        // CompanyFormService::makeEmployeeSelect(
                        //     name: 'trustee_employee_id',
                        //     label: __('funds.loan_funds.fields.trustee_employee'),
                        //     required: false,
                        //     relationshipName: 'trusteeEmployee',
                        //     saudiOnly: true,
                        // ),

                        TextInput::make('trustee_name')
                            ->label(__('funds.loan_funds.fields.trustee_name')),
                    ]),

                Section::make(__('funds.loan_funds.sections.rules'))
                    ->icon('heroicon-o-document-text')
                    ->columns(1)
                    ->schema([
                        Textarea::make('rules_and_conditions')
                            ->label(__('funds.loan_funds.fields.rules_and_conditions'))
                            ->rows(4),

                        Textarea::make('notes')
                            ->label(__('funds.loan_funds.fields.notes'))
                            ->rows(2),
                    ]),
            ]);
    }
}
