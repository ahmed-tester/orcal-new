<?php

namespace App\Filament\Pages;

use App\Filament\Resources\OfficialLetters\OfficialLetterResource;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Navigation\NavigationItem;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Illuminate\Contracts\Support\Htmlable;

class SystemDocumentationPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-book-open';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.system-documentation';

    public static function getNavigationItems(): array
    {
        return collect(parent::getNavigationItems())
            ->map(fn (NavigationItem $item) => $item->openUrlInNewTab(true))
            ->all();
    }

    public string $activeGroup = 'letters';

    protected $queryString = [
        'activeGroup' => ['except' => 'letters'],
    ];

    public function setGroup(string $group): void
    {
        $this->activeGroup = $group;
    }

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.support_group');
    }

    public static function getNavigationLabel(): string
    {
        return __('navigation.system_documentation');
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }

    public function getTitle(): string|Htmlable
    {
        return __('navigation.system_documentation');
    }

    public function getViewData(): array
    {
        $groups = self::getDocumentationGroups();

        return [
            'groups'      => $groups,
            'activeGroup' => $this->activeGroup,
            'current'     => $groups[$this->activeGroup] ?? $groups['letters'],
        ];
    }

    public static function getDocumentationGroups(): array
    {
        return [
            'letters' => [
                'key'                => 'letters',
                'title'              => __('documentation.groups.letters'),
                'icon'               => 'heroicon-o-envelope-open',
                'has_detailed_guide' => true,
                'view'               => 'filament.pages.documentation.letters.official-letters',
                'resources'          => [
                    [
                        'name' => __('navigation.official_letters'),
                        'url'  => OfficialLetterResource::getUrl('index'),
                    ],
                ],
            ],
            'organization' => [
                'key'                => 'organization',
                'title'              => __('documentation.groups.organization'),
                'icon'               => 'heroicon-o-building-office-2',
                'has_detailed_guide' => false,
                'resources'          => [
                    ['name' => __('navigation.companies'), 'url' => '/companies'],
                    ['name' => __('navigation.branches'), 'url' => '/branches'],
                    ['name' => __('navigation.departments'), 'url' => '/departments'],
                    ['name' => __('navigation.owners'), 'url' => '/owners'],
                ],
            ],
            'hr' => [
                'key'                => 'hr',
                'title'              => __('documentation.groups.hr'),
                'icon'               => 'heroicon-o-user-group',
                'has_detailed_guide' => false,
                'resources'          => [
                    ['name' => __('navigation.saudi_employees'), 'url' => '/saudi-employees'],
                    ['name' => __('navigation.foreign_employees'), 'url' => '/foreign-employees'],
                    ['name' => __('navigation.attendance'), 'url' => '/attendances'],
                    ['name' => __('navigation.leaves'), 'url' => '/leaves'],
                    ['name' => __('navigation.leave_types'), 'url' => '/leave-types'],
                    ['name' => __('navigation.job_titles'), 'url' => '/job-titles'],
                ],
            ],
            'finance' => [
                'key'                => 'finance',
                'title'              => __('documentation.groups.finance'),
                'icon'               => 'heroicon-o-banknotes',
                'has_detailed_guide' => false,
                'resources'          => [
                    ['name' => __('navigation.treasury_transactions'), 'url' => '/treasury-transactions'],
                    ['name' => __('navigation.payment_collection_items'), 'url' => '/payment-collection-items'],
                    ['name' => __('navigation.payroll_records'), 'url' => '/payroll-records'],
                    ['name' => __('navigation.employee_rewards'), 'url' => '/employee-rewards'],
                    ['name' => __('navigation.daily_journals'), 'url' => '/daily-journals'],
                    ['name' => __('navigation.pos_devices'), 'url' => '/pos-devices'],
                    ['name' => __('navigation.installments'), 'url' => '/installments'],
                    ['name' => __('navigation.installment_payments'), 'url' => '/installment-payments'],
                    ['name' => __('navigation.installment_types'), 'url' => '/installment-types'],
                ],
            ],
            'tasks' => [
                'key'                => 'tasks',
                'title'              => __('documentation.groups.tasks'),
                'icon'               => 'heroicon-o-clipboard-document-check',
                'has_detailed_guide' => false,
                'resources'          => [
                    ['name' => __('navigation.tasks'), 'url' => '/tasks'],
                    ['name' => __('navigation.task_assignments'), 'url' => '/task-assignments'],
                    ['name' => __('navigation.supervision_shifts'), 'url' => '/supervision-shifts'],
                    ['name' => __('navigation.weekly_notes'), 'url' => '/weekly-notes'],
                ],
            ],
            'contracts' => [
                'key'                => 'contracts',
                'title'              => __('documentation.groups.contracts'),
                'icon'               => 'heroicon-o-document-chart-bar',
                'has_detailed_guide' => false,
                'resources'          => [
                    ['name' => __('navigation.customers'), 'url' => '/customers'],
                    ['name' => __('navigation.offers'), 'url' => '/offers'],
                    ['name' => __('navigation.invoices'), 'url' => '/invoices'],
                    ['name' => __('navigation.services'), 'url' => '/services'],
                ],
            ],
            'violations' => [
                'key'                => 'violations',
                'title'              => __('documentation.groups.violations'),
                'icon'               => 'heroicon-o-exclamation-triangle',
                'has_detailed_guide' => false,
                'resources'          => [
                    ['name' => __('navigation.violations'), 'url' => '/violations'],
                    ['name' => __('navigation.violation_regulations'), 'url' => '/violation-regulations'],
                    ['name' => __('navigation.violation_transactions'), 'url' => '/violation-transactions'],
                ],
            ],
            'funds' => [
                'key'                => 'funds',
                'title'              => __('documentation.groups.funds'),
                'icon'               => 'heroicon-o-currency-dollar',
                'has_detailed_guide' => false,
                'resources'          => [
                    ['name' => __('navigation.loan_funds'), 'url' => '/loan-funds'],
                    ['name' => __('navigation.loan_fund_members'), 'url' => '/loan-fund-members'],
                    ['name' => __('navigation.loan_fund_loans'), 'url' => '/loan-fund-loans'],
                    ['name' => __('navigation.loan_fund_loan_payments'), 'url' => '/loan-fund-loan-payments'],
                    ['name' => __('navigation.rosca_funds'), 'url' => '/rosca-funds'],
                    ['name' => __('navigation.rosca_fund_members'), 'url' => '/rosca-fund-members'],
                    ['name' => __('navigation.rosca_fund_payments'), 'url' => '/rosca-fund-payments'],
                ],
            ],
            'reports' => [
                'key'                => 'reports',
                'title'              => __('documentation.groups.reports'),
                'icon'               => 'heroicon-o-chart-pie',
                'has_detailed_guide' => false,
                'resources'          => [
                    ['name' => __('navigation.general_reports'), 'url' => '/general-reports'],
                    ['name' => __('navigation.notifications_report'), 'url' => '/notifications-report'],
                ],
            ],
            'system' => [
                'key'                => 'system',
                'title'              => __('documentation.groups.system'),
                'icon'               => 'heroicon-o-cog-6-tooth',
                'has_detailed_guide' => false,
                'resources'          => [
                    ['name' => __('navigation.users'), 'url' => '/users'],
                    ['name' => __('navigation.roles'), 'url' => '/roles'],
                    ['name' => __('navigation.settings'), 'url' => '/settings'],
                ],
            ],
        ];
    }
}
