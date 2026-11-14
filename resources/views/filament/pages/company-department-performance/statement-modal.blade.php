<x-filament::modal id="performance-kashf-modal" width="7xl">
    <x-slot:heading>
        <div class="perf-modal-header">
            <div class="perf-modal-icon">
                <x-heroicon-o-clipboard-document-list style="width: 20px; height: 20px;" />
            </div>
            <span>{{ $this->kashfTitle }}</span>
        </div>
    </x-slot:heading>

    @php
        $records = $this->kashfRecords;
    @endphp

    @if($records)
        @if($this->kashfDomain === 'services')
            <div class="perf-table-wrapper">
                <table class="perf-table">
                    <thead>
                        <tr>
                            <th>{{ __('performance.kashf.transaction_number') }}</th>
                            <th>{{ __('performance.kashf.service') }}</th>
                            <th>{{ __('performance.kashf.beneficiary') }}</th>
                            <th>{{ __('performance.kashf.executor') }}</th>
                            <th>{{ __('performance.kashf.transaction_date') }}</th>
                            <th>{{ __('performance.kashf.due_date') }}</th>
                            <th>{{ __('performance.kashf.completion_date') }}</th>
                            <th style="text-align: center;">{{ __('performance.kashf.status') }}</th>
                            <th style="text-align: center;">{{ __('performance.kashf.pending_action') }}</th>
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
                                        <div class="perf-empty-title">{{ __('performance.kashf.empty') }}</div>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        @elseif($this->kashfDomain === 'deficiencies')
            <div class="perf-table-wrapper">
                <table class="perf-table">
                    <thead>
                        <tr>
                            <th>{{ __('performance.kashf.transaction_number') }}</th>
                            <th>{{ __('performance.kashf.deficiency_type') }}</th>
                            <th>{{ __('performance.kashf.responsible') }}</th>
                            <th>{{ __('performance.kashf.due_date') }}</th>
                            <th style="text-align: center;">{{ __('performance.kashf.status') }}</th>
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
                                        <div class="perf-empty-title">{{ __('performance.kashf.empty') }}</div>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        @elseif($this->kashfDomain === 'tasks')
            <div class="perf-table-wrapper">
                <table class="perf-table">
                    <thead>
                        <tr>
                            <th>{{ __('performance.kashf.task_name') }}</th>
                            <th>{{ __('performance.kashf.assignment_number') }}</th>
                            <th>{{ __('performance.columns.employee') }}</th>
                            <th>{{ __('performance.kashf.due_date') }}</th>
                            <th>{{ __('performance.kashf.completion_date') }}</th>
                            <th style="text-align: center;">{{ __('performance.kashf.status') }}</th>
                            <th style="text-align: center;">{{ __('performance.kashf.weight') }} / {{ __('performance.kashf.score') }}</th>
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
                                        <div class="perf-empty-title">{{ __('performance.kashf.empty') }}</div>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @elseif($this->kashfDomain === 'compliance')
            <div class="perf-table-wrapper">
                <table class="perf-table">
                    <thead>
                        <tr>
                            <th>{{ __('performance.kashf.employee_name') }}</th>
                            <th>{{ __('performance.kashf.company') }}</th>
                            <th>{{ __('performance.kashf.iqama_number') }}</th>
                            <th>{{ __('performance.kashf.expiry_date') }}</th>
                            <th>{{ __('performance.kashf.days_remaining') }}</th>
                            <th style="text-align: center;">{{ __('performance.kashf.status') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($records as $emp)
                            @php
                                $expiryDate = match($this->kashfType) {
                                    'work_permit_expired' => $emp->details?->work_permit_expiry,
                                    'insurance_expired' => $emp->details?->insurance_expiry,
                                    default => $emp->details?->iqama_expiry,
                                };
                                $diff = $expiryDate ? (int) now()->startOfDay()->diffInDays($expiryDate, false) : null;
                            @endphp
                            <tr>
                                <td><strong>{{ $emp->user?->name ?? '—' }}</strong></td>
                                <td>{{ $emp->company?->trade_name ?? '—' }}</td>
                                <td><bdi>{{ $emp->details?->iqama_number ?? '—' }}</bdi></td>
                                <td><bdi>{{ $expiryDate?->format('Y-m-d') ?? '—' }}</bdi></td>
                                <td>
                                    @if($diff !== null)
                                        @if($diff < 0)
                                            <span style="color: #e11d48; font-weight: 700;">منتهية منذ {{ abs($diff) }} يوم</span>
                                        @elseif($diff === 0)
                                            <span style="color: #ea580c; font-weight: 700;">تنتهي اليوم</span>
                                        @else
                                            <span style="color: #059669; font-weight: 600;">متبقي {{ $diff }} يوم</span>
                                        @endif
                                    @else
                                        —
                                    @endif
                                </td>
                                <td style="text-align: center;">
                                    @if($diff !== null && $diff < 0)
                                        <x-filament::badge color="danger" size="xs">{{ __('performance.compliance.expired') }}</x-filament::badge>
                                    @elseif($diff !== null && $diff <= 90)
                                        <x-filament::badge color="warning" size="xs">{{ __('performance.compliance.expiring_soon') }}</x-filament::badge>
                                    @else
                                        <x-filament::badge color="success" size="xs">{{ __('performance.compliance.valid') }}</x-filament::badge>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6">
                                    <div class="perf-empty-state-card" style="padding: 2.5rem 1rem;">
                                        <div class="perf-empty-icon">
                                            <x-heroicon-o-document-magnifying-glass style="width: 28px; height: 28px;" />
                                        </div>
                                        <div class="perf-empty-title">{{ __('performance.kashf.empty') }}</div>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        @elseif($this->kashfDomain === 'violations')
            <div class="perf-table-wrapper">
                <table class="perf-table">
                    <thead>
                        <tr>
                            <th>{{ __('performance.kashf.violation_number') }}</th>
                            <th>{{ __('performance.kashf.company') }}</th>
                            <th>{{ __('performance.kashf.issuing_authority') }}</th>
                            <th>{{ __('performance.kashf.violation_amount') }}</th>
                            <th>{{ __('performance.kashf.paid_amount') }}</th>
                            <th>{{ __('performance.kashf.remaining_amount') }}</th>
                            <th style="text-align: center;">{{ __('performance.kashf.stage') }}</th>
                            <th style="text-align: center;">{{ __('performance.kashf.status') }}</th>
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
                                        <div class="perf-empty-title">{{ __('performance.kashf.empty') }}</div>
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
        <x-filament::button color="gray" wire:click="closeKashf">
            {{ __('performance.kashf.close') }}
        </x-filament::button>
    </x-slot:footerActions>
</x-filament::modal>

