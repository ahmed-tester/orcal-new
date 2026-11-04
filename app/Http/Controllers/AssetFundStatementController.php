<?php

namespace App\Http\Controllers;

use App\Models\AssetFund;
use App\Models\AssetFundVoucher;
use Illuminate\Http\Request;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Mpdf;

class AssetFundStatementController extends Controller
{
    /**
     * Generate and stream the Asset Fund Statement as a PDF.
     */
    public function pdf(Request $request)
    {
        $fundId     = $request->query('fund_id');
        $dateFrom   = $request->query('date_from');
        $dateTo     = $request->query('date_to');
        $filterType = $request->query('filter_type', 'all');

        $fund = AssetFund::findOrFail($fundId);

        $user = auth()->user();
        $employeeId = $user?->employee?->id;
        $isManagerOrAccountant = $employeeId && ($fund->manager_employee_id === $employeeId || $fund->accountant_employee_id === $employeeId);

        if (! ($user?->isSuperAdminOrOwner() || $user?->can('view_asset_fund_statement') || $user?->can('view_any_asset_fund') || $isManagerOrAccountant)) {
            abort(403, __('asset_funds.statement.unauthorized'));
        }

        $query = AssetFundVoucher::with(['company', 'creator'])
            ->where('asset_fund_id', $fund->id);

        if ($filterType !== 'all') {
            $query->where('type', $filterType);
        }
        if ($dateFrom) {
            $query->whereDate('voucher_date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate('voucher_date', '<=', $dateTo);
        }

        $vouchers = $query->orderBy('voucher_date')->get();

        $depositVouchers    = $vouchers->filter(fn ($v) => $v->type?->value === 'deposit')->values();
        $withdrawalVouchers = $vouchers->filter(fn ($v) => $v->type?->value === 'withdrawal')->values();

        $depositsSummary = [
            'gross_amount'         => (float) $depositVouchers->sum('gross_amount'),
            'tax_amount'           => (float) $depositVouchers->sum('tax_amount'),
            'net_after_tax'        => (float) $depositVouchers->sum('net_after_tax'),
            'retention_amount'     => (float) $depositVouchers->sum('retention_amount'),
            'fund_share_amount'    => (float) $depositVouchers->sum('fund_share_amount'),
            'net_after_deductions' => (float) $depositVouchers->sum('total_amount'),
        ];

        $withdrawalsSummary = [
            'amount_without_tax' => (float) $withdrawalVouchers->sum('amount_without_tax'),
            'tax_amount'         => (float) $withdrawalVouchers->sum('tax_amount'),
            'total_amount'       => (float) $withdrawalVouchers->sum('total_amount'),
        ];

        $totalDeposits    = (float) $depositVouchers->sum('total_amount');
        $totalWithdrawals = (float) $withdrawalVouchers->sum('total_amount');
        $netBalance       = $totalDeposits - $totalWithdrawals;

        $summary = [
            'total_deposits'    => $totalDeposits,
            'total_withdrawals' => $totalWithdrawals,
            'net_balance'       => $netBalance,
        ];

        $mpdf = $this->buildPdf(
            fund: $fund,
            vouchers: $vouchers,
            depositVouchers: $depositVouchers,
            withdrawalVouchers: $withdrawalVouchers,
            depositsSummary: $depositsSummary,
            withdrawalsSummary: $withdrawalsSummary,
            summary: $summary,
            filters: [
                'date_from'   => $dateFrom,
                'date_to'     => $dateTo,
                'filter_type' => $filterType,
            ]
        );

        $filename = 'asset-fund-statement-' . $fund->fund_number . '.pdf';

        return response($mpdf->Output($filename, 'S'), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
        ]);
    }

    private function buildPdf(
        AssetFund $fund,
        $vouchers,
        $depositVouchers,
        $withdrawalVouchers,
        array $depositsSummary,
        array $withdrawalsSummary,
        array $summary,
        array $filters
    ): Mpdf {
        $defaultConfig = (new ConfigVariables())->getDefaults();
        $defaultFonts  = (new FontVariables())->getDefaults();

        $mpdf = new Mpdf([
            'mode'             => 'utf-8',
            'format'           => 'A4-L',
            'margin_top'       => 10,
            'margin_bottom'    => 10,
            'margin_left'      => 8,
            'margin_right'     => 8,
            'default_font'     => 'dejavusans',
            'autoScriptToLang' => true,
            'autoLangToFont'   => true,
            'autoArabic'       => true,
            'baseScript'       => 1,
            'biDirectional'    => true,
            'fontDir'          => array_merge($defaultConfig['fontDir'], [storage_path('fonts')]),
            'fontdata'         => $defaultFonts['fontdata'],
        ]);

        $mpdf->SetDirectionality('rtl');
        $mpdf->autoScriptToLang = true;
        $mpdf->autoLangToFont   = true;

        $html = view('pdf.asset-fund-statement', compact(
            'fund',
            'vouchers',
            'depositVouchers',
            'withdrawalVouchers',
            'depositsSummary',
            'withdrawalsSummary',
            'summary',
            'filters'
        ))->render();

        $html = preg_replace('/^\xEF\xBB\xBF/', '', $html);

        $mpdf->WriteHTML($html);

        return $mpdf;
    }
}
