<?php

namespace App\Filament\Resources\SaudiEmployees\Pages;

use App\Enums\UserType;
use App\Filament\Resources\ForeignEmployees\ForeignEmployeeResource;
use App\Filament\Resources\SaudiEmployees\SaudiEmployeeResource;
use App\Models\Company;
use App\Models\Employee;
use App\Services\CompanyFormService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListSaudiEmployees extends ListRecords
{
    protected static string $resource = SaudiEmployeeResource::class;

    public function getTitle(): string
    {
        return __('navigation.employees');
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make('create_saudi')
                ->label('إضافة موظف سعودي')
                ->icon('heroicon-o-user-plus')
                ->color('success'),

            Action::make('create_foreign')
                ->label('إضافة موظف غير سعودي')
                ->icon('heroicon-o-globe-alt')
                ->color('info')
                ->url(ForeignEmployeeResource::getUrl('create')),

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
                            'employees_only' => 'كشف للموظفين فقط',
                            'owners_only' => 'كشف للمُلّاك فقط',
                        ])
                        ->default('all')
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
                            'active' => 'النشطين / على رأس العمل فقط',
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

    public function getTabs(): array
    {
        $baseQuery = Employee::query();
        $user = auth()->user();
        if ($user && ! $user->isSuperAdmin() && ! $user->hasRole('Super Admin')) {
            $companyIds = CompanyFormService::getAuthorizedCompanyIds($user);
            if (! empty($companyIds)) {
                $baseQuery->whereIn('company_id', $companyIds);
            }
        }

        $allCount = (clone $baseQuery)->count();
        $saudiCount = (clone $baseQuery)->whereHas('user', fn ($q) => $q->where('user_type', UserType::SAUDI_EMPLOYEE))->count();
        $foreignCount = (clone $baseQuery)->whereHas('user', fn ($q) => $q->where('user_type', UserType::FOREIGN_EMPLOYEE))->count();

        return [
            'all' => Tab::make(__('employees.tabs.all'))
                ->badge($allCount)
                ->badgeColor('gray'),

            'saudi' => Tab::make(__('employees.tabs.saudi'))
                ->modifyQueryUsing(fn (Builder $query) => $query->whereHas('user', fn ($q) => $q->where('user_type', UserType::SAUDI_EMPLOYEE)))
                ->badge($saudiCount)
                ->badgeColor('success'),

            'foreign' => Tab::make(__('employees.tabs.foreign'))
                ->modifyQueryUsing(fn (Builder $query) => $query->whereHas('user', fn ($q) => $q->where('user_type', UserType::FOREIGN_EMPLOYEE)))
                ->badge($foreignCount)
                ->badgeColor('info'),
        ];
    }
}
