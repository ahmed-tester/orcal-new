<x-filament::modal id="performance-statement-modal" width="7xl">
    <x-slot:heading>
        <div class="perf-modal-header">
            <div class="perf-modal-icon">
                <x-heroicon-o-clipboard-document-list style="width: 20px; height: 20px;" />
            </div>
            <span>{{ $this->statementTitle }}</span>
        </div>
    </x-slot:heading>

    @php
        $records = $this->statementRecords;
    @endphp

    @if($records)
        @if($this->statementDomain === 'services')
            <div class="perf-table-wrapper">
                <table class="perf-table">
                    <thead>
                        <tr>
                            <th>{{ __('performance.statement.transaction_number') }}</th>
                            <th>{{ __('performance.statement.service') }}</th>
                            <th>{{ __('performance.statement.beneficiary') }}</th>
                            <th>{{ __('performance.statement.executor') }}</th>
                            <th>{{ __('performance.statement.transaction_date') }}</th>
                            <th>{{ __('performance.statement.due_date') }}</th>
                            <th>{{ __('performance.statement.completion_date') }}</th>
                            <th style="text-align: center;">{{ __('performance.statement.status') }}</th>
                            <th style="text-align: center;">{{ __('performance.statement.pending_action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($records as $srv)
                            @php
                                $pending = \App\Services\ServicePerformanceSummaryService::pendingAction($srv);
                            @endphp
                            <tr>
                                <td>
                                    <strong class="perf-tx-code"><bdi>{{ $srv->transaction_number }}</bdi></strong>
                                </td>
                                <td>
                                    <span style="font-weight: 600;">{{ $srv->display_service_name }}</span>
                                </td>
                                <td>{{ $srv->display_beneficiary }}</td>
                                <td>
                                    <span style="color: var(--mu, #6b7490);">{{ $srv->executor?->user?->name ?? '—' }}</span>
                                </td>
                                <td><bdi>{{ $srv->transaction_date?->format('Y-m-d') ?? '—' }}</bdi></td>
                                <td><bdi>{{ $srv->due_date?->format('Y-m-d') ?? '—' }}</bdi></td>
                                <td><bdi>{{ $srv->completion_date?->format('Y-m-d') ?? '—' }}</bdi></td>
                                <td style="text-align: center;">
                                    <x-filament::badge :color="\App\Services\ServicePerformanceSummaryService::statusColor($srv->service_status)" size="xs">
                                        {{ \App\Services\ServicePerformanceSummaryService::statusLabel($srv->service_status) }}
                                    </x-filament::badge>
                                </td>
                                <td style="text-align: center;">
                                    <x-filament::badge :color="$pending['color']" size="xs">
                                        {{ $pending['label'] }}
                                    </x-filament::badge>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9">
                                    <div class="perf-empty-state-card" style="padding: 2.5rem 1rem;">
                                        <div class="perf-empty-icon">
                                            <x-heroicon-o-document-magnifying-glass style="width: 28px; height: 28px;" />
                                        </div>
                                        <div class="perf-empty-title">{{ __('performance.statement.empty') }}</div>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        @elseif($this->statementDomain === 'deficiencies')
            <div class="perf-table-wrapper">
                <table class="perf-table">
                    <thead>
                        <tr>
                            <th>{{ __('performance.statement.transaction_number') }}</th>
                            <th>{{ __('performance.statement.deficiency_type') }}</th>
                            <th>{{ __('performance.statement.responsible') }}</th>
                            <th>{{ __('performance.statement.due_date') }}</th>
                            <th style="text-align: center;">{{ __('performance.statement.status') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($records as $def)
                            @php
                                $tx = \App\Services\ServicePerformanceSummaryService::deficiencyTransaction($def);
                            @endphp
                            <tr>
                                <td>
                                    <strong class="perf-tx-code"><bdi>{{ $tx?->transaction_number ?? ($def->task_code ?? "#{$def->id}") }}</bdi></strong>
                                </td>
                                <td>
                                    <div style="display: flex; flex-direction: column; gap: 0.15rem;">
                                        <strong style="color: var(--tx, #141a2e);">{{ $def->title }}</strong>
                                        <span style="font-size: 0.725rem; color: var(--mu, #6b7490);">
                                            {{ \App\Services\ServicePerformanceSummaryService::deficiencyTypeLabel($def->deficiency_type) }}
                                        </span>
                                    </div>
                                </td>
                                <td>{{ $def->responsibleEmployee?->user?->name ?? '—' }}</td>
                                <td><bdi>{{ $def->due_date?->format('Y-m-d') ?? '—' }}</bdi></td>
                                <td style="text-align: center;">
                                    <x-filament::badge :color="\App\Services\ServicePerformanceSummaryService::deficiencyStatusColor($def->status)" size="xs">
                                        {{ \App\Services\ServicePerformanceSummaryService::deficiencyStatusLabel($def->status) }}
                                    </x-filament::badge>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5">
                                    <div class="perf-empty-state-card" style="padding: 2.5rem 1rem;">
                                        <div class="perf-empty-icon">
                                            <x-heroicon-o-document-magnifying-glass style="width: 28px; height: 28px;" />
                                        </div>
                                        <div class="perf-empty-title">{{ __('performance.statement.empty') }}</div>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        @elseif($this->statementDomain === 'tasks')
            <div class="perf-table-wrapper">
                <table class="perf-table">
                    <thead>
                        <tr>
                            <th>{{ __('performance.statement.task_name') }}</th>
                            <th>{{ __('performance.statement.assignment_number') }}</th>
                            <th>{{ __('performance.columns.employee') }}</th>
                            <th>{{ __('performance.statement.due_date') }}</th>
                            <th>{{ __('performance.statement.completion_date') }}</th>
                            <th style="text-align: center;">{{ __('performance.statement.status') }}</th>
                            <th style="text-align: center;">{{ __('performance.statement.weight') }} / {{ __('performance.statement.score') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($records as $item)
                            <tr>
                                <td><strong>{{ $item->task?->name ?? '—' }}</strong></td>
                                <td><bdi>{{ $item->taskAssignment?->transaction_number ?? '—' }}</bdi></td>
                                <td>{{ $item->taskAssignment?->employee?->user?->name ?? '—' }}</td>
                                <td><bdi>{{ $item->due_date?->format('Y-m-d') ?? '—' }}</bdi></td>
                                <td><bdi>{{ $item->completed_at?->format('Y-m-d') ?? '—' }}</bdi></td>
                                <td style="text-align: center;">
                                    <x-filament::badge :color="$item->status?->getColor() ?? 'gray'" size="xs">
                                        {{ $item->status?->getLabel() ?? '—' }}
                                    </x-filament::badge>
                                </td>
                                <td style="text-align: center;">
                                    <bdi>{{ number_format((float) $item->weight, 1) }}%</bdi> /
                                    <bdi>{{ number_format((float) $item->score, 1) }}</bdi>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7">
                                    <div class="perf-empty-state-card" style="padding: 2.5rem 1rem;">
                                        <div class="perf-empty-icon">
                                            <x-heroicon-o-document-magnifying-glass style="width: 28px; height: 28px;" />
                                        </div>
                                        <div class="perf-empty-title">{{ __('performance.statement.empty') }}</div>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @elseif($this->statementDomain === 'attendance' || $this->statementDomain === 'leaves')
            @include('filament.pages.company-department-performance.workforce-records', [
                'group' => $this->statementDomain,
                'type' => $this->statementType,
                'records' => $records,
                'preview' => false,
            ])

        @elseif($this->statementDomain === 'compliance')
            @include('filament.pages.company-department-performance.compliance-records', [
                'type' => $this->statementType,
                'records' => $records,
            ])

        @elseif($this->statementDomain === 'violations')
            <div class="perf-table-wrapper">
                <table class="perf-table">
                    <thead>
                        <tr>
                            <th>{{ __('performance.statement.violation_number') }}</th>
                            <th>{{ __('performance.statement.company') }}</th>
                            <th>{{ __('performance.statement.issuing_authority') }}</th>
                            <th>{{ __('performance.statement.violation_amount') }}</th>
                            <th>{{ __('performance.statement.paid_amount') }}</th>
                            <th>{{ __('performance.statement.remaining_amount') }}</th>
                            <th style="text-align: center;">{{ __('performance.statement.stage') }}</th>
                            <th style="text-align: center;">{{ __('performance.statement.status') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($records as $vio)
                            <tr>
                                <td><strong><bdi>{{ $vio->violation_number ?? "#{$vio->id}" }}</bdi></strong></td>
                                <td>{{ $vio->company?->trade_name ?? '—' }}</td>
                                <td>{{ $vio->issuing_authority ?? '—' }}</td>
                                <td><bdi>{{ number_format($vio->violation_amount, 2) }} ر.س</bdi></td>
                                <td style="color: #059669;"><bdi>{{ number_format($vio->paid_amount, 2) }} ر.س</bdi></td>
                                <td style="color: #e11d48;"><bdi>{{ number_format($vio->remaining_amount, 2) }} ر.س</bdi></td>
                                <td style="text-align: center;">
                                    <x-filament::badge :color="$vio->stage?->getColor() ?? 'gray'" size="xs">
                                        {{ $vio->stage?->getLabel() ?? $vio->stage }}
                                    </x-filament::badge>
                                </td>
                                <td style="text-align: center;">
                                    <x-filament::badge :color="$vio->payment_status?->getColor() ?? 'gray'" size="xs">
                                        {{ $vio->payment_status?->getLabel() ?? $vio->payment_status }}
                                    </x-filament::badge>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8">
                                    <div class="perf-empty-state-card" style="padding: 2.5rem 1rem;">
                                        <div class="perf-empty-icon">
                                            <x-heroicon-o-document-magnifying-glass style="width: 28px; height: 28px;" />
                                        </div>
                                        <div class="perf-empty-title">{{ __('performance.statement.empty') }}</div>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        @elseif($this->statementDomain === 'financial')
            @include('filament.pages.company-department-performance.financial-records', ['type' => $this->statementType])

        @elseif($this->statementDomain === 'tickets')
            <div class="perf-table-wrapper">
                <table class="perf-table">
                    <thead>
                        <tr>
                            <th>{{ __('performance.statement.ticket_number') }}</th>
                            <th>{{ __('performance.statement.ticket_subject') }}</th>
                            <th>{{ __('performance.statement.company') }}</th>
                            <th style="text-align: center;">{{ __('performance.statement.ticket_category') }}</th>
                            <th style="text-align: center;">{{ __('performance.statement.ticket_priority') }}</th>
                            <th style="text-align: center;">{{ __('performance.statement.status') }}</th>
                            <th>{{ __('performance.statement.ticket_assignee') }}</th>
                            <th>{{ __('performance.statement.ticket_created_at') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($records as $tkt)
                            <tr>
                                <td>
                                    @if(\App\Filament\Resources\Tickets\TicketResource::canViewAny())
                                        <a href="{{ \App\Filament\Resources\Tickets\TicketResource::getUrl('view', ['record' => $tkt]) }}" class="perf-tx-code" style="text-decoration: underline;">
                                            <bdi>{{ $tkt->ticket_number ?? "#{$tkt->id}" }}</bdi>
                                        </a>
                                    @else
                                        <strong class="perf-tx-code"><bdi>{{ $tkt->ticket_number ?? "#{$tkt->id}" }}</bdi></strong>
                                    @endif
                                </td>
                                <td><span style="font-weight: 600;">{{ $tkt->subject }}</span></td>
                                <td>{{ $tkt->company?->trade_name ?? '—' }}</td>
                                <td style="text-align: center;">
                                    <x-filament::badge :color="$tkt->category?->getColor() ?? 'gray'" size="xs">
                                        {{ $tkt->category?->getLabel() ?? $tkt->category }}
                                    </x-filament::badge>
                                </td>
                                <td style="text-align: center;">
                                    <x-filament::badge :color="$tkt->priority?->getColor() ?? 'gray'" size="xs">
                                        {{ $tkt->priority?->getLabel() ?? $tkt->priority }}
                                    </x-filament::badge>
                                </td>
                                <td style="text-align: center;">
                                    <x-filament::badge :color="$tkt->status?->getColor() ?? 'gray'" size="xs">
                                        {{ $tkt->status?->getLabel() ?? $tkt->status }}
                                    </x-filament::badge>
                                </td>
                                <td>{{ $tkt->assignedTo?->name ?? '—' }}</td>
                                <td><bdi>{{ $tkt->created_at?->format('Y-m-d') ?? '—' }}</bdi></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8">
                                    <div class="perf-empty-state-card" style="padding: 2.5rem 1rem;">
                                        <div class="perf-empty-icon">
                                            <x-heroicon-o-document-magnifying-glass style="width: 28px; height: 28px;" />
                                        </div>
                                        <div class="perf-empty-title">{{ __('performance.statement.empty') }}</div>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif

        @if($records->hasPages())
            <div style="padding: 1rem 0; border-top: 1px solid var(--bd, #e4e8f3);">
                <x-filament::pagination :paginator="$records" />
            </div>
        @endif
    @endif

    <x-slot:footerActions>
        <x-filament::button color="gray" wire:click="closeStatement">
            {{ __('performance.statement.close') }}
        </x-filament::button>
    </x-slot:footerActions>
</x-filament::modal>

