<?php

namespace App\Http\Controllers;

use App\Models\DailyJournal;
use App\Services\DailyJournalSummaryService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class DailyJournalSummaryPrintController extends Controller
{
    public function __invoke(Request $request, ?int $journal = null): View
    {
        Gate::authorize('viewAny', DailyJournal::class);

        $filters = $request->validate([
            'company_id' => ['nullable'],
            'branch_id' => ['nullable'],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d'],
            'search' => ['nullable', 'string', 'max:255'],
            'is_detailed' => ['nullable'],
        ]);

        return view('daily-journals.summary-print', [
            'rows' => $journal === null ? DailyJournalSummaryService::summaryQuery($filters)->get() : null,
            'details' => $journal === null ? null : DailyJournalSummaryService::details($journal, $filters),
            'stats' => $journal === null ? DailyJournalSummaryService::stats($filters) : null,
            'filters' => $filters,
        ]);
    }
}
