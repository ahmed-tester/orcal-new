<?php

namespace App\Services\Reports\Types;

use App\Models\Ticket;

class TicketsReport extends AbstractReport
{
    public function getKey(): string
    {
        return 'tickets';
    }

    public function getTitle(): string
    {
        return __('reports.reports.tickets.title');
    }

    public function getLabel(): string
    {
        return __('reports.reports.tickets.label');
    }

    public function getIcon(): string
    {
        return 'heroicon-o-ticket';
    }

    public function getExportFilename(): string
    {
        return 'tickets_report';
    }

    public function getStats(?string $dateFrom = null, ?string $dateTo = null, ?int $branchId = null): array
    {
        $query = Ticket::query();
        $this->applyCompanyScope($query);
        $this->applyDateFilter($query, $dateFrom, $dateTo);

        $total = (clone $query)->count();
        $open = (clone $query)->whereIn('status', ['open', 'in_progress', 'pending'])->count();
        $resolved = (clone $query)->whereIn('status', ['resolved', 'closed'])->count();

        return [
            [
                'title' => __('reports.stats.total_tickets'),
                'value' => $total,
                'subtitle' => __('reports.stats.total_tickets_sub'),
                'icon' => 'heroicon-o-ticket',
                'color' => 'blue',
            ],
            [
                'title' => __('reports.stats.open_tickets'),
                'value' => $open,
                'subtitle' => __('reports.stats.open_tickets_sub'),
                'icon' => 'heroicon-o-exclamation-circle',
                'color' => 'amber',
            ],
            [
                'title' => __('reports.stats.resolved_tickets'),
                'value' => $resolved,
                'subtitle' => __('reports.stats.resolved_tickets_sub'),
                'icon' => 'heroicon-o-check-circle',
                'color' => 'green',
            ],
        ];
    }

    public function getQuery(?string $dateFrom, ?string $dateTo, ?int $branchId, string $status, ?string $search)
    {
        $query = Ticket::query()
            ->with(['user', 'company', 'assignedTo']);

        $this->applyCompanyScope($query);
        $this->applyDateFilter($query, $dateFrom, $dateTo);
        $this->applyStatusFilter($query, $status);

        $this->applySearchFilter($query, $search, function ($sub, $search) {
            $sub->where('ticket_number', 'like', "%{$search}%")
                ->orWhere('subject', 'like', "%{$search}%")
                ->orWhereHas('user', fn($uq) => $uq->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));
        });

        return $query->orderByDesc('id');
    }

    public function getSortableColumns(): array
    {
        return [
            'ticket_number' => 'ticket_number',
            'subject' => 'subject',
            'priority' => 'priority',
            'status' => 'status',
            'created_at' => 'created_at',
        ];
    }

    public function getColumns(): array
    {
        return [
            ['key' => 'ticket_number', 'label' => __('reports.columns.ticket_number')],
            ['key' => 'subject', 'label' => __('reports.columns.subject')],
            ['key' => 'user_name', 'label' => __('reports.columns.customer_name')],
            ['key' => 'company', 'label' => __('reports.columns.company_name')],
            ['key' => 'category', 'label' => __('reports.columns.violation_category')],
            ['key' => 'priority', 'label' => __('reports.columns.priority')],
            ['key' => 'assigned_to', 'label' => __('reports.columns.responsible_employee')],
            ['key' => 'status', 'label' => __('reports.columns.status')],
            ['key' => 'created_at', 'label' => __('reports.columns.date')],
        ];
    }

    public function formatRow($item, bool $isCsv = false): array
    {
        $datePrefix = $isCsv ? "\t" : "";

        return [
            'ticket_number' => $item->ticket_number ?? '—',
            'subject' => $item->subject ?? '—',
            'user_name' => $item->user?->name ?? '—',
            'company' => $item->company?->trade_name ?? '—',
            'category' => $this->translateStatus($item->category),
            'priority' => $this->translateStatus($item->priority),
            'assigned_to' => $item->assignedTo?->name ?? '—',
            'status' => $this->translateStatus($item->status),
            'created_at' => $item->created_at ? ($datePrefix . $item->created_at->format('Y-m-d H:i')) : '—',
        ];
    }
}
