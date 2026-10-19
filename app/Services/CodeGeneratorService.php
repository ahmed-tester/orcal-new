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

        $includeCompanyId = static::modelHasCompanyId($modelClass);

        $baseParts = [$prefix];
        if ($includeCompanyId && $companyId) {
            $baseParts[] = 'C' . $companyId;
        }
        if ($datePart) {
            $baseParts[] = date($datePart);
        }
        $codePrefix = implode('-', $baseParts) . '-';

        // Find the maximum existing sequence number for this prefix
        $existingCodes = $modelClass::withoutGlobalScopes()
            ->where($column, 'like', "{$codePrefix}%")
            ->pluck($column);

        $maxSeq = 0;
        foreach ($existingCodes as $existingCode) {
            $seqPart = substr($existingCode, strlen($codePrefix));
            if (is_numeric($seqPart)) {
                $maxSeq = max($maxSeq, (int) $seqPart);
            }
        }

        // Count existing records as a baseline
        $count = $modelClass::withoutGlobalScopes()
            ->whereNotNull($column)
            ->where($column, '!=', '')
            ->when($includeCompanyId && $companyId, fn ($q) => $q->where('company_id', $companyId))
            ->count();

        $nextSeq = max($maxSeq + 1, $count + 1, 1);
        $code = static::buildCode($prefix, $companyId, $nextSeq, $padLength, $datePart, $includeCompanyId);

        // Ensure global uniqueness across the entire table (not scoped to company_id,
        // because unique constraints on number columns are table-wide)
        while ($modelClass::withoutGlobalScopes()->where($column, $code)->exists()) {
            $nextSeq++;
            $code = static::buildCode($prefix, $companyId, $nextSeq, $padLength, $datePart, $includeCompanyId);
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
