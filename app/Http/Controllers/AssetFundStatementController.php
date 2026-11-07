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

        $baseQuery = fn () => AssetFundVoucher::with(['company', 'creator', 'linkedDepositVoucher'])
            ->where('asset_fund_id', $fund->id)
            ->when($dateFrom, fn ($q) => $q->whereDate('voucher_date', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->whereDate('voucher_date', '<=', $dateTo))
            ->orderBy('voucher_date');

        $depositVouchers = (clone $baseQuery())
            ->where(function ($q) {
                $q->where('type', 'deposit')
                  ->orWhere('type', \App\Enums\AssetFundVoucherType::DEPOSIT->value);
            })
            ->get();

        $withdrawalVouchers = (clone $baseQuery())
            ->where(function ($q) {
                $q->where('type', 'withdrawal')
                  ->orWhere('type', \App\Enums\AssetFundVoucherType::WITHDRAWAL->value);
            })
            ->get();

        $vouchers = match ($filterType) {
            'deposit'    => $depositVouchers,
            'withdrawal' => $withdrawalVouchers,
            default      => $depositVouchers->concat($withdrawalVouchers)->sortBy('voucher_date')->values(),
        };

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

        $cards = AssetFundStatementService::resolveActiveCards(
            $request->query('cards'),
            $filterType,
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
