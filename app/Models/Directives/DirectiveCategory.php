<?php

namespace App\Models\Directives;

use App\Observers\DirectiveCategoryObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[ObservedBy([DirectiveCategoryObserver::class])]
class DirectiveCategory extends Model
{
    public const ALLOWED_COLORS = [
        'gray',
        'primary',
        'info',
        'success',
        'warning',
        'danger',
    ];

    protected $fillable = [
        'slug',
        'name_ar',
        'name_en',
        'color',
        'is_active',
        'sort_order',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'metadata' => 'array',
        ];
    }

    public function directives(): HasMany
    {
        return $this->hasMany(Directive::class, 'category_id');
    }

    public function localizedName(?string $locale = null): string
    {
        $locale ??= app()->getLocale();

        return $locale === 'ar'
            ? (string) ($this->name_ar ?: $this->name_en ?: $this->slug)
            : (string) ($this->name_en ?: $this->name_ar ?: $this->slug);
    }

    /**
     * @return array<string, string>
     */
    public static function colorOptions(): array
    {
        return collect(self::ALLOWED_COLORS)
            ->mapWithKeys(fn (string $color): array => [$color => __("directives.colors.{$color}")])
            ->all();
    }

    /**
     * @return array<int, string>
     */
    public static function options(bool $onlyActive = true): array
    {
        return static::query()
            ->when($onlyActive, fn ($q) => $q->where('is_active', true))
            ->orderBy('sort_order')
            ->get()
            ->mapWithKeys(fn (self $category): array => [$category->id => $category->localizedName()])
            ->all();
    }
}
