<x-filament-panels::page>
    @php
        $data = $this->performance;
        $employee = $data['employee'] ?? null;
        $serviceData = $this->servicePerformance;
    @endphp

    <style>
        .task-performance-page { display: flex; flex-direction: column; gap: 1.25rem; }
        .task-performance-page .tp-header { display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:1rem; padding:1rem 1.15rem; border:1px solid #e2e8f0; border-radius:0.9rem; background:#fff; }
        .dark .task-performance-page .tp-header { background:#1e293b; border-color:#334155; }
        .task-performance-page .tp-name { font-size:1.15rem; font-weight:800; color:#0f172a; }
        .dark .task-performance-page .tp-name { color:#f8fafc; }
        .task-performance-page .tp-meta { display:flex; flex-wrap:wrap; gap:0.35rem 1.1rem; margin-top:0.35rem; font-size:0.8rem; color:#64748b; }
        .dark .task-performance-page .tp-meta { color:#94a3b8; }
        .task-performance-page .tp-service-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:0.8rem; }
        .task-performance-page .tp-card { border:1px solid #e2e8f0; border-radius:0.85rem; padding:0.9rem; background:#fff; display:flex; flex-direction:column; gap:0.55rem; min-height:108px; text-align:start; }
        .dark .task-performance-page .tp-card { background:#1e293b; border-color:#334155; }
        .task-performance-page .tp-card.is-clickable { cursor:pointer; transition:box-shadow .15s, border-color .15s; }
        .task-performance-page .tp-card.is-clickable:hover { border-color:#94a3b8; box-shadow:0 3px 10px rgba(15,23,42,0.06); }
        .task-performance-page .tp-card.is-active { outline:2px solid #2563eb; outline-offset:1px; }
        .task-performance-page .tp-card-title { font-size:0.78rem; font-weight:700; color:#475569; display:flex; align-items:center; gap:0.35rem; }
        .dark .task-performance-page .tp-card-title { color:#cbd5e1; }
        .task-performance-page .tp-card-value { font-size:1.5rem; font-weight:800; color:#0f172a; font-variant-numeric:tabular-nums; }
        .dark .task-performance-page .tp-card-value { color:#f8fafc; }
        .task-performance-page .tp-card-hint { font-size:0.68rem; color:#94a3b8; }
        .task-performance-page .tp-task-metrics { container:task-metrics / inline-size; display:grid; gap:0.65rem; width:100%; }
        .task-performance-page .tp-task-grid { display:grid; grid-template-columns:minmax(0, 1fr); gap:0.65rem; }
        .task-performance-page .tp-task-grid .tp-card { display:grid; gap:0.35rem; min-width:0; min-height:0; padding:0.75rem 0.85rem; }
        .task-performance-page .tp-task-grid .tp-card-title { grid-area:title; color:#334155; line-height:1.4; }
        .task-performance-page .tp-task-grid .tp-card-value { grid-area:value; color:var(--tp-metric-accent); line-height:1.15; }
        .task-performance-page .tp-task-grid .tp-card-hint { grid-area:hint; color:#475569; font-size:0.75rem; line-height:1.5; }
        .task-performance-page .tp-task-icon { width:1rem; height:1rem; flex:none; color:var(--tp-metric-accent); }
        .task-performance-page .tp-task-counts .tp-card {
            grid-template-areas:'title filter' 'value value' 'hint hint'; grid-template-columns:minmax(0, 1fr) auto;
            grid-template-rows:auto auto 1fr; align-content:start;
        }
        .task-performance-page .tp-task-counts .tp-card:focus-visible { outline:2px solid #2563eb; outline-offset:2px; }
        .task-performance-page .tp-task-filter-icon { grid-area:filter; width:0.9rem; height:0.9rem; margin-top:0.1rem; color:#475569; }
        .task-performance-page .tp-task-counts .tp-card.is-active { border-color:#2563eb; background:#eff6ff; }
        .task-performance-page .tp-task-rates { --bd:#e2e8f0; --tp-ring-center:#fff; }
        .dark .task-performance-page .tp-task-rates { --bd:#475569; --tp-ring-center:#1e293b; }
        .tp-task-rates .perf-ring-item {
            display:grid; grid-template-areas:'ring label' 'ring hint'; grid-template-columns:auto minmax(0, 1fr);
            align-content:center; align-items:center; gap:0.25rem 0.85rem;
        }
        .tp-task-rates .ring {
            grid-area:ring; display:grid; place-items:center; width:4.5rem; aspect-ratio:1; border-radius:50%; direction:ltr;
            background:conic-gradient(var(--col) calc(var(--p) * 1%), var(--bd) 0);
        }
        .tp-task-rates .ring i {
            display:grid; place-items:center; width:3.55rem; aspect-ratio:1; border-radius:50%; background:var(--tp-ring-center);
            color:#0f172a; font-size:0.8rem; font-weight:800; font-style:normal; font-variant-numeric:tabular-nums;
        }
        .tp-task-rates .perf-ring-label { grid-area:label; color:#334155; font-size:0.8rem; font-weight:700; align-self:end; }
        .tp-task-rates .perf-ring-sublabel { grid-area:hint; color:#475569; font-size:0.75rem; align-self:start; }
        .dark .tp-task-rates .ring i { color:#f8fafc; }
        .dark .tp-task-rates .perf-ring-label,
        .dark .tp-task-rates .perf-ring-sublabel { color:#cbd5e1; }
        .dark .task-performance-page .tp-task-grid .tp-card-title,
        .dark .task-performance-page .tp-task-grid .tp-card-hint,
        .dark .task-performance-page .tp-task-filter-icon { color:#cbd5e1; }
        .dark .task-performance-page .tp-task-grid .tp-card-value,
        .dark .task-performance-page .tp-task-icon { color:color-mix(in srgb, var(--tp-metric-accent), white 55%); }
        .dark .task-performance-page .tp-task-counts .tp-card.is-active { border-color:#60a5fa; background:#172554; }
        @container task-metrics (min-width:19rem) {
            .task-performance-page .tp-task-counts { grid-template-columns:repeat(2, minmax(0, 1fr)); }
        }
        @container task-metrics (min-width:42rem) {
            .task-performance-page .tp-task-rates { grid-template-columns:repeat(3, minmax(0, 1fr)); }
            .tp-task-rates .perf-ring-item { grid-template-areas:'ring' 'label' 'hint'; grid-template-columns:minmax(0, 1fr); justify-items:center; align-content:start; gap:0.4rem; text-align:center; }
        }
        @container task-metrics (min-width:56rem) {
            .task-performance-page .tp-task-counts { grid-template-columns:repeat(4, minmax(0, 1fr)); }
        }
        .task-performance-page .tp-section { display:flex; flex-direction:column; gap:0.8rem; padding-top:0.5rem; border-top:1px solid #e2e8f0; }
        .dark .task-performance-page .tp-section { border-top-color:#334155; }
        .task-performance-page .tp-section-title { font-size:1rem; font-weight:800; color:#0f172a; }
        .dark .task-performance-page .tp-section-title { color:#f8fafc; }
        .task-performance-page .tp-section-subtitle { font-size:0.78rem; color:#64748b; margin-top:0.2rem; }
        .dark .task-performance-page .tp-section-subtitle { color:#94a3b8; }
        .task-performance-page .tp-table-container { width:100%; min-width:0; overflow-x:auto; border:1px solid #e2e8f0; border-radius:0.75rem; background:#fff; }
        .dark .task-performance-page .tp-table-container { border-color:#334155; background:#1e293b; }
        .task-performance-page .tp-table { width:100%; border-collapse:collapse; font-size:0.8125rem; min-width:960px; }
        .task-performance-page .tp-service-table { min-width:1080px; }
        .task-performance-page .tp-deficiency-table { min-width:980px; }
        .task-performance-page .tp-table thead tr { background:#f8fafc; border-bottom:2px solid #e2e8f0; }
        .dark .task-performance-page .tp-table thead tr { background:#0f172a; border-color:#334155; }
        .task-performance-page .tp-table th { padding:0.75rem; text-align:inherit; font-weight:700; color:#334155; white-space:nowrap; }
        .dark .task-performance-page .tp-table th { color:#cbd5e1; }
        .task-performance-page .tp-table tbody tr { border-bottom:1px solid #f1f5f9; }
        .dark .task-performance-page .tp-table tbody tr { border-bottom-color:#334155; }
        .task-performance-page .tp-table tbody tr:hover { background:#f8fafc; }
        .dark .task-performance-page .tp-table tbody tr:hover { background:rgba(255,255,255,0.03); }
        .task-performance-page .tp-table td { padding:0.65rem 0.75rem; vertical-align:top; color:#0f172a; }
        .dark .task-performance-page .tp-table td { color:#f1f5f9; }
        .task-performance-page .tp-table td.tp-record-cell { padding-block:0.25rem; vertical-align:middle; white-space:nowrap; }
        .task-performance-page .tp-record-link {
            display:inline-flex; align-items:center; gap:0.38rem; min-height:2rem; padding-inline:0.5rem;
            border-radius:0.4rem; background:#eff6ff; color:#1d4ed8; font-weight:700; white-space:nowrap;
        }
        .task-performance-page .tp-record-link svg { width:0.9rem; height:0.9rem; flex:none; }
        .task-performance-page .tp-record-link:hover { background:#dbeafe; }
        .task-performance-page .tp-record-link:hover bdi { text-decoration:underline; text-underline-offset:0.18em; }
        .task-performance-page .tp-record-link:focus-visible { outline:2px solid currentColor; outline-offset:2px; }
        .dark .task-performance-page .tp-record-link { background:#172554; color:#bfdbfe; }
        .dark .task-performance-page .tp-record-link:hover { background:#1e3a8a; }
        @media (pointer:coarse), (max-width:640px) {
            .task-performance-page .tp-record-link { min-height:2.75rem; }
        }
        .task-performance-page .tp-linked-list { display:flex; flex-direction:column; gap:0.25rem; font-size:0.75rem; }
        .task-performance-page .tp-muted { color:#94a3b8; }
    </style>

    @if(! $employee)
        <x-filament::section>
            <p>{{ __('tasks.performance.no_employee') }}</p>
        </x-filament::section>
    @else
        <div class="task-performance-page">
            <div class="tp-header">
                <div>
                    <div class="tp-name">{{ $employee->user?->name ?? $employee->employee_number }}</div>
                    <div class="tp-meta">
                        <span>{{ __('tasks.performance.company') }}: {{ $employee->company?->trade_name ?? '—' }}</span>
                        <span>{{ __('tasks.performance.branch') }}: {{ $employee->branch?->name ?? '—' }}</span>
                        <span>{{ __('tasks.performance.department') }}: {{ $employee->department?->name ?? '—' }}</span>
                        <span>{{ __('tasks.performance.job_title') }}: {{ $employee->jobTitleDisplay ?? '—' }}</span>
                        <span>{{ __('tasks.performance.employee_number') }}: <bdi>{{ $employee->employee_number ?? '—' }}</bdi></span>
                    </div>
                </div>

                <div style="display:flex; align-items:flex-end; gap:0.5rem; flex-wrap:wrap;">
                    <div>
                        <label style="display:block; font-size:0.75rem; font-weight:700; color:#64748b; margin-bottom:0.3rem;">{{ __('tasks.performance.period') }}</label>
                        <x-filament::input.wrapper>
                            <x-filament::input.select wire:model.live="month">
                                @foreach($this->monthOptions as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </x-filament::input.select>
                        </x-filament::input.wrapper>
                    </div>

                    <x-filament::button color="gray" size="sm" wire:click="previousMonth" icon="heroicon-m-chevron-right">
                        {{ __('tasks.performance.previous') }}
                    </x-filament::button>
                    <x-filament::button color="gray" size="sm" wire:click="currentMonth">
                        {{ __('tasks.performance.current') }}
                    </x-filament::button>
                    <x-filament::button color="gray" size="sm" wire:click="nextMonth" icon="heroicon-m-chevron-left" icon-position="after">
                        {{ __('tasks.performance.next') }}
                    </x-filament::button>
                    @if($data['task_visible'])
                        <x-filament::button color="primary" size="sm" wire:click="exportCsv" icon="heroicon-o-arrow-down-tray">
                            {{ __('tasks.performance.export') }}
                        </x-filament::button>
                    @endif
                </div>
            </div>

            @if($data['task_visible'])
                <div class="tp-section">
                    <h2 class="tp-section-title">{{ __('tasks.performance.task_section_title') }}</h2>

                    <div class="tp-task-metrics">
                        @php
                            $countCards = [
                                ['key' => 'completed', 'title' => __('tasks.performance.completed_in_period'), 'value' => (int) $data['employee']->completed_in_period, 'hint' => __('tasks.performance.completed_in_period_hint'), 'icon' => 'heroicon-o-check-circle', 'color' => '#059669'],
                                ['key' => 'due', 'title' => __('tasks.performance.due_in_period'), 'value' => (int) $data['employee']->due_in_period, 'hint' => __('tasks.performance.due_in_period_hint'), 'icon' => 'heroicon-o-calendar-days', 'color' => '#1d4ed8'],
                                ['key' => 'overdue', 'title' => __('tasks.performance.overdue_now'), 'value' => (int) $data['employee']->overdue_count, 'hint' => __('tasks.performance.overdue_now_hint'), 'icon' => 'heroicon-o-exclamation-triangle', 'color' => '#b91c1c'],
                                ['key' => 'postponed', 'title' => __('tasks.performance.postponed'), 'value' => (int) $data['employee']->postponed_count, 'hint' => __('tasks.performance.postponed_hint'), 'icon' => 'heroicon-o-pause-circle', 'color' => '#b45309'],
                            ];
                            $rateCards = [
                                ['title' => 'weighted_score', 'rate' => $data['weighted_percentage'], 'color' => '#7e22ce'],
                                ['title' => 'completion_rate', 'rate' => $data['completion_rate'], 'color' => '#3762eb'],
                                ['title' => 'on_time_rate', 'rate' => $data['on_time_rate'], 'color' => '#16a34a'],
                            ];
                        @endphp

                        <div class="tp-task-grid tp-task-counts">
                            @foreach($countCards as $card)
                                <button
                                    type="button"
                                    @class(['tp-card', 'is-clickable', 'is-active' => $this->cardFilter === $card['key']])
                                    style="--tp-metric-accent: {{ $card['color'] }}"
                                    wire:click="filterCard('{{ $card['key'] }}')"
                                    aria-pressed="{{ $this->cardFilter === $card['key'] ? 'true' : 'false' }}"
                                >
                                    <span class="tp-card-title">
                                        <x-dynamic-component :component="$card['icon']" class="tp-task-icon" aria-hidden="true" />
                                        <span>{{ $card['title'] }}</span>
                                    </span>
                                    <x-heroicon-m-funnel class="tp-task-filter-icon" aria-hidden="true" />
                                    <bdi class="tp-card-value">{{ $card['value'] }}</bdi>
                                    <span class="tp-card-hint">{{ $card['hint'] }}</span>
                                </button>
                            @endforeach
                        </div>

                        <div class="tp-task-grid tp-task-rates">
                            @foreach($rateCards as $card)
                                <div class="tp-card">
                                    <x-performance.rate-ring
                                        :rate="$card['rate']"
                                        :label="__('tasks.performance.'.$card['title'])"
                                        :sublabel="__('tasks.performance.'.($card['rate'] === null ? 'no_data' : $card['title'].'_hint'))"
                                        :color="$card['rate'] === null ? null : $card['color']"
                                        :precision="2"
                                        :fixed-precision="true"
                                    />
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div style="display:flex; align-items:center; justify-content:space-between; gap:0.75rem; flex-wrap:wrap;">
                        <div style="font-size:0.85rem; font-weight:700;">
                            {{ __('tasks.performance.statement') }}
                            @if($this->cardFilter)
                                <x-filament::badge color="info" style="margin-inline-start:0.4rem;">
                                    {{ __('tasks.performance.filter_'.$this->cardFilter) }}
                                </x-filament::badge>
                                <x-filament::link tag="button" wire:click="filterCard(null)" style="margin-inline-start:0.4rem; font-size:0.75rem;">
                                    {{ __('tasks.performance.clear_filter') }}
                                </x-filament::link>
                            @endif
                        </div>
                        <div style="font-size:0.75rem; color:#94a3b8;">
                            {{ __('tasks.performance.items_count', ['count' => $this->performanceItems->count()]) }}
                        </div>
                    </div>

                    <div class="tp-table-container">
                        <table class="tp-table">
                            <thead>
                                <tr>
                                    @foreach(['task', 'assignment', 'assignment_date', 'status', 'due_date', 'completed_at', 'weight', 'score', 'score_percentage', 'overall_percentage'] as $column)
                                        <th scope="col">{{ __('tasks.performance.columns.'.$column) }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($this->performanceItems as $item)
                                    <tr wire:key="task-item-{{ $item->id }}">
                                        <td>{{ $item->task?->name ?? '—' }}</td>
                                        <td class="tp-record-cell">
                                            @php $assignmentNumber = $item->taskAssignment?->transaction_number ?? '—'; @endphp
                                            @if($url = $this->assignmentUrl($item))
                                                <a class="tp-record-link" href="{{ $url }}" aria-label="{{ __('tasks.performance.open_assignment') }} {{ $assignmentNumber }}">
                                                    <bdi>{{ $assignmentNumber }}</bdi>
                                                    <x-heroicon-m-arrow-top-right-on-square aria-hidden="true" />
                                                </a>
                                            @else
                                                <bdi>{{ $assignmentNumber }}</bdi>
                                            @endif
                                        </td>
                                        <td><bdi>{{ $item->taskAssignment?->assignment_date?->format('Y-m-d') ?? '—' }}</bdi></td>
                                        <td>
                                            <x-filament::badge :color="$item->status?->getColor() ?? 'gray'">
                                                {{ $item->status?->getLabel() ?? '—' }}
                                            </x-filament::badge>
                                        </td>
                                        <td><bdi>{{ $item->due_date?->format('Y-m-d') ?? '—' }}</bdi></td>
                                        <td><bdi>{{ $item->completed_at?->format('Y-m-d') ?? '—' }}</bdi></td>
                                        <td><bdi>{{ number_format((float) $item->weight, 2) }}%</bdi></td>
                                        <td><bdi>{{ number_format((float) $item->score, 2) }}</bdi></td>
                                        <td><bdi>{{ number_format((float) $item->score_percentage, 2) }}%</bdi></td>
                                        <td><bdi>{{ number_format((float) $item->taskAssignment?->overall_percentage, 2) }}%</bdi></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="10">{{ __('tasks.performance.empty') }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            @if($data['service_visible'] && $serviceData)
                <div class="tp-section">
                    <div>
                        <div class="tp-section-title">{{ __('tasks.performance.service_section_title') }}</div>
                        <div class="tp-section-subtitle">{{ __('tasks.performance.services.subtitle') }}</div>
                    </div>

                    <div class="tp-service-grid">
                        @php
                            $serviceCards = [
                                ['key' => 'active', 'title' => __('tasks.performance.services.cards.active'), 'value' => (int) $serviceData['stats']->active, 'hint' => __('tasks.performance.services.cards.active_hint'), 'icon' => 'heroicon-o-play-circle', 'color' => '#1d4ed8'],
                                ['key' => 'completed', 'title' => __('tasks.performance.services.cards.completed'), 'value' => (int) $serviceData['stats']->completed, 'hint' => __('tasks.performance.services.cards.completed_hint'), 'icon' => 'heroicon-o-check-badge', 'color' => '#059669'],
                                ['key' => 'overdue', 'title' => __('tasks.performance.services.cards.overdue'), 'value' => (int) $serviceData['stats']->overdue, 'hint' => __('tasks.performance.services.cards.overdue_hint'), 'icon' => 'heroicon-o-exclamation-triangle', 'color' => '#b91c1c'],
                                ['key' => 'assigned_deficiencies', 'title' => __('tasks.performance.services.cards.assigned_deficiencies'), 'value' => (int) $serviceData['stats']->assigned_deficiencies, 'hint' => __('tasks.performance.services.cards.assigned_deficiencies_hint'), 'icon' => 'heroicon-o-clipboard-document-list', 'color' => '#b45309'],
                            ];
                        @endphp

                        @foreach($serviceCards as $card)
                            <div
                                class="tp-card is-clickable {{ $this->serviceCardFilter === $card['key'] ? 'is-active' : '' }}"
                                wire:click="filterServiceCard('{{ $card['key'] }}')"
                            >
                                <div class="tp-card-title">
                                    <x-dynamic-component :component="$card['icon']" style="width:16px;height:16px;color:{{ $card['color'] }};" />
                                    <span>{{ $card['title'] }}</span>
                                </div>
                                <div class="tp-card-value" style="color: {{ $card['color'] }};">{{ $card['value'] }}</div>
                                <div class="tp-card-hint">{{ $card['hint'] }}</div>
                            </div>
                        @endforeach
                    </div>

                    <div style="display:flex; align-items:center; justify-content:space-between; gap:0.75rem; flex-wrap:wrap;">
                        <div style="font-size:0.85rem; font-weight:700;">
                            {{ $serviceData['deficiencies_only'] ? __('tasks.performance.services.deficiencies_statement') : __('tasks.performance.services.transaction_statement') }}
                            @if($this->serviceCardFilter)
                                <x-filament::badge color="info" style="margin-inline-start:0.4rem;">
                                    {{ __('tasks.performance.services.cards.'.$this->serviceCardFilter) }}
                                </x-filament::badge>
                                <x-filament::link tag="button" wire:click="clearServiceCardFilter" style="margin-inline-start:0.4rem; font-size:0.75rem;">
                                    {{ __('tasks.performance.services.clear_filter') }}
                                </x-filament::link>
                            @endif
                        </div>
                        <div style="font-size:0.75rem; color:#94a3b8;">
                            @if($serviceData['deficiencies_only'])
                                {{ __('tasks.performance.services.deficiencies_count', ['count' => $serviceData['deficiencies']->count()]) }}
                            @else
                                {{ __('tasks.performance.services.items_count', ['count' => $serviceData['transactions']->count()]) }}
                            @endif
                        </div>
                    </div>

                    @if(! $serviceData['deficiencies_only'])
                        <div class="tp-table-container">
                            <table class="tp-table tp-service-table">
                                <thead>
                                    <tr>
                                        @foreach(['transaction_number', 'service', 'service_status', 'transaction_date', 'due_date', 'completion_date', 'linked_deficiencies', 'pending_action'] as $column)
                                            <th scope="col">{{ __('tasks.performance.services.columns.'.$column) }}</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($serviceData['transactions'] as $transaction)
                                        @php
                                            $linked = \App\Services\ServicePerformanceSummaryService::linkedDeficiencies($transaction);
                                            $pending = \App\Services\ServicePerformanceSummaryService::pendingAction($transaction);
                                        @endphp
                                        <tr wire:key="service-transaction-{{ $transaction->id }}">
                                            <td class="tp-record-cell">
                                                @if(\App\Filament\Resources\ServiceTransactions\ServiceTransactionResource::canEdit($transaction))
                                                    <a class="tp-record-link" href="{{ \App\Filament\Resources\ServiceTransactions\ServiceTransactionResource::getUrl('edit', ['record' => $transaction]) }}" aria-label="{{ __('tasks.performance.services.open_service') }} {{ $transaction->transaction_number }}">
                                                        <bdi>{{ $transaction->transaction_number }}</bdi>
                                                        <x-heroicon-m-arrow-top-right-on-square aria-hidden="true" />
                                                    </a>
                                                @else
                                                    <bdi>{{ $transaction->transaction_number }}</bdi>
                                                @endif
                                            </td>
                                            <td>{{ $transaction->display_service_name }}</td>
                                            <td>
                                                <x-filament::badge :color="\App\Services\ServicePerformanceSummaryService::statusColor($transaction->service_status)">
                                                    {{ \App\Services\ServicePerformanceSummaryService::statusLabel($transaction->service_status) }}
                                                </x-filament::badge>
                                            </td>
                                            <td><bdi>{{ $transaction->transaction_date?->format('Y-m-d') ?? '—' }}</bdi></td>
                                            <td><bdi>{{ $transaction->due_date?->format('Y-m-d') ?? '—' }}</bdi></td>
                                            <td><bdi>{{ $transaction->completion_date?->format('Y-m-d') ?? '—' }}</bdi></td>
                                            <td>
                                                @if($linked->isEmpty())
                                                    <span class="tp-muted">—</span>
                                                @else
                                                    <div class="tp-linked-list">
                                                        @foreach($linked as $deficiency)
                                                            <span>
                                                                <x-filament::badge :color="\App\Services\ServicePerformanceSummaryService::deficiencyStatusColor($deficiency->status)">
                                                                    {{ \App\Services\ServicePerformanceSummaryService::deficiencyTypeLabel($deficiency->deficiency_type) }}
                                                                </x-filament::badge>
                                                                {{ $deficiency->responsibleEmployee?->user?->name ?? __('tasks.performance.services.no_responsible') }}
                                                            </span>
                                                        @endforeach
                                                    </div>
                                                @endif
                                            </td>
                                            <td>
                                                <x-filament::badge :color="$pending['color']">{{ $pending['label'] }}</x-filament::badge>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="8">{{ __('tasks.performance.services.empty') }}</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    @endif

                    <div style="font-size:0.85rem; font-weight:700; margin-top:0.25rem;">
                        {{ __('tasks.performance.services.deficiencies_statement') }}
                    </div>

                    <div class="tp-table-container">
                        <table class="tp-table tp-deficiency-table">
                            <thead>
                                <tr>
                                    @foreach(['transaction_number', 'type', 'title', 'status', 'due_date', 'responsible', 'pending_action'] as $column)
                                        <th scope="col">{{ __('tasks.performance.services.deficiency_columns.'.$column) }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($serviceData['deficiencies'] as $deficiency)
                                    @php
                                        $deficiencyTransaction = \App\Services\ServicePerformanceSummaryService::deficiencyTransaction($deficiency);
                                        $pending = $deficiencyTransaction
                                            ? \App\Services\ServicePerformanceSummaryService::pendingAction($deficiencyTransaction)
                                            : ['label' => __('tasks.performance.services.pending_actions.none'), 'color' => 'gray'];
                                    @endphp
                                    <tr wire:key="service-deficiency-{{ $deficiency->id }}">
                                        <td><bdi>{{ $deficiencyTransaction?->transaction_number ?? '—' }}</bdi></td>
                                        <td>{{ \App\Services\ServicePerformanceSummaryService::deficiencyTypeLabel($deficiency->deficiency_type) }}</td>
                                        <td>{{ $deficiency->title }}</td>
                                        <td>
                                            <x-filament::badge :color="\App\Services\ServicePerformanceSummaryService::deficiencyStatusColor($deficiency->status)">
                                                {{ \App\Services\ServicePerformanceSummaryService::deficiencyStatusLabel($deficiency->status) }}
                                            </x-filament::badge>
                                        </td>
                                        <td><bdi>{{ $deficiency->due_date?->format('Y-m-d') ?? '—' }}</bdi></td>
                                        <td>{{ $deficiency->responsibleEmployee?->user?->name ?? __('tasks.performance.services.no_responsible') }}</td>
                                        <td><x-filament::badge :color="$pending['color']">{{ $pending['label'] }}</x-filament::badge></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="7">{{ __('tasks.performance.services.deficiencies_empty') }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    @endif
</x-filament-panels::page>
