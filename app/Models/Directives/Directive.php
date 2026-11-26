<?php

namespace App\Models\Directives;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Directive extends Model
{
    public const TARGET_ALL = 'all';

    public const TARGET_SELECTED = 'selected';

    protected $fillable = [
        'title',
        'category_id',
        'description',
        'target_type',
        'created_by',
        'is_active',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'category_id' => 'integer',
            'created_by' => 'integer',
            'is_active' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(DirectiveCategory::class, 'category_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(DirectiveRecipient::class);
    }

    public function acknowledgedRecipients(): HasMany
    {
        return $this->hasMany(DirectiveRecipient::class)->whereNotNull('acknowledged_at');
    }

    public function pendingRecipients(): HasMany
    {
        return $this->hasMany(DirectiveRecipient::class)->whereNull('acknowledged_at');
    }

    /**
     * Resolve acknowledgment summary metadata from preloaded recipient counts.
     *
     * @return array{label: string, color: string, icon: string, progress: string}
     */
    public function acknowledgmentStatusMeta(): array
    {
        $total = (int) ($this->recipients_count ?? $this->recipients()->count());
        $acked = (int) ($this->acknowledged_recipients_count ?? $this->acknowledgedRecipients()->count());

        if ($total === 0) {
            return [
                'label' => __('directives.statuses.no_recipients'),
                'color' => 'gray',
                'icon' => 'heroicon-o-minus-circle',
                'progress' => '0 / 0',
            ];
        }

        $percent = (int) round(($acked / $total) * 100);
        $progress = "{$acked} / {$total} ({$percent}%)";

        if ($acked >= $total) {
            return [
                'label' => __('directives.statuses.completed'),
                'color' => 'success',
                'icon' => 'heroicon-o-check-circle',
                'progress' => $progress,
            ];
        }

        if ($acked > 0) {
            return [
                'label' => __('directives.statuses.partial'),
                'color' => 'warning',
                'icon' => 'heroicon-o-clock',
                'progress' => $progress,
            ];
        }

        return [
            'label' => __('directives.statuses.unread'),
            'color' => 'danger',
            'icon' => 'heroicon-o-exclamation-circle',
            'progress' => $progress,
        ];
    }
}
