<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

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
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $category): void {
            if (blank($category->slug)) {
                $base = Str::slug((string) ($category->name_en ?: $category->name_ar), '_');
                $base = $base !== '' ? substr($base, 0, 54) : 'category';
                $slug = $base;
                $counter = 1;

                while (static::where('slug', $slug)->exists()) {
                    $slug = substr($base, 0, 50) . '_' . $counter++;
                }

                $category->slug = $slug;
            } else {
                $category->slug = strtolower(trim((string) $category->slug));
            }
        });

        static::deleting(function (self $category): void {
            if ($category->directives()->exists()) {
                throw ValidationException::withMessages([
                    'name_ar' => __('directives.validation.category_has_directives'),
                ]);
            }
        });
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
