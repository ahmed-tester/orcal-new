<x-filament-panels::page>
    @php
        $report = $this->getReportData();
        $rows = $report['rows'];
        $totals = $report['totals'];
        $canViewFinancial = $report['can_view_financial'];
        $hasActivePolicy = (bool) ($report['has_active_policy'] ?? false);
        $companies = $this->getCompanyOptions();
    @endphp

    <div class="space-y-6">
        <div class="rounded-xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-950/40 dark:text-amber-200">
            <p class="font-medium">
                {{ $hasActivePolicy ? __('attendance.period_report.active_policy_notice') : __('attendance.period_report.pending_net_notice') }}
            </p>
            <p class="mt-1 text-xs">{{ __('attendance.period_report.overtime_source_note') }}</p>
        </div>

        <x-filament::section>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-5">
                <div class="md:col-span-2">
                    <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">
                        {{ __('attendance.period_report.filters.period_preset') }}
                    </label>
                    <div class="flex flex-wrap gap-2">
                        @foreach (['day', 'week', 'month', 'custom'] as $preset)
                            <x-filament::button
                                size="sm"
                                :color="$periodPreset === $preset ? 'primary' : 'gray'"
                                wire:click="applyPreset('{{ $preset }}')"
                            >
                                {{ __('attendance.period_report.presets.' . $preset) }}
                            </x-filament::button>
                        @endforeach
                    </div>
                </div>

                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">
                        {{ __('attendance.period_report.filters.date_from') }}
                    </label>
                    <input
                        type="date"
                        wire:model.live="dateFrom"
                        class="w-full rounded-lg border-gray-300 text-sm shadow-sm dark:border-gray-700 dark:bg-gray-900"
                    />
                </div>

                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">
                        {{ __('attendance.period_report.filters.date_to') }}
                    </label>
                    <input
                        type="date"
                        wire:model.live="dateTo"
                        class="w-full rounded-lg border-gray-300 text-sm shadow-sm dark:border-gray-700 dark:bg-gray-900"
                    />
                </div>

                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">
                        {{ __('attendance.period_report.columns.company') }}
                    </label>
                    <select
                        wire:model.live="companyId"
                        class="w-full rounded-lg border-gray-300 text-sm shadow-sm dark:border-gray-700 dark:bg-gray-900"
                    >
                        <option value="">{{ __('employees.statement.all_authorized_companies') }}</option>
                        @foreach ($companies as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="md:col-span-2">
                    <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">
                        {{ __('attendance.period_report.filters.search') }}
                    </label>
                    <input
                        type="text"
                        wire:model.live.debounce.400ms="search"
                        placeholder="{{ __('attendance.period_report.filters.search') }}"
                        class="w-full rounded-lg border-gray-300 text-sm shadow-sm dark:border-gray-700 dark:bg-gray-900"
                    />
                </div>
            </div>
        </x-filament::section>

        <x-filament::section>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 text-start text-xs font-semibold text-gray-600 dark:border-gray-700 dark:text-gray-300">
                            <th class="px-3 py-2">{{ __('attendance.period_report.columns.employee_name') }}</th>
                            <th class="px-3 py-2">{{ __('attendance.period_report.columns.company') }}</th>
                            <th class="px-3 py-2 text-center">{{ __('attendance.period_report.columns.recorded_days') }}</th>
                            <th class="px-3 py-2 text-center">{{ __('attendance.period_report.columns.present_days') }}</th>
                            <th class="px-3 py-2 text-center">{{ __('attendance.period_report.columns.late_days') }}</th>
                            <th class="px-3 py-2 text-center">{{ __('attendance.period_report.columns.absent_days') }}</th>
                            <th class="px-3 py-2 text-center">{{ __('attendance.period_report.columns.late_minutes') }}</th>
                            <th class="px-3 py-2 text-center">{{ __('attendance.period_report.columns.overtime_minutes') }}</th>
                            <th class="px-3 py-2 text-center">{{ __('attendance.period_report.columns.total_hours') }}</th>
                            @if ($canViewFinancial)
                                <th class="px-3 py-2 text-center">{{ __('attendance.period_report.columns.reference_total_salary') }}</th>
                                <th class="px-3 py-2 text-center">{{ __('attendance.period_report.columns.overtime_hourly_rate') }}</th>
                                <th class="px-3 py-2 text-center">{{ __('attendance.period_report.columns.overtime_cost_estimate') }}</th>
                                @if ($hasActivePolicy)
                                    <th class="px-3 py-2 text-center">{{ __('attendance.period_report.columns.late_deduction') }}</th>
                                    <th class="px-3 py-2 text-center">{{ __('attendance.period_report.columns.compensation_minutes_used') }}</th>
                                    <th class="px-3 py-2 text-center">{{ __('attendance.period_report.columns.compensation_amount') }}</th>
                                    <th class="px-3 py-2 text-center">{{ __('attendance.period_report.columns.remaining_late_deduction') }}</th>
                                @endif
                            @endif
                            <th class="px-3 py-2 text-center">{{ __('attendance.period_report.details_action') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse ($rows as $row)
                            <tr>
                                <td class="px-3 py-2">
                                    <div class="font-medium">{{ $row['employee_name'] }}</div>
                                    <div class="text-xs text-gray-500">{{ $row['employee_number'] }}</div>
                                </td>
                                <td class="px-3 py-2 text-xs">
                                    <div>{{ $row['company_name'] }}</div>
                                    <div class="text-gray-500">{{ $row['branch_name'] }}</div>
                                </td>
                                <td class="px-3 py-2 text-center">{{ $row['recorded_days'] }}</td>
                                <td class="px-3 py-2 text-center">{{ $row['present_days'] }}</td>
                                <td class="px-3 py-2 text-center">{{ $row['late_days'] }}</td>
                                <td class="px-3 py-2 text-center">{{ $row['absent_days'] }}</td>
                                <td class="px-3 py-2 text-center">{{ $row['late_minutes'] }}</td>
                                <td class="px-3 py-2 text-center">{{ $row['overtime_minutes'] }}</td>
                                <td class="px-3 py-2 text-center">{{ number_format((float) $row['total_hours'], 2) }}</td>
                                @if ($canViewFinancial)
                                    @php $ref = $row['salary_reference']; @endphp
                                    <td class="px-3 py-2 text-center">
                                        <div>{{ $ref ? number_format((float) $ref['total_salary'], 2) : '—' }}</div>
                                        @if ($ref)
                                            <div class="text-[11px] text-gray-500">
                                                {{ $ref['is_dated_snapshot'] ? __('attendance.period_report.reference_salary_dated_label') : __('attendance.period_report.reference_salary_current_label') }}
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 text-center">
                                        {{ ($ref && $ref['overtime_hourly_rate'] !== null) ? number_format((float) $ref['overtime_hourly_rate'], 2) : __('employees.salary_profile.placeholders.unspecified') }}
                                    </td>
                                    <td class="px-3 py-2 text-center">
                                        @if ($row['overtime_cost_estimate'] !== null)
                                            <div class="font-medium">{{ number_format((float) $row['overtime_cost_estimate'], 2) }}</div>
                                            <div class="text-[11px] text-amber-700 dark:text-amber-300">
                                                {{ __('attendance.period_report.provisional_estimate_badge') }}
                                            </div>
                                        @else
                                            <span class="text-xs text-gray-500">{{ __('attendance.period_report.pending_calculation') }}</span>
                                        @endif
                                    </td>
                                    @if ($hasActivePolicy)
                                        @php $pol = $row['policy_summary'] ?? null; @endphp
                                        <td class="px-3 py-2 text-center">{{ ($pol && $pol['active']) ? number_format((float) $pol['late_deduction_amount'], 2) : '—' }}</td>
                                        <td class="px-3 py-2 text-center">{{ ($pol && $pol['active']) ? number_format((float) $pol['compensation_minutes_used'], 2) : '—' }}</td>
                                        <td class="px-3 py-2 text-center">{{ ($pol && $pol['active']) ? number_format((float) $pol['compensation_amount'], 2) : '—' }}</td>
                                        <td class="px-3 py-2 text-center">{{ ($pol && $pol['active']) ? number_format((float) $pol['remaining_late_deduction'], 2) : '—' }}</td>
                                    @endif
                                @endif
                                <td class="px-3 py-2 text-center">
                                    <x-filament::button
                                        size="xs"
                                        color="gray"
                                        wire:click="openEmployeeDetails({{ $row['employee_id'] }})"
                                    >
                                        {{ __('attendance.period_report.details_action') }}
                                    </x-filament::button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $canViewFinancial ? ($hasActivePolicy ? 17 : 13) : 10 }}" class="px-3 py-6 text-center text-gray-500">
                                    {{ __('attendance.table.no_records') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if ($rows->isNotEmpty())
                        <tfoot>
                            <tr class="border-t-2 border-gray-200 bg-gray-50 font-semibold dark:border-gray-700 dark:bg-gray-900/50">
                                <td class="px-3 py-2" colspan="2">{{ __('attendance.summary.stats.employees') }}: {{ $totals['employees_count'] }}</td>
                                <td class="px-3 py-2 text-center">{{ $totals['recorded_days'] }}</td>
                                <td class="px-3 py-2 text-center">{{ $totals['present_days'] }}</td>
                                <td class="px-3 py-2 text-center">{{ $totals['late_days'] }}</td>
                                <td class="px-3 py-2 text-center">{{ $totals['absent_days'] }}</td>
                                <td class="px-3 py-2 text-center">{{ $totals['late_minutes'] }}</td>
                                <td class="px-3 py-2 text-center">{{ $totals['overtime_minutes'] }}</td>
                                <td class="px-3 py-2 text-center">{{ number_format((float) $totals['total_hours'], 2) }}</td>
                                @if ($canViewFinancial)
                                    <td class="px-3 py-2 text-center">—</td>
                                    <td class="px-3 py-2 text-center">—</td>
                                    <td class="px-3 py-2 text-center">
                                        @if ($totals['provisional_overtime_cost_total'] !== null)
                                            <div>{{ number_format((float) $totals['provisional_overtime_cost_total'], 2) }}</div>
                                            <div class="text-[11px] text-amber-700 dark:text-amber-300">
                                                {{ __('attendance.period_report.provisional_estimate_badge') }}
                                            </div>
                                        @else
                                            <span class="text-xs text-gray-500">{{ __('attendance.period_report.pending_calculation') }}</span>
                                        @endif
                                    </td>
                                    @if ($hasActivePolicy)
                                        <td class="px-3 py-2 text-center">{{ number_format((float) ($totals['late_deduction_amount'] ?? 0), 2) }}</td>
                                        <td class="px-3 py-2 text-center">{{ number_format((float) ($totals['compensation_minutes_used'] ?? 0), 2) }}</td>
                                        <td class="px-3 py-2 text-center">{{ number_format((float) ($totals['compensation_amount'] ?? 0), 2) }}</td>
                                        <td class="px-3 py-2 text-center">{{ number_format((float) ($totals['remaining_late_deduction'] ?? 0), 2) }}</td>
                                    @endif
                                @endif
                                <td class="px-3 py-2"></td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </x-filament::section>
    </div>

    <x-filament::modal id="saudi-period-details-modal" width="5xl">
        @if ($details)
            <x-slot name="heading">
                {{ __('attendance.period_report.modal_heading', ['name' => $details['employee']['name']]) }}
            </x-slot>

            <div class="space-y-4">
                <div class="flex flex-wrap gap-4 text-xs text-gray-600 dark:text-gray-300">
                    <span>{{ __('attendance.period_report.columns.employee_number') }}: {{ $details['employee']['employee_number'] }}</span>
                    <span>{{ __('attendance.period_report.columns.company') }}: {{ $details['employee']['company_name'] }}</span>
                    <span>{{ __('attendance.period_report.columns.branch') }}: {{ $details['employee']['branch_name'] }}</span>
                    <span>{{ $details['date_from'] }} — {{ $details['date_to'] }}</span>
                </div>

                @if ($details['can_view_financial'] && $details['salary_reference'])
                    @php $ref = $details['salary_reference']; @endphp
                    <div class="rounded-lg border border-gray-200 bg-gray-50 p-3 text-xs dark:border-gray-700 dark:bg-gray-900">
                        <div class="font-semibold">
                            {{ $ref['is_dated_snapshot'] ? __('attendance.period_report.reference_salary_dated_label') : __('attendance.period_report.reference_salary_current_label') }}
                        </div>
                        <div class="mt-2 grid grid-cols-2 gap-2 md:grid-cols-4">
                            <span>{{ __('employees.fields.basic_salary') }}: {{ number_format((float) $ref['basic_salary'], 2) }}</span>
                            <span>{{ __('employees.salary_profile.fields.allowances_amount') }}: {{ number_format((float) $ref['allowances_amount'], 2) }}</span>
                            <span>{{ __('employees.salary_profile.fields.bonuses_amount') }}: {{ number_format((float) $ref['bonuses_amount'], 2) }}</span>
                            <span>{{ __('employees.salary_profile.fields.total_salary') }}: {{ number_format((float) $ref['total_salary'], 2) }}</span>
                        </div>
                    </div>
                @endif

                <div class="overflow-x-auto">
                    <table class="w-full text-xs">
                        <thead>
                            <tr class="border-b border-gray-200 text-start dark:border-gray-700">
                                <th class="px-2 py-2">{{ __('attendance.period_report.columns.date') }}</th>
                                <th class="px-2 py-2">{{ __('attendance.period_report.columns.status') }}</th>
                                <th class="px-2 py-2">{{ __('attendance.period_report.columns.check_in') }}</th>
                                <th class="px-2 py-2">{{ __('attendance.period_report.columns.check_out') }}</th>
                                <th class="px-2 py-2">{{ __('attendance.period_report.columns.late_minutes') }}</th>
                                <th class="px-2 py-2">{{ __('attendance.period_report.columns.overtime_minutes') }}</th>
                                <th class="px-2 py-2">{{ __('attendance.period_report.columns.total_hours') }}</th>
                                <th class="px-2 py-2">{{ __('attendance.period_report.columns.source') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @forelse ($details['records'] as $rec)
                                <tr>
                                    <td class="px-2 py-1.5">{{ $rec['date'] }}</td>
                                    <td class="px-2 py-1.5">{{ $rec['status_label'] }}</td>
                                    <td class="px-2 py-1.5">{{ $rec['check_in'] }}</td>
                                    <td class="px-2 py-1.5">{{ $rec['check_out'] }}</td>
                                    <td class="px-2 py-1.5">{{ $rec['late_minutes'] }}</td>
                                    <td class="px-2 py-1.5">{{ $rec['overtime_minutes'] }}</td>
                                    <td class="px-2 py-1.5">{{ number_format((float) $rec['total_hours'], 2) }}</td>
                                    <td class="px-2 py-1.5">{{ $rec['source_label'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-2 py-4 text-center text-gray-500">
                                        {{ __('attendance.table.no_records') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </x-filament::modal>
</x-filament-panels::page>
