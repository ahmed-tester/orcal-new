<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Owner;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

class OwnerSalaryService
{
    public static function amountRules(): array
    {
        return ['required', 'numeric', 'decimal:0,2', 'between:0,999999999999.99'];
    }

    public static function rowsForReview(): array
    {
        return self::scopedOwners()
            ->with(['user', 'company'])
            ->orderBy('id')
            ->get()
            ->filter(fn (Owner $owner) => Gate::allows('update', $owner))
            ->map(fn (Owner $owner) => [
                'owner_id' => $owner->id,
                'owner_name' => $owner->name,
                'company_name' => $owner->company?->trade_name ?? '—',
                'basic_salary' => $owner->basic_salary,
            ])
            ->values()
            ->all();
    }

    public static function save(array $rows): int
    {
        $data = Validator::make(['owners' => $rows], [
            'owners' => ['required', 'array', 'min:1'],
            'owners.*.owner_id' => ['required', 'integer', 'distinct', 'min:1'],
            'owners.*.basic_salary' => self::amountRules(),
        ])->validate();

        return DB::transaction(function () use ($data): int {
            $salaries = collect($data['owners'])->pluck('basic_salary', 'owner_id');
            $owners = self::scopedOwners()
                ->whereKey($salaries->keys()->all())
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            abort_unless($owners->count() === $salaries->count(), 403);

            foreach ($owners as $owner) {
                Gate::authorize('update', $owner);
                $owner->basic_salary = $salaries[$owner->id];
                if ($owner->isDirty('metadata')) {
                    $owner->save();
                }
            }

            return $owners->count();
        }, 3);
    }

    private static function scopedOwners(): Builder
    {
        $query = CompanyAccessScopeService::apply(Owner::query());
        $user = auth()->user();

        // Owners have a company but no branch column.
        if ($user?->branch_id && ! $user->canAccessAllBranches()) {
            $query->whereIn('company_id', Branch::query()->select('company_id')->whereKey($user->branch_id));
        }

        return $query;
    }
}
