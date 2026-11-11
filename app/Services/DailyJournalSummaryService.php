<?php

namespace App\Services;

use App\Models\DailyJournal;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class DailyJournalSummaryService
{
    public const TOTALS = ['sales_sum', 'purchases_sum', 'net_sum'];

    private const GROUP_COLUMNS = 'company_id, branch_id';

    public static function journals(): Builder
    {
        $query = CompanyAccessScopeService::apply(DailyJournal::query());
        $user = auth()->user();

        if ($user && ! $user->isSuperAdmin() && $user->branch_id && ! $user->canAccessAllBranches()) {
            $query->whereIn('daily_journals.company_id', CompanyFormService::getAuthorizedCompanyIds($user));
        }

        return $query;
    }

    public static function summaryQuery(array $filters): Builder
    {
        return DailyJournal::query()
            ->joinSub(self::grouped($filters), 'summary', 'daily_journals.id', '=', 'summary.row_id')
            ->select('daily_journals.*', 'summary.entries_count', ...array_map(fn ($key) => "summary.{$key}", self::TOTALS))
            ->with(['company', 'branch'])
            ->orderByDesc('summary.net_sum')
            ->orderBy('daily_journals.id');
    }

    public static function stats(array $filters): object
    {
        return DB::query()->fromSub(self::grouped($filters), 'summary')
            ->selectRaw('COUNT(*) AS branches')
            ->selectRaw('COALESCE(SUM(entries_count), 0) AS entries_count')
            ->selectRaw(implode(', ', array_map(fn ($key) => "COALESCE(SUM({$key}), 0) AS {$key}", self::TOTALS)))
            ->first();
    }

    public static function details(int $id, array $filters): array
    {
        $query = self::filtered($filters);
        $journal = (clone $query)->with(['company', 'branch'])->find($id);
        abort_if($journal === null, 404);

        $query->where('company_id', $journal->company_id);

        if ($journal->branch_id === null) {
            $query->whereNull('branch_id');
        } else {
            $query->where('branch_id', $journal->branch_id);
        }

        return [
            'journal' => $journal,
            'entries' => $query->orderByDesc('entry_date')->orderByDesc('id')->get(),
        ];
    }

    private static function grouped(array $filters): Builder
    {
        return self::filtered($filters)
            ->selectRaw('MIN(id) AS row_id, COUNT(*) AS entries_count, SUM(sales) AS sales_sum,
                SUM(purchases) AS purchases_sum, SUM(net_amount) AS net_sum')
            ->groupByRaw(self::GROUP_COLUMNS);
    }

    private static function filtered(array $filters): Builder
    {
        return FilterBarService::applyFilters(self::journals(), $filters, [
            'company' => true,
            'branch' => true,
            'date_column' => 'entry_date',
            'search_columns' => ['entry_number', 'company.trade_name', 'branch.name'],
            'where_in' => [
                'is_detailed' => $filters['is_detailed'] ?? null,
            ],
        ]);
    }
}
