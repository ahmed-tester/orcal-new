<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Services\CompanyFormService;
use App\Services\ExportPdfService;
use App\Services\HijriDateService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class CompanyPrintController extends Controller
{
    /**
     * Preview single company official profile sheet for print.
     */
    public function print(Company $company): View
    {
        $this->authorizeCompanyAccess($company);

        $company->load([
            'branches',
            'owners.user',
            'attachments',
        ])->loadCount(['branches', 'employees']);

        return view('companies.print', [
            'company' => $company,
            'isPdf' => false,
            'printed_at' => now()->format('Y/m/d H:i'),
            'hijri_date' => HijriDateService::formatDual(now(), true),
            'printed_by' => auth()->user()?->name ?? __('organization.companies.print.unspecified'),
        ]);
    }

    /**
     * Redirect to company official profile print sheet.
     */
    public function pdf(Company $company)
    {
        $this->authorizeCompanyAccess($company);

        return redirect()->route('companies.print', $company);
    }

    /**
     * Preview or export directory statement of all authorized companies.
     */
    public function statement(Request $request): Response|View
    {
        $user = auth()->user();
        $isSuperAdmin = $user?->isSuperAdmin() ?? false;
        $authorizedCompanyIds = $user ? array_map('intval', CompanyFormService::getAuthorizedCompanyIds($user)) : [];

        if (! $isSuperAdmin && empty($authorizedCompanyIds)) {
            abort(403);
        }

        $query = Company::withCount(['branches', 'employees'])
            ->orderBy('id', 'desc');

        if (! $isSuperAdmin) {
            $query->whereIn('id', $authorizedCompanyIds);
        }

        $status = $request->query('status', 'all');
        if (! empty($status) && $status !== 'all') {
            $query->where('status', $status);
        }

        $isExpiringSoon = $request->boolean('expiring_soon');
        if ($isExpiringSoon) {
            $query->whereNotNull('commercial_registration_expiry')
                ->whereBetween('commercial_registration_expiry', [now()->subDays(30), now()->addDays(30)]);
        }

        $companies = $query->get();

        $stats = [
            'total' => $companies->count(),
            'branches' => $companies->sum('branches_count'),
            'employees' => $companies->sum('employees_count'),
            'active' => $companies->filter(fn ($c) => ($c->status instanceof \BackedEnum ? $c->status->value : (string) $c->status) === 'active')->count(),
            'expiring' => $companies->filter(function ($c) {
                if (! $c->commercial_registration_expiry) {
                    return false;
                }
                $days = (int) now()->diffInDays(Carbon::parse($c->commercial_registration_expiry), false);

                return $days <= 30;
            })->count(),
        ];

        if ($request->query('export_mode') === 'pdf') {
            return $this->exportStatementPdf($companies, $stats);
        }

        return response()->view('companies.statement-print', [
            'title' => __('organization.companies.print.statement_title'),
            'companies' => $companies,
            'stats' => $stats,
            'status' => $status,
            'isExpiringSoon' => $isExpiringSoon,
            'date_label' => now()->format('Y/m/d'),
            'hijri_date' => HijriDateService::formatDual(now(), true),
            'printed_by' => auth()->user()?->name ?? __('organization.companies.print.unspecified'),
        ]);
    }

    /**
     * Export statement table as PDF report.
     */
    protected function exportStatementPdf($companies, array $stats): Response
    {
        $headers = [
            __('organization.companies.print.col_index'),
            __('organization.companies.print.col_company'),
            __('organization.companies.print.col_code'),
            __('organization.companies.print.col_cr'),
            __('organization.companies.print.col_contact'),
            __('organization.companies.print.col_branches'),
            __('organization.companies.print.col_employees'),
            __('organization.companies.print.col_status'),
        ];

        $rows = $companies->map(function ($c, $i) {
            $statusVal = $c->status instanceof \BackedEnum ? $c->status->value : (string) ($c->status ?? 'active');
            $statusBadge = $statusVal === 'active'
                ? '<span class="badge badge-success">' . __('organization.companies.print.active') . '</span>'
                : '<span class="badge badge-danger">' . __('organization.companies.print.inactive') . '</span>';

            $crText = $c->commercial_registration_number ?? '—';
            if ($c->commercial_registration_expiry) {
                $crText .= '<br><small>' . $c->commercial_registration_expiry->format('Y-m-d') . '</small>';
            }

            $contact = $c->phone ? '📱 ' . $c->phone : '';
            if ($c->email) {
                $contact .= ($contact ? '<br>' : '') . '✉️ ' . $c->email;
            }
            if (empty($contact)) {
                $contact = '—';
            }

            return [
                $i + 1,
                "<strong>{$c->trade_name}</strong>" . ($c->trade_name_en ? "<br><small style=\"color:#64748b;\">{$c->trade_name_en}</small>" : ''),
                $c->company_number ?? '—',
                $crText,
                $contact,
                $c->branches_count ?? 0,
                $c->employees_count ?? 0,
                $statusBadge,
            ];
        })->toArray();

        return ExportPdfService::exportTableReport(
            title: __('organization.companies.print.statement_title'),
            headers: $headers,
            rows: $rows,
            options: [
                'orientation' => 'L',
                'subtitle' => __('organization.companies.print.statement_subtitle'),
                'totals' => [
                    __('organization.companies.print.total_companies') => $stats['total'],
                    __('organization.companies.print.total_branches') => $stats['branches'],
                    __('organization.companies.print.total_employees') => $stats['employees'],
                    __('organization.companies.print.active_companies') => $stats['active'],
                ],
                'filename' => 'companies-statement-' . date('Ymd-His'),
            ]
        );
    }

    /**
     * Check if authenticated user is authorized to access the company.
     */
    protected function authorizeCompanyAccess(Company $company): void
    {
        $user = auth()->user();

        if (! $user) {
            abort(403);
        }

        if ($user->isSuperAdmin()) {
            return;
        }

        $authorizedIds = CompanyFormService::getAuthorizedCompanyIds($user);

        if (! in_array($company->id, $authorizedIds, true)) {
            abort(403);
        }
    }
}
