<?php

namespace App\Services;

use BackedEnum;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class FilterBarService
{
    /**
     * Apply a declarative set of filters to an Eloquent builder.
     *
     * Supported options:
     * - search_columns: array<string> supports "relation.column" and "relation.nested.column".
     * - company: bool + company_column.
     * - branch: bool + branch_column (dotted columns use whereHas).
     * - status: bool + status_column.
     * - date_column: string.
     * - where_in: array<string, mixed> generic multi-select filters.
     * - extra: array<callable(Builder, array): void> page-specific filters.
     *
     * The caller is responsible for applying the access scope first
     * (HasCompanyScope::getEloquentQuery() or the service's applyAccessScope()).
     */
    public static function applyFilters(Builder $query, array $state, array $options = []): Builder
    {
        $search = trim((string) ($state['search'] ?? ''));

        if ($search !== '') {
            $columns = $options['search_columns'] ?? ['name'];

            $query->where(function (Builder $group) use ($columns, $search): void {
                foreach ($columns as $column) {
                    static::applyLike($group, $column, $search, 'or');
                }
            });
        }

        if (! empty($options['company'])) {
            static::applyIn($query, $options['company_column'] ?? 'company_id', $state['company_id'] ?? null);
        }

        if (! empty($options['branch'])) {
            static::applyIn($query, $options['branch_column'] ?? 'branch_id', $state['branch_id'] ?? null);
        }

        if (! empty($options['status'])) {
            static::applyIn($query, $options['status_column'] ?? 'status', $state['status'] ?? null, ['all']);
        }

        $dateColumn = $options['date_column'] ?? null;
        if (! empty($dateColumn)) {
            if (! empty($state['date_from'])) {
                $query->whereDate($dateColumn, '>=', $state['date_from']);
            }

            if (! empty($state['date_to'])) {
                $query->whereDate($dateColumn, '<=', $state['date_to']);
            }
        }

        foreach (($options['where_in'] ?? []) as $column => $value) {
            static::applyIn($query, $column, $value);
        }

        foreach (($options['extra'] ?? []) as $callback) {
            if (is_callable($callback)) {
                $callback($query, $state);
            }
        }

        return $query;
    }

    /**
     * Normalize any filter value into a list of scalar database values.
     * Keeps 0 / "0" and drops nulls, empty strings and ignored markers.
     */
    public static function values(mixed $value, array $ignore = []): array
    {
        $items = is_array($value) ? $value : [$value];
        $values = [];

        foreach ($items as $item) {
            if ($item instanceof BackedEnum) {
                $item = $item->value;
            } elseif ($item instanceof UnitEnum) {
                $item = $item->name;
            }

            if ($item === null || $item === '' || in_array($item, $ignore, true)) {
                continue;
            }

            $values[] = $item;
        }

        return array_values(array_unique($values, SORT_REGULAR));
    }

    /**
     * Apply a scalar or multi-select filter, optionally through a relation.
     */
    protected static function applyIn(Builder $query, string $column, mixed $value, array $ignore = []): void
    {
        $values = static::values($value, $ignore);

        if ($values === []) {
            return;
        }

        [$relation, $columnName] = static::splitColumn($column);

        if ($relation === null) {
            if (count($values) === 1) {
                $query->where($columnName, $values[0]);
            } else {
                $query->whereIn($columnName, $values);
            }

            return;
        }

        $query->whereHas($relation, function (Builder $related) use ($columnName, $values): void {
            if (count($values) === 1) {
                $related->where($columnName, $values[0]);
            } else {
                $related->whereIn($columnName, $values);
            }
        });
    }

    /**
     * Add a LIKE condition, optionally through a relation.
     */
    protected static function applyLike(Builder $query, string $column, string $term, string $boolean = 'and'): void
    {
        [$relation, $columnName] = static::splitColumn($column);
        $pattern = "%{$term}%";

        if ($relation !== null) {
            $method = $boolean === 'or' ? 'orWhereHas' : 'whereHas';
            $query->{$method}($relation, fn (Builder $related) => $related->where($columnName, 'like', $pattern));

            return;
        }

        if ($boolean === 'or') {
            $query->orWhere($columnName, 'like', $pattern);
        } else {
            $query->where($columnName, 'like', $pattern);
        }
    }

    /**
     * Split "relation.nested.column" into [relation, column].
     *
     * @return array{0: string|null, 1: string}
     */
    protected static function splitColumn(string $column): array
    {
        $parts = explode('.', $column);
        $columnName = array_pop($parts);
        $relation = $parts === [] ? null : implode('.', $parts);

        return [$relation, $columnName];
    }
}
