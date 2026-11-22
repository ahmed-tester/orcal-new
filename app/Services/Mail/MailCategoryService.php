<?php

namespace App\Services\Mail;

use App\Enums\Mail\MailCategory as LegacyMailCategory;
use App\Models\Mail\MailCategory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class MailCategoryService
{
    private const CACHE_KEY = 'mail_categories_all_v1';

    public static function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * @return Collection<string, MailCategory>
     */
    public function all(): Collection
    {
        if (! Schema::hasTable('mail_categories')) {
            return collect();
        }

        return Cache::remember(self::CACHE_KEY, 60, fn (): Collection => MailCategory::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->keyBy('slug'));
    }

    public function resolveActiveNonExcludedSlug(string $preferredSlug = 'inquiry'): string
    {
        $all = $this->all();
        if ($all->isEmpty()) {
            return $this->isActive($preferredSlug) && ! $this->isExcluded($preferredSlug)
                ? $preferredSlug
                : MailCategory::SYSTEM_OTHER_SLUG;
        }

        $preferred = $this->findBySlug($preferredSlug);
        if ($preferred !== null && $preferred->is_active && ! $preferred->is_excluded) {
            return $preferred->slug;
        }

        $fallback = $all->first(fn (MailCategory $cat): bool => $cat->is_active && ! $cat->is_excluded);
        if ($fallback !== null) {
            return $fallback->slug;
        }

        return MailCategory::SYSTEM_OTHER_SLUG;
    }

    public function findBySlug(?string $slug): ?MailCategory
    {
        $normalized = strtolower(trim((string) $slug));
        if ($normalized === '') {
            return null;
        }

        return $this->all()->get($normalized);
    }

    /**
     * Active category options [slug => localizedName].
     * Optionally keeps an inactive current slug so editing legacy records does not lose its value.
     *
     * @return array<string, string>
     */
    public function activeOptions(?string $includeSlug = null, ?string $locale = null): array
    {
        $categories = $this->all();
        if ($categories->isEmpty()) {
            return collect(LegacyMailCategory::cases())
                ->mapWithKeys(fn (LegacyMailCategory $case): array => [$case->value => $case->getLabel()])
                ->all();
        }

        $options = $categories
            ->filter(fn (MailCategory $cat): bool => $cat->is_active || ($includeSlug !== null && $cat->slug === $includeSlug))
            ->mapWithKeys(fn (MailCategory $cat): array => [$cat->slug => $cat->localizedName($locale)])
            ->all();

        if ($includeSlug !== null && $includeSlug !== '' && ! array_key_exists($includeSlug, $options)) {
            $options[$includeSlug] = $this->labelFor($includeSlug, $locale);
        }

        return $options;
    }

    /**
     * Active non-excluded categories [slug => localizedName].
     *
     * @return array<string, string>
     */
    public function nonExcludedActiveOptions(?string $locale = null): array
    {
        $categories = $this->all();
        if ($categories->isEmpty()) {
            return collect(LegacyMailCategory::cases())
                ->reject(fn (LegacyMailCategory $case): bool => in_array($case, [LegacyMailCategory::SPAM, LegacyMailCategory::MARKETING], true))
                ->mapWithKeys(fn (LegacyMailCategory $case): array => [$case->value => $case->getLabel()])
                ->all();
        }

        return $categories
            ->filter(fn (MailCategory $cat): bool => $cat->is_active && ! $cat->is_excluded)
            ->mapWithKeys(fn (MailCategory $cat): array => [$cat->slug => $cat->localizedName($locale)])
            ->all();
    }

    /**
     * Active excluded categories [slug => localizedName].
     *
     * @return array<string, string>
     */
    public function excludedActiveOptions(?string $locale = null): array
    {
        $categories = $this->all();
        if ($categories->isEmpty()) {
            return [
                LegacyMailCategory::MARKETING->value => LegacyMailCategory::MARKETING->getLabel(),
                LegacyMailCategory::SPAM->value => LegacyMailCategory::SPAM->getLabel(),
            ];
        }

        return $categories
            ->filter(fn (MailCategory $cat): bool => $cat->is_active && $cat->is_excluded)
            ->mapWithKeys(fn (MailCategory $cat): array => [$cat->slug => $cat->localizedName($locale)])
            ->all();
    }

    /**
     * All categories for filters and historical charts.
     *
     * @return array<string, string>
     */
    public function allFilterOptions(?string $locale = null): array
    {
        $categories = $this->all();
        if ($categories->isEmpty()) {
            return collect(LegacyMailCategory::cases())
                ->mapWithKeys(fn (LegacyMailCategory $case): array => [$case->value => $case->getLabel()])
                ->all();
        }

        return $categories
            ->mapWithKeys(fn (MailCategory $cat): array => [$cat->slug => $cat->localizedName($locale)])
            ->all();
    }

    /**
     * @return array<int, string>
     */
    public function excludedSlugs(): array
    {
        $categories = $this->all();
        if ($categories->isEmpty()) {
            return [LegacyMailCategory::SPAM->value, LegacyMailCategory::MARKETING->value];
        }

        return $categories
            ->filter(fn (MailCategory $cat): bool => $cat->is_excluded)
            ->pluck('slug')
            ->values()
            ->all();
    }

    public function isExcluded(?string $slug): bool
    {
        $normalized = strtolower(trim((string) $slug));
        if ($normalized === '') {
            return false;
        }

        return in_array($normalized, $this->excludedSlugs(), true);
    }

    public function isActive(?string $slug): bool
    {
        $cat = $this->findBySlug($slug);
        if ($cat !== null) {
            return $cat->is_active;
        }

        if ($this->all()->isEmpty()) {
            return LegacyMailCategory::tryFrom((string) $slug) !== null;
        }

        return false;
    }

    public function exists(?string $slug): bool
    {
        if ($this->findBySlug($slug) !== null) {
            return true;
        }

        if ($this->all()->isEmpty()) {
            return LegacyMailCategory::tryFrom((string) $slug) !== null;
        }

        return false;
    }

    public function labelFor(?string $slug, ?string $locale = null): string
    {
        $normalized = strtolower(trim((string) $slug));
        if ($normalized === '') {
            return '-';
        }

        $cat = $this->findBySlug($normalized);
        if ($cat !== null) {
            return $cat->localizedName($locale);
        }

        if ($legacy = LegacyMailCategory::tryFrom($normalized)) {
            return $legacy->getLabel();
        }

        return $normalized;
    }

    public function colorFor(?string $slug): string
    {
        $cat = $this->findBySlug($slug);
        if ($cat !== null) {
            return $cat->color ?: 'gray';
        }

        if ($legacy = LegacyMailCategory::tryFrom((string) $slug)) {
            return (string) ($legacy->getColor() ?? 'gray');
        }

        return 'gray';
    }

    public function chartColorFor(?string $slug): string
    {
        $cat = $this->findBySlug($slug);
        if ($cat !== null && preg_match('/^#[0-9a-fA-F]{6}$/', (string) $cat->chart_color)) {
            return $cat->chart_color;
        }

        return match ((string) $slug) {
            'inquiry' => '#6366f1',
            'request' => '#0ea5e9',
            'complaint' => '#ef4444',
            'official' => '#10b981',
            'marketing' => '#f59e0b',
            'spam' => '#64748b',
            'other' => '#8b5cf6',
            default => '#64748b',
        };
    }

    /**
     * Resolve a raw slug or exact localized Arabic/English label to an active category slug.
     * Falls back to 'other' if no exact active match exists.
     */
    public function resolveActiveSlugFromLabel(?string $label): string
    {
        $normalized = trim((string) $label);
        if ($normalized === '') {
            return MailCategory::SYSTEM_OTHER_SLUG;
        }

        $lower = mb_strtolower($normalized);
        $active = $this->all()->filter(fn (MailCategory $cat): bool => $cat->is_active);

        if ($active->isEmpty()) {
            return LegacyMailCategory::fromLabel($normalized)->value;
        }

        foreach ($active as $cat) {
            if (
                mb_strtolower($cat->slug) === $lower
                || mb_strtolower($cat->name_ar) === $lower
                || mb_strtolower($cat->name_en) === $lower
            ) {
                return $cat->slug;
            }
        }

        return MailCategory::SYSTEM_OTHER_SLUG;
    }

    public function geminiPromptCategoriesText(): string
    {
        $active = $this->all()->filter(fn (MailCategory $cat): bool => $cat->is_active);

        if ($active->isEmpty()) {
            return collect(LegacyMailCategory::cases())
                ->map(fn (LegacyMailCategory $case): string => "{$case->value} ({$case->getLabel()})")
                ->implode('، ');
        }

        return $active
            ->map(fn (MailCategory $cat): string => "{$cat->slug} ({$cat->name_ar} / {$cat->name_en})")
            ->implode('، ');
    }

    public function geminiVersion(): string
    {
        $payload = $this->all()
            ->filter(fn (MailCategory $cat): bool => $cat->is_active)
            ->map(fn (MailCategory $cat): string => implode('|', [
                $cat->slug,
                $cat->name_ar,
                $cat->name_en,
                $cat->is_excluded ? '1' : '0',
            ]))
            ->implode(';');

        return substr(md5($payload), 0, 12);
    }
}
