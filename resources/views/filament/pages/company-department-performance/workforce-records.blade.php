@if($group === 'attendance')
    <div class="perf-table-wrapper">
        <table class="perf-table">
            <thead>
                <tr>
                    <th>{{ __('performance.columns.employee') }}</th>
                    <th>{{ __('performance.columns.company') }}</th>
                    <th>{{ __('performance.filters.branch') }}</th>
                    <th>{{ __('performance.workforce.last_attendance_date') }}</th>
                    <th style="text-align: end;">{{ __('performance.overview.'.$type) }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($records as $employee)
                    <tr>
                        <td>
                            <strong>{{ $employee->name }}</strong>
                            @if($employee->jobTitle?->name)
                                <div style="font-size: 0.8rem; color: var(--mu, #6b7490);">{{ $employee->jobTitle->name }}</div>
                            @endif
                        </td>
                        <td>{{ $employee->company?->trade_name ?? '—' }}</td>
                        <td>{{ $employee->branch?->name ?? '—' }}</td>
                        <td><bdi>{{ $employee->last_date ?? '—' }}</bdi></td>
                        <td style="text-align: end; font-weight: 700;">
                            @php
                                $val = (int) $employee->getAttribute($type);
                                $color = match($type) {
                                    'present_days' => '#059669',
                                    'late_days' => '#b45309',
                                    'absent_days' => '#e11d48',
                                    default => 'var(--tx)',
                                };
                            @endphp
                            <span style="color: {{ $color }};">
                                <bdi>{{ number_format($val) }} {{ __('performance.workforce.days_unit') }}</bdi>
                            </span>
                            @if($type === 'late_days' && (float) $employee->late_minutes > 0)
                                <div style="font-size: 0.75rem; color: var(--mu, #6b7490); font-weight: normal;">
                                    {{ __('performance.workforce.late_minutes_format', ['minutes' => number_format((float) $employee->late_minutes)]) }}
                                </div>
                            @endif
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
@elseif($group === 'leaves')
    <div class="perf-table-wrapper">
        <table class="perf-table">
            <thead>
                <tr>
                    <th>{{ __('performance.columns.employee') }}</th>
                    <th>{{ __('performance.columns.company') }}</th>
                    <th>{{ __('performance.filters.branch') }}</th>
                    <th>{{ __('performance.workforce.leave_details') }}</th>
                    <th style="text-align: end;">{{ __('performance.overview.'.$type) }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($records as $employee)
                    <tr>
                        <td>
                            <strong>{{ $employee->name }}</strong>
                            @if($employee->jobTitle?->name)
                                <div style="font-size: 0.8rem; color: var(--mu, #6b7490);">{{ $employee->jobTitle->name }}</div>
                            @endif
                        </td>
                        <td>{{ $employee->company?->trade_name ?? '—' }}</td>
                        <td>{{ $employee->branch?->name ?? '—' }}</td>
                        <td>
                            @if($type === 'on_leave_today_count')
                                <x-filament::badge color="info" size="xs">
                                    {{ __('performance.workforce.active_leave_today') }}
                                </x-filament::badge>
                            @elseif($type === 'pending_requests_count')
                                <x-filament::badge color="warning" size="xs">
                                    {{ __('performance.workforce.pending_request') }}
                                </x-filament::badge>
                            @elseif($employee->last_leave_end_date)
                                <span style="font-size: 0.8rem; color: var(--mu, #6b7490);">
                                    {{ __('performance.workforce.last_leave_end') }}: <bdi>{{ $employee->last_leave_end_date }}</bdi>
                                </span>
                            @else
                                —
                            @endif
                        </td>
                        <td style="text-align: end; font-weight: 700;">
                            @if($type === 'on_leave_today_count')
                                <x-filament::badge color="info" size="xs">
                                    {{ __('performance.workforce.active_leave_today') }}
                                </x-filament::badge>
                            @elseif($type === 'pending_requests_count')
                                <span style="color: #7c3aed;">
                                    <bdi>{{ number_format((int) $employee->pending_leaves_count) }} {{ __('performance.workforce.requests_unit') }}</bdi>
                                </span>
                            @else
                                <span style="color: #059669;">
                                    <bdi>{{ number_format((int) $employee->total_approved_days) }} {{ __('performance.workforce.days_unit') }}</bdi>
                                </span>
                            @endif
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
@endif
