<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;

class CodeGeneratorService
{
    /**
     * Generate a sequential code scoped by company_id.
     *
     * @param  class-string<Model>  $modelClass   The Eloquent model class.
     * @param  string               $column       The code/number column name.
     * @param  string               $prefix       The prefix (e.g. 'EMP', 'INV', 'BR').
     * @param  int|null             $companyId    The company ID to scope the sequence.
     * @param  int                  $padLength    How many digits to pad the sequence to.
     * @param  string|null          $datePart     Optional date format to include (e.g. 'Ym', 'Ymd').
     * @return string
     */
    public static function generate(
        string  $modelClass,
        string  $column,
        string  $prefix,
        ?int    $companyId = null,
        int     $padLength = 4,
        ?string $datePart = null,
    ): string {
        $companyId = $companyId ?: static::resolveCompanyId();

        // Count existing records for this company to determine next sequence
        $query = $modelClass::withoutGlobalScopes()
            ->whereNotNull($column)
            ->where($column, '!=', '');

        if (static::modelHasCompanyId($modelClass)) {
            $query->where('company_id', $companyId);
        }

        $count = $query->count();
        $nextSeq = $count + 1;

        $code = static::buildCode($prefix, $companyId, $nextSeq, $padLength, $datePart, static::modelHasCompanyId($modelClass));

        // Ensure uniqueness across the entire table to prevent database unique constraint violations
        while ($modelClass::withoutGlobalScopes()->where($column, $code)->exists()) {
            $nextSeq++;
            $code = static::buildCode($prefix, $companyId, $nextSeq, $padLength, $datePart, static::modelHasCompanyId($modelClass));
        }

        return $code;
    }

    /**
     * Build the formatted code string.
     *
     * Format: PREFIX-C{companyId}-{datePart}-{sequence}
     */
    protected static function buildCode(
        string $prefix,
        ?int   $companyId,
        int    $seq,
        int    $padLength,
        ?string $datePart,
        bool   $includeCompanyId,
    ): string {
        $parts = [$prefix];

        if ($includeCompanyId && $companyId) {
            $parts[] = 'C' . $companyId;
        }

        if ($datePart) {
            $parts[] = date($datePart);
        }

        $parts[] = str_pad((string) $seq, $padLength, '0', STR_PAD_LEFT);

        return implode('-', $parts);
    }

    /**
     * Resolve the current user's company ID as a fallback.
     */
    protected static function resolveCompanyId(): ?int
    {
        return CompanyFormService::getDefaultCompanyId();
    }

    /**
     * Check if the model's table has a company_id column.
     */
    protected static function modelHasCompanyId(string $modelClass): bool
    {
        static $cache = [];

        if (!isset($cache[$modelClass])) {
            $instance = new $modelClass;
            $cache[$modelClass] = in_array('company_id', $instance->getFillable());
        }

        return $cache[$modelClass];
    }
}
