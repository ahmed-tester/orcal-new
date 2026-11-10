<?php

namespace App\Filament\Resources\Owners\Pages;

use App\Filament\Resources\Owners\OwnerResource;
use App\Models\Company;
use App\Services\CompanyFormService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Resources\Pages\ListRecords;

class ListOwners extends ListRecords
{
    protected static string $resource = OwnerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),

            Action::make('print_statement')
                ->label('طباعة وتصدير كشف 🖨️')
                ->icon('heroicon-o-printer')
                ->color('warning')
                ->modalHeading('خيارات كشف الموظفين والمُلّاك (عرض وطباعة)')
                ->modalDescription('اختر نوع الكشف والمنشأة المراد استعراضها أو طباعتها.')
                ->modalSubmitActionLabel('فتح واستعراض الكشف 🖨️')
                ->form([
                    Select::make('statement_type')
                        ->label('نوع الكشف')
                        ->options([
                            'all' => 'كشف شامل (الكل: الموظفين والمُلّاك معاً مع إظهار الصفة)',
                            'owners_only' => 'كشف للمُلّاك فقط',
                            'employees_only' => 'كشف للموظفين فقط',
                        ])
                        ->default('owners_only')
                        ->required(),

                    Select::make('company_id')
                        ->label('المنشأة / الشركة')
                        ->options(function () {
                            $user = auth()->user();
                            $query = Company::query();
                            if ($user && ! $user->isSuperAdmin()) {
                                $ids = CompanyFormService::getAuthorizedCompanyIds($user);
                                if (! empty($ids)) {
                                    $query->whereIn('id', $ids);
                                }
                            }

                            return ['all' => 'كافة المنشآت المصرح بها'] + $query->orderBy('trade_name')->pluck('trade_name', 'id')->toArray();
                        })
                        ->default('all')
                        ->searchable()
                        ->required(),

                    Select::make('status')
                        ->label('حالة السجلات')
                        ->options([
                            'all' => 'الكل (نشط وغير نشط)',
                            'active' => 'النشطين فقط',
                        ])
                        ->default('all')
                        ->required(),

                    Select::make('export_mode')
                        ->label('طريقة العرض')
                        ->options([
                            'print' => 'معاينة شاشة وطباعة فورية (HTML Print View)',
                            'pdf' => 'تصدير وتحميل مستند PDF مباشر',
                        ])
                        ->default('print')
                        ->required(),
                ])
                ->action(function (array $data) {
                    $params = array_filter($data, fn ($v) => ! is_null($v) && $v !== '');
                    $url = route('exports.employees-statement', $params);
                    $this->js("window.open('{$url}', '_blank')");
                }),
        ];
    }
}
