<?php

namespace App\Services\Reports\Types;

use App\Models\Faq;

class FaqsReport extends AbstractReport
{
    public function getKey(): string
    {
        return 'faqs';
    }

    public function getTitle(): string
    {
        return __('reports.reports.faqs.title');
    }

    public function getLabel(): string
    {
        return __('reports.reports.faqs.label');
    }

    public function getIcon(): string
    {
        return 'heroicon-o-question-mark-circle';
    }

    public function getExportFilename(): string
    {
        return 'faqs_report';
    }

    public function getStats(?string $dateFrom = null, ?string $dateTo = null, ?int $branchId = null): array
    {
        $query = Faq::query();
        $this->applyDateFilter($query, $dateFrom, $dateTo);

        $total = (clone $query)->count();
        $active = (clone $query)->where('is_active', true)->count();

        return [
            [
                'title' => __('reports.stats.total_faqs'),
                'value' => $total,
                'subtitle' => __('reports.stats.total_faqs_sub'),
                'icon' => 'heroicon-o-question-mark-circle',
                'color' => 'blue',
            ],
            [
                'title' => __('reports.stats.active_faqs'),
                'value' => $active,
                'subtitle' => __('reports.stats.active_faqs_sub'),
                'icon' => 'heroicon-o-check-circle',
                'color' => 'green',
            ],
        ];
    }

    public function getQuery(?string $dateFrom, ?string $dateTo, ?int $branchId, string $status, ?string $search)
    {
        $query = Faq::query();

        $this->applyDateFilter($query, $dateFrom, $dateTo);
        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        $this->applySearchFilter($query, $search, function ($sub, $search) {
            $sub->where('question', 'like', "%{$search}%")
                ->orWhere('answer', 'like', "%{$search}%");
        });

        return $query->orderBy('sort_order')->orderByDesc('id');
    }

    public function getSortableColumns(): array
    {
        return [
            'question' => 'question',
            'sort_order' => 'sort_order',
            'is_active' => 'is_active',
            'created_at' => 'created_at',
        ];
    }

    public function getColumns(): array
    {
        return [
            ['key' => 'question', 'label' => __('reports.columns.subject')],
            ['key' => 'answer', 'label' => __('reports.columns.description')],
            ['key' => 'sort_order', 'label' => __('reports.columns.installment_order')],
            ['key' => 'is_active', 'label' => __('reports.columns.status')],
        ];
    }

    public function formatRow($item, bool $isCsv = false): array
    {
        return [
            'question' => $item->question ?? '—',
            'answer' => strip_tags($item->answer ?? '—'),
            'sort_order' => (string)($item->sort_order ?? 0),
            'is_active' => $item->is_active ? __('reports.status.active') : __('reports.status.inactive'),
        ];
    }
}
