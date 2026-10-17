<?php

namespace App\Filament\Resources\ContractCategories;

use App\Filament\Resources\ContractCategories\Pages\CreateContractCategory;
use App\Filament\Resources\ContractCategories\Pages\EditContractCategory;
use App\Filament\Resources\ContractCategories\Pages\ListContractCategories;
use App\Filament\Traits\HasCompanyScope;
use App\Models\ContractCategory;
use BackedEnum;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ContractCategoryResource extends Resource
{
    use HasCompanyScope;

    protected static ?string $model = ContractCategory::class;

    protected static ?int $navigationSort = 4;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-tag';

    public static function getModelLabel(): string
    {
        return __('contract_categories.single');
    }

    public static function getPluralModelLabel(): string
    {
        return __('contract_categories.plural');
    }

    public static function getNavigationLabel(): string
    {
        return __('navigation.contract_categories');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.contracts_group');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make(__('contract_categories.single'))
                    ->description('تحديد بيانات التصنيف وقالب الشروط والأحكام التلقائي')
                    ->icon('heroicon-o-tag')
                    ->columns(12)
                    ->schema([
                        TextInput::make('name')
                            ->label(__('contract_categories.fields.name'))
                            ->placeholder(__('contract_categories.placeholders.name'))
                            ->required()
                            ->columnSpan([
                                'default' => 12,
                                'md' => 6,
                            ]),

                        TextInput::make('sort_order')
                            ->label(__('contract_categories.fields.sort_order'))
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->columnSpan([
                                'default' => 6,
                                'md' => 3,
                            ]),

                        Toggle::make('is_active')
                            ->label(__('contract_categories.fields.is_active'))
                            ->default(true)
                            ->inline(false)
                            ->columnSpan([
                                'default' => 6,
                                'md' => 3,
                            ]),

                        RichEditor::make('default_template')
                            ->label(__('contract_categories.fields.default_template'))
                            ->helperText('سيتم إدراج هذا النص تلقائياً كشروط وأحكام عند اختيار هذا التصنيف أثناء إنشاء العقود أو عروض الأسعار.')
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
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('contract_categories.fields.name'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),


                IconColumn::make('is_active')
                    ->label(__('contract_categories.fields.is_active'))
                    ->boolean(),

                TextColumn::make('sort_order')
                    ->label(__('contract_categories.fields.sort_order'))
                    ->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->defaultSort('sort_order', 'asc');
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListContractCategories::route('/'),
            'create' => CreateContractCategory::route('/create'),
            'edit'   => EditContractCategory::route('/{record}/edit'),
        ];
    }
}
