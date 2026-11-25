<x-filament-panels::page>
    <style>
        .task-summary-page .report-stats-grid { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 0.875rem; }
        .task-summary-page .report-stat-card { background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 0.875rem; padding: 1rem; min-width: 0; min-height: 116px; display: flex; flex-direction: column; justify-content: space-between; gap: 0.75rem; }
        .dark .task-summary-page .report-stat-card { background-color: #1e293b; border-color: #334155; }
        .task-summary-page .report-stat-top { display: flex; align-items: center; justify-content: space-between; gap: 0.5rem; }
        .task-summary-page .report-stat-title { font-size: 0.8125rem; font-weight: 700; color: #475569; }
        .dark .task-summary-page .report-stat-title { color: #cbd5e1; }
        .task-summary-page .report-stat-icon-wrapper { width: 36px; height: 36px; border-radius: 0.5rem; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .task-summary-page .report-stat-value { font-size: 1.75rem; font-weight: 800; line-height: 1.15; font-variant-numeric: tabular-nums; overflow-wrap: anywhere; }
        .task-summary-page .report-stat-subtitle { font-size: 0.75rem; font-weight: 600; }

        .task-summary-page .report-table-container { width: 100%; overflow-x: auto; border-radius: 0.75rem; border: 1px solid #e2e8f0; background-color: #ffffff; }
        .dark .task-summary-page .report-table-container { border-color: #334155; background-color: #1e293b; }
        .task-summary-page .task-summary-table { width: 100%; text-align: start; border-collapse: collapse; font-size: 0.8125rem; min-width: 1250px; }
        .task-summary-page .task-summary-table thead tr { background-color: #f8fafc; border-bottom: 2px solid #e2e8f0; }
        .dark .task-summary-page .task-summary-table thead tr { background-color: #0f172a; border-color: #334155; }
        .task-summary-page .task-summary-table th { padding: 0.75rem; font-weight: 700; color: #334155; text-align: inherit; white-space: nowrap; }
        .dark .task-summary-page .task-summary-table th { color: #cbd5e1; }
        .task-summary-page .task-summary-table tbody tr { border-bottom: 1px solid #f1f5f9; }
        .dark .task-summary-page .task-summary-table tbody tr { border-bottom-color: #334155; }
        .task-summary-page .task-summary-table tbody tr:hover { background-color: #f8fafc; }
        .dark .task-summary-page .task-summary-table tbody tr:hover { background-color: rgba(255, 255, 255, 0.03); }
        .task-summary-page .task-summary-table td { padding: 0.65rem 0.75rem; white-space: nowrap; color: #0f172a; }
        .dark .task-summary-page .task-summary-table td { color: #f1f5f9; }
        .task-summary-page .report-table-pagination { padding: 1rem; border-top: 1px solid #e2e8f0; }
        .dark .task-summary-page .report-table-pagination { border-top-color: #334155; }
        .task-summary-page .task-statement-employee { padding: 0.875rem 1rem; border: 1px solid #e2e8f0; border-radius: 0.75rem; background: #f8fafc; margin-bottom: 1rem; display: flex; flex-direction: column; gap: 0.5rem; }
        .dark .task-summary-page .task-statement-employee { background: #0f172a; border-color: #334155; }
        .task-summary-page .task-statement-employee-meta { display: flex; flex-wrap: wrap; gap: 0.4rem 1.25rem; font-size: 0.8125rem; color: #475569; }
        .dark .task-summary-page .task-statement-employee-meta { color: #cbd5e1; }
        .task-summary-page .task-statement-item { margin-block: 0.75rem; padding: 1rem; border: 1px solid #e2e8f0; border-radius: 0.75rem; background: #ffffff; box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04); }
        .dark .task-summary-page .task-statement-item { background: #0f172a; border-color: #334155; }
        .task-summary-page .task-statement-item-header { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 0.75rem; padding-bottom: 0.75rem; border-bottom: 1px solid #f1f5f9; }
        .dark .task-summary-page .task-statement-item-header { border-bottom-color: #1e293b; }
        .task-summary-page .task-statement-code { font-size: 0.75rem; font-weight: 600; color: #475569; background: #f1f5f9; padding: 0.2rem 0.6rem; border-radius: 9999px; }
        .dark .task-summary-page .task-statement-code { color: #cbd5e1; background: #1e293b; }
        .task-summary-page .task-statement-meta { display: grid; grid-template-columns: repeat(6, minmax(0, 1fr)); gap: 0.625rem; margin-top: 0.75rem; }
        .task-summary-page .task-statement-meta > div { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 0.5rem; padding: 0.5rem 0.65rem; display: flex; flex-direction: column; gap: 0.2rem; min-width: 0; }
        .dark .task-summary-page .task-statement-meta > div { background: #1e293b; border-color: #334155; }
        .task-summary-page .task-statement-meta dt { font-size: 0.72rem; font-weight: 600; color: #64748b; }
        .dark .task-summary-page .task-statement-meta dt { color: #94a3b8; }
        .task-summary-page .task-statement-meta dd { font-weight: 700; font-size: 0.875rem; color: #0f172a; font-variant-numeric: tabular-nums; margin: 0; }
        .dark .task-summary-page .task-statement-meta dd { color: #f8fafc; }
        .task-summary-page .report-icon-md { width: 20px !important; height: 20px !important; flex-shrink: 0; }
        .task-summary-page .report-icon-sm { width: 16px !important; height: 16px !important; flex-shrink: 0; }

        @media (max-width: 1100px) { .task-summary-page .report-stats-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
        @media (max-width: 900px) { .task-summary-page .task-statement-meta { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
        @media (max-width: 768px) { .task-summary-page .report-stats-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
        @media (max-width: 540px) { .task-summary-page .task-statement-meta { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
        @media (max-width: 480px) { .task-summary-page .report-stats-grid { grid-template-columns: minmax(0, 1fr); } }
    </style>

    <div class="task-summary-page" style="display:flex; flex-direction:column; gap:1.25rem;">
        @php
            $stats = $this->stats;
            $rows = $this->summary;
            $details = $this->details;
            $cards = [
                ['key' => null, 'clickable' => true, 'title' => __('tasks.summary.employees_count'), 'value' => number_format((float) ($stats->employees_count ?? 0)), 'icon' => 'heroicon-o-users', 'color' => '#1d4ed8', 'bg' => '#eff6ff', 'icon_bg' => '#dbeafe'],
                ['key' => 'total_items', 'title' => __('tasks.summary.total_items'), 'value' => number_format((float) ($stats->total_items ?? 0)), 'icon' => 'heroicon-o-queue-list', 'color' => '#15803d', 'bg' => '#f0fdf4', 'icon_bg' => '#dcfce7'],
                ['key' => 'completed_in_period', 'title' => __('tasks.summary.completed_in_period'), 'value' => number_format((float) ($stats->completed_in_period ?? 0)), 'icon' => 'heroicon-o-check-circle', 'color' => '#059669', 'bg' => '#ecfdf5', 'icon_bg' => '#d1fae5'],
                ['key' => 'overdue_now', 'title' => __('tasks.summary.overdue_now'), 'value' => number_format((float) ($stats->overdue_count ?? 0)), 'icon' => 'heroicon-o-exclamation-triangle', 'color' => '#b91c1c', 'bg' => '#fef2f2', 'icon_bg' => '#fee2e2'],
                ['key' => 'postponed', 'title' => __('tasks.summary.postponed'), 'value' => number_format((float) ($stats->postponed_count ?? 0)), 'icon' => 'heroicon-o-pause-circle', 'color' => '#b45309', 'bg' => '#fffbeb', 'icon_bg' => '#fef3c7'],
            ];
        @endphp

        <x-summary-stat-cards :cards="$cards" :active="$this->quickFilter" />

        <x-filter-bar
            :title="__('tasks.summary.filter_title')"
            :show-company="true"
            :companies="$this->companies"
            :company-label="__('tasks.summary.columns.company')"
            :multiple-company="true"
            :show-branch="true"
            :branches="$this->branches"
            :branch-label="__('tasks.summary.columns.branch')"
            :multiple-branch="true"
            :show-dates="true"
            :date-from-label="__('tasks.summary.date_from')"
            :date-to-label="__('tasks.summary.date_to')"
            :show-search="true"
            :search-placeholder="__('tasks.summary.search_placeholder')"
            :has-active="$this->hasActiveFilters()"
            reset-method="resetFilters"
        >
            <x-slot name="actions">
                <div style="display:inline-flex; align-items:center; gap:0.5rem; flex-wrap:wrap;">
                    @if(! empty($this->selectedEmployees))
                        <a
                            href="{{ $this->buildPrintUrl() }}"
                            target="_blank"
                            class="fi-btn fi-btn-color-primary fi-size-md"
                            style="display:inline-flex; align-items:center; gap:0.4rem; padding:0.5rem 1rem; border-radius:0.5rem; background:#2563eb; color:#fff; font-weight:700; font-size:0.85rem; text-decoration:none;"
                        >
                            <x-heroicon-o-printer class="report-icon-sm" />
                            <span>{{ __('tasks.summary.print_selected', ['count' => count($this->selectedEmployees)]) }}</span>
                        </a>
                    @else
                        <a
                            href="{{ $this->buildPrintUrl() }}"
                            target="_blank"
                            class="fi-btn fi-btn-color-gray fi-size-md"
                            style="display:inline-flex; align-items:center; gap:0.4rem; padding:0.5rem 1rem; border-radius:0.5rem; background:#334155; color:#fff; font-weight:700; font-size:0.85rem; text-decoration:none;"
                        >
                            <x-heroicon-o-printer class="report-icon-sm" />
                            <span>{{ __('tasks.summary.print') }}</span>
                        </a>
                    @endif

                    <button type="button" wire:click="exportCsv" class="fi-btn fi-btn-color-gray fi-size-md" style="display:inline-flex; align-items:center; gap:0.4rem; padding:0.5rem 1rem; border-radius:0.5rem; background:#0f172a; color:#fff; font-weight:700; font-size:0.85rem;">
                        <x-heroicon-o-arrow-down-tray class="report-icon-sm" />
                        <span>{{ __('tasks.summary.export') }}</span>
                    </button>
                </div>
            </x-slot>
        </x-filter-bar>

        <div class="report-table-container">
            <table class="task-summary-table">
                <thead>
                    <tr>
                        <th scope="col" style="width: 36px; text-align: center; padding: 0.5rem;">
                            <input
                                type="checkbox"
                                wire:model.live="selectAll"
                                title="{{ __('tasks.summary.select_all') }}"
                                style="border-radius: 4px; cursor: pointer; width: 16px; height: 16px;"
                            />
                        </th>
                        @foreach(['employee', 'company', 'branch', 'department', 'total_items', 'completed_count', 'in_progress_count', 'pending_count', 'postponed_count', 'overdue_count', 'completed_in_period', 'due_in_period', 'weighted_percentage', 'completion_rate', 'on_time_rate'] as $column)
                            <th scope="col">{{ __('tasks.summary.columns.'.$column) }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        <tr wire:key="task-employee-{{ $row->id }}">
                            <td style="text-align: center; padding: 0.5rem;">
                                <input
                                    type="checkbox"
                                    value="{{ $row->id }}"
                                    wire:model.live="selectedEmployees"
                                    style="border-radius: 4px; cursor: pointer; width: 16px; height: 16px;"
                                />
                            </td>
                            <td>
                                <div style="display:flex; align-items:center; justify-content:space-between; gap:0.5rem;">
                                    <div>
                                        <x-filament::link tag="button" wire:click="openDetails({{ $row->id }})">
                                            {{ $row->user?->name ?? $row->employee_number ?? '#'.$row->id }}
                                        </x-filament::link>
                                        <div style="font-size:0.7rem; color:#94a3b8;"><bdi>{{ $row->employee_number }}</bdi></div>
                                    </div>
                                    <a
                                        href="{{ $this->buildEmployeeStatementPrintUrl($row->id) }}"
                                        target="_blank"
                                        title="{{ __('tasks.summary.print_statement') }}"
                                        style="color:#64748b; padding:4px; border-radius:4px; display:inline-flex; align-items:center; justify-content:center; text-decoration:none;"
                                        onmouseover="this.style.color='#2563eb'"
                                        onmouseout="this.style.color='#64748b'"
                                    >
                                        <x-heroicon-o-printer class="report-icon-sm" />
                                    </a>
                                </div>
                            </td>
                            <td>{{ $row->company?->trade_name ?? '—' }}</td>
                            <td>{{ $row->branch?->name ?? '—' }}</td>
                            <td>{{ $row->department?->name ?? '—' }}</td>
                            <td>{{ (int) $row->total_items }}</td>
                            <td>{{ (int) $row->completed_count }}</td>
                            <td>{{ (int) $row->in_progress_count }}</td>
                            <td>{{ (int) $row->pending_count }}</td>
                            <td>{{ (int) $row->postponed_count }}</td>
                            <td style="color:#b91c1c; font-weight:700;">{{ (int) $row->overdue_count }}</td>
                            <td>{{ (int) $row->completed_in_period }}</td>
                            <td>{{ (int) $row->due_in_period }}</td>
                            <td>
                                <bdi>{{ (float) ($row->weight_sum ?? 0) > 0 ? number_format(\App\Services\TaskAssignmentSummaryService::weightedPercentage($row), 2).'%' : __('tasks.summary.no_data') }}</bdi>
                            </td>
                            <td>
                                @php($rate = \App\Services\TaskAssignmentSummaryService::completionRate($row))
                                <bdi>{{ $rate === null ? __('tasks.summary.no_data') : number_format($rate, 2).'%' }}</bdi>
                            </td>
                            <td>
                                @php($rate = \App\Services\TaskAssignmentSummaryService::onTimeRate($row))
                                <bdi>{{ $rate === null ? __('tasks.summary.no_data') : number_format($rate, 2).'%' }}</bdi>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="16">{{ __('tasks.summary.empty') }}</td></tr>
                    @endforelse
                </tbody>
            </table>

            @if($rows->hasPages())
                <div class="report-table-pagination">
                    <x-filament::pagination :paginator="$rows" />
                </div>
            @endif
        </div>

        <x-filament::modal id="task-assignment-details" width="5xl">
            <x-slot:heading>{{ __('tasks.summary.statement') }}</x-slot:heading>

            @if($details)
                @php($employee = $details['employee'])
                <div class="task-statement-employee">
                    <div style="font-size:1.05rem; font-weight:800;">{{ $employee->user?->name ?? $employee->employee_number }}</div>
                    <div class="task-statement-employee-meta">
                        <span><strong>{{ __('tasks.summary.columns.company') }}:</strong> {{ $employee->company?->trade_name ?? '—' }}</span>
                        <span><strong>{{ __('tasks.summary.columns.branch') }}:</strong> {{ $employee->branch?->name ?? '—' }}</span>
                        <span><strong>{{ __('tasks.summary.columns.department') }}:</strong> {{ $employee->department?->name ?? '—' }}</span>
                        <span><strong>{{ __('tasks.summary.columns.employee_number') }}:</strong> <bdi>{{ $employee->employee_number ?? '—' }}</bdi></span>
                    </div>
                </div>

                @forelse($details['items'] as $item)
                    <article class="task-statement-item">
                        <header class="task-statement-item-header">
                            <div style="display:flex; flex-wrap:wrap; align-items:center; gap:0.5rem;">
                                <strong style="font-size:0.95rem;">{{ $item->task?->name ?? '—' }}</strong>
                                <x-filament::badge :color="$item->status?->getColor() ?? 'gray'">
                                    {{ $item->status?->getLabel() ?? '—' }}
                                </x-filament::badge>
                                @if(filled($item->taskAssignment?->transaction_number))
                                    <span class="task-statement-code"><bdi>{{ $item->taskAssignment?->transaction_number }}</bdi></span>
                                @endif
                            </div>

                            @if($item->taskAssignment && \App\Filament\Resources\TaskAssignments\TaskAssignmentResource::canEdit($item->taskAssignment))
                                <x-filament::button
                                    tag="a"
                                    size="xs"
                                    color="primary"
                                    outlined
                                    :href="\App\Filament\Resources\TaskAssignments\TaskAssignmentResource::getUrl('edit', ['record' => $item->taskAssignment])"
                                    icon="heroicon-m-arrow-top-right-on-square"
                                >
                                    {{ __('tasks.summary.open_assignment') }}
                                </x-filament::button>
                            @endif
                        </header>

                        <dl class="task-statement-meta">
                            <div><dt>{{ __('tasks.fields.due_date') }}</dt><dd><bdi>{{ $item->due_date?->format('Y-m-d') ?? '—' }}</bdi></dd></div>
                            <div><dt>{{ __('tasks.fields.completed_at') }}</dt><dd><bdi>{{ $item->completed_at?->format('Y-m-d') ?? '—' }}</bdi></dd></div>
                            <div><dt>{{ __('tasks.fields.weight') }}</dt><dd><bdi>{{ number_format((float) $item->weight, 2) }}%</bdi></dd></div>
                            <div><dt>{{ __('tasks.fields.score') }}</dt><dd><bdi>{{ number_format((float) $item->score, 2) }}</bdi></dd></div>
                            <div><dt>{{ __('tasks.fields.score_percentage') }}</dt><dd><bdi>{{ number_format((float) $item->score_percentage, 2) }}%</bdi></dd></div>
                            <div><dt>{{ __('tasks.fields.overall_percentage') }}</dt><dd><bdi>{{ number_format((float) $item->taskAssignment?->overall_percentage, 2) }}%</bdi></dd></div>
                        </dl>
                    </article>
                @empty
                    <p>{{ __('tasks.summary.empty') }}</p>
                @endforelse

                <x-slot:footerActions>
                    <x-filament::button
                        tag="a"
                        color="primary"
                        target="_blank"
                        :href="$this->buildEmployeeStatementPrintUrl($employee->id)"
                        icon="heroicon-o-printer"
                    >
                        {{ __('tasks.summary.print_statement') }}
                    </x-filament::button>

                    <x-filament::button color="gray" x-on:click="close()" wire:click="closeDetails">
                        {{ __('tasks.summary.close') }}
                    </x-filament::button>
                </x-slot:footerActions>
            @endif
        </x-filament::modal>
    </div>
</x-filament-panels::page>
