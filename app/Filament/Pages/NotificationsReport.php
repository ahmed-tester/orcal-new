<?php

namespace App\Filament\Pages;

use App\Models\Company;
use App\Models\Debt;
use App\Models\Employee;
use App\Models\Invoice;
use App\Models\Leave;
use App\Models\ServiceTransaction;
use App\Models\Task;
use App\Models\User;
use App\Models\Violation;
use App\Notifications\BaseSystemNotification;
use App\Services\CompanyFormService;
use App\Services\FilterBarService;
use App\Services\Notifications\NotificationAlertsService;
use App\Services\Notifications\NotificationDispatcher;
use App\Services\Notifications\NotificationRecipientService;
use Carbon\Carbon;
use Filament\Notifications\Notification as FilamentNotification;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
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

    // Standard FilterBar State
    public ?string $search = '';

    public mixed $companyId = [];

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    public mixed $status = []; // all, overdue, urgent, warning, safe

    public int|string $perPage = 20;

    // Report-Specific Filter States
    public mixed $targetType = null;   // null/all, employees, companies

    public mixed $documentType = [];    // array of types e.g. ['iqama', 'insurance'], or string/null

    public string $category = 'all';     // all, documents, violations, tasks, invoices

    public string $viewMode = 'matrix';  // matrix, list

    protected $queryString = [
        'search' => ['except' => ''],
        'companyId' => ['except' => []],
        'dateFrom' => ['except' => null],
        'dateTo' => ['except' => null],
        'status' => ['except' => []],
        'targetType' => ['except' => null],
        'documentType' => ['except' => []],
        'category' => ['except' => 'all'],
        'viewMode' => ['except' => 'matrix'],
    ];

    public function mount(): void
    {
        $this->documentType = is_array($this->documentType) ? $this->documentType : ($this->documentType && $this->documentType !== 'all' ? [$this->documentType] : []);
        $this->status = is_array($this->status) ? $this->status : ($this->status && $this->status !== 'all' ? [$this->status] : []);
        $this->companyId = is_array($this->companyId) ? $this->companyId : ($this->companyId ? [$this->companyId] : []);
        $this->targetType = (filled($this->targetType) && $this->targetType !== 'all') ? (string) $this->targetType : null;
        $this->category = $this->category ?: 'all';
        $this->viewMode = $this->viewMode ?: 'matrix';
    }

    public function __get($property): mixed
    {
        if ($property === 'documentType') {
            return $this->documentType ?? [];
        }

        if ($property === 'targetType') {
            return $this->targetType;
        }

        if ($property === 'status') {
            return $this->status ?? [];
        }

        return parent::__get($property);
    }

    public function __set($property, $value): void
    {
        if ($property === 'documentType') {
            $this->documentType = is_array($value) ? $value : (empty($value) || $value === 'all' ? [] : [$value]);

            return;
        }

        if ($property === 'targetType') {
            $this->targetType = (filled($value) && $value !== 'all') ? (string) $value : null;

            return;
        }

        if ($property === 'status') {
            $this->status = is_array($value) ? $value : (empty($value) || $value === 'all' ? [] : [$value]);

            return;
        }

        $this->{$property} = $value;
    }

    public function updated($propertyName)
    {
        if (in_array($propertyName, [
            'search',
            'companyId',
            'dateFrom',
            'dateTo',
            'status',
            'targetType',
            'documentType',
            'category',
            'perPage',
            'viewMode',
        ])) {
            $this->resetPage();
        }
    }

    public function switchViewMode(string $mode)
    {
        $this->viewMode = $mode;
        $this->resetPage();
    }

    public function setCategory(string $category)
    {
        $this->category = $category;
        $this->resetPage();
    }

    public function setTargetType(?string $target)
    {
        $this->targetType = (filled($target) && $target !== 'all') ? $target : null;
        // Clean incompatible selected document types if any
        $selected = FilterBarService::values($this->documentType, ['all']);
        if ($this->targetType === 'employees') {
            $this->documentType = array_values(array_diff($selected, ['cr', 'municipality', 'civil_defense']));
        } elseif ($this->targetType === 'companies') {
            $this->documentType = array_values(array_intersect($selected, ['cr', 'municipality', 'civil_defense']));
        }
        $this->resetPage();
    }

    public function setDocumentType(mixed $type)
    {
        $this->documentType = is_array($type) ? $type : ($type === 'all' || ! $type ? [] : [$type]);
        $this->resetPage();
    }

    public function hasActiveFilters(): bool
    {
        if (filled($this->search)) {
            return true;
        }

        if (FilterBarService::values($this->companyId) !== []) {
            return true;
        }

        if (! empty($this->dateFrom) || ! empty($this->dateTo)) {
            return true;
        }

        if (FilterBarService::values($this->status, ['all']) !== []) {
            return true;
        }

        if (FilterBarService::values($this->targetType, ['all']) !== []) {
            return true;
        }

        if (FilterBarService::values($this->documentType, ['all']) !== []) {
            return true;
        }

        if (FilterBarService::values($this->category, ['all']) !== []) {
            return true;
        }

        return false;
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->companyId = [];
        $this->dateFrom = null;
        $this->dateTo = null;
        $this->status = [];
        $this->targetType = null;
        $this->documentType = [];
        $this->category = 'all';
        $this->resetPage();
    }

    public function getCompaniesProperty()
    {
        $query = Company::query()->where('status', 'active');
        $user = auth()->user();

        if ($user && ! $user->isSuperAdmin()) {
            $companyIds = CompanyFormService::getAuthorizedCompanyIds($user);
            $query->whereIn('id', ! empty($companyIds) ? $companyIds : [0]);
        }

        return $query->orderBy('trade_name')->get();
    }

    public function getDocumentTypeOptionsProperty(): array
    {
        $counts = $this->summaryCounts['types'] ?? [];

        $allOptions = [];
        foreach (['iqama' => '🪪', 'work_permit' => '💼', 'insurance' => '🏥', 'contract' => '📝', 'passport' => '🛂', 'cr' => '📜', 'municipality' => '🏛️', 'civil_defense' => '🚒'] as $type => $icon) {
            $document = $type === 'insurance' ? 'health_insurance' : $type;
            $allOptions[$type] = $icon.' '.__('notifications.documents.'.$document).' ('.($counts[$type] ?? 0).')';
        }
        $allOptions['leave_overdue'] = __('notifications.ui.overdue_leaves');
        foreach (['violations', 'tasks', 'invoices'] as $type) {
            $key = $type === 'violations' ? 'violations_option' : $type;
            $allOptions[$type] = __('notifications.ui.'.$key).' ('.($this->summaryCounts[$type] ?? 0).')';
        }

        if ($this->targetType === 'employees') {
            unset($allOptions['cr'], $allOptions['municipality'], $allOptions['civil_defense']);
        } elseif ($this->targetType === 'companies') {
            unset(
                $allOptions['iqama'],
                $allOptions['work_permit'],
                $allOptions['insurance'],
                $allOptions['contract'],
                $allOptions['passport'],
                $allOptions['leave_overdue']
            );
        }

        return $allOptions;
    }

    public function formatDocCell(?string $dateString): ?array
    {
        if (! $dateString) {
            return null;
        }

        $date = Carbon::parse($dateString);
        $today = Carbon::today();
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

    public function getDocumentsMatrixProperty(): Collection
    {
        $matrix = collect();

        // Selected document types (supports multi-select array or single)
        $selectedDocTypes = FilterBarService::values($this->documentType, ['all']);
        $employeeTypes = ['iqama', 'work_permit', 'insurance', 'contract', 'passport', 'leave_overdue'];
        $companyTypes = ['cr', 'municipality', 'civil_defense'];

        // Determine if selected document types include employee or company types
        $hasEmployeeTypes = empty($selectedDocTypes) || count(array_intersect($selectedDocTypes, $employeeTypes)) > 0;
        $hasCompanyTypes = empty($selectedDocTypes) || count(array_intersect($selectedDocTypes, $companyTypes)) > 0;

        $showEmployees = (empty($this->targetType) || $this->targetType === 'all' || $this->targetType === 'employees') && $hasEmployeeTypes;
        $showCompanies = (empty($this->targetType) || $this->targetType === 'all' || $this->targetType === 'companies') && $hasCompanyTypes;

        $companyIds = FilterBarService::values($this->companyId);
        $selectedStatuses = FilterBarService::values($this->status, ['all']);

        $user = auth()->user();
        $authorizedCompanyIds = ($user && ! $user->isSuperAdmin())
            ? array_map('intval', CompanyFormService::getAuthorizedCompanyIds($user))
            : null;

        // If user is restricted to specific companies, restrict the query
        $effectiveCompanyIds = $companyIds !== []
            ? ($authorizedCompanyIds !== null ? array_values(array_intersect($companyIds, $authorizedCompanyIds)) : $companyIds)
            : ($authorizedCompanyIds ?? []);

        try {
            // 1. Employees query
            if ($showEmployees) {
                $employeesQuery = Employee::with(['user', 'company', 'detail'])
                    ->where('status', 'active');

                if ($authorizedCompanyIds !== null) {
                    $employeesQuery->whereIn('company_id', ! empty($effectiveCompanyIds) ? $effectiveCompanyIds : [0]);
                }

                // Apply centralized FilterBarService filters
                FilterBarService::applyFilters($employeesQuery, [
                    'search' => $this->search,
                    'company_id' => $companyIds,
                ], [
                    'company' => ! empty($companyIds),
                    'search_columns' => [
                        'user.name',
                        'user.email',
                        'detail.iqama_number',
                        'detail.national_id',
                        'company.trade_name',
                    ],
                ]);

                $employees = $employeesQuery->get();
                $matchAllDocs = empty($selectedDocTypes);

                foreach ($employees as $emp) {
                    $detail = $emp->detail;

                    $iqamaCell = ($matchAllDocs || in_array('iqama', $selectedDocTypes)) ? $this->formatDocCell($detail?->iqama_expiry) : null;
                    $workPermitCell = ($matchAllDocs || in_array('work_permit', $selectedDocTypes)) ? $this->formatDocCell($detail?->work_permit_expiry) : null;
                    $insuranceCell = ($matchAllDocs || in_array('insurance', $selectedDocTypes)) ? $this->formatDocCell($detail?->insurance_expiry) : null;
                    $contractCell = ($matchAllDocs || in_array('contract', $selectedDocTypes)) ? $this->formatDocCell($emp->contract_end_date) : null;

                    if (! $matchAllDocs) {
                        if (! $iqamaCell && ! $workPermitCell && ! $insuranceCell && ! $contractCell) {
                            continue;
                        }
                    }

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
                            $cDate = Carbon::parse($d);
                            $fromOk = empty($this->dateFrom) || $cDate->greaterThanOrEqualTo(Carbon::parse($this->dateFrom));
                            $toOk = empty($this->dateTo) || $cDate->lessThanOrEqualTo(Carbon::parse($this->dateTo));
                            if ($fromOk && $toOk) {
                                $hasMatchInDateRange = true;
                                break;
                            }
                        }

                        if (! $hasMatchInDateRange) {
                            continue;
                        }
                    }

                    $minDays = min(array_filter([
                        $iqamaCell['days'] ?? 9999,
                        $workPermitCell['days'] ?? 9999,
                        $insuranceCell['days'] ?? 9999,
                        $contractCell['days'] ?? 9999,
                    ]) ?: [9999]);

                    // Apply Status / Urgency filter (multi-select supported)
                    if (! empty($selectedStatuses)) {
                        $matchesStatus = false;
                        foreach ($selectedStatuses as $st) {
                            $matches = match ($st) {
                                'overdue' => $minDays < 0,
                                'urgent' => $minDays >= 0 && $minDays <= 15,
                                'warning' => $minDays > 15 && $minDays <= 90,
                                'safe' => $minDays > 90,
                                default => true,
                            };
                            if ($matches) {
                                $matchesStatus = true;
                                break;
                            }
                        }
                        if (! $matchesStatus) {
                            continue;
                        }
                    }

                    $iqamaNum = $detail?->iqama_number ?? $detail?->national_id ?? '—';
                    $maskedIqama = strlen($iqamaNum) >= 6
                        ? (substr($iqamaNum, 0, 2).'******'.substr($iqamaNum, -4))
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
                        'min_days' => $minDays,
                    ]);
                }
            }

            // 2. Company / Establishment Row(s)
            if ($showCompanies) {
                $compQuery = Company::where('status', 'active');

                if ($authorizedCompanyIds !== null) {
                    $compQuery->whereIn('id', ! empty($effectiveCompanyIds) ? $effectiveCompanyIds : [0]);
                }

                // Apply centralized FilterBarService filters
                FilterBarService::applyFilters($compQuery, [
                    'search' => $this->search,
                    'company_id' => $companyIds,
                ], [
                    'company' => ! empty($companyIds),
                    'company_column' => 'id',
                    'search_columns' => [
                        'trade_name',
                        'name',
                        'commercial_registration_number',
                    ],
                ]);

                $comps = $compQuery->get();
                $matchAllDocs = empty($selectedDocTypes);

                foreach ($comps as $c) {
                    $crCell = ($matchAllDocs || in_array('cr', $selectedDocTypes)) ? $this->formatDocCell($c->commercial_registration_expiry) : null;
                    $munCell = ($matchAllDocs || in_array('municipality', $selectedDocTypes)) ? $this->formatDocCell($c->municipality_license_expiry) : null;
                    $cdCell = ($matchAllDocs || in_array('civil_defense', $selectedDocTypes)) ? $this->formatDocCell($c->civil_defense_license_expiry) : null;

                    if (! $matchAllDocs) {
                        if (! $crCell && ! $munCell && ! $cdCell) {
                            continue;
                        }
                    }

                    // Apply Date Range Filter if set
                    if ($this->dateFrom || $this->dateTo) {
                        $hasMatchInDateRange = false;
                        $allDates = array_filter([
                            $c->commercial_registration_expiry,
                            $c->municipality_license_expiry,
                            $c->civil_defense_license_expiry,
                        ]);

                        foreach ($allDates as $d) {
                            $cDate = Carbon::parse($d);
                            $fromOk = empty($this->dateFrom) || $cDate->greaterThanOrEqualTo(Carbon::parse($this->dateFrom));
                            $toOk = empty($this->dateTo) || $cDate->lessThanOrEqualTo(Carbon::parse($this->dateTo));
                            if ($fromOk && $toOk) {
                                $hasMatchInDateRange = true;
                                break;
                            }
                        }

                        if (! $hasMatchInDateRange) {
                            continue;
                        }
                    }

                    $minDays = min(array_filter([
                        $crCell['days'] ?? 9999,
                        $munCell['days'] ?? 9999,
                        $cdCell['days'] ?? 9999,
                    ]) ?: [9999]);

                    // Apply Status / Urgency filter (multi-select supported)
                    if (! empty($selectedStatuses)) {
                        $matchesStatus = false;
                        foreach ($selectedStatuses as $st) {
                            $matches = match ($st) {
                                'overdue' => $minDays < 0,
                                'urgent' => $minDays >= 0 && $minDays <= 15,
                                'warning' => $minDays > 15 && $minDays <= 90,
                                'safe' => $minDays > 90,
                                default => true,
                            };
                            if ($matches) {
                                $matchesStatus = true;
                                break;
                            }
                        }
                        if (! $matchesStatus) {
                            continue;
                        }
                    }

                    $matrix->push([
                        'is_company' => true,
                        'name' => $c->trade_name.' ('.__('notifications.documents.company_documents').')',
                        'company_name' => $c->trade_name,
                        'iqama_number' => '—',
                        'cr' => $crCell,
                        'municipality' => $munCell,
                        'civil_defense' => $cdCell,
                        'min_days' => $minDays,
                    ]);
                }
            }
        } catch (\Throwable $e) {
            Log::error('[NotificationsReport] Error building documents matrix: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
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
            urgency: $this->status,
            search: $this->search,
            documentType: $this->documentType,
            targetType: $this->targetType,
            companyId: $this->companyId,
            dateFrom: $this->dateFrom,
            dateTo: $this->dateTo,
        );
    }

    public function getPaginatedDocumentsMatrixProperty()
    {
        $matrix = $this->documentsMatrix;
        $total = $matrix->count();
        $perPage = $this->perPage === 'all' ? ($total ?: 20) : (int) $this->perPage;
        $page = $this->getPage();

        return new LengthAwarePaginator(
            $this->perPage === 'all' ? $matrix : $matrix->forPage($page, $perPage),
            $total,
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );
    }

    public function getPaginatedAlertsProperty()
    {
        $alerts = $this->alerts;
        $total = $alerts->count();
        $perPage = $this->perPage === 'all' ? ($total ?: 20) : (int) $this->perPage;
        $page = $this->getPage();

        return new LengthAwarePaginator(
            $this->perPage === 'all' ? $alerts : $alerts->forPage($page, $perPage),
            $total,
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );
    }

    /**
     * Dispatch a manual in-app reminder for a specific alert item after verifying server-side update permissions.
     */
    public function sendManualReminder(string $type, int|string $recordId, ?string $documentType = null): void
    {
        $user = auth()->user();
        if (! $user) {
            return;
        }

        // 1. Resolve target entity record
        $record = match ($type) {
            'employee', 'iqama', 'passport', 'work_permit', 'insurance', 'contract' => Employee::with(['user', 'company', 'detail'])->find($recordId),
            'company', 'cr', 'municipality', 'civil_defense' => Company::find($recordId),
            'leave', 'leave_overdue' => Leave::with(['employee.user', 'employee.company'])->find($recordId),
            'debt', 'debt_due' => Debt::with(['company'])->find($recordId),
            'violation', 'legal_deadline', 'admin_execution', 'suspended', 'stage_deadline' => Violation::with(['company'])->find($recordId),
            'task' => Task::find($recordId),
            'invoice' => Invoice::with(['company'])->find($recordId),
            'service', 'service_due' => ServiceTransaction::with(['company'])->find($recordId),
            default => null,
        };

        if (! $record) {
            FilamentNotification::make()
                ->title(__('notifications.delivery.missing_record'))
                ->body(__('notifications.delivery.missing_record_body'))
                ->danger()
                ->send();

            return;
        }

        // 2. Server-side authorization check: current user MUST have update rights on this record
        $defaultPerms = NotificationRecipientService::resolveDefaultUpdatePermissions($record);
        $companyId = NotificationRecipientService::resolveCompanyId($record);
        $branchId = NotificationRecipientService::resolveBranchId($record);

        if (! NotificationRecipientService::isUserEligibleToReceive($user, $record, $defaultPerms, $companyId, $branchId)) {
            FilamentNotification::make()
                ->title(__('notifications.delivery.forbidden'))
                ->body(__('notifications.delivery.forbidden_body'))
                ->danger()
                ->send();

            return;
        }

        // 4. Dispatch the contextual notification through Laravel Queue
        try {
            $notification = $this->buildNotificationForRecord($record, $type, $documentType);
            $count = NotificationDispatcher::send($record, $notification);
            if ($count === 0) {
                FilamentNotification::make()->title(__('notifications.delivery.no_recipients'))->warning()->send();

                return;
            }

            FilamentNotification::make()
                ->title(__('notifications.delivery.queued'))
                ->body(__('notifications.delivery.queued_body', ['count' => $count]))
                ->success()
                ->send();
        } catch (\Throwable $e) {
            Log::error('[NotificationsReport] Error dispatching manual reminder: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            FilamentNotification::make()
                ->title(__('notifications.delivery.failed'))
                ->body(__('notifications.delivery.failed_body'))
                ->danger()
                ->send();
        }
    }

    /**
     * Build contextual BaseSystemNotification for the target entity record.
     */
    protected function buildNotificationForRecord(Model $record, string $type, ?string $docType = null): BaseSystemNotification
    {
        [$kind, $event, $name, $icon, $color] = match (true) {
            $record instanceof Employee => ['employee', 'IqamaExpiryAlert', $record->user?->name ?? '#'.$record->id, 'heroicon-o-identification', 'warning'],
            $record instanceof Company => ['company', 'CompanyLicenseExpiryAlert', $record->trade_name, 'heroicon-o-building-office', 'warning'],
            $record instanceof Leave => ['leave', 'LeaveOverdueAlert', $record->employee?->user?->name ?? '#'.$record->employee_id, 'heroicon-o-clock', 'danger'],
            $record instanceof Debt => ['debt', 'DebtDueAlert', $record->debt_number ?? '#'.$record->id, 'heroicon-o-currency-dollar', 'warning'],
            $record instanceof Violation => ['violation', 'ViolationDeadlineAlert', $record->violation_number ?? '#'.$record->id, 'heroicon-o-scale', 'danger'],
            $record instanceof Task => ['task', 'TaskDueAlert', $record->title ?? '#'.$record->id, 'heroicon-o-clipboard-document-check', 'info'],
            $record instanceof Invoice => ['invoice', 'InvoiceDueAlert', $record->invoice_number ?? '#'.$record->id, 'heroicon-o-document-text', 'info'],
            $record instanceof ServiceTransaction => ['service', 'ServiceDueAlert', $record->transaction_number ?? '#'.$record->id, 'heroicon-o-document-text', 'info'],
            default => ['generic', 'GenericAlert', '#'.$record->id, 'heroicon-o-bell', 'primary'],
        };

        $document = $docType ? __('notifications.documents.'.($docType === 'insurance' ? 'health_insurance' : $docType)) : __('notifications.documents.iqama');
        $parameters = ['name' => $name, 'document' => $document];

        return new BaseSystemNotification(
            eventKey: $event,
            customTitle: __("notifications.manual.{$kind}_title", $parameters),
            customMessage: __("notifications.manual.{$kind}_body", $parameters),
            icon: $icon,
            color: $color,
        );
    }
}
