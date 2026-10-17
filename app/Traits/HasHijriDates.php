<?php

namespace App\Traits;

use App\Services\HijriDateService;
use Illuminate\Database\Eloquent\Builder;

trait HasHijriDates
{
    /**
     * Boot the trait and automatically compute and save Hijri dates into metadata on model saving.
     */
    public static function bootHasHijriDates(): void
    {
        static::saving(function ($model) {
            if (!method_exists($model, 'getHijriDateFieldNames')) {
                return;
            }

            $meta = $model->metadata ?? [];
            if (!is_array($meta)) {
                $meta = [];
            }

            $hijriDates = $meta['hijri_dates'] ?? [];
            if (!is_array($hijriDates)) {
                $hijriDates = [];
            }

            foreach ($model->getHijriDateFieldNames() as $field) {
                $dateVal = $model->{$field};
                if (!empty($dateVal)) {
                    $hijriDates[$field] = HijriDateService::toHijriStandard($dateVal) ?? HijriDateService::toHijriNumeric($dateVal);
                } else {
                    unset($hijriDates[$field]);
                }
            }

            $meta['hijri_dates'] = $hijriDates;
            $model->metadata = $meta;
        });
    }

    /**
     * Get the Hijri representation of a date attribute.
     */
    public function getHijriDate(string $field): ?string
    {
        $cached = $this->metadata['hijri_dates'][$field] ?? null;
        if (!empty($cached)) {
            return $cached;
        }

        $dateVal = $this->{$field} ?? null;
        if (!empty($dateVal)) {
            return HijriDateService::toHijriNumeric($dateVal);
        }

        return null;
    }

    /**
     * Query scope to filter records by a Hijri date within metadata.
     *
     * Example: Model::whereHijriDate('iqama_expiry', '1448/05/02')
     * Example: Model::whereHijriDateLike('iqama_expiry', '1448%')
     */
    public function scopeWhereHijriDate(Builder $query, string $field, string $operator, mixed $value = null): Builder
    {
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }

        return $query->where("metadata->hijri_dates->{$field}", $operator, $value);
    }

    /**
     * Query scope to filter records by a partial Hijri date pattern (LIKE).
     */
    public function scopeWhereHijriDateLike(Builder $query, string $field, string $pattern): Builder
    {
        return $query->where("metadata->hijri_dates->{$field}", 'LIKE', $pattern);
    }
}
