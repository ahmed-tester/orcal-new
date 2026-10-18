<?php

namespace App\Filament\Resources\Offers\Schemas;

use App\Enums\CustomerType;
use App\Enums\OfferStatus;
use App\Enums\OfferType;
use App\Models\ContractCategory;
use App\Models\Employee;
use App\Services\ContractTemplateService;
use App\Services\PhoneService;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OfferForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make(__('offers.sections.offer_info'))
                    ->description(__('offers.sections.offer_info_desc'))
                    ->icon('heroicon-o-document-text')
                    ->columns(3)
                    ->schema([
                        Select::make('contract_category_id')
                             ->label(__('offers.fields.offer_type'))
                            ->relationship('contractCategory', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->default(fn () => ContractCategory::where('code', 'QUOTATION')->value('id') ?? ContractCategory::first()?->id)
                            ->createOptionForm([
                                TextInput::make('name')
                                    ->label(__('contract_categories.fields.name'))
                                    ->required(),
                                RichEditor::make('default_template')
                                    ->label(__('contract_categories.fields.default_template')),
                            ])
                            ->live()
                            ->afterStateUpdated(function ($state, $set) {
                                if ($state && ($cat = ContractCategory::find($state))) {
                                    if (!empty($cat->default_template)) {
                                        $set('terms_and_conditions', $cat->default_template);
                                    } elseif (!empty($cat->code)) {
                                        $set('terms_and_conditions', ContractTemplateService::getDefaultTemplate(strtolower($cat->code)));
                                    }

                                    $matchedType = !empty($cat->code) ? OfferType::tryFrom(strtolower($cat->code)) : null;
                                    $set('offer_type', $matchedType?->value ?? OfferType::UNIFIED_CONTRACT->value);
                                }
                            }),

                        Hidden::make('offer_type')
                            ->default(OfferType::QUOTATION->value)
                            ->dehydrated(),

                        TextInput::make('offer_number')
                            ->label(__('offers.fields.offer_number'))
                            ->placeholder(__('offers.placeholders.auto'))
                            ->disabled()
                            ->dehydrated()
                            ->hiddenOn('create'),

                        \App\Services\CompanyFormService::makeCompanySelect('company_id', __('offers.fields.company')),

                        Select::make('customer_id')
                            ->label(__('offers.fields.customer'))
                            ->relationship('customer', 'name')
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                Select::make('customer_type')
                                    ->label(__('customers.fields.type') ?? 'Type')
                                    ->options(CustomerType::class)
                                    ->default(CustomerType::COMPANY)
                                    ->required(),
                                TextInput::make('name')
                                    ->label(__('customers.fields.name') ?? 'Name')
                                    ->required(),
                                TextInput::make('commercial_registration_number')
                                    ->label(__('customers.fields.commercial_registration_number') ?? 'C.R. / National ID')
                                    ->maxLength(50),
                                PhoneService::makeFormField('phone', __('customers.fields.phone') ?? 'Phone'),
                                TextInput::make('address')
                                    ->label(__('customers.fields.address') ?? 'Address'),
                            ])
                            ->required(),

                        TextInput::make('subject')
                            ->label(__('offers.fields.subject'))
                            ->placeholder(__('offers.placeholders.subject'))
                            ->required()
                            ->columnSpan(2),

                        Select::make('status')
                            ->label(__('offers.fields.status'))
                            ->options(OfferStatus::class)
                            ->default(OfferStatus::DRAFT)
                            ->required(),

                        DatePicker::make('offer_date')
                            ->label(__('offers.fields.offer_date'))
                            ->default(now())
                            ->native(false)
                            ->required(),

                        DatePicker::make('valid_until')
                            ->label(__('offers.fields.valid_until'))
                            ->native(false),

                        Select::make('created_by')
                            ->label(__('offers.fields.created_by'))
                            ->relationship('creator', 'name')
                            ->default(fn () => auth()->id())
                            ->disabled()
                            ->dehydrated()
                            ->required(),

                        Textarea::make('description')
                            ->label(__('offers.fields.description'))
                            ->placeholder(__('offers.placeholders.description'))
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),

                Section::make(__('offers.sections.items'))
                    ->description(__('offers.sections.items_desc'))
                    ->icon('heroicon-o-table-cells')
                    ->schema([
                        Repeater::make('items')
                            ->relationship('items')
                            ->label(__('offers.sections.items'))
                            ->addActionLabel(__('offers.fields.add_item'))
                            ->orderColumn('sort_order')
                            ->columns(12)
                            ->schema([
                                TextInput::make('item_name')
                                    ->label(__('offers.fields.item_name'))
                                    ->required()
                                    ->columnSpan(5),

                                TextInput::make('unit')
                                    ->label(__('offers.fields.unit'))
                                    ->placeholder(__('offers.placeholders.unit'))
                                    ->columnSpan(2),

                                TextInput::make('quantity')
                                    ->label(__('offers.fields.quantity'))
                                    ->numeric()
                                    ->default(1)
                                    ->minValue(0.01)
                                    ->live()
                                    ->afterStateUpdated(function ($state, $set, $get) {
                                        $qty = (float)($state ?: 0);
                                        $unitPrice = (float)($get('unit_price') ?: 0);
                                        $set('price', round($qty * $unitPrice, 2));
                                    })
                                    ->columnSpan(1),

                                TextInput::make('unit_price')
                                    ->label(__('offers.fields.unit_price'))
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->live()
                                    ->afterStateUpdated(function ($state, $set, $get) {
                                        $unitPrice = (float)($state ?: 0);
                                        $qty = (float)($get('quantity') ?: 1);
                                        $set('price', round($qty * $unitPrice, 2));
                                    })
                                    ->columnSpan(2),

                                TextInput::make('price')
                                    ->label(__('offers.fields.price'))
                                    ->numeric()
                                    ->readOnly()
                                    ->dehydrated()
                                    ->columnSpan(2),

                                Textarea::make('specifications')
                                    ->label(__('offers.fields.specifications'))
                                    ->placeholder(__('offers.placeholders.specifications'))
                                    ->rows(2)
                                    ->columnSpanFull(),
                            ])
                            ->collapsible()
                            ->columnSpanFull(),
                    ]),

                Section::make(__('offers.sections.terms_section'))
                    ->description(__('offers.sections.terms_desc'))
                    ->icon('heroicon-o-shield-check')
                    ->schema([
                        RichEditor::make('terms_and_conditions')
                            ->label(__('offers.fields.terms_and_conditions'))
                            ->default(fn ($get) => ContractTemplateService::getDefaultTemplate($get('offer_type') ?? OfferType::QUOTATION))
                            ->toolbarButtons([
                                'bold',
                                'italic',
                                'underline',
                                'strike',
                                'h2',
                                'h3',
                                'bulletList',
                                'orderedList',
                                'table',
                                'undo',
                                'redo',
                            ])
                            ->columnSpanFull(),
                    ]),

                Section::make(__('offers.sections.responsible'))
                    ->description(__('offers.sections.responsible_desc'))
                    ->icon('heroicon-o-user-group')
                    ->columns(3)
                    ->schema([
                        Select::make('manager_id')
                            ->label(__('offers.fields.manager'))
                            ->options(fn ($get) => static::getEmployeeOptions($get('company_id')))
                            ->searchable()
                            ->preload(),

                        Select::make('supervisor_id')
                            ->label(__('offers.fields.supervisor'))
                            ->options(fn ($get) => static::getEmployeeOptions($get('company_id')))
                            ->searchable()
                            ->preload(),

                        Toggle::make('show_stamp')
                            ->label(__('offers.fields.show_stamp'))
                            ->helperText(function (callable $get) {
                                $companyId = $get('company_id') ?? \App\Services\CompanyFormService::getDefaultCompanyId();
                                if ($companyId && ($company = \App\Models\Company::find($companyId))) {
                                    if ($company->stamp) {
                                        $stampUrl = asset('storage/' . $company->stamp);
                                        return new \Illuminate\Support\HtmlString(
                                            '<div style="margin-top: 6px; display: flex; align-items: center; gap: 8px;">' .
                                            '<img src="' . e($stampUrl) . '" style="max-height: 48px; max-width: 90px; border: 1px dashed #2377c3; border-radius: 6px; padding: 2px; background: #fff;" />' .
                                            '<span style="color: #16a34a; font-weight: 500; font-size: 11px;">' . e(__('offers.stamp.uploaded_notice')) . '</span>' .
                                            '</div>'
                                        );
                                    }
                                    return new \Illuminate\Support\HtmlString(
                                        '<span style="color: #d97706; font-size: 11px;">' . e(__('offers.stamp.no_stamp_notice')) . '</span>'
                                    );
                                }
                                return __('offers.stamp.default_helper');
                            })
                            ->live()
                            ->default(true)
                            ->inline(false),
                    ]),

                Section::make(__('offers.sections.notes_section'))
                    ->icon('heroicon-o-chat-bubble-bottom-center-text')
                    ->collapsed()
                    ->schema([
                        Textarea::make('notes')
                            ->label(__('offers.fields.notes'))
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    protected static function getEmployeeOptions(mixed $companyId): array
    {
        return Employee::with(['user', 'jobTitle'])
            ->when($companyId, fn ($q, $cid) => $q->where('company_id', $cid))
            ->get()
            ->mapWithKeys(fn ($emp) => [
                $emp->id => ($emp->user?->name ?? '—')
                           . ($emp->jobTitle?->name ? ' - ' . $emp->jobTitle->name : ''),
            ])
            ->toArray();
    }
}
