<?php

namespace App\Traits;

use App\Enums\ApprovalStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;

trait HasApprovalStatus
{
    /**
     * Boot the trait and ensure default approval status on model creating.
     */
    public static function bootHasApprovalStatus(): void
    {
        static::creating(function ($model) {
            $raw = $model->attributes['metadata'] ?? null;
            if (is_array($raw)) {
                $meta = $raw;
            } elseif (is_string($raw) && $raw !== '') {
                $meta = json_decode($raw, true) ?: [];
            } else {
                $meta = [];
            }

            if (! isset($meta['status'])) {
                $meta['status'] = ApprovalStatus::UNAPPROVED->value;
                $model->attributes['metadata'] = is_string($raw) ? json_encode($meta) : $meta;
            }
        });
    }

    /**
     * Safe JSON decoding that never throws TypeError if value is already an array.
     */
    public function fromJson($value, $asObject = false)
    {
        if (is_array($value)) {
            return $asObject ? (object) $value : $value;
        }

        return parent::fromJson($value, $asObject);
    }

    /**
     * Virtual status attribute stored inside the metadata JSON column.
     */
    protected function status(): Attribute
    {
        return Attribute::make(
            get: function ($value, array $attributes) {
                $raw = $attributes['metadata'] ?? null;
                if (is_array($raw)) {
                    $meta = $raw;
                } elseif (is_string($raw) && $raw !== '') {
                    $meta = json_decode($raw, true) ?: [];
                } else {
                    $meta = [];
                }
                $val = $meta['status'] ?? ApprovalStatus::UNAPPROVED->value;

                return ApprovalStatus::tryFrom($val) ?? ApprovalStatus::UNAPPROVED;
            },
            set: function ($value, array $attributes) {
                $val = $value instanceof ApprovalStatus ? $value->value : (string) $value;
                $raw = $attributes['metadata'] ?? null;
                if (is_array($raw)) {
                    $meta = $raw;
                } elseif (is_string($raw) && $raw !== '') {
                    $meta = json_decode($raw, true) ?: [];
                } else {
                    $meta = [];
                }
                $meta['status'] = $val ?: ApprovalStatus::UNAPPROVED->value;

                return [
                    'metadata' => is_string($raw) ? json_encode($meta) : $meta,
                ];
            }
        );
    }

    /**
     * Check if the model is approved.
     */
    public function isApproved(): bool
    {
        return $this->status === ApprovalStatus::APPROVED;
    }

    /**
     * Query scope to filter records by approval status in the metadata JSON column.
     */
    public function scopeWhereStatus(Builder $query, ApprovalStatus|string $status): Builder
    {
        $val = $status instanceof ApprovalStatus ? $status->value : $status;

        return $query->where('metadata->status', $val);
    }
}
