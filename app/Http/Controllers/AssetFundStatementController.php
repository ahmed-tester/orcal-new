<?php

namespace App\Http\Controllers;

use App\Models\AssetFund;
use App\Models\AssetFundVoucher;
use App\Services\AssetFundStatementService;
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

        $depositsSummary    = AssetFundStatementService::depositsSummary($depositVouchers);
        $withdrawalsSummary = AssetFundStatementService::withdrawalsSummary($withdrawalVouchers);
        $summary            = AssetFundStatementService::summary($depositVouchers, $withdrawalVouchers);

        // Selected columns/cards; missing parameters fall back to every available column.
        $depositColumns = AssetFundStatementService::sanitizeColumns(
            $request->query('deposit_cols'),
            array_keys(AssetFundStatementService::depositColumns()),
        );

        $withdrawalColumns = AssetFundStatementService::sanitizeColumns(
            $request->query('withdrawal_cols'),
            array_keys(AssetFundStatementService::withdrawalColumns()),
        );

        $cards = AssetFundStatementService::sanitizeColumns(
            $request->query('cards'),
            array_keys(AssetFundStatementService::summaryCards()),
        );

        $mpdf = $this->buildPdf(
            fund: $fund,
            vouchers: $vouchers,
            depositVouchers: $depositVouchers,
            withdrawalVouchers: $withdrawalVouchers,
            depositsSummary: $depositsSummary,
            withdrawalsSummary: $withdrawalsSummary,
            summary: $summary,
            depositColumns: $depositColumns,
            withdrawalColumns: $withdrawalColumns,
            cards: $cards,
            filters: [
                'date_from'   => $dateFrom,
                'date_to'     => $dateTo,
                'filter_type' => $filterType,
                'preset'      => $request->query('preset', 'all'),
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
        array $depositColumns,
        array $withdrawalColumns,
        array $cards,
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
            'depositColumns',
            'withdrawalColumns',
            'cards',
            'filters'
        ))->render();

        $html = preg_replace('/^\xEF\xBB\xBF/', '', $html);

        $mpdf->WriteHTML($html);

        return $mpdf;
    }
}
