<?php

namespace App\Filament\Resources\Mail\MailMessages\Widgets;

use App\Enums\Mail\MailCategory;
use App\Models\Mail\MailMessage;
use App\Services\Mail\MailAccessService;
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

        $colors = [
            MailCategory::INQUIRY->value => '#6366f1',
            MailCategory::REQUEST->value => '#0ea5e9',
            MailCategory::COMPLAINT->value => '#ef4444',
            MailCategory::OFFICIAL->value => '#10b981',
            MailCategory::MARKETING->value => '#f59e0b',
            MailCategory::SPAM->value => '#64748b',
            MailCategory::OTHER->value => '#8b5cf6',
        ];

        $labels = [];
        $data = [];
        $backgroundColors = [];

        foreach (MailCategory::cases() as $category) {
            $labels[] = $category->getLabel();
            $data[] = (int) ($counts[$category->value] ?? 0);
            $backgroundColors[] = $colors[$category->value];
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
