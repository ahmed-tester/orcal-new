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

        $totalDeposits    = $vouchers->filter(fn ($v) => $v->type?->value === 'deposit')->sum('total_amount');
        $totalWithdrawals = $vouchers->filter(fn ($v) => $v->type?->value === 'withdrawal')->sum('total_amount');
        $netBalance       = $totalDeposits - $totalWithdrawals;

        $summary = [
            'total_deposits'    => (float) $totalDeposits,
            'total_withdrawals' => (float) $totalWithdrawals,
            'net_balance'       => (float) $netBalance,
        ];

        $mpdf = $this->buildPdf($fund, $vouchers, $summary, [
            'date_from'   => $dateFrom,
            'date_to'     => $dateTo,
            'filter_type' => $filterType,
        ]);

        $filename = 'asset-fund-statement-' . $fund->fund_number . '.pdf';

        return response($mpdf->Output($filename, 'S'), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
        ]);
    }

    private function buildPdf(AssetFund $fund, $vouchers, array $summary, array $filters): Mpdf
    {
        $defaultConfig = (new ConfigVariables())->getDefaults();
        $defaultFonts  = (new FontVariables())->getDefaults();

        $mpdf = new Mpdf([
            'mode'             => 'utf-8',
            'format'           => 'A4',
            'margin_top'       => 15,
            'margin_bottom'    => 15,
            'margin_left'      => 12,
            'margin_right'     => 12,
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

        $html = view('pdf.asset-fund-statement', compact('fund', 'vouchers', 'summary', 'filters'))->render();
        $html = preg_replace('/^\xEF\xBB\xBF/', '', $html);

        $mpdf->WriteHTML($html);

        return $mpdf;
    }
}

