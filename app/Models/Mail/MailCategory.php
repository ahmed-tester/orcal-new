<?php

namespace App\Models\Mail;

use App\Services\Mail\MailCategoryService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

class MailCategory extends Model
{
    public const SYSTEM_OTHER_SLUG = 'other';

    public const ALLOWED_COLORS = [
        'gray',
        'primary',
        'info',
        'success',
        'warning',
        'danger',
    ];

    protected $table = 'mail_categories';

    protected $fillable = [
        'slug',
        'name_ar',
        'name_en',
        'color',
        'chart_color',
        'is_excluded',
        'is_active',
        'is_system',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_excluded' => 'boolean',
            'is_active' => 'boolean',
            'is_system' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $category): void {
            $category->slug = strtolower(trim((string) $category->slug));
            if ($category->slug === self::SYSTEM_OTHER_SLUG) {
                $category->is_active = true;
                $category->is_excluded = false;
                $category->is_system = true;
            }
        });

        static::updating(function (self $category): void {
            if ($category->isDirty('slug')) {
                $category->slug = (string) $category->getOriginal('slug');
            }

            if ($category->slug === self::SYSTEM_OTHER_SLUG) {
                if (! $category->is_active || $category->is_excluded) {
                    throw ValidationException::withMessages([
                        'is_active' => __('mail_inbox.validation.other_category_protected'),
                    ]);
                }
            }
        });

        static::deleting(function (self $category): void {
            if ($category->is_system || $category->slug === self::SYSTEM_OTHER_SLUG) {
                throw ValidationException::withMessages([
                    'slug' => __('mail_inbox.validation.system_category_protected'),
                ]);
            }

            if ($category->messages()->exists()) {
                throw ValidationException::withMessages([
                    'slug' => __('mail_inbox.validation.category_has_messages'),
                ]);
            }
        });

        static::saved(fn () => MailCategoryService::clearCache());
        static::deleted(fn () => MailCategoryService::clearCache());
    }

    public function messages(): HasMany
    {
        return $this->hasMany(MailMessage::class, 'category', 'slug');
    }

    public function localizedName(?string $locale = null): string
    {
        $locale ??= app()->getLocale();

        return $locale === 'ar'
            ? (string) ($this->name_ar ?: $this->name_en ?: $this->slug)
            : (string) ($this->name_en ?: $this->name_ar ?: $this->slug);
    }
}
