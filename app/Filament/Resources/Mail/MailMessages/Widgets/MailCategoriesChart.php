<?php

namespace App\Filament\Resources\Mail\MailMessages\Widgets;

use App\Models\Mail\MailMessage;
use App\Services\Mail\MailAccessService;
use App\Services\Mail\MailCategoryService;
use Filament\Widgets\ChartWidget;

class MailCategoriesChart extends ChartWidget
{
    public function getHeading(): ?string
    {
        return __('mail_inbox.stats.categories');
    }

    protected function getData(): array
    {
        $counts = MailAccessService::scopeRecords(MailMessage::query(), auth()->user(), 'emails')
            ->selectRaw('category, COUNT(*) AS total')
            ->groupBy('category')
            ->pluck('total', 'category');

        $categoryService = app(MailCategoryService::class);
        $categories = $categoryService->all();

        $labels = [];
        $data = [];
        $backgroundColors = [];

        if ($categories->isNotEmpty()) {
            foreach ($categories as $category) {
                $total = (int) ($counts[$category->slug] ?? 0);
                if (! $category->is_active && $total === 0) {
                    continue;
                }

                $labels[] = $category->localizedName();
                $data[] = $total;
                $backgroundColors[] = $categoryService->chartColorFor($category->slug);
            }

            foreach ($counts as $slug => $total) {
                $slugStr = (string) $slug;
                if ($slugStr !== '' && ! $categories->has($slugStr) && (int) $total > 0) {
                    $labels[] = $categoryService->labelFor($slugStr);
                    $data[] = (int) $total;
                    $backgroundColors[] = $categoryService->chartColorFor($slugStr);
                }
            }
        } else {
            foreach ($categoryService->allFilterOptions() as $slug => $label) {
                $labels[] = $label;
                $data[] = (int) ($counts[$slug] ?? 0);
                $backgroundColors[] = $categoryService->chartColorFor($slug);
            }
        }

        return [
            'labels' => $labels,
            'datasets' => [[
                'data' => $data,
                'backgroundColor' => $backgroundColors,
            ]],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
