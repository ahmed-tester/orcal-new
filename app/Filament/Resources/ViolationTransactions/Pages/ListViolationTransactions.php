<?php

namespace App\Filament\Resources\ViolationTransactions\Pages;

use App\Filament\Resources\ViolationTransactions\ViolationTransactionResource;
use App\Filament\Resources\ViolationTransactions\Widgets\ViolationTreasuryStatsWidget;
use App\Models\Company;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Resources\Pages\ListRecords;

class ListViolationTransactions extends ListRecords
{
    protected static string $resource = ViolationTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            Action::make('export_statement')
                ->label('تصدير كشف الصندوق PDF 📑')
                ->icon('heroicon-o-document-arrow-down')
                ->color('info')
                ->modalHeading('تصدير كشف حركة صندوق تحصيل المخالفات')
                ->modalDescription('حدد المؤسسة ونوع الكشف والفترة الزمنية لتصدير وطباعة كشف الحساب والعمليات بصيغة PDF.')
                ->modalSubmitActionLabel('توليد وعرض الكشف')
                ->form([
                    Select::make('company_id')
                        ->label('المؤسسة / الشركة')
                        ->options(['all' => 'كافة المؤسسات والشركات (الكل)'] + Company::orderBy('trade_name')->pluck('trade_name', 'id')->toArray())
                        ->default('all')
                        ->searchable()
                        ->required(),
                    Select::make('transaction_type')
                        ->label('نوع الكشف')
                        ->options([
                            'all' => 'الكل (قبض وصرف)',
                            'incoming' => 'مقبوضات وتحصيل (قبض فقط)',
                            'outgoing' => 'منصرفات الصندوق (صرف فقط)',
                        ])
                        ->default('all')
                        ->required(),
                    DatePicker::make('from_date')
                        ->label('من تاريخ'),
                    DatePicker::make('to_date')
                        ->label('إلى تاريخ'),
                ])
                ->action(function (array $data) {
                    $params = array_filter($data, fn ($v) => !is_null($v) && $v !== '');
                    $url = route('exports.violation-statement', $params);
                    $this->js("window.open('{$url}', '_blank')");
                }),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            ViolationTreasuryStatsWidget::class,
        ];
    }
}
