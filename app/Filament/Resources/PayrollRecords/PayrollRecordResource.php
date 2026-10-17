<?php

namespace App\Filament\Resources\PayrollRecords;

use App\Filament\Resources\PayrollRecords\Pages\CreatePayrollRecord;
use App\Filament\Resources\PayrollRecords\Pages\EditPayrollRecord;
use App\Filament\Resources\PayrollRecords\Pages\ListPayrollRecords;
use App\Filament\Resources\PayrollRecords\Schemas\PayrollRecordForm;
use App\Filament\Resources\PayrollRecords\Tables\PayrollRecordsTable;
use App\Filament\Traits\HasCompanyScope;
use App\Models\PayrollRecord;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class PayrollRecordResource extends Resource
{
    use HasCompanyScope;

    protected static ?string $model = PayrollRecord::class;

    protected static ?int $navigationSort = 3;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    public static function getModelLabel(): string
    {
        return __('finance.payroll_records.single');
    }

    public static function getPluralModelLabel(): string
    {
        return __('finance.payroll_records.plural');
    }

    public static function getNavigationLabel(): string
    {
        return __('navigation.payroll_records');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.finance_group');
    }

    public static function form(Schema $schema): Schema
    {
        return PayrollRecordForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PayrollRecordsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListPayrollRecords::route('/'),
            'create' => CreatePayrollRecord::route('/create'),
            'edit'   => EditPayrollRecord::route('/{record}/edit'),
        ];
    }
}
