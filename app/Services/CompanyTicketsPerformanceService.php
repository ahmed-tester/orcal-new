<?php

namespace App\Services;

use App\Enums\TicketCategory;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class CompanyTicketsPerformanceService
{
    public const STATEMENT_TYPES = [
        'all',
        'open',
        'in_progress',
        'waiting_user_reply',
        'resolved_closed',
        'urgent_high',
    ];

    public static function canView(?User $user = null): bool
    {
        $user ??= auth()->user();

        if (! $user) {
            return false;
        }

        return $user->isSuperAdminOrOwner()
            || $user->can('view_any_ticket')
            || $user->can('manage_tickets');
    }

    /**
     * Compute the dashboard metrics for support tickets.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public static function getStats(array $filters): array
    {
        abort_unless(self::canView(), 403);

        $scopedCompanyIds = self::scopedCompanyIds($filters);

        if ($scopedCompanyIds === []) {
            return self::emptyStats();
        }

        $baseQuery = self::dashboardQuery($filters, $scopedCompanyIds);

        $totals = (clone $baseQuery)
            ->selectRaw('
                COUNT(*) as total_count,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as open_count,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as in_progress_count,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as waiting_user_reply_count,
                SUM(CASE WHEN status IN (?, ?) THEN 1 ELSE 0 END) as resolved_closed_count,
                SUM(CASE WHEN priority IN (?, ?) THEN 1 ELSE 0 END) as urgent_high_count
            ', [
                TicketStatus::OPEN->value,
                TicketStatus::IN_PROGRESS->value,
                TicketStatus::WAITING_USER_REPLY->value,
                TicketStatus::RESOLVED->value,
                TicketStatus::CLOSED->value,
                TicketPriority::URGENT->value,
                TicketPriority::HIGH->value,
            ])
            ->first();

        $categories = (clone $baseQuery)
            ->selectRaw('
                category,
                COUNT(*) as total_count,
                SUM(CASE WHEN status IN (?, ?, ?) THEN 1 ELSE 0 END) as active_count,
                SUM(CASE WHEN status IN (?, ?) THEN 1 ELSE 0 END) as resolved_closed_count,
                SUM(CASE WHEN priority IN (?, ?) THEN 1 ELSE 0 END) as urgent_high_count
            ', [
                TicketStatus::OPEN->value,
                TicketStatus::IN_PROGRESS->value,
                TicketStatus::WAITING_USER_REPLY->value,
                TicketStatus::RESOLVED->value,
                TicketStatus::CLOSED->value,
                TicketPriority::URGENT->value,
                TicketPriority::HIGH->value,
            ])
            ->groupBy('category')
            ->orderByDesc('total_count')
            ->orderBy('category')
            ->get()
            ->map(function ($row): array {
                $rawCategory = $row->category instanceof TicketCategory
                    ? $row->category->value
                    : (string) $row->category;
                $enum = TicketCategory::tryFrom($rawCategory);

                return [
                    'key' => $rawCategory,
                    'label' => $enum?->getLabel() ?? ($rawCategory !== '' ? $rawCategory : __('performance.tickets.unknown_category')),
                    'color' => $enum?->getColor() ?? 'gray',
                    'total' => (int) ($row->total_count ?? 0),
                    'active' => (int) ($row->active_count ?? 0),
                    'resolved_closed' => (int) ($row->resolved_closed_count ?? 0),
                    'urgent_high' => (int) ($row->urgent_high_count ?? 0),
                ];
            })
            ->values()
            ->all();

        return [
            'has_data' => true,
            'totals' => [
                'all' => (int) ($totals->total_count ?? 0),
                'open' => (int) ($totals->open_count ?? 0),
                'in_progress' => (int) ($totals->in_progress_count ?? 0),
                'waiting_user_reply' => (int) ($totals->waiting_user_reply_count ?? 0),
                'resolved_closed' => (int) ($totals->resolved_closed_count ?? 0),
                'urgent_high' => (int) ($totals->urgent_high_count ?? 0),
            ],
            'categories' => $categories,
        ];
    }

    /**
     * Build the statement (Kashf) query for tickets.
     *
     * @param  array<string, mixed>  $filters
     */
    public static function getKashfQuery(array $filters, string $type = 'all'): Builder
    {
        abort_unless(self::canView(), 403);
        abort_unless(in_array($type, self::STATEMENT_TYPES, true), 404);

        $scopedCompanyIds = self::scopedCompanyIds($filters);

        if ($scopedCompanyIds === []) {
            return Ticket::query()->whereRaw('1 = 0');
        }

        $query = self::dashboardQuery($filters, $scopedCompanyIds)
            ->with(['company', 'user', 'assignedTo'])
            ->latest('created_at')
            ->latest('id');

        return match ($type) {
            'open' => $query->where('status', TicketStatus::OPEN->value),
            'in_progress' => $query->where('status', TicketStatus::IN_PROGRESS->value),
            'waiting_user_reply' => $query->where('status', TicketStatus::WAITING_USER_REPLY->value),
            'resolved_closed' => $query->whereIn('status', [
                TicketStatus::RESOLVED->value,
                TicketStatus::CLOSED->value,
            ]),
            'urgent_high' => $query->whereIn('priority', [
                TicketPriority::URGENT->value,
                TicketPriority::HIGH->value,
            ]),
            default => $query,
        };
    }

    /**
     * @param  array<string, mixed>  $filters
     * @param  array<int, int>  $scopedCompanyIds
     */
    private static function dashboardQuery(array $filters, array $scopedCompanyIds): Builder
    {
        [$dateFrom, $dateTo] = CompanyDepartmentPerformanceService::resolveDateRange($filters);

        return Ticket::query()
            ->whereIn('company_id', $scopedCompanyIds)
            ->whereDate('created_at', '>=', $dateFrom)
            ->whereDate('created_at', '<=', $dateTo);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, int>
     */
    private static function scopedCompanyIds(array $filters): array
    {
        $authorizedCompanyIds = CompanyDepartmentPerformanceService::getAuthorizedCompanyIds(auth()->user());

        if ($authorizedCompanyIds === []) {
            return [];
        }

        return filled($filters['company_id'] ?? null)
            ? array_values(array_intersect($authorizedCompanyIds, array_map('intval', (array) $filters['company_id'])))
            : $authorizedCompanyIds;
    }

    /**
     * @return array<string, mixed>
     */
    private static function emptyStats(): array
    {
        return [
            'has_data' => false,
            'totals' => [
                'all' => 0,
                'open' => 0,
                'in_progress' => 0,
                'waiting_user_reply' => 0,
                'resolved_closed' => 0,
                'urgent_high' => 0,
            ],
            'categories' => [],
        ];
    }
}
