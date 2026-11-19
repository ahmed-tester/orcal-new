<div class="perf-table-wrapper">
    <table class="perf-table">
        <thead>
            <tr>
                <th>{{ __('performance.financial.kashf_transaction_number') }}</th>
                <th>{{ __('performance.kashf.company') }}</th>
                <th>{{ __('performance.financial.kashf_date') }}</th>
                <th>{{ __('performance.financial.kashf_beneficiary') }}</th>
                <th>{{ __('performance.financial.kashf_description') }}</th>
                <th>{{ __('performance.financial.kashf_amount') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($records as $row)
                @php
                    $formatMoney = static fn ($value) => number_format((float) $value, 2).' '.__('performance.financial.currency');
                    [$number, $date, $person, $details, $amount] = match (true) {
                        $row instanceof \App\Models\FinancialReceipt => [
                            $row->receipt_number, $row->confirmed_at, $row->collector?->user?->name ?? $row->payer_name,
                            $type === 'custody' ? [
                                __('performance.financial.receipt_amount') => $formatMoney($row->amount),
                                __('performance.financial.metrics.handover_confirmed') => $formatMoney($row->dashboard_handed_over),
                                __('performance.financial.reserved') => $formatMoney($row->dashboard_reserved),
                                __('performance.financial.available') => $formatMoney(max(0, $row->dashboard_amount - $row->dashboard_reserved)),
                            ] : array_filter([
                                __('performance.financial.payment_method') => $row->payment_method?->getLabel(),
                                __('performance.financial.reference') => $row->reference_number,
                            ]),
                            $row->dashboard_amount,
                        ],
                        $row instanceof \App\Models\CashHandover => [
                            $row->handover_number, $row->confirmed_at ?? $row->requested_at, $row->collector?->user?->name,
                            $type === 'handover_confirmed' ? [
                                __('performance.financial.kashf_voucher_number') => $row->treasuryTransaction?->voucher_number ?? $row->treasuryTransaction?->transaction_number,
                                __('performance.financial.allocated') => $formatMoney($row->total_confirmed_amount),
                                __('performance.financial.count_difference') => $formatMoney($row->difference_amount),
                                __('performance.financial.excess') => $formatMoney($row->metadata['extra_amount'] ?? 0),
                            ] : [], $row->dashboard_amount,
                        ],
                        $row instanceof \App\Models\FinancialDisbursementPayment => [
                            $row->payment_number, $row->paid_at, $row->disbursement?->paidByEmployee?->user?->name,
                            array_filter([
                                __('performance.financial.claim_reference') => $row->disbursement?->disbursement_number,
                                __('performance.financial.kashf_voucher_number') => $row->treasuryTransaction?->voucher_number ?? $row->treasuryTransaction?->transaction_number,
                            ]), $row->dashboard_amount,
                        ],
                        $row instanceof \App\Models\FinancialDisbursement => [
                            $row->disbursement_number, $row->dashboard_date ?? $row->approved_at ?? $row->created_at,
                            $row->paidByEmployee?->user?->name ?? $row->beneficiary_name,
                            array_filter([
                                __('performance.financial.status') => $row->status?->getLabel(),
                                __('performance.financial.kashf_voucher_number') => $row->treasuryTransaction?->voucher_number ?? $row->treasuryTransaction?->transaction_number,
                                __('performance.financial.metrics.reimbursement_paid') => $type === 'reimbursement_due' ? $formatMoney($row->dashboard_reimbursed) : null,
                            ]), $row->dashboard_amount,
                        ],
                        $row instanceof \App\Models\PayrollRecord => [
                            $row->record_number, $row->payroll_month?->format('Y-m'), $row->employee?->user?->name,
                            [
                                __('performance.financial.status') => \App\Enums\PaymentStatus::tryFrom(
                                    $row->getRawOriginal('payment_status') === 'partial'
                                        ? \App\Enums\PaymentStatus::PARTIALLY_PAID->value
                                        : (string) $row->getRawOriginal('payment_status')
                                )?->getLabel(),
                                ...(($preview ?? false) ? [
                                    __('performance.financial.metrics.'.$row->operation_type.'_net') => $formatMoney($row->net_amount),
                                    __('performance.financial.metrics.'.$row->operation_type.'_paid') => $formatMoney($row->paid_amount),
                                    __('performance.financial.metrics.'.$row->operation_type.'_remaining') => $formatMoney($row->remaining_amount),
                                ] : []),
                            ],
                            $row->{\App\Services\CompanyFinancialPerformanceService::PAYROLL_METRICS[$type]},
                        ],
                        default => [
                            $row->debt_number, $row->debt_date, $row->beneficiary_name,
                            [__('performance.financial.due_date') => $row->due_date?->format('Y-m-d')],
                            $row->{\App\Services\CompanyFinancialPerformanceService::DEBT_METRICS[$type]},
                        ],
                    };
                @endphp
                <tr>
                    <td><strong class="perf-tx-code"><bdi>{{ $number ?? '—' }}</bdi></strong></td>
                    <td>{{ $row->company?->trade_name ?? '—' }}</td>
                    <td><bdi>{{ $date instanceof \DateTimeInterface ? $date->format('Y-m-d') : ($date ?? '—') }}</bdi></td>
                    <td>{{ $person ?? '—' }}</td>
                    <td>
                        @forelse(array_filter($details) as $label => $value)
                            <div>{{ $label }} <bdi>{{ $value }}</bdi></div>
                        @empty
                            —
                        @endforelse
                    </td>
                    <td><strong><bdi>{{ $formatMoney($amount) }}</bdi></strong></td>
                </tr>
            @empty
                <tr><td colspan="6">{{ __('performance.kashf.empty') }}</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
