<?php

namespace App\Filament\Resources\Attendance\Pages;

use App\Filament\Resources\Attendance\AttendanceResource;
use App\Models\Attendance;
use App\Models\Company;
use App\Models\Employee;
use App\Services\Attendance\AttendanceSummaryService;
use App\Services\CompanyFormService;
use App\Services\Attendance\SaudiAttendancePeriodReportService;
use Filament\Actions\Action;
use Filament\Resources\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SaudiPeriodReport extends Page
{
    protected static string $resource = AttendanceResource::class;

    public string $periodPreset = 'month';

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    public ?string $companyId = null;

    public ?string $search = null;

    public ?array $details = null;

    public function mount(): void
    {
        [$start, $end] = SaudiAttendancePeriodReportService::resolvePeriodBounds([
            'period_preset' => $this->periodPreset,
            'date_from' => $this->dateFrom,
            'date_to' => $this->dateTo,
        ]);

        $this->dateFrom = $start;
        $this->dateTo = $end;
    }

    public function getView(): string
    {
        return 'filament.resources.attendance.pages.saudi-period-report';
    }

    public static function canAccess(array $parameters = []): bool
    {
        return auth()->user()?->can('viewAny', Attendance::class) ?? false;
    }

    public function getTitle(): string|Htmlable
    {
        return __('attendance.period_report.title');
    }

    public function applyPreset(string $preset): void
    {
        $this->periodPreset = in_array($preset, ['day', 'week', 'month', 'custom'], true) ? $preset : 'month';

        if ($this->periodPreset !== 'custom') {
            [$start, $end] = SaudiAttendancePeriodReportService::resolvePeriodBounds([
                'period_preset' => $this->periodPreset,
            ]);
            $this->dateFrom = $start;
            $this->dateTo = $end;
        }
    }

    public function updatedDateFrom(): void
    {
        $this->periodPreset = 'custom';
    }

    public function updatedDateTo(): void
    {
        $this->periodPreset = 'custom';
    }

    public function filtersArray(): array
    {
        return array_filter([
            'period_preset' => $this->periodPreset,
            'date_from' => $this->dateFrom,
            'date_to' => $this->dateTo,
            'company_id' => $this->companyId ? [(int) $this->companyId] : null,
            'search' => $this->search,
        ], fn ($v) => $v !== null && $v !== '');
    }

    public function getReportData(): array
    {
        return SaudiAttendancePeriodReportService::buildReport($this->filtersArray(), auth()->user());
    }

    public function getCompanyOptions(): array
    {
        $user = auth()->user();
        if (! $user) {
            return [];
        }

        $ids = CompanyFormService::getAuthorizedCompanyIds($user);
        if (empty($ids)) {
            return [];
        }

        return Company::query()
            ->whereIn('id', $ids)
            ->orderBy('trade_name')
            ->pluck('trade_name', 'id')
            ->all();
    }

    public function openEmployeeDetails(int $employeeId): void
    {
        $employee = Employee::with(['user', 'company', 'branch'])->find($employeeId);
        if (! $employee || ! AttendanceSummaryService::canAccessEmployee($employee)) {
            return;
        }

        $report = $this->getReportData();
        $this->details = SaudiAttendancePeriodReportService::buildEmployeePeriodDetails(
            $employee,
            $report['date_from'],
            $report['date_to'],
            auth()->user()
        );

        $this->dispatch('open-modal', id: 'saudi-period-details-modal');
    }

    public function exportCsv(): StreamedResponse
    {
        $filters = $this->filtersArray();
        $csv = SaudiAttendancePeriodReportService::buildCsv($filters, auth()->user());
        $filename = 'saudi-attendance-period-'.($this->dateFrom ?? now()->toDateString()).'-to-'.($this->dateTo ?? now()->toDateString()).'.csv';

        return response()->streamDownload(function () use ($csv): void {
            echo $csv;
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('printReport')
                ->label(__('attendance.period_report.print_action'))
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->url(fn (): string => route('attendance.saudi-period.print', $this->filtersArray()), shouldOpenInNewTab: true),

            Action::make('exportCsv')
                ->label(__('attendance.period_report.export_csv_action'))
                ->icon('heroicon-o-arrow-down-tray')
                ->color('primary')
                ->action('exportCsv'),

            Action::make('backToSummary')
                ->label(__('attendance.summary.title'))
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('gray')
                ->url(fn (): string => AttendanceResource::getUrl('index')),
        ];
    }
}
