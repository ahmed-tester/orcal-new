<?php

namespace App\Filament\Resources\Mail\MailMessages\Widgets;

use App\Services\Mail\MailStatsService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class MailOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $stats = app(MailStatsService::class)->forUser(auth()->user());

        return [
            Stat::make(__('mail_inbox.stats.total'), $stats['total'])
                ->description(__('mail_inbox.stats.total_hint'))
                ->color('gray'),
            Stat::make(__('mail_inbox.stats.today'), $stats['today'])
                ->description(__('mail_inbox.stats.today_hint'))
                ->color('primary'),
            Stat::make(__('mail_inbox.stats.reply_rate'), $stats['reply_rate'].'%')
                ->description(__('mail_inbox.stats.reply_rate_hint'))
                ->color('success'),
            Stat::make(__('mail_inbox.stats.avg_reply_hours'), $stats['avg_reply_hours'])
                ->description(__('mail_inbox.stats.avg_reply_hours_hint'))
                ->color('info'),
            Stat::make(__('mail_inbox.stats.urgent_unanswered'), $stats['urgent_unanswered'])
                ->description(__('mail_inbox.stats.urgent_unanswered_hint'))
                ->color('danger'),
            Stat::make(__('mail_inbox.stats.review'), $stats['review'])
                ->description(__('mail_inbox.stats.review_hint'))
                ->color('warning'),
            Stat::make(__('mail_inbox.stats.connected_accounts'), $stats['connected_accounts'])
                ->description(__('mail_inbox.stats.connected_accounts_hint'))
                ->color('success'),
        ];
    }
}
