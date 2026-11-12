<?php

namespace App\Http\Controllers;

use App\Models\Debt;
use App\Services\DebtSummaryService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class DebtSummaryPrintController extends Controller
{
    public function __invoke(Request $request, ?int $debt = null): View
    {
        Gate::authorize('viewAny', Debt::class);

        $filters = $request->validate([
            'company_id' => ['nullable'],
            'branch_id' => ['nullable'],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d'],
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable'],
            'debtor_type' => ['nullable'],
        ]);

        return view('debts.summary-print', [
            'rows' => $debt === null ? DebtSummaryService::summaryQuery($filters)->get() : null,
            'details' => $debt === null ? null : DebtSummaryService::details($debt, $filters),
            'stats' => $debt === null ? DebtSummaryService::stats($filters) : null,
            'filters' => $filters,
        ]);
    }
}
