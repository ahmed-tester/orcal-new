<?php

namespace App\Filament\Pages;

use App\Models\User;
use App\Services\Notifications\NotificationAlertsService;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;
use Livewire\WithPagination;

class NotificationsReport extends Page
{
    use WithPagination;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-bell-alert';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.notifications-report';

    public static function canAccess(): bool
    {
        $user = auth()->user();
        return $user && ($user->isSuperAdminOrOwner() || $user->can('view_notifications_report'));
    }

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.reports_group');
    }

    public static function getNavigationLabel(): string
    {
        return __('notifications.report_label');
    }

    public function getTitle(): string|Htmlable
    {
        return __('notifications.report_title');
    }

    // Filter states
    public string $category = 'all'; // all, documents, tasks, invoices
    public string $urgency = 'all';   // all, urgent, overdue, upcoming
    public string $search = '';
    public int $perPage = 15;

    // Comprehensive Matrix Filters
    public string $entityFilter = 'all'; // all, companies, employees, or specific id
    public ?string $dateFrom = null;
    public ?string $dateTo = null;
    public string $viewMode = 'matrix'; // matrix, list

    protected $queryString = [
        'category' => ['except' => 'all'],
        'urgency' => ['except' => 'all'],
        'search' => ['except' => ''],
        'entityFilter' => ['except' => 'all'],
        'dateFrom' => ['except' => null],
        'dateTo' => ['except' => null],
        'viewMode' => ['except' => 'matrix'],
    ];

    public function updated($propertyName)
    {
        if (in_array($propertyName, ['category', 'urgency', 'search', 'perPage'])) {
            $this->resetPage();
        }
    }

    public function setCategory(string $category)
    {
        $this->category = $category;
        $this->resetPage();
    }

    public function setUrgency(string $urgency)
    {
        $this->urgency = $urgency;
        $this->resetPage();
    }

    public function resetFilters()
    {
        $this->category = 'all';
        $this->urgency = 'all';
        $this->search = '';
        $this->resetPage();
    }

    public function formatDocCell(?string $dateString): ?array
    {
        if (!$dateString) {
            return null;
        }

        $date = \Carbon\Carbon::parse($dateString);
        $today = \Carbon\Carbon::today();
        $days = (int) $today->diffInDays($date, false);

        $badgeClass = match (true) {
            $days < 0 => 'badge-danger',
            $days <= 30 => 'badge-danger',
            $days <= 90 => 'badge-warning',
            default => 'badge-safe',
        };

        $icon = match (true) {
            $days < 0 => '🚨',
            $days <= 15 => '🔴',
            $days <= 30 => '⚠️',
            default => '',
        };

        $text = match (true) {
            $days < 0 => __('notifications.stages.days_overdue', ['days' => abs($days)]),
            $days == 0 => __('notifications.stages.expires_today'),
            default => __('notifications.stages.days_remaining', ['days' => $days]),
        };

        return [
            'date' => $date->format('Y-m-d'),
            'days' => $days,
            'text' => $text,
            'class' => $badgeClass,
            'icon' => $icon,
        ];
    }

    public function getEntitiesListProperty(): array
    {
        $list = [];
        try {
            $companies = \App\Models\Company::where('status', 'active')->get();
            foreach ($companies as $c) {
                $list['comp_' . $c->id] = '🏢 ' . $c->trade_name;
            }

            $employees = \App\Models\Employee::with('user')->where('status', 'active')->get();
            foreach ($employees as $e) {
                $name = $e->user?->name ?? __('notifications.alerts.employee_fallback', ['id' => $e->id]);
                $list['emp_' . $e->id] = '👤 ' . $name;
            }
        } catch (\Throwable $e) {
            // fallback
        }

        return $list;
    }

    public function getDocumentsMatrixProperty(): Collection
    {
        $matrix = collect();

        try {
            // 1. Employees query
            $employeesQuery = \App\Models\Employee::with(['user', 'company', 'detail'])
                ->where('status', 'active');

            if ($this->entityFilter !== 'all') {
                if (str_starts_with($this->entityFilter, 'comp_')) {
                    $compId = (int) str_replace('comp_', '', $this->entityFilter);
                    $employeesQuery->where('company_id', $compId);
                } elseif (str_starts_with($this->entityFilter, 'emp_')) {
                    $empId = (int) str_replace('emp_', '', $this->entityFilter);
                    $employeesQuery->where('id', $empId);
                }
            }

            if (!empty($this->search)) {
                $s = mb_strtolower($this->search);
                $employeesQuery->where(function ($q) use ($s) {
                    $q->whereHas('user', function ($uq) use ($s) {
                        $uq->where('name', 'like', "%{$s}%")->orWhere('email', 'like', "%{$s}%");
                    })->orWhereHas('detail', function ($dq) use ($s) {
                        $dq->where('iqama_number', 'like', "%{$s}%")->orWhere('national_id', 'like', "%{$s}%");
                    });
                });
            }

            $employees = $employeesQuery->get();

            foreach ($employees as $emp) {
                $detail = $emp->detail;
                $iqamaCell = $this->formatDocCell($detail?->iqama_expiry);
                $workPermitCell = $this->formatDocCell($detail?->work_permit_expiry);
                $insuranceCell = $this->formatDocCell($detail?->insurance_expiry);
                $contractCell = $this->formatDocCell($emp->contract_end_date);

                // Apply Date Range Filter if set
                if ($this->dateFrom || $this->dateTo) {
                    $hasMatchInDateRange = false;
                    $allDates = array_filter([
                        $detail?->iqama_expiry,
                        $detail?->work_permit_expiry,
                        $detail?->insurance_expiry,
                        $emp->contract_end_date,
                    ]);

                    foreach ($allDates as $d) {
                        $cDate = \Carbon\Carbon::parse($d);
                        $fromOk = empty($this->dateFrom) || $cDate->greaterThanOrEqualTo(\Carbon\Carbon::parse($this->dateFrom));
                        $toOk = empty($this->dateTo) || $cDate->lessThanOrEqualTo(\Carbon\Carbon::parse($this->dateTo));
                        if ($fromOk && $toOk) {
                            $hasMatchInDateRange = true;
                            break;
                        }
                    }

                    if (!$hasMatchInDateRange) {
                        continue;
                    }
                }

                $iqamaNum = $detail?->iqama_number ?? $detail?->national_id ?? '—';
                $maskedIqama = strlen($iqamaNum) >= 6
                    ? (substr($iqamaNum, 0, 2) . '******' . substr($iqamaNum, -4))
                    : $iqamaNum;

                $matrix->push([
                    'is_company' => false,
                    'name' => $emp->user?->name ?? __('notifications.alerts.employee_fallback', ['id' => $emp->id]),
                    'company_name' => $emp->company?->trade_name,
                    'iqama_number' => $maskedIqama,
                    'raw_iqama_number' => $iqamaNum,
                    'iqama' => $iqamaCell,
                    'work_permit' => $workPermitCell,
                    'insurance' => $insuranceCell,
                    'contract' => $contractCell,
                    'min_days' => min(array_filter([
                        $iqamaCell['days'] ?? 9999,
                        $workPermitCell['days'] ?? 9999,
                        $insuranceCell['days'] ?? 9999,
                        $contractCell['days'] ?? 9999,
                    ]) ?: [9999]),
                ]);
            }

            // 2. Company / Establishment Row(s)
            if ($this->entityFilter === 'all' || str_starts_with($this->entityFilter, 'comp_')) {
                $compQuery = \App\Models\Company::where('status', 'active');
                if (str_starts_with($this->entityFilter, 'comp_')) {
                    $compQuery->where('id', (int) str_replace('comp_', '', $this->entityFilter));
                }
                $comps = $compQuery->get();

                foreach ($comps as $c) {
                    $crCell = $this->formatDocCell($c->commercial_registration_expiry);
                    $munCell = $this->formatDocCell($c->municipality_license_expiry);
                    $cdCell = $this->formatDocCell($c->civil_defense_license_expiry);

                    $matrix->push([
                        'is_company' => true,
                        'name' => $c->trade_name . ' (' . __('notifications.documents.company_documents') . ')',
                        'company_name' => $c->trade_name,
                        'iqama_number' => '—',
                        'cr' => $crCell,
                        'municipality' => $munCell,
                        'civil_defense' => $cdCell,
                        'min_days' => min(array_filter([
                            $crCell['days'] ?? 9999,
                            $munCell['days'] ?? 9999,
                            $cdCell['days'] ?? 9999,
                        ]) ?: [9999]),
                    ]);
                }
            }
        } catch (\Throwable $e) {
            // graceful
        }

        return $matrix->sortBy('min_days')->values();
    }

    public function getSummaryCountsProperty(): array
    {
        return app(NotificationAlertsService::class)->getSummaryCounts();
    }

    public function getAlertsProperty(): Collection
    {
        return app(NotificationAlertsService::class)->getActiveAlerts(
            category: $this->category,
            urgency: $this->urgency,
            search: $this->search
        );
    }

    public function getPaginatedAlertsProperty()
    {
        $alerts = $this->alerts;
        $page = $this->getPage();
        $perPage = $this->perPage;

        return new \Illuminate\Pagination\LengthAwarePaginator(
            $alerts->forPage($page, $perPage),
            $alerts->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );
    }
}
