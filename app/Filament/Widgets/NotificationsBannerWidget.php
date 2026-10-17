<?php

namespace App\Filament\Widgets;

use App\Services\Notifications\NotificationAlertsService;
use Filament\Widgets\Widget;

class NotificationsBannerWidget extends Widget
{
    protected string $view = 'filament.widgets.notifications-banner-widget';

    protected static ?int $sort = 0;

    protected int|string|array $columnSpan = 'full';

    public array $counts = [];

    public function mount(): void
    {
        $service = app(NotificationAlertsService::class);
        $this->counts = $service->getSummaryCounts();
    }

    public function getCounts(): array
    {
        return $this->counts;
    }
}
