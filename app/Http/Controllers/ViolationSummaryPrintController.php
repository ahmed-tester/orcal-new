<?php

namespace App\Http\Controllers;

use App\Models\Violation;
use App\Services\ViolationSummaryService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ViolationSummaryPrintController extends Controller
{
    public function __invoke(Request $request, ?int $company = null): View
    {
        Gate::authorize('viewAny', Violation::class);

        $filters = $request->validate([
            'company_id' => ['nullable'],
            'category_id' => ['nullable'],
            'type_id' => ['nullable'],
            'stage' => ['nullable'],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d'],
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable'],
        ]);

        return view('violations.summary-print', [
            'rows' => $company === null ? ViolationSummaryService::summaryQuery($filters)->get() : null,
            'details' => $company === null ? null : ViolationSummaryService::details($company, $filters),
            'stats' => $company === null ? ViolationSummaryService::stats($filters) : null,
            'filters' => $filters,
        ]);
    }
}

