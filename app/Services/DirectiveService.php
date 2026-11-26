<?php

namespace App\Services;

use App\Models\Directives\Directive;
use App\Models\Directives\DirectiveRecipient;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class DirectiveService
{
    /**
     * Sync recipients for a directive based on target_type.
     * - TARGET_ALL: creates a DirectiveRecipient for every active employee user.
     * - TARGET_SELECTED: creates a DirectiveRecipient for each given employee_id.
     *
     * Existing acknowledged rows are preserved; only unacknowledged rows are
     * removed if the employee is no longer in the target list.
     *
     * @param  array<int>  $selectedEmployeeIds  Used only when target_type = selected
     */
    public static function syncRecipients(Directive $directive, array $selectedEmployeeIds = []): void
    {
        DB::transaction(function () use ($directive, $selectedEmployeeIds): void {
            if ($directive->target_type === Directive::TARGET_ALL) {
                $employeeUserPairs = static::scopedEmployeesQuery()
                    ->get(['id', 'user_id']);

                $existingPairs = DirectiveRecipient::query()
                    ->where('directive_id', $directive->id)
                    ->pluck('user_id')
                    ->flip();

                foreach ($employeeUserPairs as $emp) {
                    if (! $existingPairs->has($emp->user_id)) {
                        DirectiveRecipient::create([
                            'directive_id' => $directive->id,
                            'user_id' => $emp->user_id,
                            'employee_id' => $emp->id,
                        ]);
                        $existingPairs->put($emp->user_id, true);
                    }
                }

                // Remove unacknowledged rows for users no longer active
                $allUserIds = $employeeUserPairs->pluck('user_id')->all();
                DirectiveRecipient::query()
                    ->where('directive_id', $directive->id)
                    ->whereNull('acknowledged_at')
                    ->whereNotIn('user_id', $allUserIds)
                    ->delete();
            } else {
                // TARGET_SELECTED
                $pairs = static::scopedEmployeesQuery()
                    ->whereIn('id', $selectedEmployeeIds)
                    ->get(['id', 'user_id']);

                $existingMap = DirectiveRecipient::query()
                    ->where('directive_id', $directive->id)
                    ->pluck('user_id')
                    ->flip();

                foreach ($pairs as $emp) {
                    if (! $existingMap->has($emp->user_id)) {
                        DirectiveRecipient::create([
                            'directive_id' => $directive->id,
                            'user_id' => $emp->user_id,
                            'employee_id' => $emp->id,
                        ]);
                        $existingMap->put($emp->user_id, true);
                    }
                }

                // Remove unacknowledged rows for users not in selected list
                $selectedUserIds = $pairs->pluck('user_id')->all();
                DirectiveRecipient::query()
                    ->where('directive_id', $directive->id)
                    ->whereNull('acknowledged_at')
                    ->whereNotIn('user_id', $selectedUserIds)
                    ->delete();
            }
        });
    }

    /**
     * Returns options array for the employee multi-select.
     * Format: [employee_id => "Name (Company)"]
     *
     * @return array<int, string>
     */
    public static function getEmployeeSelectOptions(): array
    {
        return static::scopedEmployeesQuery()
            ->with(['user:id,name', 'company:id,trade_name,trade_name_en'])
            ->get()
            ->mapWithKeys(function (Employee $emp): array {
                $name = $emp->user?->name ?? __('directives.labels.employee_fallback', ['id' => $emp->id]);
                $company = $emp->company?->trade_name ?: ($emp->company?->trade_name_en ?: '');
                $label = $company ? "{$name} — {$company}" : $name;

                return [$emp->id => $label];
            })
            ->all();
    }

    /**
     * Build an Employee query constrained by the authenticated user's company/branch scope.
     */
    protected static function scopedEmployeesQuery(): Builder
    {
        $query = Employee::query()->whereNotNull('user_id');

        if (auth()->check()) {
            CompanyAccessScopeService::apply($query);
        }

        return $query;
    }
}
