<?php

namespace App\Observers;

use App\Models\Directives\DirectiveCategory;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class DirectiveCategoryObserver
{
    public function saving(DirectiveCategory $category): void
    {
        if (blank($category->name_en)) {
            $category->name_en = (string) $category->name_ar;
        }
    }

    public function creating(DirectiveCategory $category): void
    {
        if (blank($category->slug)) {
            $base = Str::slug((string) ($category->name_en ?: $category->name_ar), '_');
            $base = $base !== '' ? substr($base, 0, 54) : 'category';
            $slug = $base;
            $counter = 1;

            while (DirectiveCategory::where('slug', $slug)->exists()) {
                $slug = substr($base, 0, 50).'_'.$counter++;
            }

            $category->slug = $slug;
        } else {
            $category->slug = strtolower(trim((string) $category->slug));
        }
    }

    public function deleting(DirectiveCategory $category): void
    {
        if ($category->directives()->exists()) {
            throw ValidationException::withMessages([
                'name_ar' => __('directives.validation.category_has_directives'),
            ]);
        }
    }
}
